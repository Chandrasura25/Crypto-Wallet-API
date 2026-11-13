<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CryptoAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'coin_type',
        'balance',
    ];

    protected $casts = [
        'balance' => 'decimal:8',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function sentTransactions()
    {
        return $this->hasMany(Transaction::class, 'source_crypto_account_id');
    }

    public function receivedTransactions()
    {
        return $this->hasMany(Transaction::class, 'destination_crypto_account_id');
    }
}
