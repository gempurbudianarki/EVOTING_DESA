<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable; // Penting: Import ini!

class Admin extends Authenticatable // Penting: Extend class Authenticatable!
{
    use HasFactory, Notifiable;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'admins'; // Model ini akan berinteraksi dengan tabel 'admins'

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'username',
        'password',
        'nama_lengkap',
        'role',
        'last_login_at',
        'last_login_ip',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'last_login_at' => 'datetime', // Mengubah ke objek Carbon secara otomatis
        'password' => 'hashed', // Laravel akan otomatis me-hash password saat disimpan
    ];

    // --- Tambahan: Relasi (Jika nanti diperlukan) ---
    // public function createdElections()
    // {
    //     return $this->hasMany(Election::class); // Contoh: Jika admin bisa membuat sesi pemilihan
    // }

    // --- Tambahan: Metode untuk mengecek role admin (Contoh) ---
    public function isSuperAdmin()
    {
        return $this->role === 'superadmin';
    }

    public function isPanitia()
    {
        return $this->role === 'panitia';
    }
}