<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Concerns\OwnerOnly;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Component;
use Livewire\Livewire;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_sent_to_admin_login(): void
    {
        $this->get('/admin')->assertRedirect('/admin/dang-nhap');
        $this->get('/tai-khoan')->assertRedirect('/tai-khoan/dang-nhap');
        $this->get('/admin/dang-nhap')->assertOk()->assertSee('Đăng nhập');
    }

    public function test_staff_logs_in_with_admin_guard_only(): void
    {
        $user = User::factory()->create(['email' => 'nv@shop.test', 'password' => 'matkhau123']);

        $this->post('/admin/dang-nhap', ['email' => 'nv@shop.test', 'password' => 'matkhau123'])->assertRedirect('/admin');

        $this->assertAuthenticatedAs($user, 'admin');
        $this->assertGuest('web');
    }

    public function test_inactive_user_gets_generic_error(): void
    {
        User::factory()->inactive()->create(['email' => 'nv@shop.test', 'password' => 'matkhau123']);

        $this->from('/admin/dang-nhap')->post('/admin/dang-nhap', ['email' => 'nv@shop.test', 'password' => 'matkhau123'])
            ->assertSessionHasErrors(['email' => 'Email hoặc mật khẩu không đúng.']);
        $this->assertGuest('admin');
    }

    public function test_user_locked_mid_session_is_logged_out(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'admin')->get('/admin')->assertOk();

        $user->update(['is_active' => false]);

        $this->get('/admin')->assertRedirect('/admin/dang-nhap');
        $this->assertGuest('admin');
    }

    public function test_customer_session_does_not_open_admin(): void
    {
        $this->actingAs(Customer::factory()->registered()->create(), 'web')
            ->get('/admin')->assertRedirect('/admin/dang-nhap');
    }

    public function test_logged_in_staff_is_redirected_away_from_login(): void
    {
        $this->actingAs(User::factory()->create(), 'admin')->get('/admin/dang-nhap')->assertRedirect('/admin');
    }

    public function test_logout(): void
    {
        $this->actingAs(User::factory()->create(), 'admin')->post('/admin/dang-xuat')->assertRedirect('/admin/dang-nhap');
        $this->assertGuest('admin');
    }

    public function test_owner_gate_and_menu(): void
    {
        $this->actingAs(User::factory()->create(), 'admin')->get('/admin')->assertOk()->assertDontSee('Nhân viên</span>', false);
        $this->actingAs(User::factory()->owner()->create(), 'admin')->get('/admin')->assertOk()->assertSee('Nhân viên</span>', false);
    }

    public function test_owner_only_trait_blocks_staff_livewire_calls(): void
    {
        $component = new class extends Component
        {
            use OwnerOnly;

            public function render(): string
            {
                return '<div>ok</div>';
            }
        };

        Livewire::actingAs(User::factory()->owner()->create(), 'admin')->test($component::class)->assertOk();
        Livewire::actingAs(User::factory()->create(), 'admin')->test($component::class)->assertForbidden();
    }
}
