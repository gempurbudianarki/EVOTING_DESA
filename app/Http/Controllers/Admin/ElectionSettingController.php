<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ElectionSetting; // Import model ElectionSetting
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log; // Untuk logging

class ElectionSettingController extends Controller
{
    /**
     * Menampilkan halaman pengaturan pemilihan.
     * Admin bisa melihat status pemilihan dan mengaturnya.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // Ambil pengaturan pemilihan (asumsi hanya ada satu record)
        $setting = ElectionSetting::firstOrCreate(
            ['id' => 1], // Mencari record dengan ID 1
            ['name' => 'Pemilihan Kepala Desa', 'is_active' => false] // Jika tidak ditemukan, buat dengan nilai default ini
        );
        
        return view('admin.settings.election', compact('setting')); // Mengarahkan ke view resources/views/admin/settings/election.blade.php
    }

    /**
     * Memperbarui pengaturan pemilihan.
     * Admin bisa mengaktifkan/menonaktifkan dan mengatur jadwal.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request)
    {
        // Ambil pengaturan pemilihan (asumsi hanya ada satu record dengan ID 1)
        $setting = ElectionSetting::find(1);

        if (!$setting) {
            // Ini seharusnya tidak terjadi jika kita sudah pakai firstOrCreate di index
            return back()->withErrors(['general' => 'Pengaturan pemilihan tidak ditemukan.']);
        }

        // Validasi input
        $request->validate([
            'name' => 'required|string|max:255',
            'is_active' => 'boolean',
            'start_time' => 'nullable|date',
            'end_time' => 'nullable|date|after_or_equal:start_time',
        ], [
            'end_time.after_or_equal' => 'Waktu berakhir harus setelah atau sama dengan waktu mulai.'
        ]);

        // Perbarui data setting
        $setting->name = $request->input('name');
        $setting->is_active = $request->boolean('is_active'); // Mendapatkan boolean dari checkbox

        // Konversi string tanggal/waktu dari form ke format yang benar jika tidak null
        $setting->start_time = $request->input('start_time') ? \Carbon\Carbon::parse($request->input('start_time')) : null;
        $setting->end_time = $request->input('end_time') ? \Carbon\Carbon::parse($request->input('end_time')) : null;
        
        $setting->save(); // Simpan perubahan

        Log::info('Election settings updated by admin. Active: ' . ($setting->is_active ? 'Yes' : 'No') . 
                   ', Start: ' . ($setting->start_time ? $setting->start_time->format('Y-m-d H:i') : 'N/A') . 
                   ', End: ' . ($setting->end_time ? $setting->end_time->format('Y-m-d H:i') : 'N/A'));

        return redirect()->route('admin.settings.election')->with('success', 'Pengaturan pemilihan berhasil diperbarui!');
    }
}