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
        // Koreksi: Nama tabel kita adalah 'kandidat' (singular) agar sesuai dengan Model 'Kandidat'
        // Jika Anda ingin menggunakan 'kandidats' (plural) secara default, Anda tidak perlu mengubah ini.
        // Tapi untuk konsistensi dengan diskusi kita, kita pakai 'kandidat'.
        Schema::create('kandidat', function (Blueprint $table) {
            $table->id(); // Kolom ID otomatis
            $table->string('nomor_urut')->unique(); // Nomor urut kandidat, harus unik
            $table->string('nama_lengkap'); // Nama lengkap kandidat
            $table->string('jabatan_lama')->nullable(); // Jabatan/profesi sebelumnya, bisa kosong
            $table->text('visi'); // Visi kandidat
            $table->text('misi'); // Misi kandidat
            $table->string('foto_path')->nullable(); // Path ke file foto kandidat (disimpan di storage), bisa kosong
            $table->unsignedBigInteger('jumlah_suara')->default(0); // Jumlah suara yang diperoleh (default 0)
            $table->timestamps(); // created_at dan updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kandidat'); // Hapus tabel jika rollback
    }
};