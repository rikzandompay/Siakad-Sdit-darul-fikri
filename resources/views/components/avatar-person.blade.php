{{-- Lingkaran avatar netral; biru untuk laki-laki, kuning untuk perempuan. --}}
@props([
    'jenisKelamin' => null,
    'size' => 'md',
    'shrink' => false,
])

@php
    $dimensi = ['sm' => 'w-7 h-7', 'md' => 'w-8 h-8', 'lg' => 'w-9 h-9'][$size] ?? 'w-8 h-8';
    $ikon = ['sm' => 'text-[16px]', 'md' => 'text-[18px]', 'lg' => 'text-[20px]'][$size] ?? 'text-[18px]';
    $warna = $jenisKelamin === 'L' ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700';
@endphp

<div
    {{ $attributes->merge(['class' => $dimensi.' '.($shrink ? 'shrink-0 ' : '').'rounded-full '.$warna.' flex items-center justify-center']) }}
    aria-hidden="true">
    <span class="material-symbols-outlined {{ $ikon }}">person</span>
</div>
