<?php

namespace App\Models;

use App\Services\FileStorageService;
use Illuminate\Database\Eloquent\Model;

class LoanCollateralDocument extends Model
{
    protected $fillable = [
        'loan_type',
        'loan_id',
        'member_id',
        'collateral_field',
        'document_name',
        'document_type',
        'estimated_value',
        'forced_sale_value',
        'file_path',
        'file_type',
        'file_size',
        'description',
        'uploaded_by',
    ];

    protected $casts = [
        'estimated_value' => 'decimal:2',
        'forced_sale_value' => 'decimal:2',
        'file_size' => 'integer',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getFileUrlAttribute()
    {
        return FileStorageService::getFileUrl($this->file_path);
    }
}
