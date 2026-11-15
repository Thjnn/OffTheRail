<?php

namespace App\Providers;

use App\Http\Middleware\EnsureStaffActive;
use App\Models\Category;
use App\Models\User;
use App\Support\ShopSettings;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ShopSettings::class);
    }

    public function boot(): void
    {
        // Chỉ hai vai trò cố định. Route admin chạy `auth:admin` trước nên Gate nhận đúng User.
        Gate::define('owner', fn ($user) => $user instanceof User && $user->isOwner());

        // Request Livewire (bấm bán, sửa kho… từ tab đang mở) cũng phải qua bước kiểm tra tài khoản bị khoá.
        Livewire::addPersistentMiddleware([EnsureStaffActive::class]);

        ResetPassword::toMailUsing(fn ($notifiable, string $token) => (new MailMessage)
            ->subject('Đặt lại mật khẩu · '.shop('name'))
            ->greeting('Xin chào '.$notifiable->name.',')
            ->line('Bạn (hoặc ai đó) vừa yêu cầu đặt lại mật khẩu tài khoản tại '.shop('name').'.')
            ->action('Đặt lại mật khẩu', url('/tai-khoan/dat-lai-mat-khau/'.$token.'?email='.urlencode((string) $notifiable->email)))
            ->line('Link có hiệu lực 60 phút. Nếu bạn không yêu cầu, cứ bỏ qua email này.')
            ->salutation(shop('name')));

        // Danh mục cho menu: gắn vào layout, các partial được include sẽ thừa hưởng biến này.
        View::composer('layouts.shop', function ($view) {
            $view->with('navCategories', Category::orderBy('position')->orderBy('id')->get());
        });
    }
}
