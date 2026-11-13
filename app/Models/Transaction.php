<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'source_crypto_account_id',
        'destination_crypto_account_id',
        'coin_type',
        'amount',
    ];

    public function sourceAccount()
    {
        return $this->belongsTo(CryptoAccount::class, 'source_crypto_account_id');
    }

    public function destinationAccount()
    {
        return $this->belongsTo(CryptoAccount::class, 'destination_crypto_account_id');
    }

    /**
     * Whether the given user owns either side of this transaction.
     */
    public function involvesUser(int $userId): bool
    {
        return $this->sourceAccount?->user_id === $userId
            || $this->destinationAccount?->user_id === $userId;
    }
}
