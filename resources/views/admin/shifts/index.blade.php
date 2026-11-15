@extends('layouts.admin')

@section('title', 'Ca bán hàng')

@section('content')
    @php($money = fn ($v) => $v === null ? '—' : \App\Support\Money::vnd($v))
    <div class="a-card overflow-x-auto">
        <table class="a-table">
            <thead>
                <tr>
                    <th>Người mở</th><th>Mở lúc</th><th>Chốt lúc</th><th>Người chốt</th>
                    <th class="text-right">Đầu ca</th><th class="text-right">Tiền mặt bán</th><th class="text-right">QR bán</th>
                    <th class="text-right">Kỳ vọng</th><th class="text-right">Đếm được</th><th class="text-right">Chênh lệch</th><th>Ghi chú</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($shifts as $s)
                    <tr>
                        <td>{{ $s->user?->name }}</td>
                        <td class="whitespace-nowrap">{{ $s->opened_at->format('H:i d/m/Y') }}</td>
                        <td class="whitespace-nowrap">
                            @if ($s->closed_at)
                                {{ $s->closed_at->format('H:i d/m/Y') }}
                            @else
                                <span class="a-badge bg-green-100 text-green-800">Đang mở</span>
                            @endif
                        </td>
                        <td>{{ $s->closer?->name }}</td>
                        <td class="text-right tabular-nums">{{ $money($s->opening_cash) }}</td>
                        <td class="text-right tabular-nums">{{ $money($s->cashSales()) }}</td>
                        <td class="text-right tabular-nums">{{ $money($s->qrSales()) }}</td>
                        <td class="text-right tabular-nums">{{ $money($s->expected_cash) }}</td>
                        <td class="text-right tabular-nums">{{ $money($s->counted_cash) }}</td>
                        <td class="text-right tabular-nums">@include('admin.shifts.difference', ['value' => $s->difference()])</td>
                        <td>{{ $s->note }}</td>
                    </tr>
                @empty
                    <tr><td colspan="11" class="py-8 text-center text-a-muted">Chưa có ca nào.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $shifts->links() }}</div>
@endsection
