<?php
// gempurbudianarki/evoting_desa/EVOTING_DESA-b34845e78ac5420a547413a68e63d1d0080f4b73/routes/web.php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Pemilih\AuthController as PemilihAuthController;
use App\Http\Controllers\Admin\PemilihController as AdminPemilihController;
use App\Http\Controllers\Admin\KandidatController as AdminKandidatController;
use App\Http\Controllers\Admin\ElectionSettingController as AdminElectionSettingController;
use App\Http\Controllers\Pemilih\VoteController as PemilihVoteController;
use App\Http\Controllers\Admin\ResultController as AdminResultController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Rute default Laravel (halaman welcome)
Route::get('/', function () {
    return view('welcome'); // Atau bisa redirect ke halaman login utama nantinya
});

// --- Rute untuk Admin ---
Route::prefix('admin')->name('admin.')->group(function () {
    // Rute yang hanya bisa diakses oleh guest (belum login) admin
    Route::middleware('guest:web_admin')->group(function () {
        Route::get('/login', [AdminAuthController::class, 'showLoginForm'])->name('login');
        Route::post('/login', [AdminAuthController::class, 'login']);
    });

    // Grup rute yang hanya bisa diakses setelah admin login
    Route::middleware('auth:web_admin')->group(function () {
        Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');
        
        // Rute Dashboard Admin
        Route::get('/dashboard', function () {
            return view('admin.dashboard');
        })->name('dashboard');

        // --- Rute Manajemen Pemilih (Disesuaikan) ---
        // Menggunakan Route::resource untuk sebagian besar operasi CRUD
        Route::resource('pemilih', AdminPemilihController::class)->except(['show']);
        Route::prefix('pemilih')->name('pemilih.')->group(function () {
            // Rute khusus untuk enrollment wajah (tetap terpisah dari resource)
            Route::get('/enroll', [AdminPemilihController::class, 'showEnrollForm'])->name('enroll.form');
            Route::post('/enroll', [AdminPemilihController::class, 'enrollFace'])->name('enroll.submit');
            // Route liveness.check dipindahkan keluar dari middleware auth SEMENTARA untuk debugging
            // Route::post('/liveness-check', [AdminPemilihController::class, 'checkLiveness'])->name('liveness.check'); 
        });
        // --- Akhir Rute Manajemen Pemilih ---

        // --- Rute Manajemen Kandidat ---
        Route::resource('kandidat', AdminKandidatController::class)->except(['show']); 
        // --- Akhir Rute Manajemen Kandidat ---

        // --- Rute Pengaturan Pemilihan ---
        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('/election', [AdminElectionSettingController::class, 'index'])->name('election');
            Route::put('/election', [AdminElectionSettingController::class, 'update'])->name('election.update');
        });
        // --- Akhir Rute Pengaturan Pemilihan ---

        // --- Rute Hasil Pemilihan ---
        Route::prefix('results')->name('results.')->group(function () {
            Route::get('/', [AdminResultController::class, 'index'])->name('index');
            Route::get('/export-excel', [AdminResultController::class, 'exportExcel'])->name('export');
        });
        // --- Akhir Rute Hasil Pemilihan ---

        // --- Tambahkan rute-rute admin lainnya di sini nantinya ---
    });
});

// --- BARU: Route liveness check untuk admin yang diakses terpisah (DEBUGGING ONLY) ---
// Ini untuk mengatasi error 500/SyntaxError yang mungkin disebabkan middleware auth
// AKAN DIKEMBALIKAN KE DALAM GROUP AUTH SETELAH DEBUGGING
// Pertahankan rute ini di luar group auth admin jika diperlukan akses anonim untuk liveness check pada enroll
Route::post('admin/pemilih/liveness-check', [AdminPemilihController::class, 'checkLiveness'])->name('admin.pemilih.liveness.check');


// --- Rute untuk Pemilih ---
Route::prefix('pemilih')->name('pemilih.')->group(function () {
    // Rute yang hanya bisa diakses oleh guest (belum login) pemilih
    Route::middleware('guest:web_pemilih')->group(function () {
        Route::get('/login', [PemilihAuthController::class, 'showLoginForm'])->name('login');
        Route::post('/login', [PemilihAuthController::class, 'login']);
    });

    // Rute liveness check untuk pemilih, dipindahkan keluar dari middleware guest
    // Agar bisa diakses baik saat auto-fill (belum login) maupun saat liveness challenge (proses login)
    Route::post('/liveness-check', [PemilihAuthController::class, 'checkLiveness'])->name('liveness.check');


    // Grup rute yang hanya bisa diakses setelah pemilih login
    Route::middleware('auth:web_pemilih')->group(function () {
        Route::post('/logout', [PemilihAuthController::class, 'logout'])->name('logout');

        // Rute Dashboard Pemilih
        Route::get('/dashboard', function () {
            return view('pemilih.dashboard');
        })->name('dashboard');

        // --- Rute Pemungutan Suara (Voting) ---
        Route::get('/vote', [PemilihVoteController::class, 'showVoteForm'])->name('vote.form');
        Route::post('/vote', [PemilihVoteController::class, 'submitVote'])->name('vote.submit');
        // --- Akhir Rute Pemungutan Suara ---
    });
});