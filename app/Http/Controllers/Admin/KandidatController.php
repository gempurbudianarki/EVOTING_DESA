<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kandidat; // Import model Kandidat
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage; // Untuk upload dan hapus file

class KandidatController extends Controller
{
    /**
     * Menampilkan daftar semua kandidat.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $kandidat = Kandidat::orderBy('nomor_urut')->get(); // Ambil semua data kandidat, urutkan berdasarkan nomor urut
        return view('admin.kandidat.index', compact('kandidat')); // Mengarahkan ke view resources/views/admin/kandidat/index.blade.php
    }

    /**
     * Menampilkan form untuk membuat kandidat baru.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('admin.kandidat.create'); // Mengarahkan ke view resources/views/admin/kandidat/create.blade.php
    }

    /**
     * Menyimpan kandidat baru ke database.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        // 1. Validasi input
        $request->validate([
            'nomor_urut' => 'required|integer|unique:kandidat,nomor_urut|min:1',
            'nama_lengkap' => 'required|string|max:255',
            'jabatan_lama' => 'nullable|string|max:255',
            'visi' => 'required|string',
            'misi' => 'required|string',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Max 2MB
        ]);

        $kandidatData = $request->except('foto'); // Ambil semua data kecuali foto

        // 2. Upload foto jika ada
        if ($request->hasFile('foto')) {
            $path = $request->file('foto')->store('public/foto_kandidat'); // Simpan di storage/app/public/foto_kandidat
            $kandidatData['foto_path'] = Storage::url($path); // Simpan path yang bisa diakses publik
        } else {
            $kandidatData['foto_path'] = null; // Pastikan null jika tidak ada foto
        }

        // 3. Buat kandidat baru
        Kandidat::create($kandidatData);

        return redirect()->route('admin.kandidat.index')->with('success', 'Kandidat berhasil ditambahkan!');
    }

    /**
     * Menampilkan detail kandidat (opsional, mungkin tidak perlu di admin panel).
     *
     * @param  \App\Models\Kandidat  $kandidat
     * @return \Illuminate\View\View
     */
    public function show(Kandidat $kandidat)
    {
        // Biasanya tidak diperlukan halaman detail terpisah di admin untuk CRUD
        // Bisa langsung edit dari daftar atau memang ada kebutuhan view detail.
        return view('admin.kandidat.show', compact('kandidat'));
    }

    /**
     * Menampilkan form untuk mengedit kandidat yang sudah ada.
     *
     * @param  \App\Models\Kandidat  $kandidat
     * @return \Illuminate\View\View
     */
    public function edit(Kandidat $kandidat)
    {
        return view('admin.kandidat.edit', compact('kandidat')); // Mengarahkan ke view resources/views/admin/kandidat/edit.blade.php
    }

    /**
     * Memperbarui kandidat yang sudah ada di database.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Kandidat  $kandidat
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Kandidat $kandidat)
    {
        // 1. Validasi input
        $request->validate([
            'nomor_urut' => 'required|integer|unique:kandidat,nomor_urut,' . $kandidat->id . '|min:1', // Kecualikan ID kandidat saat ini
            'nama_lengkap' => 'required|string|max:255',
            'jabatan_lama' => 'nullable|string|max:255',
            'visi' => 'required|string',
            'misi' => 'required|string',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Max 2MB
        ]);

        $kandidatData = $request->except('foto');

        // 2. Upload foto baru jika ada, hapus foto lama jika diganti
        if ($request->hasFile('foto')) {
            // Hapus foto lama jika ada
            if ($kandidat->foto_path && Storage::exists(str_replace('/storage/', 'public/', $kandidat->foto_path))) {
                Storage::delete(str_replace('/storage/', 'public/', $kandidat->foto_path));
            }
            $path = $request->file('foto')->store('public/foto_kandidat');
            $kandidatData['foto_path'] = Storage::url($path);
        } elseif ($request->input('clear_foto')) { // Jika ada checkbox untuk menghapus foto
            if ($kandidat->foto_path && Storage::exists(str_replace('/storage/', 'public/', $kandidat->foto_path))) {
                Storage::delete(str_replace('/storage/', 'public/', $kandidat->foto_path));
            }
            $kandidatData['foto_path'] = null;
        }

        // 3. Perbarui kandidat
        $kandidat->update($kandidatData);

        return redirect()->route('admin.kandidat.index')->with('success', 'Kandidat berhasil diperbarui!');
    }

    /**
     * Menghapus kandidat dari database.
     *
     * @param  \App\Models\Kandidat  $kandidat
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Kandidat $kandidat)
    {
        // Hapus file foto terkait jika ada
        if ($kandidat->foto_path && Storage::exists(str_replace('/storage/', 'public/', $kandidat->foto_path))) {
            Storage::delete(str_replace('/storage/', 'public/', $kandidat->foto_path));
        }

        $kandidat->delete();

        return redirect()->route('admin.kandidat.index')->with('success', 'Kandidat berhasil dihapus!');
    }
}