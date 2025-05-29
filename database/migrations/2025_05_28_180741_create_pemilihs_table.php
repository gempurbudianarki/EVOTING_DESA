<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Koreksi: Nama tabel kita adalah 'pemilih' (singular) agar sesuai dengan Model 'Pemilih'
        // Jika Anda ingin menggunakan 'pemilihs' (plural) secara default, Anda tidak perlu mengubah ini.
        // Tapi untuk konsistensi dengan diskusi kita, kita pakai 'pemilih'.
        Schema::create('pemilih', function (Blueprint $table) {
            $table->id(); // Kolom ID otomatis
            $table->string('nik', 16)->unique(); // NIK 16 digit, harus unik
            $table->string('nama_lengkap'); // Nama lengkap pemilih
            $table->string('alamat')->nullable(); // Alamat pemilih, bisa kosong
            $table->text('face_embedding')->nullable(); // Untuk menyimpan data fitur wajah (vektor angka), bisa kosong di awal
            $table->boolean('sudah_memilih')->default(false); // Status sudah memilih (default false)
            $table->timestamp('waktu_memilih')->nullable(); // Kapan terakhir memilih
            $table->timestamp('last_login_at')->nullable(); // Waktu login terakhir
            $table->string('last_login_ip')->nullable(); // IP login terakhir
            $table->timestamps(); // created_at dan updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pemilih'); // Hapus tabel jika rollback
    }
};