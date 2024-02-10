<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CryptoPriceController extends Controller
{
    public function getMarketPrice($crypto)
    {
        try {
            // Initialize Guzzle HTTP client
            $client = new Client();
    
            // Make API request to fetch market price for the specified cryptocurrency
            $response = $client->get('https://api.coingecko.com/api/v3/simple/price?ids=' . $coinType . '&vs_currencies=usd');
    
            // Decode the JSON response
            $data = json_decode($response->getBody()->getContents(), true);
    
            // Extract the market price
            $marketPrice = $data[$coinType]['usd'];
    
            return $marketPrice;
        } catch (\Exception $e) {
            // Log or handle the error appropriately
            return null;
        }
    }
    public function convertBalance(Request $request)
    {
        // Validate the request
        $request->validate([
            'source_coin' => 'required|string',
            'target_coin' => 'required|string',
            'amount' => 'required|numeric|min:0',
        ]);
    
        // Get the authenticated user
        $user = auth()->user();
    
        // Fetch the market price of the source and target coins
        $sourceCoinPrice = $this->getMarketPrice($request->source_coin);
        $targetCoinPrice = $this->getMarketPrice($request->target_coin);
    
        // Ensure the market prices are fetched successfully
        if (!$sourceCoinPrice || !$targetCoinPrice) {
            return response()->json(['error' => 'Failed to fetch market prices'], 500);
        }
    
        // Calculate the amount of target coin equivalent to the source coin amount
        $targetAmount = ($request->amount / $sourceCoinPrice) * $targetCoinPrice;
    
    
        // Update the source crypto account balance
        $sourceCryptoAccount = $user->cryptoAccounts()->where('coin_type', $request->source_coin)->firstOrFail();
        $sourceCryptoAccount->balance -= $request->amount;
        $sourceCryptoAccount->save();
    
        // Update or create the target crypto account balance
        $targetCryptoAccount = $user->cryptoAccounts()->updateOrCreate(
            ['coin_type' => $request->target_coin],
            ['balance' => $targetAmount]
        );
    
        return response()->json(['message' => 'Balance converted successfully']);
    }
}
