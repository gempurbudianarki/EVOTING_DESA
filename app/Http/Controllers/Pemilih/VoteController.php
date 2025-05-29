<?php

namespace App\Http\Controllers\Pemilih;

use App\Http\Controllers\Controller;
use App\Models\Kandidat;         // Import model Kandidat
use App\Models\ElectionSetting;  // Import model ElectionSetting
use App\Models\Suara;            // Import model Suara
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;   // Untuk transaksi database
use Illuminate\Support\Facades\Log;  // Untuk logging
use Carbon\Carbon;               // Untuk timestamp

class VoteController extends Controller
{
    /**
     * Menampilkan halaman pemilihan bagi pemilih.
     * Memeriksa status pemilihan dan status voting pemilih.
     *
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function showVoteForm()
    {
        /** @var \App\Models\Pemilih $pemilih */ // Type hinting untuk Intelephense
        $pemilih = Auth::guard('web_pemilih')->user(); // Dapatkan pemilih yang sedang login

        // 1. Cek apakah pemilihan sedang aktif
        $electionSetting = ElectionSetting::find(1); // Ambil record pengaturan
        
        if (!$electionSetting || !$electionSetting->is_active) {
            return redirect()->route('pemilih.dashboard')->with('error', 'Proses pemilihan belum/tidak aktif. Silakan kembali nanti.');
        }

        // 2. Cek apakah pemilih sudah memilih
        if ($pemilih->sudah_memilih) {
            return redirect()->route('pemilih.dashboard')->with('info', 'Anda sudah menggunakan hak pilih Anda.');
        }

        // 3. Cek apakah waktu pemilihan sudah dimulai atau berakhir
        $now = Carbon::now();
        if ($electionSetting->start_time && $now->lt($electionSetting->start_time)) {
            return redirect()->route('pemilih.dashboard')->with('error', 'Pemilihan belum dimulai. Akan dimulai pada: ' . $electionSetting->start_time->format('d M Y H:i'));
        }
        if ($electionSetting->end_time && $now->gt($electionSetting->end_time)) {
            return redirect()->route('pemilih.dashboard')->with('error', 'Pemilihan sudah berakhir.');
        }

        // Jika semua kondisi terpenuhi, tampilkan daftar kandidat
        $kandidat = Kandidat::orderBy('nomor_urut')->get();
        //dd($kandidat->toArray());
        return view('pemilih.vote', compact('kandidat', 'electionSetting')); // Nanti kita buat view ini
    }

    /**
     * Memproses suara yang diberikan oleh pemilih.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function submitVote(Request $request)
    {
        /** @var \App\Models\Pemilih $pemilih */ // Type hinting
        $pemilih = Auth::guard('web_pemilih')->user();

        // 1. Validasi input: kandidat_id harus ada dan merupakan integer
        $request->validate([
            'kandidat_id' => 'required|integer|exists:kandidat,id', // Pastikan ID kandidat valid
        ], [
            'kandidat_id.required' => 'Mohon pilih kandidat terlebih dahulu.',
            'kandidat_id.exists' => 'Kandidat yang Anda pilih tidak valid.'
        ]);

        $kandidatId = $request->input('kandidat_id');

        // 2. Verifikasi ulang apakah pemilihan sedang aktif dan pemilih belum memilih (keamanan backend)
        $electionSetting = ElectionSetting::find(1);
        if (!$electionSetting || !$electionSetting->is_active || $pemilih->sudah_memilih) {
            Log::warning('Upaya voting ilegal oleh Pemilih: ' . $pemilih->nik . ' (Status: ' . ($pemilih->sudah_memilih ? 'Already Voted' : 'Election Inactive') . ')');
            return redirect()->route('pemilih.dashboard')->with('error', 'Anda tidak dapat memilih saat ini. Mungkin pemilihan belum aktif atau Anda sudah memilih.');
        }

        $now = Carbon::now();
        if (($electionSetting->start_time && $now->lt($electionSetting->start_time)) ||
            ($electionSetting->end_time && $now->gt($electionSetting->end_time))) {
            Log::warning('Upaya voting ilegal oleh Pemilih: ' . $pemilih->nik . ' (Diluar jadwal)');
            return redirect()->route('pemilih.dashboard')->with('error', 'Pemilihan tidak berlangsung sesuai jadwal.');
        }

        // 3. Lakukan proses voting dalam transaksi database untuk keamanan data
        // Ini memastikan semua operasi (menyimpan suara, update pemilih, update kandidat)
        // berhasil semua atau gagal semua (atomicity)
        try {
            DB::transaction(function () use ($pemilih, $kandidatId, $request) {
                // a. Simpan suara di tabel 'suara'
                Suara::create([
                    'pemilih_id' => $pemilih->id,
                    'kandidat_id' => $kandidatId,
                    'waktu_pilih' => Carbon::now(),
                    'ip_address' => $request->ip(),
                ]);

                // b. Update status 'sudah_memilih' pemilih
                $pemilih->sudah_memilih = true;
                $pemilih->waktu_memilih = Carbon::now();
                $pemilih->save();

                // c. Update jumlah suara di tabel 'kandidat'
                Kandidat::where('id', $kandidatId)->increment('jumlah_suara');
            });

            Log::info('Pemilih ' . $pemilih->nik . ' berhasil memberikan suara untuk Kandidat ID ' . $kandidatId);
            return redirect()->route('pemilih.dashboard')->with('success', 'Terima kasih! Suara Anda berhasil direkam.');

        } catch (\Illuminate\Database\QueryException $e) {
            // Tangani jika ada unique constraint violation (misal: pemilih double vote)
            if ($e->getCode() == 23000) { // Kode SQLSTATE untuk Integrity constraint violation
                Log::error('Pemilih ' . $pemilih->nik . ' mencoba double vote. Error: ' . $e->getMessage());
                return redirect()->route('pemilih.dashboard')->with('error', 'Anda sudah memilih dalam pemilihan ini.');
            }
            Log::error('Error saat menyimpan suara untuk Pemilih ' . $pemilih->nik . ': ' . $e->getMessage());
            return redirect()->route('pemilih.dashboard')->with('error', 'Terjadi kesalahan saat merekam suara Anda. Silakan coba lagi.');
        } catch (\Throwable $e) {
            Log::error('Kesalahan tak terduga saat proses voting untuk Pemilih ' . $pemilih->nik . ': ' . $e->getMessage());
            return redirect()->route('pemilih.dashboard')->with('error', 'Terjadi kesalahan tak terduga saat proses voting. Silakan coba lagi.');
        }
    }
}