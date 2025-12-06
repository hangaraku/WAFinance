<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'account_id',
        'transfer_account_id',
        'category_id',
        'type', // income, expense, transfer
        'amount',
        'description',
        'notes',
        'picture',
        'transaction_date',
        'transaction_time'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'date',
        'transaction_time' => 'datetime',
    ];

    /**
     * Get the user that owns the transaction.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the account where the transaction occurs.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the destination account for transfer transactions.
     */
    public function transferAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'transfer_account_id');
    }

    /**
     * Get the category of the transaction.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get transaction type display name.
     */
    public function getTypeDisplayNameAttribute(): string
    {
        return match ($this->type) {
            'income' => 'Pemasukan',
            'expense' => 'Pengeluaran',
            'transfer' => 'Transfer',
            default => ucfirst($this->type)
        };
    }

    /**
     * Get transaction type color.
     */
    public function getTypeColorAttribute(): string
    {
        return match ($this->type) {
            'income' => 'green',
            'expense' => 'red',
            'transfer' => 'blue',
            default => 'gray'
        };
    }

    /**
     * Check if transaction is a transfer.
     */
    public function isTransfer(): bool
    {
        return $this->type === 'transfer';
    }

    /**
     * Get the amount with proper sign for display.
     */
    public function getDisplayAmountAttribute(): string
    {
        $sign = match ($this->type) {
            'income' => '+',
            'expense' => '-',
            'transfer' => $this->transfer_account_id ? '+' : '-',
            default => ''
        };
        
        return $sign . 'Rp ' . number_format($this->amount);
    }

    /**
     * Get the source account name for transfers.
     */
    public function getSourceAccountNameAttribute(): string
    {
        if ($this->isTransfer()) {
            return $this->account->name;
        }
        return '';
    }

    /**
     * Get the destination account name for transfers.
     */
    public function getDestinationAccountNameAttribute(): string
    {
        if ($this->isTransfer() && $this->transferAccount) {
            return $this->transferAccount->name;
        }
        return '';
    }
}
