<?php

namespace App\Http\Controllers;

use App\Models\CryptoAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class CryptoPriceController extends Controller
{
    /**
     * Fetch live USD prices for the given CoinGecko coin ids (e.g. "bitcoin", "ethereum").
     *
     * @return array<string, float>|null  coin id => USD price, or null if the lookup failed
     */
    protected function getMarketPrices(array $coinIds): ?array
    {
        $response = Http::timeout(10)->get('https://api.coingecko.com/api/v3/simple/price', [
            'ids' => implode(',', $coinIds),
            'vs_currencies' => 'usd',
        ]);

        if (! $response->successful()) {
            return null;
        }

        $prices = [];
        foreach ($coinIds as $id) {
            $price = $response->json("$id.usd");
            if (! is_numeric($price) || $price <= 0) {
                return null;
            }
            $prices[$id] = (float) $price;
        }

        return $prices;
    }

    /**
     * Convert part of one coin balance into another coin at the live market rate.
     * target_amount = amount × price(source) ÷ price(target)
     */
    public function convertBalance(Request $request)
    {
        $request->merge([
            'source_coin' => strtolower((string) $request->source_coin),
            'target_coin' => strtolower((string) $request->target_coin),
        ]);

        $request->validate([
            'source_coin' => 'required|string',
            'target_coin' => 'required|string|different:source_coin',
            'amount' => 'required|numeric|gt:0',
        ]);

        $prices = $this->getMarketPrices([$request->source_coin, $request->target_coin]);

        if (! $prices) {
            return response()->json(['error' => 'Failed to fetch market prices'], 502);
        }

        $rate = $prices[$request->source_coin] / $prices[$request->target_coin];
        $targetAmount = number_format((float) $request->amount * $rate, 8, '.', '');
        $user = auth()->user();

        return DB::transaction(function () use ($request, $user, $targetAmount, $rate) {
            $source = $user->cryptoAccounts()
                ->where('coin_type', $request->source_coin)
                ->lockForUpdate()
                ->first();

            if (! $source) {
                return response()->json(['error' => 'You have no ' . $request->source_coin . ' account'], 404);
            }

            if (bccomp((string) $source->balance, (string) $request->amount, 8) < 0) {
                return response()->json(['error' => 'Insufficient balance in the source crypto account'], 422);
            }

            $target = $user->cryptoAccounts()
                ->where('coin_type', $request->target_coin)
                ->lockForUpdate()
                ->first()
                ?? $user->cryptoAccounts()->create(['coin_type' => $request->target_coin, 'balance' => 0]);

            $source->balance = bcsub((string) $source->balance, (string) $request->amount, 8);
            $source->save();

            $target->balance = bcadd((string) $target->balance, $targetAmount, 8);
            $target->save();

            return response()->json([
                'message' => 'Balance converted successfully',
                'rate' => $rate,
                'converted_amount' => $targetAmount,
                'source_account' => $source->fresh(),
                'target_account' => $target->fresh(),
            ]);
        });
    }
}
