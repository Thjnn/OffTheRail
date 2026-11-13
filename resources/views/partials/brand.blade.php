{{-- Logo nếu shop đã tải lên, không thì tên shop bằng chữ có chân nghiêng --}}
<a href="{{ url('/') }}" class="inline-flex items-center" aria-label="{{ shop('name') }}">
    @if (filled(shop('logo')))
        <img src="{{ Storage::disk('public')->url(shop('logo')) }}" alt="{{ shop('name') }}" class="h-9 w-auto">
    @else
        <span class="font-serif text-2xl font-semibold tracking-tight whitespace-nowrap italic">{{ shop('name') }}</span>
    @endif
</a>
