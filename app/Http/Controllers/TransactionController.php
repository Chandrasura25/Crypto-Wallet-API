<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\CryptoAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $transactions = $user->transactions()->with('cryptoAccount')->get();
        return response()->json($transactions);
    }

    public function show(Transaction $transaction)
    {
        // Ensure the transaction belongs to the authenticated user
        if ($transaction->cryptoAccount->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Return the transaction
        return response()->json($transaction);
    }
    public function transferFunds(Request $request)
    {
        // Get the authenticated user
        $sender = auth()->user();
    
        // Validate the request
        $request->validate([
            'recipient_email' => 'required|email',
            'source_crypto_account_id' => 'required|exists:crypto_accounts,id',
            'amount' => 'required|numeric|min:0',
        ]);
    
        // Find the source crypto account
        $sourceCryptoAccount = CryptoAccount::findOrFail($request->source_crypto_account_id);
    
        // Find the recipient user by email
        $recipient = User::where('email', $request->recipient_email)->first();
    
        if (!$recipient) {
            return response()->json(['error' => 'Recipient not found'], 404);
        }
    
        // Ensure sender and recipient are not the same user
        if ($sender->id === $recipient->id) {
            return response()->json(['error' => 'Sender and recipient cannot be the same user'], 422);
        }
    
        // Ensure sender owns the source crypto account
        if ($sender->id !== $sourceCryptoAccount->user_id) {
            return response()->json(['error' => 'Unauthorized access to the source crypto account'], 403);
        }
    
        // Ensure the sender has sufficient balance in the source crypto account
        if ($sourceCryptoAccount->balance < $request->amount) {
            return response()->json(['error' => 'Insufficient balance in the source crypto account'], 422);
        }
    
        // Retrieve the destination crypto account
        $destinationCryptoAccount = CryptoAccount::where('user_id', $recipient->id)
            ->where('coin_type', $sourceCryptoAccount->coin_type)
            ->first();
    
        // Ensure destination crypto account exists
        if (!$destinationCryptoAccount) {
            return response()->json(['error' => 'Recipient does not have a corresponding crypto account for this coin type'], 404);
        }
    
        // Transfer funds only if the coin types match
        if ($sourceCryptoAccount->coin_type !== $destinationCryptoAccount->coin_type) {
            return response()->json(['error' => 'Cannot transfer between different cryptocurrencies'], 422);
        }
    
        // Transfer funds: Deduct from sender's balance and add to recipient's balance
        $transaction = new Transaction();
        $transaction->source_crypto_account_id = $sourceCryptoAccount->id;
        $transaction->destination_crypto_account_id = $destinationCryptoAccount->id;
        $transaction->coin_type = $sourceCryptoAccount->coin_type;
        $transaction->amount = $request->amount;
        $transaction->save();
    
        // Update balances
        $sourceCryptoAccount->balance -= $request->amount;
        $sourceCryptoAccount->save();
    
        $destinationCryptoAccount->balance += $request->amount;
        $destinationCryptoAccount->save();
    
        return response()->json(['message' => 'Funds transferred successfully']);
    }
    

}
