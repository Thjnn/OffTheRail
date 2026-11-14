<div class="space-y-5">
    @if (session('status'))
        <div class="rounded-md border border-a-up/30 bg-a-up/5 px-3 py-2 text-a-up" role="status">{{ session('status') }}</div>
    @endif
    @error('staff')<div class="rounded-md border border-a-down/30 bg-a-down/5 px-3 py-2 text-a-down" role="alert">{{ $message }}</div>@enderror
    @error('newPassword')<div class="rounded-md border border-a-down/30 bg-a-down/5 px-3 py-2 text-a-down" role="alert">{{ $message }}</div>@enderror

    <div class="a-card overflow-x-auto">
        <table class="a-table">
            <thead><tr><th>Tên</th><th>Email</th><th>Vai trò</th><th>Trạng thái</th><th class="text-right">Doanh số 30 ngày</th><th class="text-right">Đơn</th><th></th></tr></thead>
            <tbody>
                @foreach ($users as $row)
                    @php($u = $row['user'])
                    <tr wire:key="u{{ $u->id }}" class="{{ $u->is_active ? '' : 'opacity-60' }}">
                        <td class="font-medium">{{ $u->name }} @if ($u->id === $me)<span class="text-xs text-a-muted">(bạn)</span>@endif</td>
                        <td class="text-a-muted">{{ $u->email }}</td>
                        <td>
                            @if ($u->id === $me)
                                {{ $u->roleLabel() }}
                            @else
                                <select wire:change="setRole({{ $u->id }}, $event.target.value)" class="a-input w-32 py-1" aria-label="Vai trò của {{ $u->name }}">
                                    @foreach (\App\Models\User::ROLES as $key => $label)<option value="{{ $key }}" @selected($u->role === $key)>{{ $label }}</option>@endforeach
                                </select>
                            @endif
                        </td>
                        <td>{!! $u->is_active ? '<span class="a-badge bg-green-100 text-green-800">Đang hoạt động</span>' : '<span class="a-badge bg-a-soft text-a-muted">Đã khoá</span>' !!}</td>
                        <td class="text-right">{{ \App\Support\Money::vnd($row['revenue']) }}</td>
                        <td class="text-right">{{ $row['orders'] }}</td>
                        <td class="text-right whitespace-nowrap">
                            @if ($u->id !== $me)
                                <span x-data="{ open: false, pw: '' }" class="inline-flex items-center gap-1">
                                    <button type="button" x-show="!open" @click="open = true" class="text-a-muted hover:text-a-ink">Đặt lại mật khẩu</button>
                                    <span x-show="open" x-cloak class="inline-flex gap-1">
                                        <input type="password" x-model="pw" minlength="8" class="a-input w-36 py-1" placeholder="Mật khẩu mới" aria-label="Mật khẩu mới">
                                        <button type="button" class="a-btn py-1" @click="$wire.resetPassword({{ $u->id }}, pw).then(() => { pw = ''; open = false })">Lưu</button>
                                    </span>
                                </span>
                                <button type="button" wire:click="toggleActive({{ $u->id }})"
                                    @if ($u->is_active) wire:confirm="Khoá tài khoản {{ $u->name }}? Người này sẽ bị đăng xuất ngay." @endif
                                    class="ml-3 {{ $u->is_active ? 'text-a-down' : 'text-accent' }} hover:underline">{{ $u->is_active ? 'Khoá' : 'Mở khoá' }}</button>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <form wire:submit="create" class="a-card grid max-w-3xl gap-3 p-4 sm:grid-cols-2">
        <h2 class="font-semibold sm:col-span-2">Thêm nhân sự</h2>
        <div>
            <label class="a-label" for="s-name">Tên</label>
            <input id="s-name" wire:model="name" class="a-input" maxlength="100">
            @error('name')<p class="a-error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="a-label" for="s-email">Email đăng nhập</label>
            <input id="s-email" type="email" wire:model="email" class="a-input" autocomplete="off">
            @error('email')<p class="a-error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="a-label" for="s-password">Mật khẩu (ít nhất 8 ký tự)</label>
            <input id="s-password" type="password" wire:model="password" class="a-input" autocomplete="new-password">
            @error('password')<p class="a-error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="a-label" for="s-role">Vai trò</label>
            <select id="s-role" wire:model="role" class="a-input">
                @foreach (\App\Models\User::ROLES as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
            </select>
            @error('role')<p class="a-error">{{ $message }}</p>@enderror
        </div>
        <div class="sm:col-span-2"><button class="a-btn a-btn-primary">Tạo tài khoản</button></div>
    </form>
</div>
