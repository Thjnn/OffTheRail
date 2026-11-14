<?php

namespace App\Models;

use App\Support\Phone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use InvalidArgumentException;

/**
 * Khách hàng. SĐT là khoá hợp nhất: đơn web, đơn quầy và tài khoản cùng SĐT dùng chung một bản ghi.
 * Bản ghi không có mật khẩu là khách vãng lai (chưa đăng ký).
 */
class Customer extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = ['phone', 'name', 'email', 'password', 'cart', 'registered_at'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['password' => 'hashed', 'cart' => 'array', 'registered_at' => 'datetime'];
    }

    public const MAX_ADDRESSES = 10;

    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class)->orderByDesc('is_default')->orderByDesc('id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function wishlist(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'wishlists')->withTimestamps();
    }

    /**
     * Đơn hiện trong tài khoản: chỉ các đơn từ lúc đăng ký trở đi. Đơn đặt bằng SĐT này trước đó
     * (có thể là của người dùng số cũ) chỉ xem được qua tra cứu mã đơn + SĐT.
     */
    public function visibleOrders(): HasMany
    {
        return $this->hasMany(Order::class)
            ->where('created_at', '>=', $this->registered_at ?? now()->addCentury())
            ->latest('id');
    }

    public function hasAccount(): bool
    {
        return $this->password !== null;
    }

    public static function forPhone(string $phone, string $name): self
    {
        $normalized = Phone::normalize($phone)
            ?? throw new InvalidArgumentException("Số điện thoại không hợp lệ: {$phone}");

        return static::firstOrCreate(['phone' => $normalized], ['name' => $name]);
    }
}
