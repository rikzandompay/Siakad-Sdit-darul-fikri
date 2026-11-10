<?php

namespace App\Http\Controllers;

use App\Models\HariLibur;
use App\Models\PengaturanSekolah;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Pengaturan sekolah (admin): lokasi dan radius untuk absen guru, jam masuk, dan hari libur.
 * Nilai ini dibaca aplikasi MyDarfik lewat API.
 */
class PengaturanSekolahController extends Controller
{
    public function index()
    {
        $lokasi = PengaturanSekolah::lokasi();
        $libur = HariLibur::orderByDesc('tanggal')->limit(60)->get();

        return view('pengaturan-sekolah', [
            'latitude' => $lokasi['latitude'] ?? null,
            'longitude' => $lokasi['longitude'] ?? null,
            'radius' => PengaturanSekolah::radiusMeter(),
            'jamMasuk' => PengaturanSekolah::jamMasuk(),
            'jamPulang' => PengaturanSekolah::jamPulang(),
            'libur' => $libur,
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'latitude' => 'nullable|required_with:longitude|numeric|between:-90,90',
            'longitude' => 'nullable|required_with:latitude|numeric|between:-180,180',
            'radius_meter' => 'required|integer|between:20,2000',
            'jam_masuk' => ['required', 'date_format:H:i'],
            'jam_pulang' => ['required', 'date_format:H:i', 'after:jam_masuk'],
        ]);

        PengaturanSekolah::simpan([
            PengaturanSekolah::LATITUDE => $data['latitude'] ?? null,
            PengaturanSekolah::LONGITUDE => $data['longitude'] ?? null,
            PengaturanSekolah::RADIUS => $data['radius_meter'],
            PengaturanSekolah::JAM_MASUK => $data['jam_masuk'],
            PengaturanSekolah::JAM_PULANG => $data['jam_pulang'],
        ]);

        return redirect()->route('pengaturan-sekolah.index')->with('success', 'Pengaturan sekolah disimpan.');
    }

    public function storeLibur(Request $request)
    {
        $data = $request->validate([
            'tanggal' => 'required|date|unique:hari_libur,tanggal',
            'tanggal_akhir' => 'nullable|date|after_or_equal:tanggal',
            'keterangan' => 'required|string|max:150',
        ], ['tanggal.unique' => 'Tanggal ini sudah terdaftar sebagai hari libur.']);

        $mulai = Carbon::parse($data['tanggal']);
        $akhir = Carbon::parse($data['tanggal_akhir'] ?? $data['tanggal']);
        abort_if($mulai->diffInDays($akhir) > 60, 422, 'Maksimal 60 hari sekali input.');

        for ($d = $mulai->copy(); $d->lte($akhir); $d->addDay()) {
            HariLibur::firstOrCreate(['tanggal' => $d->toDateString()], ['keterangan' => $data['keterangan']]);
        }

        return redirect()->route('pengaturan-sekolah.index')->with('success', 'Hari libur ditambahkan.');
    }

    public function destroyLibur(HariLibur $libur)
    {
        $libur->delete();

        return redirect()->route('pengaturan-sekolah.index')->with('success', 'Hari libur dihapus.');
    }
}
