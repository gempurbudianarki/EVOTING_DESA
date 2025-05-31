<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Pemilih - EVoting Desa</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
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
                       value="{{ old('nik') }}" placeholder="Contoh: 33xxxxxxxxxxxxxx" maxlength="16" required autofocus>
                @error('nik')
                    <p class="text-red-500 text-xs italic mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-6 text-center">
                <label class="block text-gray-700 text-base font-semibold mb-3">Verifikasi Wajah:</label>
                <div class="video-container mx-auto max-w-sm border-2 border-gray-300">
                    <video id="webcam" autoplay muted playsinline></video>
                    <canvas id="overlayCanvas" class="absolute top-0 left-0 w-full h-full"></canvas> {{-- Untuk menggambar landmarks --}}
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

                @error('face_image')
                    <p class="text-red-500 text-xs italic mt-1">{{ $message }}</p>
                @enderror
                @error('general')
                    <p class="text-red-500 text-xs italic mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center justify-center">
                <button type="submit" id="loginButton" class="bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-6 rounded-full focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition duration-300 ease-in-out" disabled>
                    Login & Verifikasi
                </button>
            </div>
        </form>
    </div>

    <script>
        // --- DOM Elements ---
        const webcam = document.getElementById('webcam');
        const overlayCanvas = document.getElementById('overlayCanvas');
        const captureCanvas = document.getElementById('captureCanvas');
        const faceImageSequenceInput = document.getElementById('faceImageSequenceInput');
        const livenessChallengeTypeInput = document.getElementById('livenessChallengeTypeInput');
        const cameraStatus = document.getElementById('cameraStatus');
        const livenessInstruction = document.getElementById('livenessInstruction');
        const instructionText = document.getElementById('instructionText');
        const livenessStatus = document.getElementById('livenessStatus');
        const loginButton = document.getElementById('loginButton');

        // --- Global Variables ---
        let stream;
        let displaySize;
        // let faceMatcher; // Tidak perlu jika embedding hanya di backend
        const CHALLENGES = ['blink', 'head_yaw', 'head_pitch']; // Tipe challenge yang akan diulang berurutan
        let currentChallengeIndex = 0; // Mengikuti tantangan yang sedang berjalan
        let currentChallenge = ''; // Tantangan yang aktif saat ini
        let imageSequence = []; // Kumpulan frame untuk satu tantangan
        const CHALLENGE_DURATION = 3000; // Durasi setiap challenge dalam ms (3 detik)
        const SEQUENCE_INTERVAL = 100; // Interval pengambilan frame dalam ms
        let captureIntervalId; // ID untuk clearInterval

        // --- Face-API.js Models ---
        Promise.all([
            faceapi.nets.tinyFaceDetector.loadFromUri('/models'),
            faceapi.nets.faceLandmark68Net.loadFromUri('/models'),
            // faceapi.nets.faceRecognitionNet.loadFromUri('/models'), // Tidak perlu jika embedding hanya di backend
            // faceapi.nets.faceExpressionNet.loadFromUri('/models') // Opsional jika ingin deteksi ekspresi
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
                cameraStatus.textContent = 'Kamera aktif. Harap bersiap untuk verifikasi wajah.';
                webcam.addEventListener('play', () => {
                    displaySize = {
                        width: webcam.videoWidth || webcam.offsetWidth, // Fallback jika videoWidth belum tersedia
                        height: webcam.videoHeight || webcam.offsetHeight
                    };
                    faceapi.matchDimensions(overlayCanvas, displaySize);
                    faceapi.matchDimensions(captureCanvas, displaySize);
                    loginButton.disabled = true; // Nonaktifkan tombol saat memulai, akan aktif setelah semua liveness
                    startLivenessChallengeCycle(); // Mulai siklus tantangan liveness
                });
            } catch (err) {
                console.error("Error accessing camera:", err);
                cameraStatus.textContent = 'Gagal mengakses kamera. Mohon izinkan akses kamera Anda.';
                loginButton.disabled = true;
            }
        }

        function takePicture(videoElement, canvasElement) {
            const context = canvasElement.getContext('2d');
            canvasElement.width = videoElement.videoWidth;
            canvasElement.height = videoElement.videoHeight;
            context.drawImage(videoElement, 0, 0, canvasElement.width, canvasElement.height);
            return canvasElement.toDataURL('image/jpeg', 0.9);
        }

        // --- Liveness Detection Logic (Frontend) ---

        function startLivenessChallengeCycle() {
            if (currentChallengeIndex < CHALLENGES.length) {
                currentChallenge = CHALLENGES[currentChallengeIndex]; // Dapatkan tantangan berikutnya
                instructionText.textContent = getChallengeInstruction(currentChallenge);
                livenessInstruction.classList.remove('hidden');
                livenessStatus.textContent = 'Bersiap...';
                imageSequence = []; // Reset sequence untuk tantangan baru
                challengeInProgress = true;
                loginButton.disabled = true; // Pastikan tombol dinonaktifkan selama challenge

                captureIntervalId = setInterval(() => {
                    if (imageSequence.length < (CHALLENGE_DURATION / SEQUENCE_INTERVAL) * 1.5) { // Pastikan cukup frame
                        imageSequence.push(takePicture(webcam, captureCanvas));
                    }
                }, SEQUENCE_INTERVAL);

                setTimeout(() => {
                    if (challengeInProgress) livenessStatus.textContent = 'Lakukan sekarang!';
                    detectAndDrawLandmarks(); // Mulai deteksi dan gambar landmarks
                }, 500); // Beri sedikit waktu untuk bersiap

                setTimeout(async () => {
                    clearInterval(captureIntervalId); // Hentikan capture
                    if (challengeInProgress) await processLivenessChallenge(currentChallenge); // Proses tantangan
                }, CHALLENGE_DURATION + 500); // Durasi tantangan + sedikit jeda
            } else {
                // Semua tantangan selesai
                livenessInstruction.classList.add('hidden');
                livenessStatus.textContent = 'Verifikasi liveness selesai. Anda bisa login.';
                loginButton.disabled = false; // Aktifkan tombol setelah semua challenge lulus
                // Set input tersembunyi untuk proses submit utama
                faceImageSequenceInput.value = JSON.stringify(imageSequence); // Simpan seluruh sequence gambar yang berhasil
                livenessChallengeTypeInput.value = 'all_passed'; // Tanda bahwa semua liveness lulus
                cameraStatus.textContent = 'Wajah Anda sudah dianalisis. Klik tombol Login & Verifikasi.';
            }
        }

        async function detectAndDrawLandmarks() {
            if (!webcam.paused && !webcam.ended) {
                const detections = await faceapi.detectSingleFace(webcam, new faceapi.TinyFaceDetectorOptions()).withFaceLandmarks();
                
                overlayCanvas.getContext('2d').clearRect(0, 0, overlayCanvas.width, overlayCanvas.height);
                if (detections) {
                    const resizedDetections = faceapi.resizeResults(detections, displaySize);
                    faceapi.draw.drawDetections(overlayCanvas, resizedDetections); // Gambar kotak wajah
                    faceapi.draw.drawFaceLandmarks(overlayCanvas, resizedDetections); // Gambar titik landmarks
                    
                    if (!challengeInProgress) {
                         cameraStatus.textContent = 'Wajah terdeteksi. Tunggu instruksi verifikasi liveness.';
                    }
                } else {
                    cameraStatus.textContent = 'Wajah tidak terdeteksi. Harap posisikan wajah Anda di tengah kamera.';
                }
                requestAnimationFrame(detectAndDrawLandmarks); // Lanjutkan loop untuk menggambar
            }
        }

        function getChallengeInstruction(challenge) {
            switch (challenge) {
                case 'blink': return 'Kedipkan mata Anda beberapa kali.';
                case 'head_yaw': return 'Gerakkan kepala ke kiri dan kanan.';
                case 'head_pitch': return 'Anggukkan kepala Anda ke atas dan bawah.';
                default: return 'Lakukan gerakan verifikasi.';
            }
        }

        async function processLivenessChallenge(challenge) {
            challengeInProgress = false; // Challenge saat ini selesai
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
                    currentChallengeIndex++; // Maju ke tantangan berikutnya
                    setTimeout(startLivenessChallengeCycle, 1000); // Mulai tantangan berikutnya setelah jeda
                } else {
                    livenessStatus.textContent = `Liveness Check Gagal (${challenge}): ${result.message || 'Coba lagi.'}`;
                    setTimeout(resetLivenessProcess, 2000); // Reset seluruh proses liveness jika gagal
                }
            } catch (error) {
                console.error("Error during liveness check AJAX:", error);
                livenessStatus.textContent = 'Terjadi kesalahan saat memeriksa liveness. Coba lagi.';
                setTimeout(resetLivenessProcess, 2000);
            }
        }

        function resetLivenessProcess() {
            currentChallengeIndex = 0; // Reset ke tantangan pertama
            livenessInstruction.classList.add('hidden');
            livenessStatus.textContent = '';
            cameraStatus.textContent = 'Kamera aktif. Harap bersiap untuk verifikasi wajah.';
            loginButton.disabled = true; // Nonaktifkan tombol sampai siklus selesai
            imageSequence = []; // Bersihkan sequence
            challengeInProgress = false; // Reset status challenge
            setTimeout(startLivenessChallengeCycle, 1000); // Mulai lagi siklus setelah jeda
        }

        // --- Event Listener untuk Submit Form ---
        document.getElementById('pemilihLoginForm').addEventListener('submit', function(event) {
            // Mencegah submit jika tombol masih dinonaktifkan (liveness belum selesai)
            if (loginButton.disabled) {
                event.preventDefault(); 
                alert('Mohon selesaikan semua tantangan verifikasi liveness terlebih dahulu.');
                return;
            }
            // Jika tombol sudah aktif, berarti semua liveness sudah selesai
            this.submitted = true; // Flag form sudah disubmit
            webcam.classList.add('shutter-effect'); // Efek shutter
            setTimeout(() => { webcam.classList.remove('shutter-effect'); }, 150);
            // Form akan disubmit secara otomatis setelah ini
        });

        // --- Startup & Cleanup ---
        window.addEventListener('load', () => {
            // startCamera sudah dipanggil setelah Promise.all models.
        });
        window.addEventListener('beforeunload', () => {
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
            }
        });
    </script>
</body>
</html>