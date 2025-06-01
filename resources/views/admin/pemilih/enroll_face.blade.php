@extends('admin.layouts.app')

@section('title', 'Daftarkan Wajah Pemilih')

@section('styles')
    <style>
        .video-container {
            position: relative;
            width: 100%;
            padding-top: 75%; /* 4:3 Aspect Ratio */
            overflow: hidden;
            background-color: #000;
            border-radius: 0.5rem;
        }
        .video-container video {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            transform: scaleX(-1); /* Mirror video feed for selfie-like experience */
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
        #overlayCanvas {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            /* Mirroring CSS dihapus dari sini, akan dilakukan di JS canvas context */
        }
    </style>
    {{-- Face-API.js dimuat di sini karena ini adalah @section('styles') --}}
    <script defer src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
@endsection

@section('content')
    <h2 class="text-3xl font-bold text-gray-800 mb-6">Daftarkan Wajah Pemilih</h2>

    <div class="w-full max-w-lg bg-white p-8 rounded-xl shadow-2xl mx-auto">
        <p class="text-gray-700 mb-6 text-center">
            Arahkan kamera ke wajah pemilih untuk mendaftarkan data wajah mereka.
            <br>Anda akan diminta melakukan gerakan untuk verifikasi keaktifan.
        </p>

        @if ($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative mb-6" role="alert">
                <strong class="font-bold">Perhatian!</strong>
                <span class="block sm:inline">Ada masalah saat mendaftarkan wajah.</span>
                <ul class="mt-2 list-disc list-inside text-sm">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form id="enrollFaceForm" action="{{ route('admin.pemilih.enroll.submit') }}" method="POST">
            @csrf
            <div class="mb-6">
                <label for="nik" class="block text-gray-700 text-base font-semibold mb-2">Nomor Induk Kependudukan (NIK):</label>
                <input type="text" id="nik" name="nik"
                       class="shadow appearance-none border rounded-lg w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 @error('nik') border-red-500 @enderror"
                       value="{{ old('nik', $nik) }}" placeholder="Contoh: 33xxxxxxxxxxxxxx" maxlength="16" required autofocus>
                @error('nik')
                    <p class="text-red-500 text-xs italic mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-6 text-center">
                <label class="block text-gray-700 text-base font-semibold mb-3">Tampilan Kamera:</label>
                <div class="video-container mx-auto max-w-sm border-2 border-gray-300">
                    <video id="webcam" autoplay muted playsinline></video>
                    <canvas id="overlayCanvas"></canvas>
                </div>
                <canvas id="captureCanvas" class="hidden"></canvas>
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
                <button type="button" id="enrollButton" class="bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-6 rounded-full focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition duration-300 ease-in-out" disabled>
                    Daftarkan Wajah
                </button>
            </div>
        </form>
    </div>
@endsection

@section('scripts')
<script>
const webcam = document.getElementById('webcam');
const overlayCanvas = document.getElementById('overlayCanvas');
const captureCanvas = document.getElementById('captureCanvas');
const faceImageSequenceInput = document.getElementById('faceImageSequenceInput');
const livenessChallengeTypeInput = document.getElementById('livenessChallengeTypeInput');
const cameraStatus = document.getElementById('cameraStatus');
const livenessInstruction = document.getElementById('livenessInstruction');
const instructionText = document.getElementById('instructionText');
const livenessStatus = document.getElementById('livenessStatus');
const enrollButton = document.getElementById('enrollButton');

let stream;
let displaySize; 
let currentChallengeIndex = 0;
let challengeInProgress = false;
const CHALLENGES = ['blink', 'head_yaw', 'head_pitch'];
const CHALLENGE_DURATION = 3000;
const SEQUENCE_INTERVAL = 100; // milliseconds
let imageSequence = [];
let captureIntervalId;
let detectLandmarksFrameId; // Untuk loop continuous drawing (requestAnimationFrame ID)

// Memuat model face-api.js dan memulai kamera setelah halaman dimuat sepenuhnya
window.addEventListener('load', async () => {
    try {
        await Promise.all([
            faceapi.nets.tinyFaceDetector.loadFromUri('/models'),
            faceapi.nets.faceLandmark68Net.loadFromUri('/models') // Ini adalah model 68 landmarks
        ]);
        console.log("Face-API models loaded successfully.");
        startCamera();
    } catch (err) {
        console.error("Error loading face-api models:", err);
        cameraStatus.textContent = 'Gagal memuat model verifikasi wajah. Mohon coba lagi.';
        enrollButton.disabled = true;
    }
});

async function startCamera() {
    try {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            cameraStatus.textContent = 'Browser tidak mendukung kamera.';
            enrollButton.disabled = true;
            return;
        }
        stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' } });
        webcam.srcObject = stream;
        cameraStatus.textContent = 'Kamera aktif. Silakan masukkan NIK dan klik "Daftarkan Wajah".';
        webcam.addEventListener('play', () => {
            // Setelah video mulai bermain, hitung displaySize berdasarkan dimensi rendering video
            displaySize = {
                width: webcam.offsetWidth,
                height: webcam.offsetHeight
            };
            
            // Atur dimensi asli canvas agar sesuai dengan ukuran rendering
            overlayCanvas.width = displaySize.width;
            overlayCanvas.height = displaySize.height;

            // Capture canvas tetap pakai native videoWidth/Height karena ini untuk data aktual yang dikirim ke backend
            captureCanvas.width = webcam.videoWidth; 
            captureCanvas.height = webcam.videoHeight;

            // Penting: Atur transformasi untuk mirroring pada context canvas HANYA SEKALI
            // Ini akan memastikan gambar landmarks cocok dengan video yang di-mirror
            const context = overlayCanvas.getContext('2d');
            context.translate(overlayCanvas.width, 0); // Pindahkan origin ke kanan
            context.scale(-1, 1); // Lalu mirror secara horizontal

            enrollButton.disabled = false; // Aktifkan tombol Daftar setelah kamera siap

            // Mulai loop deteksi dan gambar landmarks secara terus menerus
            detectAndDrawLandmarksContinuous(); // Panggil pertama kali untuk memulai loop requestAnimationFrame
        });
    } catch (err) {
        console.error("Error accessing camera:", err);
        cameraStatus.textContent = 'Gagal mengakses kamera. Mohon izinkan akses kamera Anda.';
        enrollButton.disabled = true;
    }
}

function takePicture(videoElement, canvasElement) {
    const context = canvasElement.getContext('2d');
    context.drawImage(videoElement, 0, 0, canvasElement.width, canvasElement.height);
    return canvasElement.toDataURL('image/jpeg', 0.9);
}

// Helper function to draw a contour from a subset of landmark points
// This function takes an array of {x, y} points and draws lines between them.
function drawContourFromPoints(context, points, drawOptions, isClosed = false) {
    if (!points || points.length < 2) return; // Need at least 2 points for a line

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


// Fungsi ini untuk menggambar landmarks secara terus-menerus menggunakan requestAnimationFrame
async function detectAndDrawLandmarksContinuous() {
    if (!webcam.paused && !webcam.ended) {
        const detections = await faceapi.detectSingleFace(
            webcam, 
            new faceapi.TinyFaceDetectorOptions({ inputSize: 416, scoreThreshold: 0.5 }) // Optimalisasi: inputSize dan scoreThreshold
        ).withFaceLandmarks(); 
        
        const context = overlayCanvas.getContext('2d');
        
        // --- Penting: Reset transformasi sebelum clearRect, lalu terapkan kembali ---
        context.setTransform(1, 0, 0, 1, 0, 0); // Reset transform ke default (un-mirrored)
        context.clearRect(0, 0, overlayCanvas.width, overlayCanvas.height); // Clear seluruh canvas (dalam kondisi un-mirrored)
        context.translate(overlayCanvas.width, 0); // Pindahkan origin ke kanan (untuk mirroring)
        context.scale(-1, 1); // Lalu mirror secara horizontal

        if (detections) {
            // console.log("Face detected. Score:", detections.detection.score); // Tidak perlu lagi log ini setiap frame
            
            if (detections.landmarks && detections.landmarks.positions && detections.landmarks.positions.length === 68) { 
                // console.log("Landmarks detected and are valid (68 points). Drawing now."); 
                const resizedDetections = faceapi.resizeResults(detections, displaySize); 
                const landmarksPositions = resizedDetections.landmarks.positions; // Ambil array posisi langsung

                // Menggambar detections (kotak wajah)
                faceapi.draw.drawDetections(overlayCanvas, resizedDetections);
                
                // --- Menggambar kontur wajah secara lebih detail (seperti yang Anda inginkan) ---
                const drawOptions = {
                    lineWidth: 2, 
                    color: 'lime' 
                };

                // Garis Rahang (Jaw Outline) - points 0-16
                drawContourFromPoints(context, landmarksPositions.slice(0, 17), drawOptions, false); 
                
                // Alis Kiri (Left Eyebrow) - points 17-21
                drawContourFromPoints(context, landmarksPositions.slice(17, 22), drawOptions, false); 
                // Alis Kanan (Right Eyebrow) - points 22-26
                drawContourFromPoints(context, landmarksPositions.slice(22, 27), drawOptions, false); 

                // Hidung (Nose) - points 27-35
                drawContourFromPoints(context, landmarksPositions.slice(27, 36), drawOptions, false);
                
                // Mata Kiri (Left Eye) - points 36-41
                drawContourFromPoints(context, landmarksPositions.slice(36, 42), drawOptions, true); 
                // Mata Kanan (Right Eye) - points 42-47
                drawContourFromPoints(context, landmarksPositions.slice(42, 48), drawOptions, true); 

                // Mulut (Mouth) - points 48-67
                drawContourFromPoints(context, landmarksPositions.slice(48, 68), drawOptions, true); 
                
                // Opsional: Menggambar titik-titik (dots) untuk semua landmarks
                context.fillStyle = 'magenta'; // Warna titik
                for (let i = 0; i < landmarksPositions.length; i++) {
                    context.beginPath();
                    context.arc(landmarksPositions[i].x, landmarksPositions[i].y, 2, 0, 2 * Math.PI); // Radius 2
                    context.fill();
                }

                if (!challengeInProgress) {
                     cameraStatus.textContent = 'Wajah terdeteksi. Siap untuk pendaftaran wajah.';
                }

            } else {
                // console.warn("Face detected, but landmarks are missing or invalid in this frame. Detections object:", detections); 
                faceapi.draw.drawDetections(overlayCanvas, resizedDetections); // Tetap gambar bounding box jika landmarks tidak ada
                if (!challengeInProgress) {
                    cameraStatus.textContent = 'Wajah terdeteksi, tapi landmarks belum jelas. Harap posisikan wajah Anda lebih baik.';
                }
            }
        } else {
            // console.log("No face detected in this frame for landmark drawing."); 
            if (!challengeInProgress) {
                cameraStatus.textContent = 'Wajah tidak terdeteksi. Harap posisikan wajah Anda di tengah kamera.';
            }
        }
        
        detectLandmarksFrameId = requestAnimationFrame(detectAndDrawLandmarksContinuous);
    } else {
        cancelAnimationFrame(detectLandmarksFrameId);
    }
}


// --- Mulai siklus tantangan liveness saat tombol ditekan ---
enrollButton.addEventListener('click', () => {
    // Validasi NIK sebelum memulai liveness
    const nikInput = document.getElementById('nik');
    if (!nikInput.value || nikInput.value.length !== 16) {
        alert('Mohon masukkan NIK yang valid (16 digit) terlebih dahulu.');
        nikInput.focus();
        return;
    }

    if (challengeInProgress) { // Hindari memulai ulang jika sudah berjalan
        return;
    }

    currentChallengeIndex = 0; // Mulai dari tantangan pertama (blink)
    imageSequence = []; // Bersihkan sequence lama
    livenessStatus.textContent = ''; // Hapus status lama
    enrollButton.disabled = true; // Nonaktifkan tombol saat liveness dimulai
    
    // Hentikan loop continuous landmark drawing saat liveness challenge dimulai
    cancelAnimationFrame(detectLandmarksFrameId);

    startLivenessChallengeCycle(); // Mulai siklus tantangan
});


function startLivenessChallengeCycle() {
    if (currentChallengeIndex < CHALLENGES.length) {
        currentChallenge = CHALLENGES[currentChallengeIndex];
        instructionText.textContent = getChallengeInstruction(currentChallenge);
        livenessInstruction.classList.remove('hidden');
        livenessStatus.textContent = 'Bersiap...';
        imageSequence = [];
        challengeInProgress = true;
        
        captureIntervalId = setInterval(() => {
            if (imageSequence.length < (CHALLENGE_DURATION / SEQUENCE_INTERVAL) * 1.5) {
                imageSequence.push(takePicture(webcam, captureCanvas));
            }
        }, SEQUENCE_INTERVAL);

        setTimeout(() => {
            if (challengeInProgress) livenessStatus.textContent = 'Lakukan sekarang!';
        }, 500);

        setTimeout(async () => {
            clearInterval(captureIntervalId);
            if (challengeInProgress) await processLivenessChallenge(currentChallenge);
        }, CHALLENGE_DURATION + 500);
    } else {
        // Semua tantangan selesai berhasil
        livenessInstruction.classList.add('hidden');
        livenessStatus.textContent = 'Verifikasi liveness selesai. Mengirimkan data untuk pendaftaran...';
        
        // --- Otomatis submit form setelah liveness sukses ---
        faceImageSequenceInput.value = JSON.stringify(imageSequence);
        livenessChallengeTypeInput.value = 'all_passed';
        document.getElementById('enrollFaceForm').submit(); // Langsung submit form
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
    challengeInProgress = false;
    livenessInstruction.classList.add('hidden');
    livenessStatus.textContent = 'Menganalisis liveness...';
    
    if (imageSequence.length === 0) {
        livenessStatus.textContent = 'Gagal: Tidak ada frame yang ditangkap. Coba lagi.';
        setTimeout(resetLivenessProcess, 2000);
        return;
    }

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const response = await fetch("{{ route('admin.pemilih.liveness.check') }}", {
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
    }
    catch (error) {
        console.error("Error during liveness check AJAX:", error);
        livenessStatus.textContent = 'Terjadi kesalahan saat memeriksa liveness. Coba lagi.';
        setTimeout(resetLivenessProcess, 2000);
    }
}

function resetLivenessProcess() {
    currentChallengeIndex = 0; // Reset ke tantangan pertama
    livenessInstruction.classList.add('hidden');
    livenessStatus.textContent = '';
    cameraStatus.textContent = 'Kamera aktif. Silakan masukkan NIK dan klik "Daftarkan Wajah".';
    enrollButton.disabled = false; // Aktifkan kembali tombol Daftar
    imageSequence = []; // Bersihkan sequence
    challengeInProgress = false; // Reset status challenge
    
    // Mulai lagi loop deteksi landmarks setelah reset
    cancelAnimationFrame(detectLandmarksFrameId); // Pastikan animation frame lama dihentikan
    detectAndDrawLandmarksContinuous(); // Panggil untuk memulai loop continuous drawing
}

// --- Cleanup saat halaman ditutup ---
window.addEventListener('beforeunload', () => {
    if (stream) {
        stream.getTracks().forEach(track => track.stop());
    }
    cancelAnimationFrame(detectLandmarksFrameId); // Pastikan animation frame dihentikan
    if (captureIntervalId) {
        clearInterval(captureIntervalId);
    }
});
</script>
@endsection