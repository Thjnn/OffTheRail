<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\CustomerTable;
use App\Models\Customer;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_by_any_phone_format_and_sort(): void
    {
        $owner = User::factory()->owner()->create();
        $big = $this->placeOrder(['name' => 'Khách Lớn', 'phone' => '0901234567']);
        app(OrderService::class)->markPaid($big, $owner);
        $this->placeOrder(['name' => 'Khách Nhỏ', 'phone' => '0911111111']);

        $c = Livewire::actingAs($owner, 'admin')->test(CustomerTable::class);
        $this->assertBigFirst($c->html());

        $c->set('q', '+84 901 234 567')->assertSee('Khách Lớn')->assertDontSee('Khách Nhỏ')
            ->set('q', 'nhỏ')->assertSee('Khách Nhỏ')->assertDontSee('Khách Lớn')
            ->set('q', '')->set('sort', 'hack')->assertOk();
        $this->assertBigFirst($c->html());
    }

    // assertSeeInOrder của Livewire sau khi cập nhật so trên JSON (chữ có dấu bị mã hoá), nên so trên html().
    private function assertBigFirst(string $html): void
    {
        $this->assertNotFalse($big = mb_strpos($html, 'Khách Lớn'));
        $this->assertNotFalse($small = mb_strpos($html, 'Khách Nhỏ'));
        $this->assertLessThan($small, $big);
    }

    public function test_detail_shows_all_orders_including_before_registration(): void
    {
        $old = $this->placeOrder(['phone' => '0901234567']);
        $customer = Customer::firstWhere('phone', '0901234567');
        $customer->update(['password' => 'matkhau123', 'registered_at' => now()->addMinute()]);

        $this->actingAs(User::factory()->owner()->create(), 'admin')
            ->get('/admin/khach-hang/'.$customer->id)->assertOk()->assertSee($old->code)->assertSee('0901234567');
    }

    public function test_staff_forbidden(): void
    {
        $this->placeOrder();
        $this->actingAs(User::factory()->create(), 'admin');

        $this->get('/admin/khach-hang')->assertForbidden();
        $this->get('/admin/khach-hang/'.Customer::first()->id)->assertForbidden();
        Livewire::actingAs(User::factory()->create(), 'admin')->test(CustomerTable::class)->assertForbidden();
    }
}
