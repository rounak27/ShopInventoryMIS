<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $cred = $request->validate([
            'username' => 'required',
            'password' => 'required'
        ]);
        // dd($cred);
        $user = User::where('username', $cred['username'])->first();

        if (!$user || $user->password !== $cred['password']) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid username or password'
            ], 401);
        }
        $token = Auth::guard('api')->login($user);
        // dd($token);
        // if (!$token = Auth::guard('api')->attempt($cred)) {
        //     dd('Invalid credentials');
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Invalid username or password'
        //     ], 401);
        // }

        $user = Auth::guard('api')->user();
        // dd($user);
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