<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerTest extends TestCase
{
    use RefreshDatabase;

    public function test_for_phone_merges_formats_into_one_record(): void
    {
        $a = Customer::forPhone('0901234567', 'An');
        $b = Customer::forPhone('+84 901 234 567', 'Tên khác');

        $this->assertTrue($a->is($b));
        $this->assertSame('An', $b->name);
        $this->assertFalse($b->hasAccount());
    }

    public function test_web_guard_authenticates_customers(): void
    {
        $this->assertSame(Customer::class, auth('web')->getProvider()->getModel());
        $this->assertSame(User::class, auth('admin')->getProvider()->getModel());
    }
}
