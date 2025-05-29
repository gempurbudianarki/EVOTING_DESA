<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pilih Kandidat - EVoting Desa</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        /* Pastikan label mengisi area penuh untuk klik yang mudah */
        label.block {
            display: block;
            height: 100%; /* Penting agar label mengisi tinggi grid item */
        }
        /* Style untuk radio button tersembunyi yang terpilih */
        /* ini adalah styling tambahan agar ring biru lebih tebal dan jelas */
        input[type="radio"].peer:checked + div {
            border-color: #2563EB; /* Biru yang lebih gelap dari blue-500, mendekati blue-600 */
            box-shadow: 0 0 0 6px #3B82F6; /* Ring lebih tebal (6px) dengan warna blue-500 */
            transform: scale(1.02); /* Sedikit membesar saat dipilih */
        }
        /* Style untuk foto kandidat yang terpilih */
        input[type="radio"].peer:checked + div img {
            border-color: #2563EB; /* Border foto juga ikut berubah warna */
        }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-400 to-indigo-600 min-h-screen font-sans leading-normal tracking-normal p-4">

    <nav class="bg-blue-500 p-4 text-white shadow-md mb-8">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-xl font-bold">EVoting Desa - Pilih Kandidat</h1>
            <div class="flex items-center space-x-4">
                <span class="text-white text-sm">Selamat Datang, {{ Auth::guard('web_pemilih')->user()->nama_lengkap }}!</span>
                <a href="{{ route('pemilih.dashboard') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg focus:outline-none focus:shadow-outline transition duration-150 ease-in-out">
                    Dashboard
                </a>
                <form action="{{ route('pemilih.logout') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="bg-red-500 hover:bg-red-600 text-white font-semibold py-2 px-4 rounded-lg focus:outline-none focus:shadow-outline transition duration-150 ease-in-out">
                        Logout
                    </button>
                </form>
            </div>
        </div>
    </nav>

    <div class="container mx-auto">
        <h2 class="text-4xl font-extrabold text-white text-center mb-10">Pilih Calon Kepala Desa Anda</h2>

        @if ($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative mb-6" role="alert">
                <strong class="font-bold">Perhatian!</strong>
                <span class="block sm:inline">Mohon perbaiki kesalahan berikut:</span>
                <ul class="mt-2 list-disc list-inside text-sm">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="bg-white p-6 rounded-xl shadow-lg mb-8 text-center">
            <h3 class="text-2xl font-semibold text-gray-800 mb-2">Informasi Pemilihan</h3>
            <p class="text-lg text-gray-700 mb-2">{{ $electionSetting->name }}</p>
            @if($electionSetting->start_time && $electionSetting->end_time)
                <p class="text-gray-600 text-sm">
                    Periode: {{ $electionSetting->start_time->format('d F Y H:i') }} - {{ $electionSetting->end_time->format('d F Y H:i') }} WIB
                </p>
            @else
                <p class="text-gray-600 text-sm">Jadwal pemilihan belum ditetapkan.</p>
            @endif
            @if ($electionSetting->is_active)
                <span class="inline-block bg-green-200 text-green-800 text-sm font-bold px-3 py-1 rounded-full mt-3">Pemilihan Sedang Aktif</span>
            @else
                <span class="inline-block bg-red-200 text-red-800 text-sm font-bold px-3 py-1 rounded-full mt-3">Pemilihan Tidak Aktif</span>
            @endif
        </div>

        <form action="{{ route('pemilih.vote.submit') }}" method="POST" id="voteForm"> {{-- Tambahkan ID "voteForm" --}}
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                @forelse ($kandidat as $k)
                    <label class="block cursor-pointer"> 
                        <input type="radio" name="kandidat_id" value="{{ $k->id }}" class="hidden peer" required>
                        <div class="bg-white rounded-xl shadow-lg p-6 text-center transition-all duration-300 ease-in-out 
                                    peer-checked:ring-4 peer-checked:ring-blue-500 peer-checked:border-blue-500 
                                    hover:shadow-xl hover:scale-105">
                            
                            <div class="text-4xl font-bold text-blue-600 mb-3">{{ $k->nomor_urut }}</div>
                            @if ($k->foto_path)
                                <img src="{{ asset($k->foto_path) }}" alt="Foto {{ $k->nama_lengkap }}" 
                                     class="w-32 h-32 object-cover rounded-full mx-auto mb-4 border-2 border-gray-300 peer-checked:border-blue-500">
                            @else
                                <div class="w-32 h-32 flex items-center justify-center bg-gray-200 rounded-full mx-auto mb-4 text-gray-500 text-sm border-2 border-gray-300 peer-checked:border-blue-500">
                                    No Foto
                                </div>
                            @endif
                            <h3 class="text-xl font-semibold text-gray-800 mb-2">{{ $k->nama_lengkap }}</h3>
                            <p class="text-sm text-gray-600 mb-1">
                                {{ $k->jabatan_lama ? $k->jabatan_lama : 'Belum Ada Jabatan Lama' }}
                            </p>
                            <div class="mt-4 text-left text-gray-700 text-sm">
                                <h4 class="font-bold">Visi:</h4>
                                <p class="mb-2">{{ $k->visi }}</p>
                                <h4 class="font-bold">Misi:</h4>
                                <p>{{ $k->misi }}</p>
                            </div>
                        </div>
                    </label>
                @empty
                    <div class="col-span-full bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded-lg relative text-center">
                        <strong class="font-bold">Info!</strong>
                        <span class="block sm:inline">Belum ada kandidat yang terdaftar untuk pemilihan ini.</span>
                    </div>
                @endforelse
            </div>

            @if($kandidat->count() > 0)
                <div class="flex justify-center mt-10">
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-4 px-10 rounded-full text-xl 
                                               focus:outline-none focus:ring-4 focus:ring-green-500 focus:ring-offset-2 
                                               transition duration-300 ease-in-out shadow-lg">
                        Berikan Suara Saya
                    </button>
                </div>
            @endif
        </form>
    </div>

    {{-- Script JavaScript untuk konfirmasi --}}
    <script>
        document.getElementById('voteForm').addEventListener('submit', function(event) {
            // Mencegah submit default untuk menampilkan konfirmasi kustom
            event.preventDefault(); 

            // Dapatkan radio button yang dipilih
            const selectedRadio = document.querySelector('input[name="kandidat_id"]:checked');

            if (!selectedRadio) {
                alert('Mohon pilih salah satu kandidat terlebih dahulu.');
                return false; // Mencegah submit jika belum ada yang dipilih
            }

            // Dapatkan elemen kartu yang terkait dengan radio button yang dipilih
            // selectedRadio.nextElementSibling adalah div kartu
            const candidateCard = selectedRadio.nextElementSibling;
            
            // Dapatkan nama kandidat dari elemen <h3> di dalam kartu
            const candidateNameElement = candidateCard.querySelector('h3');
            let candidateName = 'Kandidat Tidak Dikenal'; // Fallback
            if (candidateNameElement) {
                candidateName = candidateNameElement.textContent.trim();
            }

            // Tampilkan konfirmasi dengan nama kandidat
            const confirmationMessage = `Anda yakin ingin memilih ${candidateName}?\nSuara yang sudah diberikan tidak dapat diubah.`;

            if (confirm(confirmationMessage)) {
                // Jika user mengklik OK, submit form
                this.submit();
            } else {
                // Jika user mengklik Cancel, jangan submit
                return false;
            }
        });
    </script>
</body>
</html>