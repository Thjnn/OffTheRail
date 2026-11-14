<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/** Nhật ký thao tác nhạy cảm: ai, lúc nào, giá trị cũ/mới. */
class ActivityLog extends Model
{
    public const LABELS = [
        'price.update' => 'Sửa giá',
        'stock.adjust' => 'Sửa kho',
        'order.cancel' => 'Huỷ đơn',
        'order.return' => 'Hoàn đơn',
        'user.role' => 'Đổi quyền',
        'user.lock' => 'Khoá tài khoản',
        'user.unlock' => 'Mở khoá tài khoản',
    ];

    protected $guarded = [];

    protected function casts(): array
    {
        return ['old' => 'array', 'new' => 'array'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public static function record(string $action, ?Model $subject = null, array $old = [], array $new = []): self
    {
        return static::create([
            'user_id' => auth('admin')->id(),
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'old' => $old ?: null,
            'new' => $new ?: null,
        ]);
    }

    public function label(): string
    {
        return self::LABELS[$this->action] ?? $this->action;
    }

    /** Tên ngắn của đối tượng bị tác động, ví dụ "Đơn DHABC123". */
    public function subjectLabel(): string
    {
        $s = $this->subject;

        return match (true) {
            $s instanceof Order => 'Đơn '.$s->code,
            $s instanceof ProductVariant => 'Biến thể '.$s->sku,
            $s instanceof Product => 'Sản phẩm '.$s->name,
            $s instanceof User => 'Nhân sự '.$s->name,
            $s === null && $this->subject_id !== null => '(đã xoá)',
            default => '',
        };
    }
}
