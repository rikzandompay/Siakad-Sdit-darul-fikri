{{-- Jejak navigasi. Tiap item: ['label' => string, 'url' => string|null]; item terakhir tanpa url = halaman aktif. --}}
@props(['items' => [], 'class' => ''])

<div {{ $attributes->merge(['class' => 'flex items-center gap-2 text-sm text-gray-500 '.$class]) }}>
    @foreach ($items as $item)
        @if ($loop->first)
            @if (! empty($item['url']))
                <a href="{{ $item['url'] }}" class="hover:text-blue-600">{{ $item['label'] }}</a>
            @else
                <span>{{ $item['label'] }}</span>
            @endif
        @else
            <span class="material-symbols-outlined text-xs">chevron_right</span>
            @if (! empty($item['url']))
                <a href="{{ $item['url'] }}" class="hover:text-blue-600">{{ $item['label'] }}</a>
            @else
                <span class="text-blue-600 font-medium">{{ $item['label'] }}</span>
            @endif
        @endif
    @endforeach
</div>
