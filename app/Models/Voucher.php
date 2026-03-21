<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Voucher extends Model
{
    protected $table = 'acc_vouchers';

    protected $fillable = [
        'voucher_no',
        'date',
        'type',
        'narration',
        'fiscal_year_id',
        'status',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function fiscalYear(): BelongsTo
    {
        return $this->belongsTo(FiscalYear::class, 'fiscal_year_id');
    }

    public function postings(): HasMany
    {
        return $this->hasMany(LedgerPosting::class, 'voucher_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
