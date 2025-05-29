<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\Admin; // <-- Tambahkan baris ini untuk import model Admin

class AuthController extends Controller
{
    /**
     * Menampilkan halaman login admin.
     *
     * @return \Illuminate\View\View
     */
    public function showLoginForm()
    {
        if (Auth::guard('web_admin')->check()) {
            return redirect()->route('admin.dashboard');
        }
        return view('admin.auth.login');
    }

    /**
     * Memproses permintaan login admin.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        if (Auth::guard('web_admin')->attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            /** @var \App\Models\Admin $admin */ // <-- Tambahkan baris ini!
            $admin = Auth::guard('web_admin')->user();
            $admin->last_login_at = Carbon::now();
            $admin->last_login_ip = $request->ip();
            $admin->save(); // <-- Ini baris yang Intelephense salah paham

            Log::info('Admin logged in successfully: ' . $admin->username . ' from IP: ' . $request->ip());
            
            return redirect()->intended(route('admin.dashboard'));
        }

        Log::warning('Admin login failed for username: ' . $request->username . ' from IP: ' . $request->ip());
        return back()->withErrors([
            'username' => 'Username atau password salah.',
        ])->onlyInput('username');
    }

    /**
     * Memproses permintaan logout admin.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function logout(Request $request)
    {
        Auth::guard('web_admin')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        Log::info('Admin logged out successfully.');
        
        return redirect()->route('admin.login');
    }
}