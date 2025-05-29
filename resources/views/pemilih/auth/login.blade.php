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
        const overlayCanvas = document.getElementById('overlayCanvas'); // Canvas untuk overlay
        const captureCanvas = document.getElementById('captureCanvas');   // Canvas untuk capture frame
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
        let faceMatcher; // Untuk perbandingan wajah (jika perlu di frontend)
        let challenges = ['blink', 'head_yaw', 'head_pitch']; // Tipe challenge yang akan diacak
        let currentChallenge = '';
        let imageSequence = [];
        const SEQUENCE_LENGTH = 15; // Jumlah frame yang akan dikirim untuk liveness check
        const CHALLENGE_DURATION = 3000; // Durasi setiap challenge dalam ms (3 detik)

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
                stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' } });
                webcam.srcObject = stream;
                cameraStatus.textContent = 'Kamera aktif. Harap bersiap untuk verifikasi wajah.';
                webcam.addEventListener('play', () => {
                    displaySize = { width: webcam.videoWidth, height: webcam.videoHeight };
                    faceapi.matchDimensions(overlayCanvas, displaySize);
                    faceapi.matchDimensions(captureCanvas, displaySize);
                    loginButton.disabled = false; // Aktifkan tombol login setelah kamera aktif
                    // Mulai deteksi wajah setelah video play
                    detectAndAnalyzeFace(); 
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
        let currentChallengeIndex = 0;
        let blinkCount = 0;
        let consecutiveNoBlinkFrames = 0;
        let lastYaw = 0;
        let lastPitch = 0;
        let headMovementDetected = false;
        let challengeInProgress = false;

        async function detectAndAnalyzeFace() {
            if (!webcam.paused && !webcam.ended) {
                const detections = await faceapi.detectSingleFace(webcam, new faceapi.TinyFaceDetectorOptions()).withFaceLandmarks();
                
                if (detections) {
                    // --- Gambar Landmarks (opsional, untuk visual feedback) ---
                    const resizedDetections = faceapi.resizeResults(detections, displaySize);
                    overlayCanvas.getContext('2d').clearRect(0, 0, overlayCanvas.width, overlayCanvas.height);
                    // faceapi.draw.drawDetections(overlayCanvas, resizedDetections); // Kotak wajah
                    faceapi.draw.drawFaceLandmarks(overlayCanvas, resizedDetections); // Titik landmarks
                    // --- End Gambar Landmarks ---

                    // --- Liveness Challenge Control ---
                    if (!challengeInProgress) {
                        currentChallenge = challenges[Math.floor(Math.random() * challenges.length)]; // Pilih challenge acak
                        instructionText.textContent = getChallengeInstruction(currentChallenge);
                        livenessInstruction.classList.remove('hidden');
                        livenessStatus.textContent = '';
                        imageSequence = []; // Reset sequence
                        blinkCount = 0;
                        consecutiveNoBlinkFrames = 0;
                        headMovementDetected = false;
                        challengeInProgress = true;
                        loginButton.disabled = true; // Nonaktifkan tombol saat challenge berlangsung

                        setTimeout(() => {
                            // Setelah durasi challenge, kirim sequence dan cek hasil
                            processLivenessChallenge();
                        }, CHALLENGE_DURATION);
                    } else {
                        // Kumpulkan frame selama challenge berlangsung
                        imageSequence.push(takePicture(webcam, captureCanvas));

                        // --- Analisis Liveness Real-time di Frontend (opsional, bisa juga hanya di backend) ---
                        // Ini hanya contoh, logika sebenarnya akan di backend
                        const eyeLeft = resizedDetections.landmarks.getLeftEye();
                        const eyeRight = resizedDetections.landmarks.getRightEye();

                        const ear = (faceapi.FaceLandmarks.measureEyeAspectRatio(eyeLeft) + faceapi.FaceLandmarks.measureEyeAspectRatio(eyeRight)) / 2;
                        
                        // Sederhana: jika mata tertutup, hitung sebagai kedipan
                        if (currentChallenge === 'blink') {
                            if (ear < 0.22) { // ambang batas EAR yang lebih rendah untuk kedipan
                                blinkCount++;
                                livenessStatus.textContent = `Kedipan terdeteksi: ${blinkCount}`;
                            }
                        }

                        // Untuk gerakan kepala, kita akan mendeteksinya di backend
                        // Frontend hanya mengirimkan sequence dan type challenge
                    }
                } else {
                    livenessInstruction.classList.add('hidden');
                    livenessStatus.textContent = 'Wajah tidak terdeteksi. Harap posisikan wajah Anda di tengah kamera.';
                }
            }
            requestAnimationFrame(detectAndAnalyzeFace); // Loop terus menerus
        }

        function getChallengeInstruction(challenge) {
            switch (challenge) {
                case 'blink': return 'Kedipkan mata Anda.';
                case 'head_yaw': return 'Miringkan kepala Anda ke kiri dan kanan.';
                case 'head_pitch': return 'Anggukkan kepala Anda ke atas dan bawah.';
                default: return 'Lakukan gerakan acak.';
            }
        }

        async function processLivenessChallenge() {
            challengeInProgress = false; // Selesaikan challenge saat ini

            if (imageSequence.length < SEQUENCE_LENGTH) {
                livenessStatus.textContent = 'Gagal: Tidak cukup frame untuk analisis liveness. Coba lagi.';
                loginButton.disabled = true; // Pastikan tombol nonaktif
                setTimeout(() => { resetLivenessChallenge(); }, 2000); // Reset setelah 2 detik
                return;
            }

            // --- Kirim Sequence ke Backend ---
            faceImageSequenceInput.value = JSON.stringify(imageSequence);
            livenessChallengeTypeInput.value = currentChallenge;

            // Submit form secara otomatis (kita tidak perlu tombol lagi, karena ini liveness)
            // Namun, untuk alur yang sama seperti sebelumnya, kita biarkan tombol.
            // Atau kita bisa langsung panggil fungsi submit form PemilihAuthController dari sini.
            
            // Simulasikan submit form setelah challenge selesai
            livenessInstruction.classList.add('hidden');
            livenessStatus.textContent = 'Challenge selesai. Mohon tunggu verifikasi...';
            loginButton.disabled = false; // Aktifkan tombol agar user bisa submit manual setelah challenge selesai
            cameraStatus.textContent = 'Wajah Anda sudah dianalisis. Klik tombol Login & Verifikasi.';

            // Reset liveness challenge jika user tidak segera submit
            setTimeout(() => {
                if (!document.getElementById('pemilihLoginForm').submitted) { // Cek jika form belum disubmit
                    resetLivenessChallenge();
                }
            }, 10000); // Reset setelah 10 detik jika tidak di-submit
        }

        function resetLivenessChallenge() {
            livenessInstruction.classList.add('hidden');
            livenessStatus.textContent = '';
            cameraStatus.textContent = 'Kamera aktif. Harap bersiap untuk verifikasi wajah.';
            loginButton.disabled = false; // Aktifkan tombol
            imageSequence = []; // Bersihkan sequence
            currentChallenge = ''; // Reset challenge type
        }

        // --- Event Listener untuk Submit Form ---
        document.getElementById('pemilihLoginForm').addEventListener('submit', function(event) {
            // Kita sudah mengontrol submit dari processLivenessChallenge, tapi jika user klik manual:
            if (!faceImageSequenceInput.value) { // Jika belum ada sequence
                event.preventDefault(); // Cegah submit
                alert('Mohon selesaikan challenge verifikasi wajah terlebih dahulu.');
                // Mulai ulang challenge jika belum selesai
                if (!challengeInProgress) {
                    resetLivenessChallenge(); // Mulai challenge baru
                    // Paksa user untuk tidak langsung submit
                    loginButton.disabled = true; 
                    instructionText.textContent = getChallengeInstruction(challenges[0]); // Mulai dengan challenge pertama
                    livenessInstruction.classList.remove('hidden');
                    challengeInProgress = true;
                    imageSequence = [];
                    setTimeout(() => { processLivenessChallenge(); }, CHALLENGE_DURATION);
                }
                return;
            }
            this.submitted = true; // Flag form sudah disubmit
            webcam.classList.add('shutter-effect'); // Efek shutter
            setTimeout(() => { webcam.classList.remove('shutter-effect'); }, 150);
            // Form akan disubmit
        });

        // --- Startup ---
        window.addEventListener('load', () => {
            // Pastikan models dimuat dulu baru startCamera()
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