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
            // 'liveness_challenge_type' => ['required', 'string', 'in:blink,head_yaw,head_pitch'], // Tipe challenge lama
            // Kita tidak lagi memvalidasi liveness_challenge_type di sini, karena liveness dilakukan terpisah.
            // Namun, jika Anda mengirim 'all_passed' sebagai penanda, Anda bisa memvalidasi itu juga.
        ], [
            'nik.required' => 'NIK wajib diisi.',
            'nik.digits' => 'NIK harus 16 digit angka.',
            'face_image_sequence.required' => 'Urutan gambar wajah diperlukan untuk verifikasi.',
            'face_image_sequence.array' => 'Data gambar wajah harus berupa urutan gambar.',
            'face_image_sequence.min' => 'Diperlukan minimal 5 gambar untuk verifikasi.',
            // 'liveness_challenge_type.required' => 'Tipe tantangan liveness diperlukan.',
            // 'liveness_challenge_type.in' => 'Tipe tantangan liveness tidak valid.'
        ]);

        $nik = $request->input('nik');
        $faceImageSequence = $request->input('face_image_sequence'); // Array of Base64 images
        // $livenessChallengeType = $request->input('liveness_challenge_type'); // Tidak digunakan lagi di sini

        // 2. Cari pemilih berdasarkan NIK
        /** @var \App\Models\Pemilih $pemilih */
        $pemilih = Pemilih::where('nik', $nik)->first();

        if (!$pemilih) {
            Log::warning('Login Pemilih Gagal: NIK tidak ditemukan: ' . $nik . ' dari IP: ' . $request->ip());
            return back()->withErrors(['nik' => 'NIK tidak terdaftar.'])->onlyInput('nik');
        }
        
        // Pastikan pemilih memiliki face_embedding
        if (empty($pemilih->face_embedding)) {
            Log::warning('Login Pemilih Gagal: Pemilih ' . $nik . ' belum mendaftarkan wajah.');
            return back()->withErrors(['general' => 'Anda belum mendaftarkan wajah. Silakan hubungi panitia untuk pendaftaran wajah.'])->onlyInput('nik');
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
                'challenge_type' => 'all_passed' // Mengirimkan tanda bahwa liveness sudah dilakukan di frontend
            ]);

            // Cek apakah permintaan ke microservice berhasil (HTTP 200 OK)
            if ($response->successful()) {
                $verificationResult = $response->json();

                $isMatch = ($verificationResult['is_match'] ?? 'false') === 'true'; // Konversi string 'true'/'false' ke boolean
                $similarityScore = $verificationResult['similarity_score'] ?? 0.0;
                $livenessStatus = ($verificationResult['liveness_challenge_status'] ?? 'false') === 'true'; // Status liveness dari backend
                $livenessMessage = $verificationResult['liveness_message'] ?? 'Liveness check failed.';

                Log::info('Face verification result for NIK ' . $nik . 
                           ': Match=' . ($isMatch ? 'true' : 'false') . 
                           ', Score=' . $similarityScore . 
                           ', Liveness=' . ($livenessStatus ? 'true' : 'false') . 
                           ', Msg=' . $livenessMessage);

                // Kedua kondisi harus terpenuhi: liveness lulus DAN wajah cocok
                if ($livenessStatus && $isMatch) { 
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
                    $errorMessage = '';
                    if (!$livenessStatus) {
                        $errorMessage .= 'Liveness check gagal: ' . $livenessMessage;
                    }
                    if (!$isMatch) {
                        if ($errorMessage) $errorMessage .= ' ';
                        $errorMessage .= 'Verifikasi wajah gagal. Wajah tidak cocok.';
                    }
                    if (!$errorMessage) $errorMessage = 'Verifikasi gagal. Silakan coba lagi.'; // Fallback jika tidak ada pesan spesifik

                    Log::warning('Login Pemilih Gagal: ' . $errorMessage . ' untuk NIK: ' . $nik . ' (Score: ' . $similarityScore . ') dari IP: ' . $request->ip());
                    return back()->withErrors(['face_image' => $errorMessage])->onlyInput('nik');
                }
            } else {
                // Microservice mengembalikan error HTTP (misal: 400, 500)
                $errorMessage = $response->json()['message'] ?? 'Terjadi kesalahan pada layanan verifikasi wajah.';
                Log::error('Microservice verifikasi wajah error (HTTP ' . $response->status() . '): ' . $response->body());
                return back()->withErrors(['general' => $errorMessage . ' (Kode: ' . $response->status() . ')']);
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
     * Endpoint untuk melakukan liveness check terpisah.
     * Dipanggil oleh frontend untuk setiap tantangan liveness.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkLiveness(Request $request)
    {
        $request->validate([
            'image_sequence' => ['required', 'array', 'min:5'],
            'image_sequence.*' => ['required', 'string'],
            'challenge_type' => ['required', 'string', 'in:blink,head_yaw,head_pitch'],
        ], [
            'image_sequence.required' => 'Urutan gambar wajah diperlukan.',
            'challenge_type.required' => 'Tipe tantangan liveness diperlukan.'
        ]);

        $face_service_url = env('FACE_VERIFICATION_SERVICE_URL', 'http://localhost:5000/verify_face');
        $liveness_only_url = str_replace('/verify_face', '/liveness_only', $face_service_url); 
        
        try {
            $response = Http::timeout(15)->post($liveness_only_url, [ // Timeout lebih pendek karena hanya liveness
                'image_sequence' => $request->input('image_sequence'),
                'challenge_type' => $request->input('challenge_type')
            ]);

            if ($response->successful()) {
                return response()->json($response->json());
            } else {
                Log::error('Microservice liveness check error (HTTP ' . $response->status() . '): ' . $response->body());
                $errorMessage = $response->json()['message'] ?? 'Layanan liveness tidak merespons dengan baik.';
                return response()->json(['status' => 'error', 'message' => $errorMessage], $response->status());
            }
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Microservice liveness check tidak dapat dijangkau: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Layanan liveness tidak tersedia. Silakan hubungi panitia.'], 500);
        } catch (\Throwable $e) {
            Log::error('Kesalahan tak terduga saat liveness check: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Terjadi kesalahan tak terduga saat liveness check.'], 500);
        }
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