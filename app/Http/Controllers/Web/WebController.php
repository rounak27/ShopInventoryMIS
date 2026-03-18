<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class WebController extends Controller
{
    //
    public function login()
    {
        return view('login');
    }
    public function post_login(Request $request)
    {
        // dd($request->all());
        // Handle login logic here (e.g., validate credentials, authenticate user)
        return redirect()->route('dashboard');
    }
    public function dashboard()
    {
        return view('dashboard');
    }
}
