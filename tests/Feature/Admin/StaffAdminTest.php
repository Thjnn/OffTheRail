<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\StaffManager;
use App\Models\ActivityLog;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class StaffAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_creates_staff_who_can_log_in(): void
    {
        Livewire::actingAs(User::factory()->owner()->create(), 'admin')->test(StaffManager::class)
            ->set('name', 'Nhân viên Mới')->set('email', 'moi@shop.test')->set('password', 'matkhau123')->set('role', 'staff')
            ->call('create')->assertHasNoErrors();

        $this->assertSame('staff', User::firstWhere('email', 'moi@shop.test')->role);
        auth('admin')->logout();
        $this->post('/admin/dang-nhap', ['email' => 'moi@shop.test', 'password' => 'matkhau123'])->assertRedirect('/admin');
    }

    public function test_create_validation(): void
    {
        $owner = User::factory()->owner()->create(['email' => 'taken@shop.test']);

        Livewire::actingAs($owner, 'admin')->test(StaffManager::class)
            ->set('name', '')->set('email', 'taken@shop.test')->set('password', 'short')->set('role', 'boss')
            ->call('create')->assertHasErrors(['name', 'email', 'password', 'role']);
    }

    public function test_cannot_lock_or_demote_self_or_last_owner(): void
    {
        $a = User::factory()->owner()->create();
        User::factory()->owner()->inactive()->create(); // owner B đã khoá: A là owner hoạt động duy nhất

        Livewire::actingAs($a, 'admin')->test(StaffManager::class)
            ->call('setRole', $a->id, 'staff')->assertHasErrors('staff')
            ->call('toggleActive', $a->id)->assertHasErrors('staff');

        $this->assertTrue($a->fresh()->isOwner());
        $this->assertTrue($a->fresh()->is_active);
    }

    public function test_cannot_demote_other_owner_when_it_leaves_no_active_owner(): void
    {
        $locked = User::factory()->owner()->inactive()->create();
        $a = User::factory()->owner()->create();
        $b = User::factory()->owner()->create();

        $c = Livewire::actingAs($a, 'admin')->test(StaffManager::class)
            ->call('setRole', $b->id, 'staff')->assertHasNoErrors();
        $this->assertFalse($b->fresh()->isOwner());

        // Mở khoá rồi hạ quyền owner đang khoá thì vẫn còn A: được.
        $c->call('setRole', $locked->id, 'staff')->assertHasNoErrors();
    }

    public function test_role_change_and_lock_are_logged(): void
    {
        $owner = User::factory()->owner()->create();
        $staff = User::factory()->create();

        Livewire::actingAs($owner, 'admin')->test(StaffManager::class)
            ->call('setRole', $staff->id, 'owner')
            ->call('toggleActive', $staff->id)
            ->call('toggleActive', $staff->id);

        $this->assertSame(['user.role', 'user.lock', 'user.unlock'], ActivityLog::orderBy('id')->pluck('action')->all());
        $this->assertSame(['role' => 'staff'], ActivityLog::first()->old);
        $this->assertTrue($staff->fresh()->is_active);
    }

    public function test_reset_password(): void
    {
        $staff = User::factory()->create();

        Livewire::actingAs(User::factory()->owner()->create(), 'admin')->test(StaffManager::class)
            ->call('resetPassword', $staff->id, '123')->assertHasErrors('newPassword')
            ->call('resetPassword', $staff->id, 'matkhaumoi1')->assertHasNoErrors();

        $this->assertTrue(Hash::check('matkhaumoi1', $staff->fresh()->password));
    }

    public function test_lists_staff_sales_of_last_30_days(): void
    {
        $staff = User::factory()->create(['name' => 'Bạn Bán Giỏi']);
        app(OrderService::class)->placeShopeeOrder('S1', [$this->variant(['stock' => 3])->id => 1], 123000, $staff);

        Livewire::actingAs(User::factory()->owner()->create(), 'admin')->test(StaffManager::class)
            ->assertSee('Bạn Bán Giỏi')->assertSee('123.000đ');
    }

    public function test_staff_forbidden(): void
    {
        $staff = User::factory()->create();

        $this->actingAs($staff, 'admin')->get('/admin/nhan-vien')->assertForbidden();
        Livewire::actingAs($staff, 'admin')->test(StaffManager::class)->assertForbidden();
    }
}
