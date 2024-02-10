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

    public function store(Request $request)
    {
        // Validate the incoming request data
        $request->validate([
            'source_crypto_account_id' => 'required|exists:crypto_accounts,id',
            'destination_crypto_account_id' => 'required|exists:crypto_accounts,id',
            'amount' => 'required|numeric|min:0',
        ]);

        // Retrieve the source and destination crypto accounts
        $sourceCryptoAccount = CryptoAccount::findOrFail($request->source_crypto_account_id);
        $destinationCryptoAccount = CryptoAccount::findOrFail($request->destination_crypto_account_id);

        // Ensure both crypto accounts belong to the same cryptocurrency
        if ($sourceCryptoAccount->coin_type !== $destinationCryptoAccount->coin_type) {
            return response()->json(['error' => 'Cannot transfer between different cryptocurrencies'], 422);
        }

        // Perform other validation logic as needed

        // Create the transaction
        $transaction = Transaction::create([
            'source_crypto_account_id' => $sourceCryptoAccount->id,
            'destination_crypto_account_id' => $destinationCryptoAccount->id,
            'amount' => $request->amount,
        ]);

        // Update balances of source and destination crypto accounts
        $sourceCryptoAccount->update(['balance' => $sourceCryptoAccount->balance - $request->amount]);
        $destinationCryptoAccount->update(['balance' => $destinationCryptoAccount->balance + $request->amount]);

        // Return a response
        return response()->json($transaction, 201);
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
        // Validate the request
        $request->validate([
            'recipient_email' => 'required|email',
            'source_crypto_account_id' => 'required|exists:crypto_accounts,id',
            'amount' => 'required|numeric|min:0',
        ]);

        // Get the authenticated user
        $sender = auth()->user();

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

        // Transfer funds: Deduct from sender's balance and add to recipient's balance
        $transaction = new Transaction();
        $transaction->source_crypto_account_id = $sourceCryptoAccount->id;
        $transaction->destination_user_id = $recipient->id;
        $transaction->amount = $request->amount;
        $transaction->save();

        // Update balances
        $sourceCryptoAccount->balance -= $request->amount;
        $sourceCryptoAccount->save();

        $recipientCryptoAccount = $recipient->cryptoAccounts()->firstOrCreate(['coin_type' => $sourceCryptoAccount->coin_type]);
        $recipientCryptoAccount->balance += $request->amount;
        $recipientCryptoAccount->save();

        return response()->json(['message' => 'Funds transferred successfully']);
    }

}