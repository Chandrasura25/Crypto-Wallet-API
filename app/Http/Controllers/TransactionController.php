<?php

namespace App\Http\Controllers;

use App\Models\CryptoAccount;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    /**
     * List every transaction the authenticated user sent or received.
     */
    public function index()
    {
        $transactions = auth()->user()
            ->transactions()
            ->with(['sourceAccount', 'destinationAccount'])
            ->get();

        return response()->json($transactions);
    }

    public function show(Transaction $transaction)
    {
        if (! $transaction->involvesUser(auth()->id())) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        return response()->json($transaction->load(['sourceAccount', 'destinationAccount']));
    }

    /**
     * Send funds to another user, identified by email, in the same coin.
     * Runs in a database transaction with row locks so concurrent transfers can't overspend.
     */
    public function transferFunds(Request $request)
    {
        $request->validate([
            'recipient_email' => 'required|email',
            'source_crypto_account_id' => 'required|integer|exists:crypto_accounts,id',
            'amount' => 'required|numeric|gt:0',
        ]);

        $sender = auth()->user();
        $recipient = User::where('email', $request->recipient_email)->first();

        if (! $recipient) {
            return response()->json(['error' => 'Recipient not found'], 404);
        }

        if ($sender->id === $recipient->id) {
            return response()->json(['error' => 'Sender and recipient cannot be the same user'], 422);
        }

        return DB::transaction(function () use ($request, $sender, $recipient) {
            $source = CryptoAccount::whereKey($request->source_crypto_account_id)->lockForUpdate()->first();

            if ($source->user_id !== $sender->id) {
                return response()->json(['error' => 'Unauthorized access to the source crypto account'], 403);
            }

            $destination = CryptoAccount::where('user_id', $recipient->id)
                ->where('coin_type', $source->coin_type)
                ->lockForUpdate()
                ->first();

            if (! $destination) {
                return response()->json(['error' => 'Recipient does not have a corresponding crypto account for this coin type'], 404);
            }

            if (bccomp((string) $source->balance, (string) $request->amount, 8) < 0) {
                return response()->json(['error' => 'Insufficient balance in the source crypto account'], 422);
            }

            $source->balance = bcsub((string) $source->balance, (string) $request->amount, 8);
            $source->save();

            $destination->balance = bcadd((string) $destination->balance, (string) $request->amount, 8);
            $destination->save();

            $transaction = Transaction::create([
                'source_crypto_account_id' => $source->id,
                'destination_crypto_account_id' => $destination->id,
                'coin_type' => $source->coin_type,
                'amount' => $request->amount,
            ]);

            return response()->json([
                'message' => 'Funds transferred successfully',
                'transaction' => $transaction,
            ]);
        });
    }
}
