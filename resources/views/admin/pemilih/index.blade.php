@extends('admin.layouts.app') {{-- Mewarisi dari base layout --}}

@section('title', 'Manajemen Pemilih') {{-- Menetapkan judul halaman --}}

@section('content') {{-- Bagian konten unik halaman ini --}}
    <h2 class="text-3xl font-bold text-gray-800 mb-6">Manajemen Data Pemilih</h2>

    <div class="flex justify-between items-center mb-6">
        {{-- Tombol Tambah Pemilih Baru (link ke form create) --}}
        <a href="{{ route('admin.pemilih.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg shadow-md transition duration-150 ease-in-out">
            + Tambah Pemilih Baru
        </a>
        {{-- Tombol untuk Enroll Wajah (tetap ada karena terpisah dari CRUD dasar) --}}
        <a href="{{ route('admin.pemilih.enroll.form') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-lg shadow-md transition duration-150 ease-in-out">
            Daftarkan Wajah Pemilih
        </a>
        {{-- <button class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded-lg shadow-md transition duration-150 ease-in-out">
            Unduh Data Pemilih (Excel)
        </button> --}}
    </div>

    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <table class="min-w-full leading-normal">
            <thead>
                <tr>
                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        ID
                    </th>
                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        NIK
                    </th>
                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        Nama Lengkap
                    </th>
                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        Alamat
                    </th>
                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        Wajah Terdaftar
                    </th>
                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        Status Memilih
                    </th>
                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        Aksi
                    </th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pemilih as $p)
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                            {{ $p->id }}
                        </td>
                        <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                            {{ $p->nik }}
                        </td>
                        <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                            {{ $p->nama_lengkap }}
                        </td>
                        <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                            {{ $p->alamat }}
                        </td>
                        <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                            @if ($p->face_embedding)
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Sudah</span>
                            @else
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Belum</span>
                            @endif
                        </td>
                        <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                            @if ($p->sudah_memilih)
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">Sudah Memilih</span>
                            @else
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Belum Memilih</span>
                            @endif
                        </td>
                        <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm whitespace-no-wrap">
                            {{-- Tautan untuk Enroll Wajah (tetap) --}}
                            <a href="{{ route('admin.pemilih.enroll.form', ['nik' => $p->nik]) }}" class="text-indigo-600 hover:text-indigo-900 mr-3">Enroll Wajah</a>
                            
                            {{-- Tautan Edit (BARU) --}}
                            <a href="{{ route('admin.pemilih.edit', $p->id) }}" class="text-blue-600 hover:text-blue-900 mr-3">Edit</a>
                            
                            {{-- Form Hapus (BARU) --}}
                            <form action="{{ route('admin.pemilih.destroy', $p->id) }}" method="POST" class="inline" onsubmit="return confirm('Anda yakin ingin menghapus data pemilih ini? Ini akan menghapus semua suara terkait!');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-5 py-5 bg-white text-sm text-center">
                            Belum ada data pemilih.
                            <br>
                            <a href="{{ route('admin.pemilih.create') }}" class="text-blue-500 hover:underline mt-2 inline-block">Tambahkan pemilih baru sekarang.</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection {{-- Akhir bagian konten --}}