<?php

namespace Tests\Feature;

use App\Support\ShopSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactTest extends TestCase
{
    use RefreshDatabase;

    public function test_stores_valid_message(): void
    {
        $this->post('/lien-he', ['name' => 'An', 'phone' => '0901234567', 'message' => 'Còn size M không ạ?'])
            ->assertRedirect('/lien-he')->assertSessionHas('status');

        $this->assertDatabaseHas('contact_messages', ['name' => 'An', 'phone' => '0901234567']);
    }

    public function test_requires_phone_or_email(): void
    {
        $this->post('/lien-he', ['name' => 'An', 'message' => 'Xin chào shop'])
            ->assertSessionHasErrors('phone');

        $this->assertDatabaseCount('contact_messages', 0);
    }

    public function test_rejects_bad_phone_short_message_and_missing_name(): void
    {
        $this->post('/lien-he', ['name' => '', 'phone' => '12345', 'message' => 'Hi'])
            ->assertSessionHasErrors(['name', 'phone', 'message']);

        $this->post('/lien-he', ['name' => 'An', 'email' => 'khong-phai-email', 'message' => 'Xin chào shop'])
            ->assertSessionHasErrors('email');

        $this->assertDatabaseCount('contact_messages', 0);
    }

    public function test_honeypot_silently_drops(): void
    {
        $this->post('/lien-he', ['name' => 'Bot', 'email' => 'b@x.io', 'message' => 'spam spam', 'website' => 'http://x'])
            ->assertRedirect('/lien-he')->assertSessionHas('status');

        $this->assertDatabaseCount('contact_messages', 0);
    }

    public function test_rate_limited_after_five_per_hour(): void
    {
        $data = ['name' => 'An', 'email' => 'a@x.io', 'message' => 'Hỏi chút ạ'];
        foreach (range(1, 5) as $_) {
            $this->post('/lien-he', $data);
        }

        $this->post('/lien-he', $data)->assertSessionHasErrors();
        $this->assertDatabaseCount('contact_messages', 5);
    }

    public function test_contact_page_shows_form_and_shop_channels(): void
    {
        app(ShopSettings::class)->set('hotline', '0900 000 000');

        $this->get('/lien-he')->assertOk()
            ->assertSee('name="message"', false)
            ->assertSee('0900 000 000');
    }

    public function test_about_page_renders(): void
    {
        $this->get('/gioi-thieu')->assertOk()->assertSee(shop('name'));
    }
}
