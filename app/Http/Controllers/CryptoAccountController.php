<?php

namespace App\Http\Controllers;
use App\Models\CryptoAccount;
use Illuminate\Http\Request;

class CryptoAccountController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $cryptoAccounts = auth()->user()->cryptoAccounts()->with('transactions')->get();
        return response()->json($cryptoAccounts);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validation
        $request->validate([
            'coin_type' => 'required|string',
        ]);

        // Create a new crypto account for the authenticated user
        $cryptoAccount = auth()->user()->cryptoAccounts()->create([
            'coin_type' => $request->coin_type,
            'balance' => 0, // Assuming initial balance is 0
        ]);

        return response()->json($cryptoAccount, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(CryptoAccount $crypto_account)
    {
        // Check if the crypto account belongs to the authenticated user
        if ($crypto_account->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return response()->json($crypto_account);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CryptoAccount $crypto_account)
    {
        // Check if the crypto account belongs to the authenticated user
        if ($crypto_account->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Update the crypto account
        $crypto_account->update($request->only(['coin_type']));

        return response()->json($crypto_account);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CryptoAccount $crypto_account)
    {
        // Check if the crypto account belongs to the authenticated user
        if ($crypto_account->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Delete the crypto account
        $crypto_account->delete();

        return response()->json(['message' => 'Crypto account deleted successfully']);
    }
}
