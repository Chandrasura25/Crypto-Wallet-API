<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\CryptoAccount;
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'phone',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function cryptoAccounts()
    {
        return $this->hasMany(CryptoAccount::class);
    }

    /**
     * Every transaction this user sent or received, newest first.
     */
    public function transactions()
    {
        $accountIds = $this->cryptoAccounts()->pluck('id');

        return Transaction::query()
            ->whereIn('source_crypto_account_id', $accountIds)
            ->orWhereIn('destination_crypto_account_id', $accountIds)
            ->latest();
    }
}
