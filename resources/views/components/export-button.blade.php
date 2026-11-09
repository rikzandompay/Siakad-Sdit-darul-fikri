@props(['type', 'href'])
{{-- Tombol ekspor yang sama di semua halaman: PDF merah, CSV hijau (warna mengikuti jenis berkas). --}}
@if ($type === 'pdf')
    <a href="{{ $href }}" target="_blank" rel="noopener" class="btn btn-alert">
        <span class="material-symbols-outlined text-lg" aria-hidden="true">picture_as_pdf</span>
        Export PDF
    </a>
@else
    <a href="{{ $href }}" class="btn btn-success">
        <span class="material-symbols-outlined text-lg" aria-hidden="true">table_chart</span>
        Export CSV
    </a>
@endif
