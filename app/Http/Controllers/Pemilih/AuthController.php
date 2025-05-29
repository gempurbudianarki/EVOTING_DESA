<?php

namespace App\Http\Controllers\Pemilih;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http; // Digunakan untuk memanggil microservice Python
use Carbon\Carbon;
use App\Models\Pemilih; // Import model Pemilih

class AuthController extends Controller
{
    /**
     * Menampilkan halaman login pemilih (dengan input NIK dan kamera).
     *
     * @return \Illuminate\View\View
     */
    public function showLoginForm()
    {
        // Jika pemilih sudah login, redirect ke dashboard pemilih
        if (Auth::guard('web_pemilih')->check()) {
            return redirect()->route('pemilih.dashboard');
        }
        return view('pemilih.auth.login'); // Mengarahkan ke view resources/views/pemilih/auth/login.blade.php
    }

    /**
     * Memproses permintaan login pemilih dengan verifikasi wajah + liveness.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        // 1. Validasi input NIK dan data gambar (base64 sequence)
        $request->validate([
            'nik' => ['required', 'string', 'digits:16'], // NIK harus 16 digit
            'face_image_sequence' => ['required', 'array', 'min:5'], // Harus array gambar, minimal 5 frame
            'face_image_sequence.*' => ['required', 'string'], // Setiap elemen array harus string base64
            'liveness_challenge_type' => ['required', 'string', 'in:blink,head_yaw,head_pitch'], // Tipe challenge
        ], [
            'nik.required' => 'NIK wajib diisi.',
            'nik.digits' => 'NIK harus 16 digit angka.',
            'face_image_sequence.required' => 'Urutan gambar wajah diperlukan untuk verifikasi.',
            'face_image_sequence.array' => 'Data gambar wajah harus berupa urutan gambar.',
            'face_image_sequence.min' => 'Diperlukan minimal 5 gambar untuk verifikasi.',
            'liveness_challenge_type.required' => 'Tipe tantangan liveness diperlukan.',
            'liveness_challenge_type.in' => 'Tipe tantangan liveness tidak valid.'
        ]);

        $nik = $request->input('nik');
        $faceImageSequence = $request->input('face_image_sequence'); // Array of Base64 images
        $livenessChallengeType = $request->input('liveness_challenge_type');

        // 2. Cari pemilih berdasarkan NIK
        /** @var \App\Models\Pemilih $pemilih */
        $pemilih = Pemilih::where('nik', $nik)->first();

        if (!$pemilih) {
            Log::warning('Login Pemilih Gagal: NIK tidak ditemukan: ' . $nik . ' dari IP: ' . $request->ip());
            return back()->withErrors(['nik' => 'NIK tidak terdaftar.'])->onlyInput('nik');
        }

        // 3. Verifikasi Wajah dengan Microservice Python (KODE BARU DENGAN LIVENESS)
        // ====================================================================================
        $face_verification_url = env('FACE_VERIFICATION_SERVICE_URL', 'http://localhost:5000/verify_face');
        // Ganti endpoint ke /verify_face_with_liveness
        $face_verification_url = str_replace('/verify_face', '/verify_face_with_liveness', $face_verification_url);
        
        try {
            // Panggil microservice Python
            $response = Http::timeout(60)->post($face_verification_url, [
                'image_sequence' => $faceImageSequence, // Kirim array gambar
                'saved_embedding' => json_decode($pemilih->face_embedding), // Embedding wajah dari DB
                'challenge_type' => $livenessChallengeType // Tipe challenge yang diminta
            ]);

            // Cek apakah permintaan ke microservice berhasil (HTTP 200 OK)
            if ($response->successful()) {
                $verificationResult = $response->json();

                $isMatch = ($verificationResult['is_match'] ?? 'false') === 'true'; // Konversi string 'true'/'false' ke boolean
                $similarityScore = $verificationResult['similarity_score'] ?? 0.0;
                $livenessStatus = ($verificationResult['liveness_challenge_status'] ?? 'false') === 'true';
                $livenessMessage = $verificationResult['liveness_message'] ?? 'Liveness check failed.';

                Log::info('Face verification result for NIK ' . $nik . 
                           ': Match=' . ($isMatch ? 'true' : 'false') . 
                           ', Score=' . $similarityScore . 
                           ', Liveness=' . ($livenessStatus ? 'true' : 'false') . 
                           ', Msg=' . $livenessMessage);

                if ($livenessStatus && $isMatch) { // Harus lulus liveness DAN wajah cocok
                    // Verifikasi berhasil, lakukan login
                    Auth::guard('web_pemilih')->login($pemilih);

                    $request->session()->regenerate();

                    $pemilih->last_login_at = Carbon::now();
                    $pemilih->last_login_ip = $request->ip();
                    $pemilih->save();

                    Log::info('Pemilih logged in successfully: ' . $pemilih->nik . ' from IP: ' . $request->ip());
                    return redirect()->intended(route('pemilih.dashboard'));
                } else {
                    // Verifikasi gagal (baik liveness atau wajah tidak cocok)
                    $errorMessage = $livenessStatus ? 'Verifikasi wajah gagal. Wajah tidak cocok.' : 'Liveness check gagal: ' . $livenessMessage;
                    Log::warning('Login Pemilih Gagal: ' . $errorMessage . ' untuk NIK: ' . $nik . ' (Score: ' . $similarityScore . ') dari IP: ' . $request->ip());
                    return back()->withErrors(['face_image' => $errorMessage])->onlyInput('nik');
                }
            } else {
                // Microservice mengembalikan error HTTP (misal: 400, 500)
                Log::error('Microservice verifikasi wajah error (HTTP ' . $response->status() . '): ' . $response->body());
                return back()->withErrors(['general' => 'Terjadi kesalahan pada layanan verifikasi wajah. Coba lagi nanti. (Kode: ' . $response->status() . ')']);
            }
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            // Jika microservice tidak bisa dijangkau
            Log::error('Microservice verifikasi wajah tidak dapat dijangkau: ' . $e->getMessage());
            return back()->withErrors(['general' => 'Layanan verifikasi wajah tidak tersedia. Silakan hubungi panitia.']);
        } catch (\Throwable $e) {
            // Penanganan error tak terduga lainnya
            Log::error('Kesalahan tak terduga saat verifikasi wajah untuk NIK ' . $nik . ': ' . $e->getMessage());
            return back()->withErrors(['general' => 'Terjadi kesalahan tak terduga. Silakan coba lagi.']);
        }
        // ====================================================================================
    }

    /**
     * Memproses permintaan logout pemilih.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function logout(Request $request)
    {
        Auth::guard('web_pemilih')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        Log::info('Pemilih logged out successfully.');

        return redirect()->route('pemilih.login');
    }
}