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
        Schema::create('suara', function (Blueprint $table) {
            $table->id(); // Kolom ID otomatis
            
            // Foreign key untuk pemilih (siapa yang memilih)
            $table->foreignId('pemilih_id')
                  ->constrained('pemilih') // Merujuk ke tabel 'pemilih'
                  ->onDelete('cascade'); // Jika pemilih dihapus, suaranya juga dihapus

            // Foreign key untuk kandidat (siapa yang dipilih)
            $table->foreignId('kandidat_id')
                  ->constrained('kandidat') // Merujuk ke tabel 'kandidat'
                  ->onDelete('cascade'); // Jika kandidat dihapus, suara untuknya juga dihapus

            // Kolom unik untuk mencegah satu pemilih memilih lebih dari satu kali
            $table->unique(['pemilih_id']); // Pastikan satu pemilih hanya bisa memiliki satu suara

            $table->timestamp('waktu_pilih'); // Waktu saat suara diberikan
            $table->string('ip_address')->nullable(); // IP address saat memilih
            $table->timestamps(); // created_at dan updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suara'); // Hapus tabel jika rollback
    }
};