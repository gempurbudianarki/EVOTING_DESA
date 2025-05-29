<?php

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
        Route::resource('pemilih', AdminPemilihController::class)->except(['show']); // <-- Perubahan di sini
        Route::prefix('pemilih')->name('pemilih.')->group(function () {
            // Rute khusus untuk enrollment wajah (tetap terpisah dari resource)
            Route::get('/enroll', [AdminPemilihController::class, 'showEnrollForm'])->name('enroll.form');
            Route::post('/enroll', [AdminPemilihController::class, 'enrollFace'])->name('enroll.submit');
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

// --- Rute untuk Pemilih ---
Route::prefix('pemilih')->name('pemilih.')->group(function () {
    // Rute yang hanya bisa diakses oleh guest (belum login) pemilih
    Route::middleware('guest:web_pemilih')->group(function () {
        Route::get('/login', [PemilihAuthController::class, 'showLoginForm'])->name('login');
        Route::post('/login', [PemilihAuthController::class, 'login']);
    });

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