<?php

namespace App\Livewire\Admin\Concerns;

/**
 * Livewire gọi boot*() ở cả lần mount lẫn mọi request cập nhật, nên trait này chặn được
 * cả việc nhân viên gửi tay request /livewire/update tới component của chủ shop.
 */
trait OwnerOnly
{
    public function bootOwnerOnly(): void
    {
        abort_unless(auth('admin')->user()?->isOwner(), 403);
    }
}
