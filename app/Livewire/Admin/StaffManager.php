<?php

namespace App\Livewire\Admin;

use App\Livewire\Admin\Concerns\OwnerOnly;
use App\Models\ActivityLog;
use App\Models\User;
use App\Support\SalesReport;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class StaffManager extends Component
{
    use OwnerOnly;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $role = 'staff';

    public string $newPassword = '';

    public function create(): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'max:100'],
            'role' => ['required', Rule::in(array_keys(User::ROLES))],
        ], [], ['role' => 'vai trò']);

        User::create($data + ['is_active' => true]);
        $this->reset('name', 'email', 'password', 'role');
        session()->flash('status', 'Đã tạo tài khoản '.$data['email'].'.');
    }

    public function setRole(int $userId, string $role): void
    {
        abort_unless(array_key_exists($role, User::ROLES), 422);
        $user = User::findOrFail($userId);

        if ($user->role === $role) {
            return;
        }

        $this->guard($user, fn () => $user->role = $role);

        $old = $user->getOriginal('role');
        $user->save();
        ActivityLog::record('user.role', $user, ['role' => $old], ['role' => $role]);
    }

    public function toggleActive(int $userId): void
    {
        $user = User::findOrFail($userId);
        $this->guard($user, fn () => $user->is_active = ! $user->is_active);

        $user->save();
        ActivityLog::record($user->is_active ? 'user.unlock' : 'user.lock', $user);
    }

    public function resetPassword(int $userId, string $password): void
    {
        $this->newPassword = $password;
        $this->validate(['newPassword' => ['required', 'string', 'min:8', 'max:100']], [], ['newPassword' => 'mật khẩu mới']);

        User::findOrFail($userId)->update(['password' => $password]);
        $this->newPassword = '';
        session()->flash('status', 'Đã đặt lại mật khẩu.');
    }

    /** Áp thay đổi lên bản nháp rồi kiểm tra: không tự hạ/khoá mình, luôn còn ít nhất một owner đang hoạt động. */
    private function guard(User $user, callable $change): void
    {
        if ($user->is(auth('admin')->user())) {
            throw ValidationException::withMessages(['staff' => 'Bạn không thể tự đổi quyền hoặc khoá tài khoản của mình.']);
        }

        $change();

        $activeOwners = User::where('role', 'owner')->where('is_active', true)->whereKeyNot($user->id)->count()
            + ($user->isOwner() && $user->is_active ? 1 : 0);

        if ($activeOwners === 0) {
            $user->refresh();
            throw ValidationException::withMessages(['staff' => 'Cửa hàng cần ít nhất một chủ shop đang hoạt động.']);
        }
    }

    public function render(): View
    {
        $from = now()->subDays(29);

        return view('livewire.admin.staff-manager', [
            'users' => User::orderByDesc('is_active')->orderBy('role')->orderBy('name')->get()->map(function (User $u) use ($from) {
                $report = new SalesReport($from, now(), $u->id);

                return ['user' => $u, 'revenue' => $report->revenue(), 'orders' => $report->orderCount()];
            }),
            'me' => auth('admin')->id(),
        ]);
    }
}
