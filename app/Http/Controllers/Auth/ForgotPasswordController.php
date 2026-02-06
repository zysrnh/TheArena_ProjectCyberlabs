<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ForgotPasswordController extends Controller
{
    /**
     * Tampilkan halaman forgot password
     */
    public function showForgotForm()
    {
        return Inertia::render('Auth/ForgotPassword', [
            'auth' => [
                'client' => auth('client')->user()
            ]
        ]);
    }

    /**
     * Proses verifikasi email dan phone
     */
    public function verify(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'phone' => 'required|string',
        ], [
            'email.required' => 'Email wajib diisi',
            'email.email' => 'Format email tidak valid',
            'phone.required' => 'Nomor telepon wajib diisi',
        ]);

        // Cari client berdasarkan email DAN phone
        $client = Client::where('email', $request->email)
            ->where('phone', $request->phone)
            ->first();

        if (!$client) {
            return back()->withErrors([
                'verification' => 'Email dan nomor telepon tidak cocok dengan data yang terdaftar.'
            ]);
        }

        // Generate token unik untuk reset password
        $token = Str::random(60);

        // Simpan token di session (alternatif: bisa pakai database table password_resets)
        Session::put('password_reset', [
            'client_id' => $client->id,
            'token' => $token,
            'expires_at' => now()->addMinutes(30), // Token berlaku 30 menit
        ]);

        // Redirect ke halaman reset password dengan token
        return redirect()->route('password.reset', ['token' => $token]);
    }

    /**
     * Tampilkan halaman reset password
     */
    public function showResetForm($token)
    {
        // Cek apakah token valid
        $resetData = Session::get('password_reset');

        if (!$resetData || $resetData['token'] !== $token) {
            return redirect()->route('login')->withErrors([
                'token' => 'Token tidak valid atau sudah kadaluarsa.'
            ]);
        }

        // Cek apakah token sudah expired
        if (now()->gt($resetData['expires_at'])) {
            Session::forget('password_reset');
            return redirect()->route('login')->withErrors([
                'token' => 'Token sudah kadaluarsa. Silakan ulangi proses forgot password.'
            ]);
        }

        return Inertia::render('Auth/ResetPassword', [
            'token' => $token,
            'auth' => [
                'client' => auth('client')->user()
            ]
        ]);
    }

    /**
     * Proses reset password
     */
    public function reset(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'password.required' => 'Password wajib diisi',
            'password.min' => 'Password minimal 8 karakter',
            'password.confirmed' => 'Konfirmasi password tidak cocok',
        ]);

        // Ambil data reset dari session
        $resetData = Session::get('password_reset');

        if (!$resetData || $resetData['token'] !== $request->token) {
            return back()->withErrors([
                'token' => 'Token tidak valid atau sudah kadaluarsa.'
            ]);
        }

        // Cek apakah token sudah expired
        if (now()->gt($resetData['expires_at'])) {
            Session::forget('password_reset');
            return back()->withErrors([
                'token' => 'Token sudah kadaluarsa. Silakan ulangi proses forgot password.'
            ]);
        }

        // Cari client
        $client = Client::find($resetData['client_id']);

        if (!$client) {
            Session::forget('password_reset');
            return back()->withErrors([
                'token' => 'Akun tidak ditemukan.'
            ]);
        }

        // Update password
        $client->update([
            'password' => bcrypt($request->password)
        ]);

        // Hapus session reset password
        Session::forget('password_reset');

        // Redirect ke login dengan flash message
        return redirect()->route('login')->with('success', 'Password berhasil direset! Silakan login dengan password baru Anda.');
    }
}