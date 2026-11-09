@props(['href', 'icon', 'active' => false, 'badge' => 0, 'badgeTitle' => ''])
<a href="{{ $href }}" @if ($active) aria-current="page" @endif
    class="mx-3 flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm transition-colors
        {{ $active ? 'bg-blue-600 font-semibold text-white' : 'font-medium text-gray-700 hover:bg-white/70 hover:text-gray-900' }}">
    <span class="material-symbols-outlined text-[22px]" aria-hidden="true">{{ $icon }}</span>
    <span>{{ $slot }}</span>
    @if ($badge > 0)
        <span class="ml-auto flex h-5 min-w-5 items-center justify-center rounded-full bg-amber-400 px-1.5 text-[11px] font-bold text-blue-950"
            title="{{ $badgeTitle }}">{{ $badge > 99 ? '99+' : $badge }}</span>
    @endif
</a>
