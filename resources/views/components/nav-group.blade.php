@props(['icon', 'label', 'active' => false])
{{-- Alpine CSP: status buka/tutup dibaca dari data-open, perilakunya ada di resources/js/app.js (menuGroup). --}}
<div x-data="menuGroup" data-open="{{ $active ? 'true' : 'false' }}">
    <button type="button" @click="toggle"
        class="mx-3 flex w-[calc(100%-1.5rem)] items-center gap-3 rounded-lg px-3 py-2.5 text-sm transition-colors
            {{ $active ? 'bg-white/70 font-semibold text-blue-700' : 'font-medium text-gray-700 hover:bg-white/70 hover:text-gray-900' }}">
        <span class="material-symbols-outlined text-[22px]" aria-hidden="true">{{ $icon }}</span>
        <span>{{ $label }}</span>
        <span class="material-symbols-outlined ml-auto text-lg transition-transform duration-200" :class="chevronClass" aria-hidden="true">expand_more</span>
    </button>
    <div x-show="open" x-cloak class="mt-0.5 space-y-0.5 pb-1">
        {{ $slot }}
    </div>
</div>
