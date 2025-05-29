@extends('admin.layouts.app') {{-- Penting: Mewarisi dari base layout --}}

@section('title', 'Dashboard Admin') {{-- Menetapkan judul halaman --}}

@section('content') {{-- Bagian konten unik halaman ini --}}
    <h2 class="text-3xl font-bold text-gray-800 mb-6">Dashboard Admin</h2>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div class="bg-white p-6 rounded-lg shadow-lg">
            <h3 class="text-xl font-semibold text-gray-700 mb-4">Manajemen Pemilih</h3>
            <p class="text-gray-600 mb-4">Lihat dan kelola data pemilih desa, status verifikasi, dan status voting.</p>
            <a href="{{ route('admin.pemilih.index') }}" class="text-blue-500 hover:underline">Lihat Semua Pemilih &raquo;</a>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-lg">
            <h3 class="text-xl font-semibold text-gray-700 mb-4">Manajemen Kandidat</h3>
            <p class="text-gray-600 mb-4">Tambah, edit, dan hapus kandidat kepala desa.</p>
            <a href="{{ route('admin.kandidat.index') }}" class="text-blue-500 hover:underline">Kelola Kandidat &raquo;</a>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-lg">
            <h3 class="text-xl font-semibold text-gray-700 mb-4">Hasil Pemilihan</h3>
            <p class="text-gray-600 mb-4">Pantau hasil pemilihan secara real-time dan unduh laporan akhir.</p>
            <a href="{{ route('admin.results.index') }}" class="text-blue-500 hover:underline">Lihat Hasil &raquo;</a> 
        </div>

        <div class="bg-white p-6 rounded-lg shadow-lg">
            <h3 class="text-xl font-semibold text-gray-700 mb-4">Pengaturan Jadwal</h3>
            <p class="text-gray-600 mb-4">Atur waktu mulai dan berakhirnya proses pemilihan.</p>
            <a href="{{ route('admin.settings.election') }}" class="text-blue-500 hover:underline">Atur Jadwal &raquo;</a>
        </div>
    </div>
@endsection {{-- Akhir bagian konten --}}