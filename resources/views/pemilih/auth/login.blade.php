<?php
// gempurbudianarki/evoting_desa/EVOTING_DESA-b34845e78ac5420a547413a68e63d1d0080f4b73/resources/views/pemilih/auth/login.blade.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Pemilih - EVoting Desa</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    {{-- BARU: Meta tag untuk CSRF Token --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .video-container {
            position: relative;
            width: 100%;
            padding-top: 75%; /* 4:3 Aspect Ratio (3/4 * 100) */
            overflow: hidden;
            background-color: #000;
            border-radius: 0.5rem; /* rounded-lg */
        }
        .video-container video {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover; /* Memastikan video mengisi container */
            transform: scaleX(-1); /* Mirror effect untuk selfie */
        }
        .shutter-effect {
            animation: flash 0.1s;
        }
        @keyframes flash {
            0% { opacity: 1; }
            50% { opacity: 0; }
            100% { opacity: 1; }
        }
        .blinking-dot {
            display: inline-block;
            width: 10px;
            height: 10px;
            background-color: red;
            border-radius: 50%;
            animation: pulse 1s infinite alternate;
            margin-left: 5px;
        }
        @keyframes pulse {
            0% { transform: scale(1); opacity: 1; }
            100% { transform: scale(1.2); opacity: 0.7; }
        }
        #overlayCanvas { /* Pastikan ini ada untuk menggambar landmarks */
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
        }
        /* Tambahan CSS untuk menyembunyikan kotak deteksi dan angka secara default */
        .faceapi-canvas {
            display: none; /* Sembunyikan canvas Face-API.js secara default */
        }
    </style>
    <script defer src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
</head>
<body class="bg-gradient-to-br from-blue-400 to-indigo-600 flex items-center justify-center min-h-screen p-4">
    <div class="w-full max-w-lg bg-white p-8 rounded-xl shadow-2xl">
        <h2 class="text-3xl font-extrabold text-center text-gray-800 mb-8">
            Login Pemilih <br> EVoting Desa
        </h2>

        @if ($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative mb-6" role="alert">
                <strong class="font-bold">Perhatian!</strong>
                <span class="block sm:inline">Ada masalah saat login.</span>
                <ul class="mt-2 list-disc list-inside text-sm">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form id="pemilihLoginForm" action="{{ route('pemilih.login') }}" method="POST">
            @csrf

            <div class="mb-6">
                <label for="nik" class="block text-gray-700 text-base font-semibold mb-2">Nomor Induk Kependudukan (NIK):</label>
                <input type="text" id="nik" name="nik"
                       class="shadow appearance-none border rounded-lg w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 @error('nik') border-red-500 @enderror"
                       value="{{ old('nik') }}" placeholder="Arahkan wajah ke kamera atau masukkan NIK" maxlength="16" required autofocus>
                @error('nik')
                    <p class="text-red-500 text-xs italic mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-6 text-center">
                <label class="block text-gray-700 text-base font-semibold mb-3">Verifikasi Wajah:</label>
                <div class="video-container mx-auto max-w-sm border-2 border-gray-300">
                    <video id="webcam" autoplay muted playsinline></video>
                    {{-- Overlay canvas untuk menggambar landmarks, awalnya tersembunyi --}}
                    <canvas id="overlayCanvas" class="absolute top-0 left-0 w-full h-full faceapi-canvas"></canvas> 
                </div>
                <canvas id="captureCanvas" class="hidden"></canvas> {{-- Untuk mengambil frame --}}
                
                {{-- Input tersembunyi untuk sequence gambar dan challenge type --}}
                <input type="hidden" name="face_image_sequence" id="faceImageSequenceInput"> 
                <input type="hidden" name="liveness_challenge_type" id="livenessChallengeTypeInput"> 
                
                <p id="cameraStatus" class="text-sm text-gray-600 mt-2">Mohon izinkan akses kamera Anda.</p>
                <p id="livenessInstruction" class="text-lg font-bold text-blue-700 mt-3 hidden">
                    <span id="instructionText"></span> <span class="blinking-dot"></span>
                </p>
                <p id="livenessStatus" class="text-sm text-gray-600 mt-2"></p>
                <p id="autoFillStatus" class="text-green-700 text-sm mt-2 hidden">NIK ditemukan! Melanjutkan ke verifikasi liveness...</p>


                @error('face_image')
                    <p class="text-red-500 text-xs italic mt-1">{{ $message }}</p>
                @enderror
                @error('general')
                    <p class="text-red-500 text-xs italic mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center justify-center">
                <button type="button" id="loginButton" class="bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-6 rounded-full focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition duration-300 ease-in-out" disabled>
                    Mulai Verifikasi Liveness
                </button>
            </div>
        </form>
    </div>

    <script>
        // Membungkus semua kode JavaScript di dalam event DOMContentLoaded
        document.addEventListener('DOMContentLoaded', () => {
            // --- DOM Elements ---
            const webcam = document.getElementById('webcam');
            const overlayCanvas = document.getElementById('overlayCanvas');
            const captureCanvas = document.getElementById('captureCanvas');
            const faceImageSequenceInput = document.getElementById('faceImageSequenceInput');
            const livenessChallengeTypeInput = document.getElementById('livenessChallengeTypeInput');
            const nikInput = document.getElementById('nik');
            const cameraStatus = document.getElementById('cameraStatus');
            const livenessInstruction = document.getElementById('livenessInstruction');
            const instructionText = document.getElementById('instructionText');
            const livenessStatus = document.getElementById('livenessStatus');
            const autoFillStatus = document.getElementById('autoFillStatus');
            const loginButton = document.getElementById('loginButton'); // Sekarang tombol ini untuk memulai liveness
            const form = document.getElementById('pemilihLoginForm');

            // --- Global Variables ---
            let stream;
            let displaySize;
            const CHALLENGES = ['blink', 'head_yaw', 'head_pitch'];
            let currentChallengeIndex = 0;
            let currentChallenge = '';
            let imageSequence = [];
            const CHALLENGE_DURATION = 3000;
            const SEQUENCE_INTERVAL = 100;
            let captureIntervalId;
            let detectionIntervalId;
            let livenessDetectionRunning = false;
            let autoFillAttempted = false; // Flag untuk memastikan auto-fill hanya sekali

            // --- Face-API.js Models ---
            // Memuat model setelah DOMContentLoaded
            Promise.all([
                faceapi.nets.ssdMobilenetv1.loadFromUri('/models'), 
                faceapi.nets.faceLandmark68Net.loadFromUri('/models')
            ]).then(startCamera).catch(err => {
                console.error("Error loading face-api models:", err);
                cameraStatus.textContent = 'Gagal memuat model verifikasi wajah. Mohon coba lagi.';
                loginButton.disabled = true;
            });

            // --- Camera & Frame Capture ---
            async function startCamera() {
                try {
                    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                        cameraStatus.textContent = 'Browser tidak mendukung akses kamera.';
                        loginButton.disabled = true;
                        return;
                    }
                    stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' } });
                    webcam.srcObject = stream;
                    cameraStatus.textContent = 'Kamera aktif. Arahkan wajah Anda untuk login otomatis atau masukkan NIK.';
                    webcam.addEventListener('play', () => {
                        displaySize = {
                            width: webcam.videoWidth || webcam.offsetWidth,
                            height: webcam.videoHeight || webcam.offsetHeight
                        };
                        faceapi.matchDimensions(overlayCanvas, displaySize);
                        faceapi.matchDimensions(captureCanvas, displaySize);
                        
                        // Penting: Atur transformasi untuk mirroring pada context canvas HANYA SEKALI
                        const context = overlayCanvas.getContext('2d');
                        context.translate(overlayCanvas.width, 0); 
                        context.scale(-1, 1); 

                        // Mulai deteksi wajah untuk auto-fill NIK
                        startInitialFaceDetection();
                    });
                } catch (err) {
                    console.error("Error accessing camera:", err);
                    // Lebih spesifik tentang error izin
                    if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
                        cameraStatus.textContent = 'Akses kamera DITOLAK. Mohon izinkan akses kamera di pengaturan browser Anda.';
                    } else if (err.name === 'NotFoundError') {
                        cameraStatus.textContent = 'Tidak ditemukan kamera. Pastikan kamera terpasang dan tidak digunakan aplikasi lain.';
                    } else {
                        cameraStatus.textContent = `Gagal mengakses kamera: ${err.message}.`;
                    }
                    loginButton.disabled = true;
                }
            }

            function takePicture(videoElement, canvasElement) {
                const context = canvasElement.getContext('2d');
                canvasElement.width = videoElement.videoWidth;
                canvasElement.height = videoElement.videoHeight;
                context.clearRect(0, 0, canvasElement.width, canvasElement.height); // Clear before drawing
                context.drawImage(videoElement, 0, 0, canvasElement.width, canvasElement.height);
                return canvasElement.toDataURL('image/jpeg', 0.9);
            }

            // --- Initial Face Detection for Auto-Fill ---
            async function startInitialFaceDetection() {
                detectionIntervalId = setInterval(async () => {
                    // Hanya jalankan auto-fill jika NIK kosong dan belum pernah dicoba auto-fill dan liveness tidak berjalan
                    if (nikInput.value === '' && !autoFillAttempted && !livenessDetectionRunning) {
                        const detections = await faceapi.detectSingleFace(webcam, new faceapi.SsdMobilenetv1Options()); // Tanpa .withFaceLandmarks() agar lebih cepat

                        if (detections) { // Hanya cek deteksi wajah, tidak perlu landmarks dulu
                            // Wajah terdeteksi, coba identifikasi
                            const singleFrameBase64 = takePicture(webcam, captureCanvas);
                            
                            try {
                                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content'); 
                                // Menggunakan route yang sama, tapi dengan challenge_type khusus untuk identifikasi
                                const response = await fetch("{{ route('pemilih.liveness.check') }}", { 
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': csrfToken
                                    },
                                    body: JSON.stringify({
                                        image_sequence: [singleFrameBase64], // Kirim hanya 1 frame untuk identifikasi awal
                                        challenge_type: 'identify_only' 
                                    })
                                });
                                const result = await response.json();

                                if (response.ok && result.status === 'success' && result.identified_nik) {
                                    nikInput.value = result.identified_nik;
                                    nikInput.readOnly = true; 
                                    autoFillStatus.textContent = `NIK ditemukan: ${result.identified_nik} (${result.identified_name}). Melanjutkan ke verifikasi liveness...`;
                                    autoFillStatus.classList.remove('hidden');
                                    autoFillAttempted = true; 
                                    clearInterval(detectionIntervalId); // Hentikan deteksi awal
                                    loginButton.disabled = false; 
                                    cameraStatus.textContent = 'NIK berhasil terisi otomatis. Klik tombol di bawah untuk mulai verifikasi.';
                                } else if (result.message) {
                                    cameraStatus.textContent = `Wajah terdeteksi, tetapi: ${result.message}`;
                                }
                            } catch (error) {
                                console.error("Error during auto-fill identification AJAX:", error);
                                cameraStatus.textContent = 'Terjadi kesalahan saat mencoba identifikasi otomatis. Silakan masukkan NIK manual.';
                            }
                        } else {
                            cameraStatus.textContent = 'Wajah tidak terdeteksi atau tidak jelas. Posisikan wajah Anda dengan baik.';
                            loginButton.disabled = true; 
                        }
                    } else if (nikInput.value !== '' && !livenessDetectionRunning) {
                        // Jika NIK diisi manual, aktifkan tombol
                        loginButton.disabled = false;
                        clearInterval(detectionIntervalId); 
                        cameraStatus.textContent = 'NIK siap. Klik tombol di bawah untuk mulai verifikasi.';
                    } else if (livenessDetectionRunning) {
                        clearInterval(detectionIntervalId); 
                    }
                }, 500); 
            }

            // --- Liveness Detection Logic (Frontend) ---

            // Fungsi ini untuk menggambar kontur wajah secara lebih detail (seperti yang Anda inginkan)
            function drawContourFromPoints(context, points, drawOptions, isClosed = false) {
                if (!points || points.length < 2) return; 

                context.beginPath();
                context.moveTo(points[0].x, points[0].y);
                for (let i = 1; i < points.length; i++) {
                    context.lineTo(points[i].x, points[i].y);
                }
                if (isClosed) {
                    context.closePath();
                }
                
                context.strokeStyle = drawOptions.color;
                context.lineWidth = drawOptions.lineWidth;
                context.stroke();
            }

            async function detectAndDrawLandmarksContinuousForLiveness() {
                if (!webcam.paused && !webcam.ended && livenessDetectionRunning) {
                    const detections = await faceapi.detectSingleFace(webcam, new faceapi.SsdMobilenetv1Options()).withFaceLandmarks();
                    
                    // --- Reset dan terapkan transformasi cermin pada canvas ---
                    const context = overlayCanvas.getContext('2d');
                    context.setTransform(1, 0, 0, 1, 0, 0); 
                    context.clearRect(0, 0, overlayCanvas.width, overlayCanvas.height); 
                    context.translate(overlayCanvas.width, 0); 
                    context.scale(-1, 1); 
                    
                    if (detections) {
                        const resizedDetections = faceapi.resizeResults(detections, displaySize); 
                        
                        // Gambar kotak wajah
                        // faceapi.draw.drawDetections(overlayCanvas, resizedDetections); // Menghilangkan kotak biru

                        // Gambar landmarks seperti yang kamu inginkan
                        const landmarksPositions = resizedDetections.landmarks.positions;
                        const drawOptions = { lineWidth: 2, color: 'lime' }; // Warna hijau neon

                        drawContourFromPoints(context, landmarksPositions.slice(0, 17), drawOptions, false); 
                        drawContourFromPoints(context, landmarksPositions.slice(17, 22), drawOptions, false); 
                        drawContourFromPoints(context, landmarksPositions.slice(22, 27), drawOptions, false); 
                        drawContourFromPoints(context, landmarksPositions.slice(27, 36), drawOptions, false);
                        drawContourFromPoints(context, landmarksPositions.slice(36, 42), drawOptions, true); 
                        drawContourFromPoints(context, landmarksPositions.slice(42, 48), drawOptions, true); 
                        drawContourFromPoints(context, landmarksPositions.slice(48, 68), drawOptions, true); 

                        // Opsional: Gambar titik-titik (dots) untuk semua landmarks
                        context.fillStyle = 'magenta'; // Warna titik
                        for (let i = 0; i < landmarksPositions.length; i++) {
                            context.beginPath();
                            context.arc(landmarksPositions[i].x, landmarksPositions[i].y, 2, 0, 2 * Math.PI); // Radius 2
                            context.fill();
                        }
                    }
                    requestAnimationFrame(detectAndDrawLandmarksContinuousForLiveness);
                } else {
                    // Hentikan menggambar landmarks jika liveness tidak berjalan
                    overlayCanvas.getContext('2d').clearRect(0, 0, overlayCanvas.width, overlayCanvas.height);
                    overlayCanvas.classList.add('faceapi-canvas'); 
                }
            }


            loginButton.addEventListener('click', () => {
                if (nikInput.value === '') {
                    alert('Mohon masukkan NIK Anda atau posisikan wajah Anda di kamera untuk identifikasi otomatis.');
                    return;
                }
                if (livenessDetectionRunning) { 
                    return;
                }

                currentChallengeIndex = 0; 
                imageSequence = []; 
                livenessStatus.textContent = ''; 
                loginButton.disabled = true; 
                nikInput.readOnly = true; 

                // Tampilkan canvas overlay saat liveness dimulai
                overlayCanvas.classList.remove('faceapi-canvas'); 

                startLivenessChallengeCycle(); 
            });


            function startLivenessChallengeCycle() {
                if (currentChallengeIndex < CHALLENGES.length) {
                    livenessDetectionRunning = true;
                    currentChallenge = CHALLENGES[currentChallengeIndex]; 
                    instructionText.textContent = getChallengeInstruction(currentChallenge);
                    livenessInstruction.classList.remove('hidden');
                    livenessStatus.textContent = 'Bersiap...';
                    imageSequence = []; 
                    
                    captureIntervalId = setInterval(() => {
                        if (imageSequence.length < (CHALLENGE_DURATION / SEQUENCE_INTERVAL) * 1.5) { 
                            imageSequence.push(takePicture(webcam, captureCanvas));
                        }
                    }, SEQUENCE_INTERVAL);

                    setTimeout(() => {
                        if (livenessDetectionRunning) livenessStatus.textContent = 'Lakukan sekarang!';
                        detectAndDrawLandmarksContinuousForLiveness(); 
                    }, 500); 

                    setTimeout(async () => {
                        clearInterval(captureIntervalId); 
                        if (livenessDetectionRunning) await processLivenessChallenge(currentChallenge); 
                    }, CHALLENGE_DURATION + 500); 
                } else {
                    livenessDetectionRunning = false;
                    livenessInstruction.classList.add('hidden');
                    livenessStatus.textContent = 'Verifikasi liveness selesai. Mengirimkan data login...';
                    nikInput.readOnly = false; 
                    
                    overlayCanvas.classList.add('faceapi-canvas');

                    faceImageSequenceInput.value = JSON.stringify(imageSequence); 
                    livenessChallengeTypeInput.value = 'all_passed'; 
                    cameraStatus.textContent = 'Wajah Anda sudah dianalisis. Tekan tombol Login & Verifikasi untuk melanjutkan.';
                    loginButton.disabled = false; 
                    loginButton.textContent = 'Login & Verifikasi'; 
                    form.submit();
                }
            }

            function getChallengeInstruction(challenge) {
                switch (challenge) {
                    case 'blink': return 'Kedipkan mata Anda beberapa kali.';
                    case 'head_yaw': return 'Gerakkan kepala ke kiri dan kanan.';
                    case 'head_pitch': return 'Anggukkan kepala Anda ke atas dan bawah.';
                    case 'identify_only': return 'Mendeteksi wajah Anda...'; 
                    default: return 'Lakukan gerakan verifikasi.';
                }
            }

            async function processLivenessChallenge(challenge) {
                livenessInstruction.classList.add('hidden');
                livenessStatus.textContent = 'Menganalisis liveness...';
                
                if (imageSequence.length === 0) {
                    livenessStatus.textContent = 'Gagal: Tidak ada frame yang ditangkap. Coba lagi.';
                    setTimeout(resetLivenessProcess, 2000);
                    return;
                }

                try {
                    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                    const response = await fetch("{{ route('pemilih.liveness.check') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({
                            image_sequence: imageSequence,
                            challenge_type: challenge
                        })
                    });
                    const result = await response.json();
                    
                    if (response.ok && result.status === 'success' && result.is_live === 'true') {
                        livenessStatus.textContent = `Liveness Check Lulus (${challenge}): ${result.message}`;
                        currentChallengeIndex++; 
                        setTimeout(startLivenessChallengeCycle, 1000); 
                    } else {
                        livenessStatus.textContent = `Liveness Check Gagal (${challenge}): ${result.message || 'Coba lagi.'}`;
                        setTimeout(resetLivenessProcess, 2000); 
                    }
                } catch (error) {
                    console.error("Error during liveness check AJAX:", error);
                    livenessStatus.textContent = 'Terjadi kesalahan saat memeriksa liveness. Coba lagi.';
                    setTimeout(resetLivenessProcess, 2000);
                }
            }

            function resetLivenessProcess() {
                currentChallengeIndex = 0; 
                livenessInstruction.classList.add('hidden');
                livenessStatus.textContent = '';
                cameraStatus.textContent = 'Kamera aktif. Harap bersiap untuk verifikasi wajah.';
                loginButton.disabled = false; 
                loginButton.textContent = 'Mulai Verifikasi Liveness';
                nikInput.readOnly = false; 
                autoFillStatus.classList.add('hidden'); 
                autoFillAttempted = false; 
                imageSequence = []; 
                livenessDetectionRunning = false; 
                
                overlayCanvas.classList.add('faceapi-canvas');
                overlayCanvas.getContext('2d').clearRect(0, 0, overlayCanvas.width, overlayCanvas.height);

                clearInterval(detectionIntervalId); 
                startInitialFaceDetection(); 
            }

            // --- Event Listener untuk Submit Form ---
            form.addEventListener('submit', function(event) {
                if (loginButton.disabled || loginButton.textContent === 'Mulai Verifikasi Liveness') {
                    event.preventDefault(); 
                    alert('Mohon selesaikan semua tantangan verifikasi liveness terlebih dahulu, atau pastikan NIK terisi.');
                    return;
                }
                this.submitted = true; 
                webcam.classList.add('shutter-effect'); 
                setTimeout(() => { webcam.classList.remove('shutter-effect'); }, 150);
            });

            // --- Cleanup saat halaman ditutup ---
            window.addEventListener('beforeunload', () => {
                if (stream) {
                    stream.getTracks().forEach(track => track.stop());
                }
                clearInterval(detectionIntervalId);
                clearInterval(captureIntervalId);
            });
        }); // Penutup DOMContentLoaded
    </script>
</body>
</html>