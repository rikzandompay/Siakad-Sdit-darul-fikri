<?php

namespace App\Http\Controllers;

use App\Models\PengaturanSekolah;
use App\Models\Pengumuman;
use App\Support\NomorSurat;
use App\Support\Sekolah;
use App\Support\SuratPengumuman;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

/**
 * Pengumuman sekolah. Admin (kepala sekolah/TU) membuat dan mengelola;
 * guru membaca pengumuman untuk "Semua" dan "Guru". Pengumuman untuk
 * "Semua" dan "Wali Murid" tampil di aplikasi MyDarfik wali.
 */
class PengumumanController extends Controller
{
    public function index(Request $request)
    {
        $isAdmin = $request->user()->isAdmin();
        $search = trim((string) $request->get('search'));

        $pengumuman = Pengumuman::with('pembuat')
            ->when(! $isAdmin, fn ($q) => $q->untukGuru())
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('judul', 'like', "%{$search}%")
                ->orWhere('isi', 'like', "%{$search}%")))
            ->orderByDesc('penting')
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('pengumuman.index', compact('pengumuman', 'isAdmin', 'search'));
    }

    public function create()
    {
        return view('pengumuman.form', ['pengumuman' => new Pengumuman([
            'sasaran' => Pengumuman::SEMUA,
            'tanggal_surat' => Sekolah::now()->toDateString(),
            'tempat' => PengaturanSekolah::ambil('kota', 'Kab. Kubu Raya'),
        ])]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['nomor_surat'] = NomorSurat::berikutnya(NomorSurat::PENGUMUMAN, $data['tanggal_surat'] ?? null);

        Pengumuman::create($data + ['dibuat_oleh' => $request->user()->id]);

        return redirect()->route('pengumuman.index')->with('success', 'Pengumuman berhasil diterbitkan.');
    }

    public function edit(Pengumuman $pengumuman)
    {
        return view('pengumuman.form', compact('pengumuman'));
    }

    public function update(Request $request, Pengumuman $pengumuman)
    {
        $data = $this->validated($request);
        // Pengumuman lama (sebelum ada format surat) mendapat nomornya saat pertama kali diubah.
        $data['nomor_surat'] = $pengumuman->nomor_surat
            ?: NomorSurat::berikutnya(NomorSurat::PENGUMUMAN, $data['tanggal_surat'] ?? null);

        $pengumuman->update($data);

        return redirect()->route('pengumuman.index')->with('success', 'Pengumuman berhasil diperbarui.');
    }

    public function destroy(Pengumuman $pengumuman)
    {
        $pengumuman->delete();

        return redirect()->route('pengumuman.index')->with('success', 'Pengumuman berhasil dihapus.');
    }

    /** Surat pengumuman resmi (PDF ber-kop, bernomor, ditandatangani kepala sekolah). */
    public function pdf(Pengumuman $pengumuman): Response
    {
        return response(SuratPengumuman::buatPdf($pengumuman), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="pengumuman-'.$pengumuman->id.'.pdf"',
        ]);
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'judul' => 'required|string|max:150',
            'isi' => 'required|string|max:5000',
            'sasaran' => ['required', Rule::in(Pengumuman::SASARAN)],
            'penting' => 'nullable|boolean',
            'tanggal_surat' => 'nullable|date',
            'tempat' => 'nullable|string|max:100',
            'jadwal_hari_tanggal' => 'nullable|string|max:150',
            'jadwal_waktu' => 'nullable|string|max:100',
            'jadwal_tempat' => 'nullable|string|max:150',
        ]);

        $data['penting'] = $request->boolean('penting');

        return $data;
    }
}
