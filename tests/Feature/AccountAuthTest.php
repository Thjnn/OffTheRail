<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Support\Cart;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AccountAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_registers_and_logs_in(): void
    {
        $this->post('/tai-khoan/dang-ky', ['name' => 'An', 'phone' => '+84 901 234 567', 'password' => 'matkhau123', 'password_confirmation' => 'matkhau123'])
            ->assertRedirect('/tai-khoan');

        $c = Customer::where('phone', '0901234567')->first();
        $this->assertAuthenticatedAs($c);
        $this->assertNotNull($c->registered_at);
    }

    public function test_register_claims_guest_record_but_refuses_existing_account(): void
    {
        $guest = Customer::forPhone('0901234567', 'Khách cũ');
        $data = ['name' => 'An', 'phone' => '0901234567', 'password' => 'matkhau123', 'password_confirmation' => 'matkhau123'];

        $this->post('/tai-khoan/dang-ky', $data)->assertRedirect('/tai-khoan');
        $this->assertSame(1, Customer::count());
        $this->assertTrue($guest->fresh()->hasAccount());

        auth('web')->logout();
        $this->post('/tai-khoan/dang-ky', $data)->assertSessionHasErrors('phone');
    }

    public function test_login_merges_guest_cart(): void
    {
        $c = Customer::factory()->registered()->create(['phone' => '0901234567']);
        $v = $this->variant(['stock' => 5]);
        app(Cart::class)->add($v->id, 2);

        $this->post('/tai-khoan/dang-nhap', ['phone' => '0901 234 567', 'password' => 'matkhau123'])->assertRedirect('/tai-khoan');

        $this->assertAuthenticatedAs($c);
        $this->assertEquals([$v->id => 2], $c->fresh()->cart);
    }

    public function test_wrong_password_and_guest_record_cannot_login_then_throttled(): void
    {
        Customer::factory()->registered()->create(['phone' => '0901234567']);
        Customer::forPhone('0911111111', 'Vãng lai');

        $this->post('/tai-khoan/dang-nhap', ['phone' => '0911111111', 'password' => ''])->assertSessionHasErrors();
        foreach (range(1, 5) as $_) {
            $this->post('/tai-khoan/dang-nhap', ['phone' => '0901234567', 'password' => 'sai'])->assertSessionHasErrors('phone');
        }
        $this->post('/tai-khoan/dang-nhap', ['phone' => '0901234567', 'password' => 'matkhau123'])->assertSessionHasErrors('phone');

        $this->assertGuest('web');
    }

    public function test_password_reset_flow(): void
    {
        Notification::fake();
        $c = Customer::factory()->registered()->create(['email' => 'an@x.io']);
        $same = 'Nếu email này có tài khoản, shop đã gửi link đặt lại mật khẩu.';

        $this->post('/tai-khoan/quen-mat-khau', ['email' => 'an@x.io'])->assertSessionHas('status', $same);
        $this->post('/tai-khoan/quen-mat-khau', ['email' => 'khong-co@x.io'])->assertSessionHas('status', $same);

        Notification::assertSentTo($c, ResetPassword::class, function ($n) use ($c) {
            $this->post('/tai-khoan/dat-lai-mat-khau', ['token' => $n->token, 'email' => 'an@x.io', 'password' => 'moi12345678', 'password_confirmation' => 'moi12345678'])
                ->assertRedirect('/tai-khoan/dang-nhap');

            return Hash::check('moi12345678', $c->fresh()->password);
        });
    }

    public function test_reset_mail_is_vietnamese(): void
    {
        $c = Customer::factory()->registered()->create(['email' => 'an@x.io']);
        $mail = (new ResetPassword('tok'))->toMail($c);

        $this->assertStringContainsString('Đặt lại mật khẩu', $mail->subject);
        $this->assertStringContainsString('/tai-khoan/dat-lai-mat-khau/tok', $mail->actionUrl);
    }

    public function test_logout_and_guest_redirect(): void
    {
        $this->get('/tai-khoan')->assertRedirect('/tai-khoan/dang-nhap');

        $this->actingAs(Customer::factory()->registered()->create())->post('/tai-khoan/dang-xuat')->assertRedirect('/');
        $this->assertGuest('web');
    }

    public function test_auth_pages_render(): void
    {
        foreach (['/tai-khoan/dang-nhap', '/tai-khoan/dang-ky', '/tai-khoan/quen-mat-khau', '/tai-khoan/dat-lai-mat-khau/abc?email=a@x.io'] as $url) {
            $this->get($url)->assertOk();
        }
    }
}
