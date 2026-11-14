<?php

namespace Tests\Feature\Admin;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_record_stores_actor_and_values(): void
    {
        $owner = User::factory()->owner()->create();
        $this->actingAs($owner, 'admin');

        $log = ActivityLog::record('price.update', $owner, ['price' => 1], ['price' => 2]);

        $this->assertSame($owner->id, $log->user_id);
        $this->assertSame(['price' => 2], $log->fresh()->new);
        $this->assertTrue($log->fresh()->subject->is($owner));
    }

    public function test_only_owner_sees_log_page(): void
    {
        $this->actingAs(User::factory()->create(), 'admin')->get('/admin/nhat-ky')->assertForbidden();

        $owner = User::factory()->owner()->create(['name' => 'Chủ Hà']);
        $this->actingAs($owner, 'admin');
        ActivityLog::record('user.lock', $owner);

        $this->get('/admin/nhat-ky?action=hack')->assertOk()->assertSee('Khoá tài khoản')->assertSee('Chủ Hà');
        $this->get('/admin/nhat-ky?action=price.update')->assertOk()->assertDontSee('Khoá tài khoản</td>', false);
    }
}
