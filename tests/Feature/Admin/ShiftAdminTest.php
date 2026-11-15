<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\ShiftClose;
use App\Models\Shift;
use App\Models\User;
use App\Services\OrderService;
use App\Services\ShiftService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ShiftAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_close_is_blind_owner_sees_difference(): void
    {
        $staff = User::factory()->create();
        $owner = User::factory()->owner()->create();
        app(ShiftService::class)->open(100000, $staff);
        $v = $this->variant(['stock' => 5, 'price' => 50000]);
        app(OrderService::class)->placePosOrder([$v->id => 1], 0, 'cash', 50000, null, null, $staff);

        Livewire::actingAs($staff, 'admin')->test(ShiftClose::class)
            ->assertSee('100.000đ')->assertDontSee('150.000đ')
            ->set('countedCash', 140000)->call('close')
            ->assertHasNoErrors()->assertSee('Đã chốt ca')->assertDontSee('-10.000đ');

        $this->assertSame(-10000, Shift::first()->difference());

        $this->actingAs($owner, 'admin')->get('/admin/ca')->assertOk()
            ->assertSee('140.000đ')->assertSee('150.000đ')->assertSee('-10.000đ');
    }

    public function test_owner_sees_expected_before_and_difference_after_closing(): void
    {
        $owner = User::factory()->owner()->create();
        app(ShiftService::class)->open(100000, $owner);

        Livewire::actingAs($owner, 'admin')->test(ShiftClose::class)
            ->assertSee('Tiền mặt kỳ vọng')
            ->set('countedCash', 105000)->call('close')
            ->assertSee('+5.000đ');
    }

    public function test_no_open_shift_message(): void
    {
        $this->actingAs(User::factory()->create(), 'admin')->get('/admin/ban-tai-quay/chot-ca')
            ->assertOk()->assertSee('Không có ca nào đang mở');
    }

    public function test_counted_cash_required(): void
    {
        $staff = User::factory()->create();
        app(ShiftService::class)->open(0, $staff);

        Livewire::actingAs($staff, 'admin')->test(ShiftClose::class)->call('close')->assertHasErrors('countedCash');
        $this->assertNotNull(app(ShiftService::class)->current());
    }

    public function test_staff_cannot_open_shift_history(): void
    {
        $this->actingAs(User::factory()->create(), 'admin')->get('/admin/ca')->assertForbidden();
        $this->actingAs(User::factory()->create(), 'admin')->get('/admin')->assertDontSee('Ca bán hàng');
        $this->actingAs(User::factory()->owner()->create(), 'admin')->get('/admin')->assertSee('Ca bán hàng');
    }
}
