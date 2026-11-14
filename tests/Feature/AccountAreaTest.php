<?php

namespace Tests\Feature;

use App\Livewire\AddressBook;
use App\Livewire\Checkout;
use App\Livewire\WishlistButton;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\Product;
use App\Support\Cart;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class AccountAreaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->locations();
    }

    private function fillAddress(Testable $c): Testable
    {
        return $c->set('name', 'An')->set('phone', '0901234567')
            ->set('provinceCode', '01')->set('wardCode', '00004')->set('street', '12 Phố Một');
    }

    public function test_account_lists_only_orders_since_registration(): void
    {
        // Người lạ từng đặt hàng bằng SĐT này trước khi tài khoản được đăng ký
        $old = $this->placeOrder(['phone' => '0901234567', 'name' => 'Chủ cũ', 'street' => '12 Ngõ Riêng']);
        $old->forceFill(['created_at' => now()->subDays(3)])->save();

        $this->post('/tai-khoan/dang-ky', ['name' => 'Người mới', 'phone' => '0901234567', 'password' => 'matkhau123', 'password_confirmation' => 'matkhau123']);
        $new = $this->placeOrder(['phone' => '0901234567'], auth('web')->user());

        $this->get('/tai-khoan')->assertOk()->assertSee($new->code)->assertDontSee($old->code);
        $this->get("/tai-khoan/don-hang/{$new->code}")->assertOk();
        $this->get("/tai-khoan/don-hang/{$old->code}")->assertNotFound();
    }

    public function test_cannot_open_another_customers_order(): void
    {
        $other = $this->placeOrder(['phone' => '0911111111']);

        $this->actingAs(Customer::factory()->registered()->create())
            ->get("/tai-khoan/don-hang/{$other->code}")->assertNotFound();
    }

    public function test_address_book_crud_and_default_rules(): void
    {
        $c = Customer::factory()->registered()->create();
        $this->actingAs($c);
        $book = fn () => $this->fillAddress(Livewire::test(AddressBook::class)->call('create'));

        $book()->call('save')->assertHasNoErrors();
        $book()->set('street', '34 Phố Hai')->call('save');

        [$first, $second] = $c->addresses()->reorder('id')->get()->all();
        $this->assertTrue($first->is_default);
        $this->assertFalse($second->is_default);

        Livewire::test(AddressBook::class)->call('delete', $first->id);
        $this->assertTrue($second->fresh()->is_default);

        $book()->set('provinceCode', '01')->set('wardCode', '26734')->call('save')->assertHasErrors('wardCode');
    }

    public function test_address_book_blocks_other_customers_rows_and_limit(): void
    {
        $mine = Customer::factory()->registered()->create();
        $theirs = CustomerAddress::factory()->create();
        $this->actingAs($mine);

        Livewire::test(AddressBook::class)->call('delete', $theirs->id)->assertNotFound();
        $this->assertModelExists($theirs);

        CustomerAddress::factory()->count(10)->for($mine)->create();
        $this->fillAddress(Livewire::test(AddressBook::class)->call('create'))->call('save')->assertHasErrors();
        $this->assertSame(10, $mine->addresses()->count());
    }

    public function test_checkout_prefills_and_saves_address(): void
    {
        $c = Customer::factory()->registered()->create(['name' => 'An', 'phone' => '0901234567']);
        CustomerAddress::factory()->for($c)->create(['province_code' => '01', 'ward_code' => '00004', 'street' => '5 Hàng Bông', 'is_default' => true]);
        $this->actingAs($c);
        $v = $this->variant(['stock' => 5]);
        app(Cart::class)->add($v->id);

        Livewire::test(Checkout::class)
            ->assertSet('name', 'An')->assertSet('phone', '0901234567')->assertSet('street', '5 Hàng Bông')->assertSet('wardCode', '00004')
            ->set('street', '9 Phố Mới')->set('saveAddress', true)->call('place');

        $this->assertSame($c->id, Order::first()->customer_id);
        $this->assertDatabaseHas('customer_addresses', ['customer_id' => $c->id, 'street' => '9 Phố Mới']);
    }

    public function test_checkout_cannot_use_someone_elses_address(): void
    {
        $theirs = CustomerAddress::factory()->create(['street' => 'Nhà người khác']);
        $this->actingAs(Customer::factory()->registered()->create());
        app(Cart::class)->add($this->variant(['stock' => 5])->id);

        Livewire::test(Checkout::class)->call('useAddress', $theirs->id)->assertNotFound();
    }

    public function test_wishlist_toggle_and_page(): void
    {
        $p = Product::factory()->create(['name' => 'Áo Thích', 'is_active' => true, 'published_at' => now()]);

        Livewire::test(WishlistButton::class, ['productId' => $p->id])->call('toggle')->assertRedirect('/tai-khoan/dang-nhap');

        $c = Customer::factory()->registered()->create();
        $this->actingAs($c);
        Livewire::test(WishlistButton::class, ['productId' => $p->id])->call('toggle')->assertSeeHtml('aria-pressed="true"');
        $this->get('/tai-khoan/yeu-thich')->assertOk()->assertSee('Áo Thích');

        Livewire::test(WishlistButton::class, ['productId' => $p->id])->call('toggle');
        $this->assertSame(0, $c->wishlist()->count());
    }

    public function test_account_pages_render(): void
    {
        $this->actingAs(Customer::factory()->registered()->create());

        foreach (['/tai-khoan', '/tai-khoan/dia-chi', '/tai-khoan/yeu-thich'] as $url) {
            $this->get($url)->assertOk();
        }
    }
}
