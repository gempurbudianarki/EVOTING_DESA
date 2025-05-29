@extends('admin.layouts.app') {{-- Mewarisi dari base layout --}}

@section('title', 'Manajemen Kandidat') {{-- Menetapkan judul halaman --}}

@section('content') {{-- Bagian konten unik halaman ini --}}
    <h2 class="text-3xl font-bold text-gray-800 mb-6">Manajemen Data Kandidat</h2>

    <div class="flex justify-between items-center mb-6">
        <a href="{{ route('admin.kandidat.create') }}" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg shadow-md transition duration-150 ease-in-out">
            + Tambah Kandidat Baru
        </a>
        </div>

    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <table class="min-w-full leading-normal">
            <thead>
                <tr>
                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        No. Urut
                    </th>
                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        Foto
                    </th>
                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        Nama Lengkap
                    </th>
                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        Visi
                    </th>
                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        Misi
                    </th>
                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        Jumlah Suara
                    </th>
                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        Aksi
                    </th>
                </tr>
            </thead>
            <tbody>
                @forelse ($kandidat as $k)
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                            {{ $k->nomor_urut }}
                        </td>
                        <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                            @if ($k->foto_path)
                                <img src="{{ asset($k->foto_path) }}" alt="Foto {{ $k->nama_lengkap }}" class="w-16 h-16 object-cover rounded-full">
                            @else
                                <span class="text-gray-500">Tidak ada foto</span>
                            @endif
                        </td>
                        <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                            {{ $k->nama_lengkap }}
                        </td>
                        <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                            {{ Str::limit($k->visi, 50) }} </td>
                        <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                            {{ Str::limit($k->misi, 50) }} </td>
                        <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                            {{ $k->jumlah_suara }}
                        </td>
                        <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                            <a href="{{ route('admin.kandidat.edit', $k->id) }}" class="text-blue-600 hover:text-blue-900 mr-3">Edit</a>
                            <form action="{{ route('admin.kandidat.destroy', $k->id) }}" method="POST" class="inline" onsubmit="return confirm('Anda yakin ingin menghapus kandidat ini? Semua suara untuk kandidat ini juga akan terhapus.');">
                                @csrf
                                @method('DELETE') <button type="submit" class="text-red-600 hover:text-red-900">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-5 py-5 bg-white text-sm text-center">
                            Belum ada data kandidat.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection {{-- Akhir bagian konten --}}