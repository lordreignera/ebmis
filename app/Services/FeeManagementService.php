<?php

namespace App\Services;

use App\Models\Fee;
use App\Models\FeeType;
use App\Models\ProductCharge;
use App\Models\Loan;
use App\Models\PersonalLoan;
use App\Models\GroupLoan;
use Illuminate\Support\Collection;

class FeeManagementService
{
    /**
     * Calculate all fees for a loan based on product charges
     */
    public function calculateLoanFees($loan): Collection
    {
        $loanData = $this->extractLoanData($loan);
        $principal = (float) $loanData['principal'];
        $productId = $loanData['product_type'];
        
        // Get product charges
        $productCharges = ProductCharge::where('product_id', $productId)
                                     ->where('isactive', '1')
                                     ->get();
        
        $fees = collect();
        
        foreach ($productCharges as $charge) {
            $feeAmount = $this->calculateChargeValue($charge, $principal);
            
            // Get or create fee type for this charge
            $feeType = $this->getOrCreateFeeType($charge->name);
            
            $fees->push([
                'charge_id' => $charge->id,
                'fee_type_id' => $feeType->id,
                'name' => $charge->name,
                'type' => $charge->type, // 1=fixed, 2=percentage
                'value' => $charge->value,
                'calculated_amount' => $feeAmount,
                'is_mandatory' => $this->isMandatoryFee($charge->name),
                'paid_upfront' => false
            ]);
        }
        
        return $fees;
    }
    
    /**
     * Calculate the actual value of a charge
     */
    private function calculateChargeValue(ProductCharge $charge, float $principal): float
    {
        if ($charge->type == '1') {
            // Fixed amount
            return (float) $charge->value;
        } elseif ($charge->type == '2') {
            // Percentage
            return ((float) $charge->value / 100) * $principal;
        }
        
        return 0.0;
    }
    
    /**
     * Create fee records for a loan
     */
    public function createLoanFees($loan, string $chargeType = '1'): Collection
    {
        $loanData = $this->extractLoanData($loan);
        $fees = $this->calculateLoanFees($loan);
        $createdFees = collect();
        
        foreach ($fees as $feeData) {
            $fee = Fee::create([
                'member_id' => $loanData['member_id'] ?? $loanData['group_id'],
                'fees_type_id' => $feeData['fee_type_id'],
                'loan_id' => $loanData['id'],
                'amount' => $feeData['calculated_amount'],
                'description' => $feeData['name'],
                'status' => $chargeType == '2' ? 1 : 0, // If upfront, mark as paid
                'added_by' => auth()->id() ?? 1,
                'datecreated' => now()
            ]);
            
            $createdFees->push($fee);
        }
        
        return $createdFees;
    }
    
    /**
     * Calculate disbursement amount based on charge type
     */
    public function calculateDisbursementAmount($loan, string $chargeType = '1'): array
    {
        $loanData = $this->extractLoanData($loan);
        $principal = (float) $loanData['principal'];
        $fees = $this->calculateLoanFees($loan);
        
        $totalFees = $fees->sum('calculated_amount');
        
        if ($chargeType == '2') {
            // Charges paid upfront - disburse full principal
            $disbursementAmount = $principal;
            $feesDeducted = 0;
        } else {
            // Deduct unpaid charges from disbursement
            $unpaidFees = $this->getUnpaidMandatoryFees($loan);
            $feesDeducted = $unpaidFees->sum('amount');
            $disbursementAmount = $principal - $feesDeducted;
        }
        
        return [
            'principal' => $principal,
            'total_fees' => $totalFees,
            'fees_deducted' => $feesDeducted,
            'disbursement_amount' => $disbursementAmount,
            'charge_type' => $chargeType,
            'fees_breakdown' => $fees->toArray()
        ];
    }
    
    /**
     * Get unpaid mandatory fees for a loan
     */
    public function getUnpaidMandatoryFees($loan): Collection
    {
        $loanData = $this->extractLoanData($loan);
        
        // Get mandatory fee types
        $mandatoryFeeTypes = FeeType::where('required_disbursement', '1')->get();
        
        $unpaidFees = collect();
        
        foreach ($mandatoryFeeTypes as $feeType) {
            // Check if this fee is paid for this loan/member
            $paidFee = Fee::where('member_id', $loanData['member_id'] ?? $loanData['group_id'])
                         ->where('fees_type_id', $feeType->id)
                         ->when(!empty($loanData['member_id']), function($query) use ($loanData) {
                             // For personal loans, check loan_id
                             return $query->where('loan_id', $loanData['id']);
                         })
                         ->when(stripos($feeType->name, 'registration') !== false, function($query) {
                             // Registration fee can be paid at member level
                             return $query->where('status', '1');
                         }, function($query) use ($loanData) {
                             // Other fees must be paid for this specific loan
                             return $query->where('loan_id', $loanData['id'])->where('status', '1');
                         })
                         ->first();
            
            if (!$paidFee) {
                // Calculate fee amount for this loan
                $feeAmount = $this->calculateMandatoryFeeAmount($feeType, $loanData);
                
                $unpaidFees->push([
                    'fee_type_id' => $feeType->id,
                    'name' => $feeType->name,
                    'amount' => $feeAmount,
                    'required_disbursement' => $feeType->required_disbursement
                ]);
            }
        }
        
        return $unpaidFees;
    }
    
    /**
     * Validate if all mandatory fees are paid before disbursement
     */
    public function validateMandatoryFees($loan): array
    {
        $unpaidFees = $this->getUnpaidMandatoryFees($loan);
        
        if ($unpaidFees->isEmpty()) {
            return [
                'valid' => true,
                'message' => 'All mandatory fees are paid'
            ];
        }
        
        $feeNames = $unpaidFees->pluck('name')->implode(', ');
        
        return [
            'valid' => false,
            'message' => "Mandatory fees not fully paid. Missing: {$feeNames}",
            'unpaid_fees' => $unpaidFees->toArray()
        ];
    }
    
    /**
     * Pay a fee
     */
    public function payFee(int $feeId, float $amount = null): bool
    {
        try {
            $fee = Fee::findOrFail($feeId);
            
            // If amount not specified, pay full amount
            $paymentAmount = $amount ?? $fee->amount;
            
            $fee->update([
                'status' => 1, // Paid
                'paid_amount' => $paymentAmount,
                'date_paid' => now()
            ]);
            
            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to pay fee: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get fees for a loan
     */
    public function getLoanFees($loan): Collection
    {
        $loanData = $this->extractLoanData($loan);
        
        return Fee::where('loan_id', $loanData['id'])
                 ->with('feeType')
                 ->get();
    }
    
    /**
     * Get or create fee type
     */
    private function getOrCreateFeeType(string $chargeName): FeeType
    {
        // Try to find existing fee type by name similarity
        $feeType = FeeType::where('name', 'LIKE', "%{$chargeName}%")->first();
        
        if (!$feeType) {
            // Create new fee type
            $feeType = FeeType::create([
                'name' => $chargeName,
                'description' => "Auto-created for charge: {$chargeName}",
                'required_disbursement' => $this->isMandatoryFee($chargeName) ? '1' : '0',
                'status' => '1'
            ]);
        }
        
        return $feeType;
    }
    
    /**
     * Check if a fee is mandatory
     */
    private function isMandatoryFee(string $feeName): bool
    {
        $mandatoryFees = [
            'registration',
            'processing',
            'admin',
            'insurance'
        ];
        
        $feeName = strtolower($feeName);
        
        foreach ($mandatoryFees as $mandatory) {
            if (stripos($feeName, $mandatory) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Calculate mandatory fee amount
     */
    private function calculateMandatoryFeeAmount(FeeType $feeType, array $loanData): float
    {
        // Try to get amount from product charges
        $productCharge = ProductCharge::where('product_id', $loanData['product_type'])
                                    ->where('name', 'LIKE', "%{$feeType->name}%")
                                    ->first();
        
        if ($productCharge) {
            return $this->calculateChargeValue($productCharge, $loanData['principal']);
        }
        
        // Default amounts for common fees
        $defaultAmounts = [
            'registration' => 10000, // 10,000 UGX
            'processing' => 5000,    // 5,000 UGX
            'admin' => 2000,         // 2,000 UGX
            'insurance' => 3000      // 3,000 UGX
        ];
        
        $feeName = strtolower($feeType->name);
        
        foreach ($defaultAmounts as $key => $amount) {
            if (stripos($feeName, $key) !== false) {
                return $amount;
            }
        }
        
        return 5000; // Default fallback amount
    }
    
    /**
     * Extract loan data from different loan models
     */
    private function extractLoanData($loan): array
    {
        if ($loan instanceof PersonalLoan) {
            return [
                'id' => $loan->id,
                'member_id' => $loan->member_id,
                'principal' => $loan->principal,
                'product_type' => $loan->product_type,
                'created_at' => $loan->datecreated ?? $loan->created_at
            ];
        } elseif ($loan instanceof GroupLoan) {
            return [
                'id' => $loan->id,
                'group_id' => $loan->group_id,
                'principal' => $loan->principal,
                'product_type' => $loan->product_type,
                'created_at' => $loan->datecreated ?? $loan->created_at
            ];
        } elseif ($loan instanceof Loan) {
            return [
                'id' => $loan->id,
                'member_id' => $loan->member_id,
                'group_id' => $loan->group_id,
                'principal' => $loan->principal,
                'product_type' => $loan->product_type,
                'created_at' => $loan->created_at
            ];
        } else {
            throw new \InvalidArgumentException('Invalid loan model provided');
        }
    }
    
    /**
     * Update loan charge type
     */
    public function updateLoanChargeType($loan, string $chargeType): bool
    {
        try {
            if ($loan instanceof PersonalLoan) {
                $loan->update(['charge_type' => $chargeType]);
            }
            
            // If setting to upfront, create and mark fees as paid
            if ($chargeType == '2') {
                $this->createLoanFees($loan, $chargeType);
            }
            
            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to update loan charge type: ' . $e->getMessage());
            return false;
        }
    }
}