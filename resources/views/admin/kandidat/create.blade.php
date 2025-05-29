<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Kandidat - Admin EVoting Desa</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
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
        <h2 class="text-3xl font-bold text-gray-800 mb-6">Tambah Kandidat Baru</h2>

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

        <div class="w-full max-w-2xl bg-white p-8 rounded-xl shadow-2xl mx-auto">
            <form action="{{ route('admin.kandidat.store') }}" method="POST" enctype="multipart/form-data">
                @csrf <div class="mb-4">
                    <label for="nomor_urut" class="block text-gray-700 text-sm font-bold mb-2">Nomor Urut:</label>
                    <input type="number" id="nomor_urut" name="nomor_urut" min="1"
                           class="shadow appearance-none border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 @error('nomor_urut') border-red-500 @enderror"
                           value="{{ old('nomor_urut') }}" required>
                    @error('nomor_urut')
                        <p class="text-red-500 text-xs italic mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="nama_lengkap" class="block text-gray-700 text-sm font-bold mb-2">Nama Lengkap:</label>
                    <input type="text" id="nama_lengkap" name="nama_lengkap"
                           class="shadow appearance-none border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 @error('nama_lengkap') border-red-500 @enderror"
                           value="{{ old('nama_lengkap') }}" required>
                    @error('nama_lengkap')
                        <p class="text-red-500 text-xs italic mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="jabatan_lama" class="block text-gray-700 text-sm font-bold mb-2">Jabatan/Profesi Sebelumnya (Opsional):</label>
                    <input type="text" id="jabatan_lama" name="jabatan_lama"
                           class="shadow appearance-none border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 @error('jabatan_lama') border-red-500 @enderror"
                           value="{{ old('jabatan_lama') }}">
                    @error('jabatan_lama')
                        <p class="text-red-500 text-xs italic mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="visi" class="block text-gray-700 text-sm font-bold mb-2">Visi:</label>
                    <textarea id="visi" name="visi" rows="4"
                              class="shadow appearance-none border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 @error('visi') border-red-500 @enderror"
                              required>{{ old('visi') }}</textarea>
                    @error('visi')
                        <p class="text-red-500 text-xs italic mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="misi" class="block text-gray-700 text-sm font-bold mb-2">Misi:</label>
                    <textarea id="misi" name="misi" rows="4"
                              class="shadow appearance-none border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 @error('misi') border-red-500 @enderror"
                              required>{{ old('misi') }}</textarea>
                    @error('misi')
                        <p class="text-red-500 text-xs italic mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-6">
                    <label for="foto" class="block text-gray-700 text-sm font-bold mb-2">Foto Kandidat:</label>
                    <input type="file" id="foto" name="foto" accept="image/*"
                           class="shadow appearance-none border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 @error('foto') border-red-500 @enderror">
                    <p class="text-gray-600 text-xs italic mt-1">Ukuran maksimal 2MB (JPG, PNG, GIF).</p>
                    @error('foto')
                        <p class="text-red-500 text-xs italic mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-between">
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg focus:outline-none focus:shadow-outline transition duration-150 ease-in-out">
                        Simpan Kandidat
                    </button>
                    <a href="{{ route('admin.kandidat.index') }}" class="inline-block align-baseline font-bold text-sm text-blue-500 hover:text-blue-800">
                        Batal
                    </a>
                </div>
            </form>
        </div>
    </div>

</body>
</html>