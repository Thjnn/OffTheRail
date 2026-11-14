@extends('layouts.admin')

@section('title', 'Nhật ký thao tác')

@section('content')
    <form method="GET" class="mb-4 flex items-center gap-2">
        <select name="action" class="a-input w-56" onchange="this.form.submit()" aria-label="Lọc theo thao tác">
            <option value="">Mọi thao tác</option>
            @foreach (\App\Models\ActivityLog::LABELS as $key => $label)
                <option value="{{ $key }}" @selected($action === $key)>{{ $label }}</option>
            @endforeach
        </select>
    </form>

    <div class="a-card overflow-x-auto">
        <table class="a-table">
            <thead>
                <tr><th>Thời gian</th><th>Người làm</th><th>Thao tác</th><th>Đối tượng</th><th>Cũ</th><th>Mới</th></tr>
            </thead>
            <tbody>
                @forelse ($logs as $log)
                    <tr>
                        <td class="whitespace-nowrap text-a-muted">{{ $log->created_at->format('d/m/Y H:i') }}</td>
                        <td>{{ $log->user?->name ?? 'Hệ thống' }}</td>
                        <td>{{ $log->label() }}</td>
                        <td>{{ $log->subjectLabel() }}</td>
                        <td class="font-mono text-xs text-a-muted">@foreach ($log->old ?? [] as $k => $v){{ $k }}: {{ is_scalar($v) ? $v : json_encode($v) }}<br>@endforeach</td>
                        <td class="font-mono text-xs">@foreach ($log->new ?? [] as $k => $v){{ $k }}: {{ is_scalar($v) ? $v : json_encode($v) }}<br>@endforeach</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-8 text-center text-a-muted">Chưa có thao tác nào.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $logs->links() }}</div>
@endsection
