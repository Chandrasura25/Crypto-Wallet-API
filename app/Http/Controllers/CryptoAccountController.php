<?php

namespace App\Http\Controllers;

use App\Models\CryptoAccount;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CryptoAccountController extends Controller
{
    /**
     * List the authenticated user's crypto accounts and balances.
     */
    public function index()
    {
        return response()->json(auth()->user()->cryptoAccounts()->get());
    }

    /**
     * Open a new crypto account (one per coin type) with a zero balance.
     */
    public function store(Request $request)
    {
        $request->merge(['coin_type' => strtolower((string) $request->coin_type)]);

        $request->validate([
            'coin_type' => [
                'required',
                'string',
                Rule::unique('crypto_accounts')->where('user_id', auth()->id()),
            ],
        ]);

        $cryptoAccount = auth()->user()->cryptoAccounts()->create([
            'coin_type' => $request->coin_type,
            'balance' => 0,
        ]);

        return response()->json($cryptoAccount, 201);
    }

    public function show(CryptoAccount $crypto_account)
    {
        if ($crypto_account->user_id !== auth()->id()) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        return response()->json($crypto_account);
    }

    /**
     * Rename the coin type of an account. Balances can only change through transfers and conversions.
     */
    public function update(Request $request, CryptoAccount $crypto_account)
    {
        if ($crypto_account->user_id !== auth()->id()) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $request->merge(['coin_type' => strtolower((string) $request->coin_type)]);

        $request->validate([
            'coin_type' => [
                'required',
                'string',
                Rule::unique('crypto_accounts')->where('user_id', auth()->id())->ignore($crypto_account->id),
            ],
        ]);

        $crypto_account->update(['coin_type' => $request->coin_type]);

        return response()->json($crypto_account);
    }

    public function destroy(CryptoAccount $crypto_account)
    {
        if ($crypto_account->user_id !== auth()->id()) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        if ($crypto_account->balance > 0) {
            return response()->json(['error' => 'Account still holds a balance; transfer or convert it first'], 422);
        }

        $crypto_account->delete();

        return response()->json(['message' => 'Crypto account deleted successfully']);
    }
}
