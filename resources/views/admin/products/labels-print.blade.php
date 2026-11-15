<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="robots" content="noindex">
    <title>Tem {{ $product->name }}</title>
    @vite('resources/js/labels.js')
    <style>
        @page { size: 50mm 30mm; margin: 0; }
        * { box-sizing: border-box; }
        body { margin: 0; font: 9px/1.2 system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif; color: #000; }
        .label { width: 50mm; height: 30mm; padding: 1.5mm 2mm; overflow: hidden; page-break-after: always; break-after: page; display: flex; flex-direction: column; }
        .shop { font-size: 7px; text-transform: uppercase; letter-spacing: .5px; }
        .name { font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .row { display: flex; justify-content: space-between; }
        .price { font-weight: 700; }
        svg { width: 100%; flex: 1; min-height: 0; }
        @media screen { body { background: #eee; padding: 8px; } .label { background: #fff; margin: 0 auto 6px; } }
    </style>
</head>
<body>
    @forelse ($labels as $v)
        <div class="label">
            <div class="shop">{{ shop('name') }}</div>
            <div class="name">{{ $product->name }}</div>
            <div class="row">
                <span>{{ collect([$v->color?->name, $v->size])->filter()->join(' / ') }}</span>
                <span class="price">{{ \App\Support\Money::vnd($v->price ?? $product->price) }}</span>
            </div>
            <svg data-barcode="{{ $v->barcode }}" role="img" aria-label="Mã vạch {{ $v->barcode }}"></svg>
        </div>
    @empty
        <p>Chưa chọn tem nào để in.</p>
    @endforelse
</body>
</html>
