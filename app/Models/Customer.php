<?php

namespace App\Models;

use App\Support\Phone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
