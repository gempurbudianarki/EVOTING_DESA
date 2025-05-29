<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; // Tambahkan ini untuk seed default

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('election_settings', function (Blueprint $table) {
            $table->id(); // Kolom ID otomatis
            $table->string('name')->default('Pemilihan Kepala Desa'); // Nama pemilihan
            $table->boolean('is_active')->default(false); // Status aktif/tidak aktif pemilihan
            $table->timestamp('start_time')->nullable(); // Waktu mulai pemilihan
            $table->timestamp('end_time')->nullable(); // Waktu berakhir pemilihan
            $table->timestamps(); // created_at dan updated_at
        });

        // --- Tambahkan 1 record default setelah tabel dibuat ---
        DB::table('election_settings')->insert([
            'name' => 'Pemilihan Kepala Desa 2025',
            'is_active' => false, // Default tidak aktif
            'start_time' => null, // Default null
            'end_time' => null,   // Default null
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        // --- Akhir penambahan record default ---
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('election_settings'); // Hapus tabel jika rollback
    }
};