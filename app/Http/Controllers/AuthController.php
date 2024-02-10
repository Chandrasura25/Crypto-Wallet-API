<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function register(Request $request){
        $request->validate([
            'phone' => 'required|unique:users',
            'password' => 'required|string|min:8',
           
        ]);

        $formfields = ([
            'phone' => $request->phone,
            'password' => bcrypt($request->password),
        ]);


        $user = User::create($formfields);

        $token = $user->createToken('token')->plainTextToken;

        $response = [
            'message' => $user,
            'token' => $token
        ];

        return response()->json($response, 201);
    }

    public function login(Request $request){
        $request->validate([
            'phone' => 'required',
            'password' => 'required'
        ]);

        // Check if user exists
        $user = User::where('phone', $request->phone)->first();

        // Check password

        if(!$user || !Hash::check($request->password, $user->password)){
            $response = [
                'message' => 'Incorrect credentials'
            ];

            return response()->json($response, 401);
        }

            $token = $user->createToken('token')->plainTextToken;

            $response = [
                'message' => $user,
                'token' => $token
            ];

            return response()->json($response, 201);
    }
}
