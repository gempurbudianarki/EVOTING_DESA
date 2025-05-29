<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Pemilih - EVoting Desa</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans leading-normal tracking-normal">

    <nav class="bg-blue-500 p-4 text-white shadow-md">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-xl font-bold">EVoting Desa</h1>
            <div class="flex items-center space-x-4">
                @auth('web_pemilih')
                    <span class="text-white text-sm">Selamat Datang, {{ Auth::guard('web_pemilih')->user()->nama_lengkap }} (NIK: {{ Auth::guard('web_pemilih')->user()->nik }})!</span>
                @endauth
                
                <form action="{{ route('pemilih.logout') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="bg-red-500 hover:bg-red-600 text-white font-semibold py-2 px-4 rounded-lg focus:outline-none focus:shadow-outline transition duration-150 ease-in-out">
                        Logout
                    </button>
                </form>
            </div>
        </div>
    </nav>

    <div class="container mx-auto mt-8 p-4">
        <h2 class="text-3xl font-bold text-gray-800 mb-6">Area Pemilihan</h2>
        
        @if (session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <strong class="font-bold">Berhasil!</strong>
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
        @endif
        @if (session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <strong class="font-bold">Perhatian!</strong>
                <span class="block sm:inline">{{ session('error') }}</span>
            </div>
        @endif
        @if (session('info'))
            <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded relative mb-4" role="alert">
                <strong class="font-bold">Info!</strong>
                <span class="block sm:inline">{{ session('info') }}</span>
            </div>
        @endif


        <div class="bg-white p-6 rounded-lg shadow-lg mb-6">
            <h3 class="text-xl font-semibold text-gray-700 mb-4">Status Pemilihan Anda</h3>
            @auth('web_pemilih')
                @if (Auth::guard('web_pemilih')->user()->sudah_memilih)
                    <p class="text-green-600 font-bold text-lg">Anda SUDAH memilih dalam pemilihan ini.</p>
                    <p class="text-gray-600">Terima kasih atas partisipasi Anda.</p>
                @else
                    <p class="text-orange-600 font-bold text-lg">Anda BELUM memilih dalam pemilihan ini.</p>
                    <p class="text-gray-600">Ayo gunakan hak pilih Anda sekarang!</p>
                    {{-- PERHATIAN: GANTI BARIS DI BAWAH INI --}}
                    <a href="{{ route('pemilih.vote.form') }}" class="mt-4 inline-block bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-full transition duration-150 ease-in-out">
                        Mulai Memilih Sekarang &raquo;
                    </a>
                    {{-- END OF PERHATIAN --}}
                @endif
            @endauth
        </div>

        <div class="bg-white p-6 rounded-lg shadow-lg">
            <h3 class="text-xl font-semibold text-gray-700 mb-4">Informasi Pemilihan</h3>
            <p class="text-gray-600">Detail tentang kandidat, jadwal pemilihan, dan informasi lainnya akan tampil di sini.</p>
            <p class="text-gray-500 mt-2 text-sm">Fitur ini akan dikembangkan selanjutnya.</p>
        </div>
    </div>

</body>
</html>