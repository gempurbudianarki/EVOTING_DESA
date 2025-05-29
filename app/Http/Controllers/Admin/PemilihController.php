<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pemilih; // Import model Pemilih
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http; // Untuk memanggil microservice Python
use Illuminate\Support\Facades\Log;   // Untuk logging
use Carbon\Carbon;                    // Untuk timestamp

class PemilihController extends Controller
{
    /**
     * Menampilkan daftar semua pemilih.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $pemilih = Pemilih::all(); // Ambil semua data pemilih
        return view('admin.pemilih.index', compact('pemilih')); // Mengarahkan ke view resources/views/admin/pemilih/index.blade.php
    }

    /**
     * Menampilkan form untuk mendaftarkan wajah pemilih (Enrollment).
     *
     * @param string $nik Opsional: NIK pemilih yang ingin di-enroll
     * @return \Illuminate\View\View
     */
    public function showEnrollForm(Request $request)
    {
        $nik = $request->query('nik'); // Ambil NIK dari query parameter
        return view('admin.pemilih.enroll_face', compact('nik')); // Mengarahkan ke view resources/views/admin/pemilih/enroll_face.blade.php
    }

    /**
     * Memproses pendaftaran wajah pemilih dengan liveness detection.
     * Mengambil urutan gambar dari frontend, mengirim ke microservice Python,
     * dan menyimpan face_embedding yang dihasilkan ke database.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function enrollFace(Request $request)
    {
        // 1. Validasi input
        $request->validate([
            'nik' => ['required', 'string', 'digits:16', 'exists:pemilih,nik'],
            'face_image_sequence' => ['required', 'array', 'min:5'], // Harus array gambar, minimal 5 frame
            'face_image_sequence.*' => ['required', 'string'],
            'liveness_challenge_type' => ['required', 'string', 'in:blink,head_yaw,head_pitch'],
        ], [
            'nik.exists' => 'NIK tidak ditemukan di database pemilih. Mohon tambahkan pemilih ini terlebih dahulu.',
            'nik.digits' => 'NIK harus 16 digit angka.',
            'face_image_sequence.required' => 'Urutan gambar wajah diperlukan untuk pendaftaran.',
            'face_image_sequence.min' => 'Diperlukan minimal 5 gambar untuk pendaftaran.',
            'liveness_challenge_type.required' => 'Tipe tantangan liveness diperlukan.',
            'liveness_challenge_type.in' => 'Tipe tantangan liveness tidak valid.'
        ]);

        $nik = $request->input('nik');
        $faceImageSequence = $request->input('face_image_sequence');
        $livenessChallengeType = $request->input('liveness_challenge_type');

        // 2. Cari pemilih berdasarkan NIK
        /** @var \App\Models\Pemilih $pemilih */
        $pemilih = Pemilih::where('nik', $nik)->first();

        // 3. Panggil microservice Python untuk mendapatkan face_embedding dengan liveness check
        $enroll_face_url = env('FACE_VERIFICATION_SERVICE_URL', 'http://localhost:5000/verify_face');
        // Ganti endpoint ke /enroll_face_with_liveness
        $enroll_face_url = str_replace('/verify_face', '/enroll_face_with_liveness', $enroll_face_url); 
        
        try {
            $response = Http::timeout(60)->post($enroll_face_url, [
                'nik' => $nik,
                'image_sequence' => $faceImageSequence,
                'challenge_type' => $livenessChallengeType
            ]);

            if ($response->successful()) {
                $result = $response->json();

                $livenessStatus = ($result['liveness_challenge_status'] ?? 'false') === 'true';
                $livenessMessage = $result['liveness_message'] ?? 'Liveness check failed.';

                if ($result['status'] === 'success' && $livenessStatus) { // Harus sukses DAN lulus liveness
                    $pemilih->face_embedding = json_encode($result['face_embedding']);
                    $pemilih->save();

                    Log::info('Face enrolled successfully with LIVENESS for NIK: ' . $nik);
                    return redirect()->route('admin.pemilih.index')->with('success', 'Wajah pemilih dengan NIK ' . $nik . ' berhasil didaftarkan dengan verifikasi liveness!');
                } else {
                    $errorMessage = $livenessStatus ? ($result['message'] ?? 'Gagal mendaftarkan wajah.') : 'Liveness check gagal: ' . $livenessMessage;
                    Log::warning('Face enrollment failed for NIK ' . $nik . ': ' . $errorMessage);
                    return back()->withErrors(['face_image' => $errorMessage])->onlyInput('nik');
                }
            } else {
                Log::error('Microservice enrollment error (HTTP ' . $response->status() . '): ' . $response->body());
                return back()->withErrors(['general' => 'Terjadi kesalahan pada layanan pendaftaran wajah. Coba lagi nanti. (Kode: ' . $response->status() . ')']);
            }
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Microservice pendaftaran wajah tidak dapat dijangkau: ' . $e->getMessage());
            return back()->withErrors(['general' => 'Layanan pendaftaran wajah tidak tersedia. Silakan hubungi pengembang.']);
        } catch (\Throwable $e) {
            Log::error('Kesalahan tak terduga saat pendaftaran wajah untuk NIK ' . $nik . ': ' . $e->getMessage());
            return back()->withErrors(['general' => 'Terjadi kesalahan tak terduga saat pendaftaran wajah. Silakan coba lagi.']);
        }
    }

    /**
     * Menampilkan form untuk membuat pemilih baru.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('admin.pemilih.create');
    }

    /**
     * Menyimpan pemilih baru ke database.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'nik' => ['required', 'string', 'digits:16', 'unique:pemilih,nik'],
            'nama_lengkap' => ['required', 'string', 'max:255'],
            'alamat' => ['nullable', 'string', 'max:255'],
        ], [
            'nik.unique' => 'NIK ini sudah terdaftar. Mohon gunakan NIK lain atau edit data yang sudah ada.',
            'nik.digits' => 'NIK harus 16 digit angka.',
        ]);

        Pemilih::create([
            'nik' => $request->nik,
            'nama_lengkap' => $request->nama_lengkap,
            'alamat' => $request->alamat,
            'sudah_memilih' => false,
        ]);

        return redirect()->route('admin.pemilih.index')->with('success', 'Pemilih dengan NIK ' . $request->nik . ' berhasil ditambahkan!');
    }

    /**
     * Menampilkan form untuk mengedit pemilih yang sudah ada.
     *
     * @param  \App\Models\Pemilih  $pemilih
     * @return \Illuminate\View\View
     */
    public function edit(Pemilih $pemilih)
    {
        return view('admin.pemilih.edit', compact('pemilih'));
    }

    /**
     * Memperbarui pemilih yang sudah ada di database.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Pemilih  $pemilih
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Pemilih $pemilih)
    {
        $request->validate([
            'nik' => ['required', 'string', 'digits:16', 'unique:pemilih,nik,' . $pemilih->id],
            'nama_lengkap' => ['required', 'string', 'max:255'],
            'alamat' => ['nullable', 'string', 'max:255'],
        ], [
            'nik.unique' => 'NIK ini sudah terdaftar. Mohon gunakan NIK lain.',
            'nik.digits' => 'NIK harus 16 digit angka.',
        ]);

        $pemilih->update([
            'nik' => $request->nik,
            'nama_lengkap' => $request->nama_lengkap,
            'alamat' => $request->alamat,
        ]);

        return redirect()->route('admin.pemilih.index')->with('success', 'Data pemilih dengan NIK ' . $request->nik . ' berhasil diperbarui!');
    }

    /**
     * Menghapus pemilih dari database.
     *
     * @param  \App\Models\Pemilih  $pemilih
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Pemilih $pemilih)
    {
        $pemilih->delete();

        return redirect()->route('admin.pemilih.index')->with('success', 'Pemilih berhasil dihapus!');
    }
}