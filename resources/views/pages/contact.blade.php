@extends('layouts.shop')

@section('title', 'Liên hệ')

@section('content')
    <div class="mx-auto grid max-w-5xl gap-10 px-4 pt-10 lg:grid-cols-2 lg:px-8 lg:pt-16">
        <div data-reveal>
            <p class="eyebrow mb-2">Liên hệ</p>
            <h1 class="display text-4xl sm:text-5xl">Nhắn cho <em>{{ shop('name') }}</em></h1>
            <p class="mt-4 leading-relaxed text-muted">Hỏi về size, đơn hàng hay hợp tác, shop đều trả lời trong ngày.</p>

            <ul class="mt-6 space-y-2 text-sm">
                @if (filled(shop('hotline')))
                    <li>Hotline: <a class="font-semibold hover:text-accent" href="tel:{{ preg_replace('/[^0-9+]/', '', shop('hotline')) }}">{{ shop('hotline') }}</a></li>
                @endif
                @if (filled(shop('zalo_url')))
                    <li><a class="font-semibold underline underline-offset-4 hover:text-accent" href="{{ shop('zalo_url') }}" target="_blank" rel="noopener">Nhắn Zalo</a></li>
                @endif
                @if (filled(shop('messenger_url')))
                    <li><a class="font-semibold underline underline-offset-4 hover:text-accent" href="{{ shop('messenger_url') }}" target="_blank" rel="noopener">Nhắn Messenger</a></li>
                @endif
            </ul>
        </div>

        <form method="post" action="{{ url('/lien-he') }}" class="space-y-4 rounded-card bg-surface p-6 shadow-soft" novalidate>
            @csrf

            @if (session('status'))
                <p class="rounded-chip bg-ok/10 px-4 py-3 text-sm text-ok" role="status">{{ session('status') }}</p>
            @endif

            {{-- Ô bẫy bot: người dùng thật không thấy và không tab tới được --}}
            <div class="hidden" aria-hidden="true">
                <label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
            </div>

            @foreach ([['name', 'Tên của bạn', 'text', 'name'], ['phone', 'Số điện thoại', 'tel', 'tel'], ['email', 'Email (nếu không có số điện thoại)', 'email', 'email']] as [$field, $label, $type, $autocomplete])
                <div>
                    <label for="{{ $field }}" class="mb-1.5 block text-sm font-medium">{{ $label }}</label>
                    <input id="{{ $field }}" name="{{ $field }}" type="{{ $type }}" autocomplete="{{ $autocomplete }}" value="{{ old($field) }}"
                        @error($field) aria-invalid="true" aria-describedby="{{ $field }}-error" @enderror
                        class="w-full rounded-chip border border-line bg-bg px-4 py-2.5 outline-none focus:border-accent">
                    @error($field)
                        <p id="{{ $field }}-error" class="mt-1 text-xs text-sale">{{ $message }}</p>
                    @enderror
                </div>
            @endforeach

            <div>
                <label for="message" class="mb-1.5 block text-sm font-medium">Lời nhắn</label>
                <textarea id="message" name="message" rows="5"
                    @error('message') aria-invalid="true" aria-describedby="message-error" @enderror
                    class="w-full rounded-chip border border-line bg-bg px-4 py-2.5 outline-none focus:border-accent">{{ old('message') }}</textarea>
                @error('message')
                    <p id="message-error" class="mt-1 text-xs text-sale">{{ $message }}</p>
                @enderror
            </div>

            <button class="btn btn-primary w-full !py-3">Gửi lời nhắn</button>
        </form>
    </div>
@endsection
