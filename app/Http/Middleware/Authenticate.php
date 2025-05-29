<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function redirectTo(Request $request): ?string
    {
        if (! $request->expectsJson()) {
            // Periksa guard yang aktif atau guard yang dicoba diakses
            // Dan arahkan ke halaman login yang sesuai
            if ($request->routeIs('admin.*')) { // Jika mencoba akses rute admin
                return route('admin.login');
            } elseif ($request->routeIs('pemilih.*')) { // Jika mencoba akses rute pemilih
                return route('pemilih.login');
            }
            // Fallback default jika tidak ada guard spesifik atau jika ada guard 'web' yang masih ada
            // return route('login'); // Ini default Laravel
        }
        return null;
    }
}