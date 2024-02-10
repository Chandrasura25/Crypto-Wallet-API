<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\CryptoAccount;

class Transaction extends Model
{
    use HasFactory;
    protected $fillable = [
        'crypto_account_id',
        'type',
        'amount',
    ];

    public function cryptoAccount()
    {
        return $this->belongsTo(CryptoAccount::class);
    }
}
