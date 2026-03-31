<?php

namespace App\Services;

use App\Models\ClientLoanApplication;
use App\Models\Product;

/**
 * Scores a self-applied client loan application using the same
 * logic shown in the System Decision-Making Layer sheet:
 *  – Evidence Score (ES)
 *  – Verification Strength Score (VSS)
 *  – DSCR
 *  – Collateral Coverage & Saleability
 *  – Guarantor Strength
 *  – Composite Score → Traffic Light
 *  – Hard Policy Gates
 */
class ClientLoanScoringService
{
    // FSV discount factors per collateral type
    private const FSV_FACTORS = [
        'Land'              => 0.71,
        'Building'          => 0.60,
        'Motorcycle'        => 0.50,
        'Vehicle'           => 0.50,
        'Equipment'         => 0.50,
        'Machinery'         => 0.50,
        'Livestock'         => 0.40,
        'Electronics'       => 0.35,
        'Other'             => 0.40,
    ];

    // Saleability scores (0-100) — how quickly and easily sold
    private const SALEABILITY_SCORES = [
        'Land'              => 60,
        'Building'          => 55,
        'Motorcycle'        => 72,
        'Vehicle'           => 70,
        'Equipment'         => 55,
        'Machinery'         => 50,
        'Livestock'         => 65,
        'Electronics'       => 60,
        'Other'             => 45,
    ];

    // Commitment level → guarantor strength points
    private const GUARANTOR_COMMITMENT = [
        'High'     => 100,
        'Moderate' => 60,
        'Low'      => 30,
    ];

    public function score(ClientLoanApplication $app): array
    {
        $product = $app->product_id ? Product::find($app->product_id) : null;

        // ── 1. Evidence Score ───────────────────────────────────────────────
        $es = $this->calculateEvidenceScore($app);

        // ── 2. Verification Strength Score ─────────────────────────────────
        $vss = $this->calculateVSSScore($app);

        // ── 3. Income & DSCR ──────────────────────────────────────────────
        $dailyDisposable = $app->daily_sales_claimed
            - $app->business_expenses_claimed
            - $app->household_expenses_claimed
            + $app->other_income_claimed;

        $dailyDisposable = max(0, $dailyDisposable);

        $proposedInstallment = $this->calculateInstallment($app, $product);

        $daysPerPeriod = match ($app->repayment_frequency) {
            'daily'  => 1,
            'weekly' => 7,
            default  => 30,
        };

        $incomePerPeriod   = $dailyDisposable * $daysPerPeriod;
        $externalPerPeriod = $app->external_installment_per_period;
        $totalDebt         = $proposedInstallment + $externalPerPeriod;
        $dscr              = $totalDebt > 0 ? round($incomePerPeriod / $totalDebt, 2) : 0;

        // ── 4. Collateral FSV & Coverage ───────────────────────────────────
        $fsv1 = $this->calculateFSV($app->collateral_1_type, $app->collateral_1_client_value);
        $fsv2 = $this->calculateFSV($app->collateral_2_type, $app->collateral_2_client_value);
        $fsvTotal = $fsv1 + $fsv2;

        $collateralCoverage = $app->requested_amount > 0
            ? round($fsvTotal / $app->requested_amount, 2)
            : 0;

        // ── 5. Collateral Saleability ──────────────────────────────────────
        [$saleability1, $saleability2, $saleabilityWeighted] =
            $this->calculateSaleability($app, $fsv1, $fsv2, $fsvTotal);

        // ── 6. Guarantor Security & Strength ──────────────────────────────
        $g1Security = (float) $app->guarantor_1_pledged_asset_value;
        $g2Security = (float) $app->guarantor_2_pledged_asset_value;
        $gTotal     = $g1Security + $g2Security;
        $gStrength  = $this->calculateGuarantorStrength($app);

        // ── 7. Max approvable amount (3× FSV policy) ─────────────────────
        $maxByCollateral = $fsvTotal > 0 ? round($fsvTotal / 3, 2) : 0;

        // ── 8. Hard Policy Gates ──────────────────────────────────────────
        $gates = $this->evaluateGates($app, $dscr, $collateralCoverage, $fsvTotal);
        $allGatesPass = !in_array('BLOCK', array_column($gates, 'status'));

        // ── 9. Composite Score (weighted) ─────────────────────────────────
        $dscrNorm        = min(100, max(0, ($dscr / 5) * 100));
        $coverageNorm    = min(100, max(0, ($collateralCoverage / 6) * 100));
        $composite = (int) round(
            ($es           * 0.20) +
            ($vss          * 0.20) +
            ($dscrNorm     * 0.25) +
            ($coverageNorm * 0.20) +
            ($gStrength    * 0.15)
        );

        // ── 10. Risk Band & Traffic Light ─────────────────────────────────
        if (!$allGatesPass || $composite < 65) {
            $riskBand    = $composite < 65 ? 'High' : 'Medium';
            $trafficLight = 'RED';
            $status       = 'rejected';
        } elseif ($composite >= 85) {
            $riskBand    = 'Low';
            $trafficLight = 'GREEN';
            $status       = 'pending_fo_review';
        } else {
            $riskBand    = 'Medium';
            $trafficLight = 'YELLOW';
            $status       = 'pending_fo_verification';
        }

        // Build system notes
        $notes = $this->buildNotes($gates, $dscr, $collateralCoverage, $composite, $allGatesPass);

        return [
            // Scores
            'es_score'                    => $es,
            'vss_score'                   => $vss,
            // Income
            'daily_disposable_income'     => $dailyDisposable,
            'proposed_installment'        => $proposedInstallment,
            'total_debt_per_period'       => $totalDebt,
            'dscr'                        => $dscr,
            // Collateral
            'fsv_collateral_1'            => $fsv1,
            'fsv_collateral_2'            => $fsv2,
            'fsv_total'                   => $fsvTotal,
            'collateral_coverage'         => $collateralCoverage,
            'collateral_1_saleability_score' => $saleability1,
            'collateral_2_saleability_score' => $saleability2,
            'collateral_saleability_score'   => $saleabilityWeighted,
            // Guarantors
            'guarantor_1_security'        => $g1Security,
            'guarantor_2_security'        => $g2Security,
            'guarantor_security_total'    => $gTotal,
            'guarantor_strength_score'    => $gStrength,
            // Decision
            'composite_score'             => $composite,
            'risk_band'                   => $riskBand,
            'traffic_light'               => $trafficLight,
            'gate_status'                 => $gates,
            'system_notes'               => $notes,
            'max_approvable_amount'       => $maxByCollateral,
            'status'                      => $status,
        ];
    }

    // ── Private Helpers ──────────────────────────────────────────────────────

    private function calculateEvidenceScore(ClientLoanApplication $app): int
    {
        // 10 points per document uploaded (max 100)
        $checks = [
            !empty($app->business_profile_photo),
            !empty($app->business_activity_photos),
            !empty($app->inventory_photos),
            !empty($app->sales_book_photo),
            !empty($app->purchases_book_photo),
            !empty($app->expense_records_photo),
            !empty($app->mobile_money_statements),
            !empty($app->collateral_1_doc_photo),
            !empty($app->collateral_1_doc_number),
            !empty($app->collateral_2_doc_photo) || !empty($app->collateral_2_doc_number),
        ];

        return array_sum($checks) * 10;
    }

    private function calculateVSSScore(ClientLoanApplication $app): int
    {
        // 10 points each verifiable field (max 100)
        $checks = [
            !empty($app->lc1_name),
            !empty($app->lc1_phone),
            !empty($app->reference_name),
            !empty($app->reference_phone),
            !empty($app->reference_relationship),
            $app->guarantor_1_signed_consent,
            $app->guarantor_2_name && $app->guarantor_2_signed_consent,
            !empty($app->business_name),
            !empty($app->business_location),
            !empty($app->landmark_directions),
        ];

        return array_sum($checks) * 10;
    }

    private function calculateInstallment(ClientLoanApplication $app, ?Product $product): float
    {
        $principal = (float) $app->requested_amount;
        $periods   = max(1, (int) $app->tenure_periods);
        // Use product interest if loaded, else 0
        $interestRate = $product ? ((float) $product->interest / 100) : 0;

        // Flat rate: total repayable = principal + (principal × rate × periods)
        // Installment = total / periods
        $total = $principal + ($principal * $interestRate * $periods);

        return round($total / $periods, 2);
    }

    private function calculateFSV(string|null $type, float|int $clientValue): float
    {
        $factor = self::FSV_FACTORS[$type] ?? 0.40;
        return round((float) $clientValue * $factor, 2);
    }

    private function calculateSaleability(
        ClientLoanApplication $app,
        float $fsv1,
        float $fsv2,
        float $fsvTotal
    ): array {
        $s1 = self::SALEABILITY_SCORES[$app->collateral_1_type] ?? 45;
        $s2 = $app->collateral_2_type
            ? (self::SALEABILITY_SCORES[$app->collateral_2_type] ?? 45)
            : 0;

        if ($fsvTotal <= 0) {
            return [$s1, $s2, $s1];
        }

        $weighted = (int) round(($s1 * $fsv1 + $s2 * $fsv2) / $fsvTotal);

        return [$s1, $s2, $weighted];
    }

    private function calculateGuarantorStrength(ClientLoanApplication $app): int
    {
        $scores = [];
        if ($app->guarantor_1_name) {
            $scores[] = self::GUARANTOR_COMMITMENT[$app->guarantor_1_commitment_level] ?? 30;
        }
        if ($app->guarantor_2_name) {
            $scores[] = self::GUARANTOR_COMMITMENT[$app->guarantor_2_commitment_level] ?? 30;
        }

        return count($scores) > 0 ? (int) round(array_sum($scores) / count($scores)) : 0;
    }

    private function evaluateGates(
        ClientLoanApplication $app,
        float $dscr,
        float $coverage,
        float $fsvTotal
    ): array {
        $gates = [];

        $gates['consents'] = [
            'label'  => 'Client consents complete',
            'status' => ($app->consent_verification && $app->consent_crb && $app->declaration_truth)
                ? 'PASS' : 'BLOCK',
        ];

        $gates['business_evidence'] = [
            'label'  => 'Business evidence sufficient',
            'status' => ($app->es_score ?? $this->calculateEvidenceScore($app)) >= 50
                ? 'PASS' : 'REVIEW',
        ];

        $gates['arrears'] = [
            'label'  => 'Arrears gate',
            'status' => ((int) $app->max_external_arrears_days) === 0 ? 'PASS' : 'BLOCK',
        ];

        $gates['legal_proof'] = [
            'label'  => 'Legal proof and pledge gate',
            'status' => !empty($app->collateral_1_doc_number) ? 'PASS' : 'BLOCK',
        ];

        $gates['fsv_3x'] = [
            'label'  => 'FSV Forced Sale Value 3× gate',
            'status' => $coverage >= 3 ? 'PASS' : 'BLOCK',
        ];

        $gates['dscr'] = [
            'label'  => 'DSCR gate',
            'status' => $dscr >= 1.0 ? 'PASS' : 'BLOCK',
        ];

        $gates['collateral_saleability'] = [
            'label'  => 'Collateral saleability gate',
            'status' => 'PASS',  // informational — never hard-blocks
        ];

        $gates['guarantor_support'] = [
            'label'  => 'Guarantor support gate',
            'status' => !empty($app->guarantor_1_name) ? 'PASS' : 'REVIEW',
        ];

        $allPass = !in_array('BLOCK', array_column($gates, 'status'));
        $gates['overall'] = [
            'label'  => 'Overall Gate Status',
            'status' => $allPass ? 'PASS' : 'BLOCK',
        ];

        return $gates;
    }

    private function buildNotes(
        array $gates,
        float $dscr,
        float $coverage,
        int $composite,
        bool $allGatesPass
    ): string {
        $lines = [];

        if (!$allGatesPass) {
            $blocked = array_filter($gates, fn($g) => $g['status'] === 'BLOCK');
            foreach ($blocked as $g) {
                $lines[] = 'BLOCKED: ' . $g['label'];
            }
        } else {
            $lines[] = 'All hard policy gates passed.';
        }

        $lines[] = sprintf('DSCR: %.2f | Collateral Coverage: %.2fx | Composite: %d/100', $dscr, $coverage, $composite);

        return implode(' | ', $lines);
    }
}
