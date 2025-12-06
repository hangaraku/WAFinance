<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'type',
        'balance',
        'color',
        'icon',
        'description',
        'is_active',
        'is_default'
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'is_active' => 'boolean',
        'is_default' => 'boolean'
    ];

    /**
     * Get the user that owns the account.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the transactions for this account.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get the transfer transactions where this account is the source.
     */
    public function transferFromTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'account_id')->where('type', 'transfer');
    }

    /**
     * Get the transfer transactions where this account is the destination.
     */
    public function transferToTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'transfer_account_id')->where('type', 'transfer');
    }

    /**
     * Calculate current balance based on transactions.
     */
    public function calculateBalance(): float
    {
        $balance = 0;
        
        foreach ($this->transactions as $transaction) {
            switch ($transaction->type) {
                case 'income':
                    $balance += $transaction->amount;
                    break;
                case 'expense':
                    $balance -= $transaction->amount;
                    break;
                case 'transfer':
                    if ($transaction->account_id == $this->id) {
                        // Money going out from this account
                        $balance -= $transaction->amount;
                    } else {
                        // Money coming in to this account
                        $balance += $transaction->amount;
                    }
                    break;
            }
        }
        
        return $balance;
    }

    /**
     * Update balance based on transactions.
     */
    public function updateBalance(): void
    {
        $this->balance = $this->calculateBalance();
        $this->save();
    }

    /**
     * Get account type display name.
     */
    public function getTypeDisplayNameAttribute(): string
    {
        return match ($this->type) {
            'cash' => 'Cash',
            'bank' => 'Bank Account',
            'credit_card' => 'Credit Card',
            'investment' => 'Investment',
            'wallet' => 'Digital Wallet',
            default => ucfirst($this->type)
        };
    }

    /**
     * Get account icon based on type.
     */
    public function getIconAttribute($value): string
    {
        if ($value) return $value;
        
        return match ($this->type) {
            'cash' => 'banknotes',
            'bank' => 'building-library',
            'credit_card' => 'credit-card',
            'investment' => 'chart-bar',
            'wallet' => 'wallet',
            default => 'wallet'
        };
    }

    /**
     * Get account color based on type.
     */
    public function getColorAttribute($value): string
    {
        if ($value) return $value;
        
        return match ($this->type) {
            'cash' => 'green',
            'bank' => 'blue',
            'credit_card' => 'purple',
            'investment' => 'yellow',
            'wallet' => 'orange',
            default => 'gray'
        };
    }
}
