<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\PendingBadge;
use App\Models\Order;
use App\Models\User;
use App\Notifications\NewWebOrder;
use App\Support\ShopSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class NewOrderAlertTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_sent_only_when_configured(): void
    {
        Notification::fake();

        $this->placeOrder();
        Notification::assertNothingSent();

        app(ShopSettings::class)->set('notify_email', 'shop@demo.test');
        $order = $this->placeOrder(['phone' => '0911111111']);

        Notification::assertSentOnDemand(NewWebOrder::class, function (NewWebOrder $n, array $channels, AnonymousNotifiable $notifiable) use ($order) {
            return $notifiable->routes['mail'] === 'shop@demo.test' && $n->order->is($order);
        });
    }

    public function test_mail_content(): void
    {
        $order = $this->placeOrder();
        $mail = (new NewWebOrder($order))->toMail(new AnonymousNotifiable);

        $this->assertStringContainsString($order->code, $mail->subject);
        $this->assertSame(url('/admin/don-hang/'.$order->code), $mail->actionUrl);
    }

    public function test_mail_failure_does_not_break_checkout(): void
    {
        app(ShopSettings::class)->set('notify_email', 'shop@demo.test');
        Notification::shouldReceive('route')->andThrow(new RuntimeException('SMTP chết'));

        $order = $this->placeOrder();

        $this->assertTrue(Order::whereKey($order->id)->exists());
    }

    public function test_badge_counts_and_dispatches_on_new_order(): void
    {
        $this->placeOrder();

        $c = Livewire::actingAs(User::factory()->create(), 'admin')->test(PendingBadge::class)
            ->assertSee('1')->assertNotDispatched('new-web-order');

        $this->placeOrder(['phone' => '0911111111']);

        $c->call('$refresh')->assertSee('2')->assertDispatched('new-web-order', count: 2);
    }

    public function test_badge_hidden_when_nothing_pending(): void
    {
        Livewire::actingAs(User::factory()->create(), 'admin')->test(PendingBadge::class)->assertDontSeeHtml('a-badge');
    }

    public function test_sidebar_shows_badge(): void
    {
        $this->placeOrder();

        $this->actingAs(User::factory()->create(), 'admin')->get('/admin/don-hang')->assertOk()->assertSeeLivewire(PendingBadge::class);
    }
}
