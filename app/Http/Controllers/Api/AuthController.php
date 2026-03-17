<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        // Registration logic (not implemented in this example)
        $user=\App\Models\User::create([
            'name' => $request->name,
            'username' => $request->username,
            'password' => bcrypt($request->password),
            'email' => $request->email
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User registered successfully'
        ]);
    }
    public function login(Request $request)
    {
        $cred = $request->validate([
            'username' => 'required',
            'password' => 'required'
        ]);

        if (! $token = Auth::guard('api')->attempt($cred)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid username or password'
            ], 401);
        }

        $user = Auth::guard('api')->user();
        return response()->json([
            'success' => true,
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username
            ]
        ]);
    }

    public function logout()
    {
        Auth::guard('api')->logout();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }

}