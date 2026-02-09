<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JournalLine extends Model
{
    use HasFactory;

    protected $table = 'journal_lines';
    protected $primaryKey = 'Id';

    protected $fillable = [
        'journal_entry_id',
        'line_number',
        'account_id',
        'debit_amount',
        'credit_amount',
        'narrative',
    ];

    protected $casts = [
        'debit_amount' => 'decimal:2',
        'credit_amount' => 'decimal:2',
    ];

    /**
     * Relationships
     */
    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id', 'Id');
    }

    public function account()
    {
        return $this->belongsTo(SystemAccount::class, 'account_id', 'Id');
    }

    /**
     * Get the net amount (debit - credit)
     */
    public function getNetAmountAttribute()
    {
        return $this->debit_amount - $this->credit_amount;
    }

    /**
     * Check if this is a debit line
     */
    public function isDebit()
    {
        return $this->debit_amount > 0;
    }

    /**
     * Check if this is a credit line
     */
    public function isCredit()
    {
        return $this->credit_amount > 0;
    }

    /**
     * Get formatted amount
     */
    public function getFormattedAmountAttribute()
    {
        if ($this->debit_amount > 0) {
            return number_format($this->debit_amount, 2) . ' DR';
        }
        return number_format($this->credit_amount, 2) . ' CR';
    }
}
