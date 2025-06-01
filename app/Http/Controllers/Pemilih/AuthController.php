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
            'nik' => ['required', 'string', 'digits:16', 'exists:pemilih,nik'], // Tambahkan exists:pemilih,nik
            'face_image_sequence' => ['required', 'array', 'min:5'], // Harus array gambar, minimal 5 frame
            'face_image_sequence.*' => ['required', 'string'], // Setiap elemen array harus string base64
            'liveness_challenge_type' => ['required', 'string', 'in:all_passed'], // Pastikan hanya all_passed
        ], [
            'nik.required' => 'NIK wajib diisi.',
            'nik.digits' => 'NIK harus 16 digit angka.',
            'nik.exists' => 'NIK tidak terdaftar. Mohon pastikan NIK benar atau daftar wajah.',
            'face_image_sequence.required' => 'Urutan gambar wajah diperlukan untuk verifikasi.',
            'face_image_sequence.array' => 'Data gambar wajah harus berupa urutan gambar.',
            'face_image_sequence.min' => 'Diperlukan minimal 5 gambar untuk verifikasi.',
            'liveness_challenge_type.required' => 'Tipe tantangan liveness diperlukan.',
            'liveness_challenge_type.in' => 'Tipe tantangan liveness tidak valid.'
        ]);

        $nik = $request->input('nik');
        $faceImageSequence = $request->input('face_image_sequence'); // Array of Base64 images
        $livenessChallengeType = $request->input('liveness_challenge_type'); // Ini akan menjadi 'all_passed'

        // 2. Cari pemilih berdasarkan NIK
        /** @var \App\Models\Pemilih $pemilih */
        $pemilih = Pemilih::where('nik', $nik)->first();

        // Pastikan pemilih memiliki face_embedding
        if (empty($pemilih->face_embedding)) {
            Log::warning('Login Pemilih Gagal: Pemilih ' . $nik . ' belum mendaftarkan wajah.');
            return back()->withErrors(['general' => 'Anda belum mendaftarkan wajah. Silakan hubungi panitia untuk pendaftaran wajah.'])->onlyInput('nik');
        }

        // 3. Verifikasi Wajah dengan Microservice Python (KODE BARU DENGAN LIVENESS)
        $face_verification_url = env('FACE_VERIFICATION_SERVICE_URL', 'http://localhost:5000/verify_face');
        // Ganti endpoint ke /verify_face_with_liveness
        $face_verification_url = str_replace('/verify_face', '/verify_face_with_liveness', $face_verification_url);
        
        try {
            // Panggil microservice Python
            $response = Http::timeout(60)->post($face_verification_url, [
                'image_sequence' => $faceImageSequence, // Kirim array gambar
                'saved_embedding' => json_decode($pemilih->face_embedding), // Embedding wajah dari DB
                'challenge_type' => $livenessChallengeType // Mengirimkan 'all_passed'
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
    }

    /**
     * Endpoint untuk melakukan liveness check terpisah.
     * Dipanggil oleh frontend untuk setiap tantangan liveness.
     * Juga digunakan untuk identifikasi awal (auto-fill NIK).
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkLiveness(Request $request)
    {
        // Validasi dasar
        $request->validate([
            'image_sequence' => ['required', 'array', 'min:1'], // Minimal 1 gambar untuk identifikasi
            'image_sequence.*' => ['required', 'string'],
            'challenge_type' => ['required', 'string', 'in:blink,head_yaw,head_pitch,identify_only'], // Tambah 'identify_only'
        ], [
            'image_sequence.required' => 'Urutan gambar wajah diperlukan.',
            'challenge_type.required' => 'Tipe tantangan diperlukan.'
        ]);

        $face_service_url = env('FACE_VERIFICATION_SERVICE_URL', 'http://localhost:5000/verify_face');
        
        $challengeType = $request->input('challenge_type');
        $imageSequence = $request->input('image_sequence');

        if ($challengeType === 'identify_only') {
            // Perbaikan di sini: Panggil /liveness_only karena sudah menangani 'identify_only' di microservice Flask
            $liveness_only_url = str_replace('/verify_face', '/liveness_only', $face_service_url);
            Log::info("Attempting to identify face via microservice: {$liveness_only_url}");
            try {
                // Kirim hanya frame pertama untuk identifikasi, dengan challenge_type 'identify_only'
                $response = Http::timeout(15)->post($liveness_only_url, [
                    'image_sequence' => $imageSequence, // Kirim sequence gambar yang ditangkap
                    'challenge_type' => 'identify_only' // Kirim challenge_type yang benar
                ]);

                if ($response->successful()) {
                    $result = $response->json();
                    if (($result['status'] ?? 'error') === 'success' && !empty($result['identified_nik'])) {
                        // NIK ditemukan, kembalikan data pemilih
                        $pemilih = Pemilih::where('nik', $result['identified_nik'])->first();
                        if ($pemilih) {
                            return response()->json([
                                'status' => 'success',
                                'message' => 'Wajah dikenali.',
                                'identified_nik' => $pemilih->nik,
                                'identified_name' => $pemilih->nama_lengkap,
                            ]);
                        } else {
                            // NIK dikenali oleh microservice tapi tidak ada di database Laravel
                            Log::warning('Identified NIK from microservice not found in Laravel DB: ' . $result['identified_nik']);
                            return response()->json(['status' => 'error', 'message' => 'Wajah dikenali, tetapi data pemilih tidak ditemukan.'], 404);
                        }
                    } else {
                        return response()->json(['status' => 'error', 'message' => $result['message'] ?? 'Wajah tidak dikenali.'], 400);
                    }
                } else {
                    Log::error('Microservice identification error (HTTP ' . $response->status() . '): ' . $response->body());
                    $errorMessage = $response->json()['message'] ?? 'Terjadi kesalahan pada layanan identifikasi wajah.';
                    return response()->json(['status' => 'error', 'message' => $errorMessage], $response->status());
                }
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                Log::error('Microservice identifikasi wajah tidak dapat dijangkau: ' . $e->getMessage());
                return response()->json(['status' => 'error', 'message' => 'Layanan identifikasi wajah tidak tersedia.'], 500);
            } catch (\Throwable $e) {
                Log::error('Kesalahan tak terduga saat identifikasi wajah: ' . $e->getMessage());
                return response()->json(['status' => 'error', 'message' => 'Terjadi kesalahan tak terduga saat identifikasi wajah.'], 500);
            }

        } else {
            // Logika liveness check yang sudah ada untuk blink, head_yaw, head_pitch
            $liveness_only_url = str_replace('/verify_face', '/liveness_only', $face_service_url);
            
            try {
                $response = Http::timeout(15)->post($liveness_only_url, [ // Timeout lebih pendek karena hanya liveness
                    'image_sequence' => $imageSequence,
                    'challenge_type' => $challengeType
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