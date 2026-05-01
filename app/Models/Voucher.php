<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Voucher extends Model
{
    protected $table = 'acc_vouchers';

    /**
     * Boot the model and add immutable protection for approved vouchers
     * (NAS compliance - prevent financial data tampering)
     */
    protected static function boot()
    {
        parent::boot();

        // Prevent updates to approved vouchers
        static::updating(function ($voucher) {
            if ($voucher->getOriginal('status') === 'approved') {
                throw new \Exception('Approved vouchers cannot be modified for audit compliance.');
            }
        });

        // Prevent deletion of vouchers (use reversal entries instead)
        static::deleting(function ($voucher) {
            throw new \Exception('Vouchers cannot be deleted. Create a reversal entry instead.');
        });
    }

    protected $fillable = [
        'tenant_id',
        'voucher_no',
        'date',
        'date_bs',
        'type',
        'narration',
        'fiscal_year_id',
        'status',
        'total_amount',
        'created_by',
        'approved_by',
        'verified_by',
    ];

    protected $casts = [
        'date' => 'date',
        'total_amount' => 'decimal:2',
    ];

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

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
