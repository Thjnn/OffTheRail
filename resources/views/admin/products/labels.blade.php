@extends('layouts.admin')

@section('title', 'In tem: '.$product->name)

@section('actions')
    <a href="{{ url('/admin/san-pham') }}" class="a-btn">← Sản phẩm</a>
@endsection

@section('content')
    <form method="GET" action="{{ url('/admin/san-pham/'.$product->id.'/in-tem') }}" target="_blank" class="max-w-2xl">
        <div class="a-card overflow-x-auto">
            <table class="a-table">
                <thead><tr><th>Màu / Size</th><th>SKU</th><th>Mã vạch</th><th class="text-right">Tồn</th><th class="w-28 text-right">Số tem</th></tr></thead>
                <tbody>
                    @forelse ($variants as $v)
                        <tr>
                            <td>{{ collect([$v->color?->name, $v->size])->filter()->join(' / ') }}</td>
                            <td class="font-mono text-xs">{{ $v->sku }}</td>
                            <td class="font-mono text-xs">{{ $v->barcode }}</td>
                            <td class="text-right">{{ $v->stock }}</td>
                            <td class="text-right">
                                <label class="sr-only" for="copies-{{ $v->id }}">Số tem {{ $v->sku }}</label>
                                <input id="copies-{{ $v->id }}" type="number" min="0" max="2000" name="copies[{{ $v->id }}]" value="{{ $v->stock }}" class="a-input w-20 text-right">
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-8 text-center text-a-muted">Sản phẩm chưa có biến thể.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <p class="mt-2 text-xs text-a-muted">Tem 50 × 30 mm, mã vạch Code128. Mặc định in bằng số đang tồn.</p>
        <button class="a-btn a-btn-primary mt-3"><x-admin.icon name="print" class="size-4" /> In tem</button>
    </form>
@endsection
