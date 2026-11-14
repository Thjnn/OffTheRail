<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\OrderService;
use Illuminate\Database\Seeder;

/** Tài khoản khách demo (0900000001 / matkhau123) có sẵn đơn, địa chỉ và món yêu thích. */
class DemoCustomerSeeder extends Seeder
{
    public function run(OrderService $orders): void
    {
        $customer = Customer::updateOrCreate(['phone' => '0900000001'], [
            'name' => 'Khách Demo',
            'email' => 'khach@demo.test',
            'password' => 'matkhau123',
            'registered_at' => now()->subDays(30),
        ]);

        if ($customer->orders()->exists()) {
            return;
        }

        $address = ['province_code' => '01', 'ward_code' => '00004', 'street' => '12 Tràng Tiền'];
        $customer->addresses()->create($address + ['name' => $customer->name, 'phone' => $customer->phone, 'is_default' => true]);

        $variants = ProductVariant::where('stock', '>', 2)
            ->whereIn('product_id', Product::visible()->select('id'))
            ->orderBy('id')->take(2)->get();

        $contact = $address + ['name' => $customer->name, 'phone' => $customer->phone, 'email' => $customer->email];

        $orders->placeWebOrder([$variants[0]->id => 1], $contact, 'cod', $customer);
        $cancelled = $orders->placeWebOrder([$variants[1]->id => 2], $contact, 'cod', $customer);
        $orders->cancel($cancelled, 'Khách đổi ý');

        $customer->wishlist()->syncWithoutDetaching(Product::visible()->latest('published_at')->take(2)->pluck('id'));
    }
}
