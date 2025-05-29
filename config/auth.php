<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Defaults
    |--------------------------------------------------------------------------
    |
    | This option controls the default authentication "guard" and password
    | reset options for your application. You may change these defaults
    | as required, but they're a perfect start for most applications.
    |
    */

    'defaults' => [
        'guard' => 'web_pemilih', // <-- Default guard kita adalah untuk pemilih
        'passwords' => 'users', // Masih bisa pakai 'users' atau ganti ke 'pemilih' jika ingin fitur reset password
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Guards
    |--------------------------------------------------------------------------
    |
    | Next, you may define every authentication guard for your application.
    | Of course, a great starting point is the "web" guard which uses
    | session storage and the Eloquent user provider.
    |
    | All authentication guards have a user provider, which defines how the
    | users are actually retrieved out of your database or other storage
    | mechanisms. Of course, you may define multiple providers for your
    | application.
    |
    */

    'guards' => [
        'web_pemilih' => [ // <-- Guard untuk Pemilih (login verifikasi wajah)
            'driver' => 'session',
            'provider' => 'pemilih', // Menggunakan provider 'pemilih'
        ],

        'web_admin' => [ // <-- Guard untuk Admin (login username/password)
            'driver' => 'session',
            'provider' => 'admin', // Menggunakan provider 'admin'
        ],

        // Guard 'web' bawaan Laravel, bisa dihapus atau biarkan jika tidak mengganggu
        // 'web' => [
        //     'driver' => 'session',
        //     'provider' => 'users',
        // ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Providers
    |--------------------------------------------------------------------------
    |
    | All authentication guards have a user provider, which defines how the
    | users are actually retrieved out of your database or other storage
    | mechanisms. Of course, you may define multiple providers for your
    | application.
    |
    | If you have several different user tables or models, you may configure
    | these as additional providers.
    |
    */

    'providers' => [
        'pemilih' => [ // <-- Provider untuk model Pemilih
            'driver' => 'eloquent',
            'model' => App\Models\Pemilih::class,
        ],

        'admin' => [ // <-- Provider untuk model Admin
            'driver' => 'eloquent',
            'model' => App\Models\Admin::class,
        ],

        // Provider 'users' bawaan Laravel
        'users' => [
            'driver' => 'eloquent',
            'model' => App\Models\User::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Resetting Passwords
    |--------------------------------------------------------------------------
    |
    | You may specify multiple password reset configurations if you have more
    | than one user table or model that requires password reset capabilities.
    |
    | The expiry time is the number of minutes that the reset token should be
    | considered valid. This security feature keeps tokens from lingering
    | indefinitely and and being abused.
    |
    */

    'passwords' => [
        'users' => [ // Default untuk user biasa (bisa kita abaikan)
            'provider' => 'users',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
        'admin' => [ // <-- Konfigurasi reset password untuk admin
            'provider' => 'admin',
            'table' => 'password_reset_tokens', // Ini tabel reset token default Laravel
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Confirmation Timeout
    |--------------------------------------------------------------------------
    |
    | Here you may define the amount of seconds before a password confirmation
    | times out and the user is prompted to re-enter their password via the
    | confirmation screen. By default, the timeout lasts for three hours.
    |
    */

    'password_timeout' => 10800,

];