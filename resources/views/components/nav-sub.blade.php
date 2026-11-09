@props(['href', 'active' => false])
<a href="{{ $href }}" @if ($active) aria-current="page" @endif
    class="mx-3 block rounded-lg py-2 pl-11 pr-3 text-sm transition-colors
        {{ $active ? 'bg-blue-800 font-semibold text-white' : 'text-gray-600 hover:bg-white/70 hover:text-gray-900' }}">
    {{ $slot }}
</a>
