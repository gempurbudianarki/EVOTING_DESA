<?php

namespace App\Http\Middleware;

use App\Providers\RouteServiceProvider;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$guards): Response
    {
        // Jika tidak ada guard yang spesifik, gunakan guard default
        if (empty($guards)) {
            $guards = [null]; // null akan menggunakan default guard dari config/auth.php
        }

        foreach ($guards as $guard) {
            // Periksa jika user sudah login dengan guard tertentu
            if (Auth::guard($guard)->check()) {
                // Jika guard adalah 'web_admin', redirect ke dashboard admin
                if ($guard === 'web_admin') {
                    return redirect(route('admin.dashboard'));
                }
                // Jika guard adalah 'web_pemilih' (atau default jika null), redirect ke dashboard pemilih
                else if ($guard === 'web_pemilih' || $guard === null) {
                    return redirect(route('pemilih.dashboard')); // Kita akan buat route ini nanti
                }
                // Fallback untuk guard lainnya (misal guard 'web' bawaan Laravel, jika tidak dihapus)
                // return redirect(RouteServiceProvider::HOME); // Default redirect Laravel
            }
        }

        return $next($request);
    }
}