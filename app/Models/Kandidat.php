<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kandidat extends Model // Model Kandidat tidak perlu extends Authenticatable
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'kandidat'; // Model ini berinteraksi dengan tabel 'kandidat' (singular)

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nomor_urut',
        'nama_lengkap',
        'jabatan_lama',
        'visi',
        'misi',
        'foto_path',
        'jumlah_suara',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'jumlah_suara' => 'integer', // Pastikan ini di-cast sebagai integer
    ];

    // --- Relasi: Kandidat bisa memiliki banyak suara ---
    public function suara()
    {
        // Asumsi ada tabel 'suara' yang menghubungkan pemilih dengan kandidat
        // Kita akan buat model Suara dan migration untuk tabel 'suara' nanti
        // return $this->hasMany(Suara::class);
    }
}