<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil Pemilihan - Admin EVoting Desa</title>
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
                <a href="{{ route('admin.pemilih.index') }}" class="bg-blue-700 hover:bg-blue-800 text-white font-semibold py-2 px-4 rounded-lg focus:outline-none focus:shadow-outline transition duration-150 ease-in-out">
                    Manajemen Pemilih
                </a>
                <a href="{{ route('admin.kandidat.index') }}" class="bg-blue-700 hover:bg-blue-800 text-white font-semibold py-2 px-4 rounded-lg focus:outline-none focus:shadow-outline transition duration-150 ease-in-out">
                    Manajemen Kandidat
                </a>
                <a href="{{ route('admin.settings.election') }}" class="bg-blue-700 hover:bg-blue-800 text-white font-semibold py-2 px-4 rounded-lg focus:outline-none focus:shadow-outline transition duration-150 ease-in-out">
                    Pengaturan Jadwal
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
        <h2 class="text-3xl font-bold text-gray-800 mb-6">Hasil Pemilihan</h2>

        @if (session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <strong class="font-bold">Berhasil!</strong>
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
        @endif
        @if (session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <strong class="font-bold">Error!</strong>
                <span class="block sm:inline">{{ session('error') }}</span>
            </div>
        @endif

        <div class="bg-white p-6 rounded-lg shadow-md mb-6">
            <h3 class="text-xl font-semibold text-gray-700 mb-4">Informasi Pemilihan: {{ $electionSetting->name ?? 'Belum ada nama pemilihan' }}</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-gray-700">
                <div>
                    <p><strong>Status:</strong> 
                        @if ($electionSetting && $electionSetting->is_active)
                            <span class="text-green-600 font-bold">Aktif</span>
                            @if ($electionSetting->end_time && $electionSetting->end_time->isPast())
                                <span class="text-red-600">(Sudah Berakhir)</span>
                            @elseif ($electionSetting->start_time && $electionSetting->start_time->isFuture())
                                <span class="text-yellow-600">(Akan Datang)</span>
                            @else
                                <span class="text-green-600">(Sedang Berlangsung)</span>
                            @endif
                        @else
                            <span class="text-red-600 font-bold">Tidak Aktif</span>
                        @endif
                    </p>
                    <p><strong>Waktu Mulai:</strong> {{ $electionSetting->start_time ? $electionSetting->start_time->format('d M Y H:i') : 'Belum diatur' }}</p>
                    <p><strong>Waktu Berakhir:</strong> {{ $electionSetting->end_time ? $electionSetting->end_time->format('d M Y H:i') : 'Belum diatur' }}</p>
                </div>
                <div>
                    <p><strong>Total Suara Sah:</strong> <span class="text-blue-600 font-bold text-lg">{{ $totalVotes }}</span></p>
                    <p><strong>Total Pemilih Terdaftar:</strong> {{ $totalPemilihTerdaftar }}</p>
                    <p><strong>Pemilih Sudah Memilih:</strong> {{ $pemilihSudahMemilih }}</p>
                    <p><strong>Pemilih Belum Memilih:</strong> {{ $pemilihBelumMemilih }}</p>
                </div>
            </div>
        </div>

        <div class="flex justify-end items-center mb-6">
            <a href="{{ route('admin.results.export') }}" class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded-lg shadow-md transition duration-150 ease-in-out">
                Unduh Hasil (Excel)
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
                            Kandidat
                        </th>
                        <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            Jumlah Suara
                        </th>
                        <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            Persentase
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($results as $result)
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                {{ $result['nomor_urut'] }}
                            </td>
                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm flex items-center">
                                @if ($result['foto_path'])
                                    <img src="{{ asset($result['foto_path']) }}" alt="Foto {{ $result['nama_lengkap'] }}" class="w-10 h-10 object-cover rounded-full mr-3">
                                @else
                                    <div class="w-10 h-10 flex items-center justify-center bg-gray-200 rounded-full mr-3 text-gray-500 text-xs">?</div>
                                @endif
                                {{ $result['nama_lengkap'] }}
                            </td>
                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                {{ $result['jumlah_suara'] }}
                            </td>
                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
                                    {{-- PERBAIKAN DI SINI: TAMBAHKAN TANDA KUTIP --}}
                                    <div class="bg-blue-600 h-2.5 rounded-full" style="width: '{{ $result['percentage'] }}%'"></div> 
                                </div>
                                <span class="text-gray-700 text-xs mt-1 block">{{ $result['percentage'] }}%</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-5 bg-white text-sm text-center">
                                Belum ada kandidat atau suara yang masuk.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</body>
</html>