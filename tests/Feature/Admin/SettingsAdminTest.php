<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\SettingsForm;
use App\Models\Setting;
use App\Models\User;
use App\Support\ShippingFee;
use App\Support\VietQr;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class SettingsAdminTest extends TestCase
{
    use RefreshDatabase;

    private function form()
    {
        return Livewire::actingAs(User::factory()->owner()->create(), 'admin')->test(SettingsForm::class);
    }

    public function test_loads_current_values(): void
    {
        $this->form()->assertSet('name', 'Off the Rail')->assertSet('sizes', 'XS, S, M, L, XL')->assertSet('staff_max_discount_percent', 10);
    }

    public function test_settings_change_storefront(): void
    {
        $this->form()->set('name', 'Shop Mới')->set('accent', '#AA3300')->set('hotline', '+84 901 234 567')
            ->call('save')->assertHasNoErrors();

        $this->assertSame('0901234567', shop('hotline'));
        $this->get('/')->assertOk()->assertSee('Shop Mới')->assertSee('#AA3300');
    }

    public function test_logo_upload_and_remove(): void
    {
        Storage::fake('public');

        $c = $this->form()->set('logo', UploadedFile::fake()->image('logo.png', 200, 80))->call('save')->assertHasNoErrors();
        $path = shop('logo');
        Storage::disk('public')->assertExists($path);

        $c->call('removeLogo');
        $this->assertNull(shop('logo'));
        Storage::disk('public')->assertMissing($path);

        $this->form()->set('logo', UploadedFile::fake()->create('logo.svg', 1, 'image/svg+xml'))->call('save')->assertHasErrors('logo');
    }

    public function test_bank_settings_enable_transfer_at_checkout(): void
    {
        $this->assertFalse(VietQr::isConfigured());

        $this->form()->set('bank_bin', '970436')->set('bank_account_no', '0123456789')->set('bank_account_name', 'Nguyễn Văn A')
            ->call('save')->assertHasNoErrors();

        $this->assertTrue(VietQr::isConfigured());
        $this->assertSame('NGUYEN VAN A', shop('bank_account_name'));
    }

    public function test_rejects_bad_values(): void
    {
        $this->form()
            ->set('accent', 'blue')->set('hotline', '123')->set('zalo_url', 'javascript:x')
            ->set('bank_bin', '970436')->set('bank_account_no', '')->set('sizes', ' , ')
            ->set('staff_max_discount_percent', 150)->set('notify_email', 'abc')
            ->call('save')
            ->assertHasErrors(['accent', 'hotline', 'zalo_url', 'bank_account_no', 'sizes', 'staff_max_discount_percent', 'notify_email']);

        $this->assertSame(0, Setting::count());
    }

    public function test_shipping_fee_rows_feed_shipping_calc(): void
    {
        $this->locations();

        $this->form()->set('shipping_fees', [['province_code' => '79', 'fee' => 20000]])->set('shipping_fee_default', 35000)
            ->call('save')->assertHasNoErrors();

        $this->assertSame(20000, ShippingFee::for(100000, '79'));
        $this->assertSame(35000, ShippingFee::for(100000, '01'));
    }

    public function test_duplicate_or_unknown_province_rejected(): void
    {
        $this->locations();

        $this->form()->set('shipping_fees', [['province_code' => '79', 'fee' => 1], ['province_code' => '79', 'fee' => 2]])
            ->call('save')->assertHasErrors('shipping_fees.1.province_code');
        $this->form()->set('shipping_fees', [['province_code' => '99', 'fee' => 1]])
            ->call('save')->assertHasErrors('shipping_fees.0.province_code');
    }

    public function test_sizes_are_trimmed_and_deduplicated(): void
    {
        $this->form()->set('sizes', 'S, M , M, 29,30')->call('save')->assertHasNoErrors();

        $this->assertSame(['S', 'M', '29', '30'], shop('sizes'));
    }

    public function test_staff_forbidden(): void
    {
        $staff = User::factory()->create();

        $this->actingAs($staff, 'admin')->get('/admin/cai-dat')->assertForbidden();
        Livewire::actingAs($staff, 'admin')->test(SettingsForm::class)->assertForbidden();
    }
}
