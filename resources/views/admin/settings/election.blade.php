<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Pemilihan - Admin EVoting Desa</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
</head>
<body class="bg-gray-100 font-sans leading-normal tracking-normal">

    <nav class="bg-blue-600 p-4 text-white shadow-md">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-xl font-bold">EVoting Desa Admin Panel</h1>
            <div class="flex items-center space-x-4">
                <span class="text-white text-sm">Selamat Datang, {{ Auth::guard('web_admin')->user()->nama_lengkap ?? Auth::guard('web_admin')->user()->username }}!</span>
                <a href="{{ route('admin.dashboard') }}" class="bg-blue-700 hover:bg-blue-800 text-white font-semibold py-2 px-4 rounded-lg focus:outline-none focus:shadow-outline transition duration-150 ease-in-out">
                    Dashboard
                </a>
                <a href="{{ route('admin.pemilih.index') }}" class="bg-blue-700 hover:bg-blue-800 text-white font-semibold py-2 px-4 rounded-lg focus:outline-none focus:shadow-outline transition duration-150 ease-in-out">
                    Manajemen Pemilih
                </a>
                <a href="{{ route('admin.kandidat.index') }}" class="bg-blue-700 hover:bg-blue-800 text-white font-semibold py-2 px-4 rounded-lg focus:outline-none focus:shadow-outline transition duration-150 ease-in-out">
                    Manajemen Kandidat
                </a>
                <form action="{{ route('admin.logout') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="bg-red-500 hover:bg-red-600 text-white font-semibold py-2 px-4 rounded-lg focus:outline-none focus:shadow-outline transition duration-150 ease-in-out">
                        Logout
                    </button>
                </form>
            </div>
        </div>
    </nav>

    <div class="container mx-auto mt-8 p-4">
        <h2 class="text-3xl font-bold text-gray-800 mb-6">Pengaturan Pemilihan</h2>

        @if (session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <strong class="font-bold">Berhasil!</strong>
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative mb-6" role="alert">
                <strong class="font-bold">Perhatian!</strong>
                <span class="block sm:inline">Ada masalah dengan input Anda.</span>
                <ul class="mt-2 list-disc list-inside text-sm">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="bg-white p-8 rounded-xl shadow-2xl mx-auto max-w-2xl">
            <form action="{{ route('admin.settings.election.update') }}" method="POST">
                @csrf @method('PUT') <div class="mb-4">
                    <label for="name" class="block text-gray-700 text-sm font-bold mb-2">Nama Pemilihan:</label>
                    <input type="text" id="name" name="name"
                           class="shadow appearance-none border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 @error('name') border-red-500 @enderror"
                           value="{{ old('name', $setting->name) }}" required>
                    @error('name')
                        <p class="text-red-500 text-xs italic mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label class="inline-flex items-center">
                        <input type="checkbox" name="is_active" class="form-checkbox h-5 w-5 text-blue-600" value="1" {{ old('is_active', $setting->is_active) ? 'checked' : '' }}>
                        <span class="ml-2 text-gray-700 text-sm font-bold">Aktifkan Pemilihan</span>
                    </label>
                    <p class="text-gray-600 text-xs italic mt-1">Centang untuk mengaktifkan proses pemilihan bagi pemilih.</p>
                </div>

                <div class="mb-4">
                    <label for="start_time" class="block text-gray-700 text-sm font-bold mb-2">Waktu Mulai Pemilihan (Opsional):</label>
                    <input type="text" id="start_time" name="start_time"
                           class="shadow appearance-none border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 @error('start_time') border-red-500 @enderror"
                           value="{{ old('start_time', $setting->start_time ? $setting->start_time->format('Y-m-d H:i') : '') }}"
                           placeholder="YYYY-MM-DD HH:MM">
                    @error('start_time')
                        <p class="text-red-500 text-xs italic mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-6">
                    <label for="end_time" class="block text-gray-700 text-sm font-bold mb-2">Waktu Berakhir Pemilihan (Opsional):</label>
                    <input type="text" id="end_time" name="end_time"
                           class="shadow appearance-none border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 @error('end_time') border-red-500 @enderror"
                           value="{{ old('end_time', $setting->end_time ? $setting->end_time->format('Y-m-d H:i') : '') }}"
                           placeholder="YYYY-MM-DD HH:MM">
                    @error('end_time')
                        <p class="text-red-500 text-xs italic mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-start">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg focus:outline-none focus:shadow-outline transition duration-150 ease-in-out">
                        Simpan Pengaturan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        // Inisialisasi Flatpickr untuk input tanggal dan waktu
        flatpickr("#start_time", {
            enableTime: true,
            dateFormat: "Y-m-d H:i",
            allowInput: true // Memungkinkan input manual
        });
        flatpickr("#end_time", {
            enableTime: true,
            dateFormat: "Y-m-d H:i",
            allowInput: true
        });
    </script>
</body>
</html>