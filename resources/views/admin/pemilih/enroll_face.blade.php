@extends('admin.layouts.app')

@section('title', 'Daftarkan Wajah Pemilih')

@section('styles')
    <style>
        .video-container {
            position: relative;
            width: 100%;
            padding-top: 75%;
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
            transform: scaleX(-1);
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
        }
    </style>
    <script defer src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
@endsection

@section('content')
    <h2 class="text-3xl font-bold text-gray-800 mb-6">Daftarkan Wajah Pemilih</h2>

    <div class="w-full max-w-lg bg-white p-8 rounded-xl shadow-2xl mx-auto">
        <p class="text-gray-700 mb-6 text-center">
            Arahkan kamera ke wajah pemilih untuk mendaftarkan data wajah mereka.
            <br>Anda akan diminta melakukan gerakan untuk verifikasi liveness.
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
                <button type="submit" id="enrollButton" class="bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-6 rounded-full focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition duration-300 ease-in-out" disabled>
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
const SEQUENCE_INTERVAL = 100;
let imageSequence = [];
let captureIntervalId;

Promise.all([
    faceapi.nets.tinyFaceDetector.loadFromUri('/models'),
    faceapi.nets.faceLandmark68Net.loadFromUri('/models')
]).then(startCamera).catch(err => {
    console.error("Error loading face-api models:", err);
    cameraStatus.textContent = 'Gagal memuat model verifikasi wajah. Mohon coba lagi.';
    enrollButton.disabled = true;
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
        cameraStatus.textContent = 'Kamera aktif. Harap bersiap untuk verifikasi wajah.';
        webcam.addEventListener('play', () => {
            displaySize = {
                width: webcam.videoWidth || 640,
                height: webcam.videoHeight || 480
            };
            faceapi.matchDimensions(overlayCanvas, displaySize);
            faceapi.matchDimensions(captureCanvas, displaySize);
            enrollButton.disabled = true;
            startLivenessChallengeCycle();
        });
    } catch (err) {
        console.error("Error accessing camera:", err);
        cameraStatus.textContent = 'Gagal mengakses kamera. Mohon izinkan akses kamera Anda.';
        enrollButton.disabled = true;
    }
}

function takePicture(videoElement, canvasElement) {
    const context = canvasElement.getContext('2d');
    canvasElement.width = videoElement.videoWidth;
    canvasElement.height = videoElement.videoHeight;
    context.drawImage(videoElement, 0, 0, canvasElement.width, canvasElement.height);
    return canvasElement.toDataURL('image/jpeg', 0.9);
}

function startLivenessChallengeCycle() {
    if (currentChallengeIndex < CHALLENGES.length) {
        const currentChallenge = CHALLENGES[currentChallengeIndex];
        instructionText.textContent = getChallengeInstruction(currentChallenge);
        livenessInstruction.classList.remove('hidden');
        livenessStatus.textContent = 'Bersiap...';
        imageSequence = [];
        challengeInProgress = true;
        enrollButton.disabled = true;

        captureIntervalId = setInterval(() => {
            if (imageSequence.length < (CHALLENGE_DURATION / SEQUENCE_INTERVAL) * 1.5) {
                imageSequence.push(takePicture(webcam, captureCanvas));
            }
        }, SEQUENCE_INTERVAL);

        setTimeout(() => {
            if (challengeInProgress) livenessStatus.textContent = 'Lakukan sekarang!';
            detectAndDrawLandmarks();
        }, 500);

        setTimeout(() => {
            clearInterval(captureIntervalId);
            if (challengeInProgress) processLivenessChallenge(currentChallenge);
        }, CHALLENGE_DURATION + 500);
    } else {
        livenessInstruction.classList.add('hidden');
        livenessStatus.textContent = 'Verifikasi liveness selesai. Anda bisa mendaftarkan wajah.';
        enrollButton.disabled = false;
    }
}

async function detectAndDrawLandmarks() {
    if (!webcam.paused && !webcam.ended && challengeInProgress) {
        const detections = await faceapi.detectSingleFace(webcam, new faceapi.TinyFaceDetectorOptions()).withFaceLandmarks();
        overlayCanvas.getContext('2d').clearRect(0, 0, overlayCanvas.width, overlayCanvas.height);
        if (detections) {
            const resizedDetections = faceapi.resizeResults(detections, displaySize);
            faceapi.draw.drawDetections(overlayCanvas, resizedDetections);
            faceapi.draw.drawFaceLandmarks(overlayCanvas, resizedDetections);
        }
        requestAnimationFrame(detectAndDrawLandmarks);
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

async function processLivenessChallenge(currentChallenge) {
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
                challenge_type: currentChallenge
            })
        });
        const result = await response.json();
        if (response.ok && result.status === 'success' && result.is_live === 'true') {
            livenessStatus.textContent = `Liveness Check Lulus: ${result.message}`;
            currentChallengeIndex++;
            setTimeout(startLivenessChallengeCycle, 1000);
        } else {
            livenessStatus.textContent = `Liveness Check Gagal: ${result.message || 'Coba lagi.'}`;
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
    enrollButton.disabled = true;
    imageSequence = [];
    challengeInProgress = false;
    setTimeout(startLivenessChallengeCycle, 1000);
}

document.getElementById('enrollFaceForm').addEventListener('submit', function(event) {
    event.preventDefault();
    if (enrollButton.disabled) {
        alert('Mohon selesaikan semua tantangan liveness terlebih dahulu.');
        return;
    }
    webcam.classList.add('shutter-effect');
    setTimeout(() => {
        webcam.classList.remove('shutter-effect');
    }, 150);
    this.submit();
});

window.addEventListener('beforeunload', () => {
    if (stream) {
        stream.getTracks().forEach(track => track.stop());
    }
});
</script>
@endsection
