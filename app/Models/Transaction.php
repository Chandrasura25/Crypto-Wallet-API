<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\CryptoAccount;

class Transaction extends Model
{
    use HasFactory;
    protected $fillable = [
        'source_crypto_account_id',
        'destination_crypto_account_id',
        'coin_type',
        'amount',
    ];

    public function cryptoAccount()
    {
        return $this->belongsTo(CryptoAccount::class);
    }
}
