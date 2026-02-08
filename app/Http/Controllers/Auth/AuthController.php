<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class AuthController extends Controller
{
    // Tampilkan halaman login
    public function showLogin()
    {
        return Inertia::render('Auth/Login', [
            'auth' => [
                'client' => auth('client')->user()
            ]
        ]);
    }

    // ✅ ALTERNATIF: Proses login dengan backend redirect + force reload
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Login menggunakan guard 'client'
        if (Auth::guard('client')->attempt($credentials, $request->remember)) {
            $request->session()->regenerate();
            
            Log::info('✅ Client logged in successfully', [
                'client_id' => Auth::guard('client')->id(),
                'email' => Auth::guard('client')->user()->email,
            ]);
            
            // ✅ FIX: Redirect dengan Inertia location visit (force reload)
            return Inertia::location('/profile');
        }

        return back()->withErrors([
            'email' => 'Email atau password salah.',
        ])->onlyInput('email');
    }

    // Tampilkan halaman register
    public function showRegister()
    {
        return Inertia::render('Auth/Register', [
            'auth' => [
                'client' => auth('client')->user()
            ]
        ]);
    }

    // Proses register untuk client
    public function register(Request $request)
    {
        $validated = $request->validate([
            'username' => 'required|string|max:255|unique:clients,name',
            'email' => 'required|string|email|max:255|unique:clients',
            'phone' => 'required|string|max:20',
            'password' => 'required|string|min:8',
        ], [
            'username.required' => 'Username wajib diisi',
            'username.unique' => 'Username sudah digunakan',
            'email.required' => 'Email wajib diisi',
            'email.email' => 'Format email tidak valid',
            'email.unique' => 'Email sudah terdaftar',
            'phone.required' => 'Nomor telepon wajib diisi',
            'password.required' => 'Password wajib diisi',
            'password.min' => 'Password minimal 8 karakter',
        ]);

        $client = Client::create([
            'name' => $validated['username'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => bcrypt($validated['password']),
        ]);

        // Login otomatis setelah register
        Auth::guard('client')->login($client);
        
        Log::info('✅ New client registered and logged in', [
            'client_id' => $client->id,
            'email' => $client->email,
        ]);

        // ✅ FIX: Gunakan Inertia location untuk force reload
        return Inertia::location('/profile');
    }

    // Logout
    public function logout(Request $request)
    {
        Log::info('🚪 Client logging out', [
            'client_id' => Auth::guard('client')->id(),
        ]);
        
        Auth::guard('client')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect('/');
    }
}