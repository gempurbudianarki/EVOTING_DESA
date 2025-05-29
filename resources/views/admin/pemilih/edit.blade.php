@extends('admin.layouts.app') {{-- Mewarisi dari base layout --}}

@section('title', 'Edit Pemilih') {{-- Menetapkan judul halaman --}}

@section('content') {{-- Bagian konten unik halaman ini --}}
    <h2 class="text-3xl font-bold text-gray-800 mb-6">Edit Pemilih: {{ $pemilih->nama_lengkap }} (NIK: {{ $pemilih->nik }})</h2>

    <div class="w-full max-w-xl bg-white p-8 rounded-xl shadow-2xl mx-auto">
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

        <form action="{{ route('admin.pemilih.update', $pemilih->id) }}" method="POST">
            @csrf {{-- Token CSRF untuk keamanan --}}
            @method('PUT') {{-- Menggunakan metode PUT untuk update resource --}}

            <div class="mb-4">
                <label for="nik" class="block text-gray-700 text-sm font-bold mb-2">Nomor Induk Kependudukan (NIK):</label>
                <input type="text" id="nik" name="nik" maxlength="16"
                       class="shadow appearance-none border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 @error('nik') border-red-500 @enderror"
                       value="{{ old('nik', $pemilih->nik) }}" placeholder="Contoh: 33xxxxxxxxxxxxxx" required>
                @error('nik')
                    <p class="text-red-500 text-xs italic mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label for="nama_lengkap" class="block text-gray-700 text-sm font-bold mb-2">Nama Lengkap:</label>
                <input type="text" id="nama_lengkap" name="nama_lengkap"
                       class="shadow appearance-none border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 @error('nama_lengkap') border-red-500 @enderror"
                       value="{{ old('nama_lengkap', $pemilih->nama_lengkap) }}" required>
                @error('nama_lengkap')
                    <p class="text-red-500 text-xs italic mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-6">
                <label for="alamat" class="block text-gray-700 text-sm font-bold mb-2">Alamat (Opsional):</label>
                <textarea id="alamat" name="alamat" rows="3"
                          class="shadow appearance-none border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 @error('alamat') border-red-500 @enderror"
                          >{{ old('alamat', $pemilih->alamat) }}</textarea>
                @error('alamat')
                    <p class="text-red-500 text-xs italic mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Opsional: Tambahkan opsi untuk reset status sudah memilih --}}
            {{-- <div class="mb-4">
                <label class="inline-flex items-center">
                    <input type="checkbox" name="sudah_memilih" class="form-checkbox h-5 w-5 text-blue-600" value="1" {{ old('sudah_memilih', $pemilih->sudah_memilih) ? 'checked' : '' }}>
                    <span class="ml-2 text-gray-700 text-sm font-bold">Sudah Memilih</span>
                </label>
                <p class="text-gray-600 text-xs italic mt-1">Centang jika pemilih sudah dianggap memilih.</p>
            </div> --}}

            <div class="flex items-center justify-between">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg focus:outline-none focus:shadow-outline transition duration-150 ease-in-out">
                    Perbarui Pemilih
                </button>
                <a href="{{ route('admin.pemilih.index') }}" class="inline-block align-baseline font-bold text-sm text-gray-500 hover:text-gray-800">
                    Batal
                </a>
            </div>
        </form>
    </div>
@endsection {{-- Akhir bagian konten --}}