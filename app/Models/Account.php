<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    protected $table = 'acc_accounts';

    protected $fillable = [
        'code',
        'name',
        'type',
        'parent_id',
        'is_group',
        'opening_balance',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Account::class, 'parent_id');
    }

    public function postings(): HasMany
    {
        return $this->hasMany(LedgerPosting::class, 'account_id');
    }

    /**
     * Get the current balance of the account
     */
    public function getBalanceAttribute()
    {
        $debits = $this->postings()->sum('debit');
        $credits = $this->postings()->sum('credit');
        
        if (in_array($this->type, ['asset', 'expense'])) {
            return ($this->opening_balance + $debits) - $credits;
        } else {
            return ($this->opening_balance + $credits) - $debits;
        }
    }
}
