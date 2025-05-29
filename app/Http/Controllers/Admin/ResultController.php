<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kandidat;          // Import model Kandidat
use App\Models\ElectionSetting;   // Import model ElectionSetting
use App\Models\Suara;             // Import model Suara
use App\Models\Pemilih;           // <-- PERBAIKAN: Import model Pemilih dari App\Models
use Illuminate\Http\Request;
use Carbon\Carbon;                // Untuk manipulasi tanggal
use Illuminate\Support\Facades\DB; // Untuk agregasi database
use Maatwebsite\Excel\Facades\Excel; // <-- Akan digunakan untuk export Excel
use App\Exports\ElectionResultsExport; // <-- Akan kita buat file ini nanti

class ResultController extends Controller
{
    /**
     * Menampilkan halaman hasil pemilihan.
     * Termasuk total suara, persentase per kandidat, dan status pemilihan.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // Ambil pengaturan pemilihan (asumsi hanya ada satu record)
        $electionSetting = ElectionSetting::first(); // Menggunakan first() karena hanya ada satu

        // Ambil semua kandidat, urutkan berdasarkan nomor urut
        $kandidat = Kandidat::orderBy('nomor_urut')->get();

        // Hitung total suara sah
        $totalVotes = Suara::count(); // Lebih akurat menghitung dari record suara

        // Hitung hasil per kandidat (persentase)
        $results = [];
        if ($totalVotes > 0) {
            foreach ($kandidat as $k) {
                // Pastikan pembagian tidak menyebabkan error jika jumlah_suara kandidat belum diupdate
                $candidateVotes = Suara::where('kandidat_id', $k->id)->count(); 
                $percentage = ($candidateVotes / $totalVotes) * 100;
                $results[] = [
                    'id' => $k->id,
                    'nomor_urut' => $k->nomor_urut,
                    'nama_lengkap' => $k->nama_lengkap,
                    'visi' => $k->visi,
                    'misi' => $k->misi,
                    'foto_path' => $k->foto_path,
                    'jumlah_suara' => $candidateVotes, // Gunakan hitungan dari tabel suara
                    'percentage' => round($percentage, 2), // Bulatkan 2 angka di belakang koma
                ];
            }
        } else {
            // Jika belum ada suara, set persentase 0
            foreach ($kandidat as $k) {
                $results[] = [
                    'id' => $k->id,
                    'nomor_urut' => $k->nomor_urut,
                    'nama_lengkap' => $k->nama_lengkap,
                    'visi' => $k->visi,
                    'misi' => $k->misi,
                    'foto_path' => $k->foto_path,
                    'jumlah_suara' => 0,
                    'percentage' => 0,
                ];
            }
        }

        // Hitung jumlah pemilih yang sudah memilih dan yang belum
        $totalPemilihTerdaftar = Pemilih::count();
        $pemilihSudahMemilih = Pemilih::where('sudah_memilih', true)->count();
        $pemilihBelumMemilih = $totalPemilihTerdaftar - $pemilihSudahMemilih;

        return view('admin.result.index', compact(
            'results', 
            'totalVotes', 
            'electionSetting', 
            'totalPemilihTerdaftar', 
            'pemilihSudahMemilih', 
            'pemilihBelumMemilih'
        )); // Mengarahkan ke view resources/views/admin/result/index.blade.php
    }

    /**
     * Mengekspor hasil pemilihan ke file Excel.
     * Akan diimplementasikan setelah package Laravel Excel terinstal.
     *
     * @return \Illuminate\Http\Response|\Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportExcel()
    {
        // Pastikan Anda sudah menginstal Maatwebsite/Laravel-Excel
        // composer require maatwebsite/excel

        // Ambil semua kandidat dan suaranya
        $kandidat = Kandidat::orderBy('nomor_urut')->get();
        $totalVotes = Suara::count(); // Hitung total suara dari tabel suara

        // Persiapkan data untuk export
        $exportData = [
            'kandidat' => $kandidat,
            'totalVotes' => $totalVotes,
            'electionSetting' => ElectionSetting::first(),
            'pemilihSudahMemilih' => Pemilih::where('sudah_memilih', true)->count(),
            'totalPemilihTerdaftar' => Pemilih::count(),
        ];

        // Nama file Excel
        $fileName = 'Hasil_Pemilihan_Kepala_Desa_' . Carbon::now()->format('Ymd_His') . '.xlsx';

        // Lakukan export
        return Excel::download(new ElectionResultsExport($exportData), $fileName);
    }
}