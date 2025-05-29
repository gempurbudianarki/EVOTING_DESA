<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Suara extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'suara'; // Model ini berinteraksi dengan tabel 'suara' (singular)

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'pemilih_id',
        'kandidat_id',
        'waktu_pilih',
        'ip_address',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'waktu_pilih' => 'datetime', // Mengubah ke objek Carbon otomatis
    ];

    // --- Relasi: Suara dimiliki oleh satu Pemilih ---
    public function pemilih()
    {
        return $this->belongsTo(Pemilih::class, 'pemilih_id');
    }

    // --- Relasi: Suara memilih satu Kandidat ---
    public function kandidat()
    {
        return $this->belongsTo(Kandidat::class, 'kandidat_id');
    }
}