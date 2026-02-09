<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class JournalEntry extends Model
{
    use HasFactory;

    protected $table = 'journal_entries';
    protected $primaryKey = 'Id';

    protected $fillable = [
        'journal_number',
        'transaction_date',
        'reference_type',
        'reference_id',
        'cost_center_id',
        'product_id',
        'officer_id',
        'fund_id',
        'narrative',
        'total_debit',
        'total_credit',
        'status',
        'posted_by',
        'posted_at',
        'reversed_by',
        'reversed_at',
        'reversal_reason',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'posted_at' => 'datetime',
        'reversed_at' => 'datetime',
        'total_debit' => 'decimal:2',
        'total_credit' => 'decimal:2',
    ];

    /**
     * Relationships
     */
    public function lines()
    {
        return $this->hasMany(JournalLine::class, 'journal_entry_id', 'Id')->orderBy('line_number');
    }

    public function journalLines()
    {
        return $this->lines();
    }

    public function postedBy()
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function reversedBy()
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }

    public function costCenter()
    {
        return $this->belongsTo(Branch::class, 'cost_center_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function officer()
    {
        return $this->belongsTo(User::class, 'officer_id');
    }

    public function fund()
    {
        return $this->belongsTo(Fund::class, 'fund_id');
    }

    /**
     * Create a new journal entry with lines
     * 
     * @param array $data Journal entry data
     * @param array $lines Array of line items [['account_id' => x, 'debit' => y, 'credit' => z, 'narrative' => '...'], ...]
     * @return JournalEntry
     * @throws \Exception
     */
    public static function postJournal(array $data, array $lines)
    {
        DB::beginTransaction();
        try {
            // Generate journal number
            $data['journal_number'] = self::generateJournalNumber($data['transaction_date']);
            $data['posted_by'] = auth()->id();
            $data['posted_at'] = now();
            $data['status'] = 'posted';

            // Calculate totals
            $totalDebit = 0;
            $totalCredit = 0;
            foreach ($lines as $line) {
                $totalDebit += $line['debit'] ?? 0;
                $totalCredit += $line['credit'] ?? 0;
            }

            // Validate balanced entry
            if (round($totalDebit, 2) != round($totalCredit, 2)) {
                throw new \Exception("Journal entry not balanced: DR={$totalDebit}, CR={$totalCredit}");
            }

            $data['total_debit'] = $totalDebit;
            $data['total_credit'] = $totalCredit;

            // Create journal entry
            $journal = self::create($data);

            // Create journal lines
            $lineNumber = 1;
            foreach ($lines as $line) {
                JournalLine::create([
                    'journal_entry_id' => $journal->Id,
                    'line_number' => $lineNumber++,
                    'account_id' => $line['account_id'],
                    'debit_amount' => $line['debit'] ?? 0,
                    'credit_amount' => $line['credit'] ?? 0,
                    'narrative' => $line['narrative'] ?? null,
                ]);

                // Update account balance
                self::updateAccountBalance($line['account_id'], $line['debit'] ?? 0, $line['credit'] ?? 0);
            }

            DB::commit();
            return $journal->fresh('lines');

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Reverse this journal entry
     * 
     * @param string $reason
     * @return JournalEntry The reversal journal
     * @throws \Exception
     */
    public function reverse(string $reason)
    {
        if ($this->status === 'reversed') {
            throw new \Exception("Journal entry already reversed");
        }

        DB::beginTransaction();
        try {
            // Mark original as reversed
            $this->update([
                'status' => 'reversed',
                'reversed_by' => auth()->id(),
                'reversed_at' => now(),
                'reversal_reason' => $reason,
            ]);

            // Create reversal entry (flip debits and credits)
            $reversalLines = [];
            foreach ($this->lines as $line) {
                $reversalLines[] = [
                    'account_id' => $line->account_id,
                    'debit' => $line->credit_amount,  // Flip
                    'credit' => $line->debit_amount,   // Flip
                    'narrative' => "Reversal: " . $line->narrative,
                ];
            }

            $reversalData = [
                'transaction_date' => now()->format('Y-m-d'),
                'reference_type' => 'Reversal',
                'reference_id' => $this->Id,
                'narrative' => "REVERSAL - {$reason} - Original: {$this->journal_number}",
            ];

            $reversalJournal = self::postJournal($reversalData, $reversalLines);

            DB::commit();
            return $reversalJournal;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Generate unique journal number: JE-YYYYMMDD-XXXX
     */
    private static function generateJournalNumber($date)
    {
        $dateStr = date('Ymd', strtotime($date));
        $lastJournal = self::where('journal_number', 'LIKE', "JE-{$dateStr}-%")
            ->orderBy('journal_number', 'desc')
            ->first();

        if ($lastJournal) {
            $lastNumber = (int) substr($lastJournal->journal_number, -4);
            $nextNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $nextNumber = '0001';
        }

        return "JE-{$dateStr}-{$nextNumber}";
    }

    /**
     * Update account balance based on category
     */
    private static function updateAccountBalance($accountId, $debit, $credit)
    {
        $account = SystemAccount::find($accountId);
        if (!$account) {
            throw new \Exception("Account not found: {$accountId}");
        }

        $net = $debit - $credit;

        // Asset & Expense accounts: increase with debit, decrease with credit
        if (in_array($account->category, ['Asset', 'Expense'])) {
            $account->running_balance += $net;
        }
        // Liability, Equity, Income accounts: decrease with debit, increase with credit
        else {
            $account->running_balance -= $net;
        }

        $account->save();
    }

    /**
     * Check if entry is balanced
     */
    public function isBalanced()
    {
        return round($this->total_debit, 2) == round($this->total_credit, 2);
    }

    /**
     * Scope: Get entries by reference
     */
    public function scopeForReference($query, $type, $id)
    {
        return $query->where('reference_type', $type)->where('reference_id', $id);
    }

    /**
     * Scope: Get posted entries
     */
    public function scopePosted($query)
    {
        return $query->where('status', 'posted');
    }

    /**
     * Scope: Get entries by date range
     */
    public function scopeDateRange($query, $start, $end)
    {
        return $query->whereBetween('transaction_date', [$start, $end]);
    }
}
