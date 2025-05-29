<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable; // Opsional, tapi disarankan jika ingin pakai guard

class Pemilih extends Authenticatable // Bisa extend Authenticatable jika ingin pakai guard autentikasi untuk pemilih
{
    use HasFactory, Notifiable;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'pemilih'; // Model ini berinteraksi dengan tabel 'pemilih' (singular)

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nik',
        'nama_lengkap',
        'alamat',
        'face_embedding',
        'sudah_memilih',
        'waktu_memilih',
        'last_login_at',
        'last_login_ip',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        // 'face_embedding', // Anda bisa menyembunyikan ini jika tidak ingin terpapar di API
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'sudah_memilih' => 'boolean', // Mengubah ke tipe boolean otomatis
        'waktu_memilih' => 'datetime', // Mengubah ke objek Carbon otomatis
        'last_login_at' => 'datetime', // Mengubah ke objek Carbon otomatis
    ];

    // --- Relasi: Pemilih bisa memiliki satu suara dalam satu pemilihan ---
    public function suara()
    {
        // Asumsi ada tabel 'suara' yang menghubungkan pemilih dengan kandidat
        // Kita akan buat model Suara dan migration untuk tabel 'suara' nanti
        // return $this->hasOne(Suara::class);
    }
}