<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; // <-- Tambahkan baris ini untuk seed admin awal

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('admins', function (Blueprint $table) {
            $table->id(); // Kolom ID otomatis (primary key, auto-increment)
            $table->string('username')->unique(); // Username unik untuk admin
            $table->string('password'); // Password admin (akan di-hash otomatis oleh model Admin)
            $table->string('nama_lengkap')->nullable(); // Nama lengkap admin, bisa kosong
            $table->string('role')->default('panitia'); // Role admin (e.g., 'superadmin', 'panitia', 'viewer')
            $table->rememberToken(); // Untuk fitur "remember me"
            $table->timestamp('last_login_at')->nullable(); // Mencatat waktu login terakhir
            $table->string('last_login_ip')->nullable(); // Mencatat IP login terakhir
            $table->timestamps(); // created_at dan updated_at
        });

        // --- Tambahkan data admin default setelah tabel dibuat ---
        DB::table('admins')->insert([
            'username' => 'superadmin',
            'password' => bcrypt('AdminSuperAman2025'), // Ganti DENGAN PASSWORD YANG SANGAT KUAT & MUDAH ANDA INGAT
            'nama_lengkap' => 'Administrator Utama Desa',
            'role' => 'superadmin',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        // --- Akhir penambahan data admin default ---
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admins'); // Hapus tabel jika rollback
    }
};