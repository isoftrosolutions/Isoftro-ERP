<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LedgerPosting extends Model
{
    protected $table = 'acc_ledger_postings';

    protected $fillable = [
        'voucher_id',
        'account_id',
        'tenant_id',
        'debit',
        'credit',
        'description',
        'sub_ledger_type',
        'sub_ledger_id',
    ];

    protected $casts = [
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
    ];

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class, 'voucher_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }
}
