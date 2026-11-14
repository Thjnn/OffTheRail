# Off the Rail v2: Kế hoạch 2, giỏ hàng, thanh toán, đơn web, tài khoản khách

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Khách đặt được hàng trên web (COD hoặc chuyển khoản VietQR), tra cứu được đơn, và có tài khoản tuỳ chọn (địa chỉ đã lưu, lịch sử đơn, yêu thích).

**Architecture:** Giỏ hàng là một lớp `Cart` lưu `[variantId => qty]` trong session (khách vãng lai) hoặc cột `customers.cart` (đã đăng nhập). Mọi thay đổi kho đi qua `Inventory` (câu `UPDATE ... WHERE stock >= qty` + một dòng `stock_movements`), mọi đơn web đi qua `OrderService` trong một transaction. Livewire dùng cho giỏ, thanh toán, sổ địa chỉ; phần còn lại là controller + Blade.

**Tech Stack:** như Kế hoạch 1 (Laravel 13, Livewire 4, Tailwind 4, GSAP, PHPUnit SQLite in-memory). Không thêm gói mới.

**Spec:** `docs/superpowers/specs/2026-11-13-offtherail-laravel-design.md` (mục 5.1, 6, 7, 8, 10)

## Global Constraints

- Làm trên nhánh mới `plan-2` tách từ `plan-1` (nhánh `plan-1` chưa merge vào `main`).
- PHP: `/e/laragon/bin/php/php-8.3.30-Win32-vs16-x64/php.exe`. Composer: `php /e/laragon/bin/composer/composer.phar`.
- MySQL của Laragon chưa chạy ở Kế hoạch 1; test chạy trên SQLite in-memory. Không tự khởi động MySQL.
- Tiền VND là số nguyên `unsignedBigInteger`, hiển thị bằng `Money::vnd()`.
- Toàn bộ chữ cho khách và mọi thông báo lỗi validate bằng **tiếng Việt**.
- SĐT luôn chuẩn hoá về `0xxxxxxxxx` bằng `Phone::normalize()` trước khi lưu hoặc so sánh.
- Giá, phí ship, tổng tiền luôn tính lại trên server từ DB. Không nhận con số nào từ trình duyệt.
- Kho chỉ đổi qua `Inventory::take()` / `Inventory::putBack()`.
- Link trong view tới route của task sau thì dùng `url('/...')`, không dùng `route()` (ruling Task 5 của Kế hoạch 1).
- Giao diện theo token và class có sẵn (`btn`, `btn-primary`, `btn-outline`, `icon-btn`, `rounded-chip`, màu `bg/surface/ink/muted/line/accent/sale`). Hiệu ứng GSAP chỉ trong nhánh `prefers-reduced-motion: no-preference`.
- Mỗi task kết thúc bằng một commit.

**Không làm ở kế hoạch này** (để Kế hoạch 3): admin xác nhận tiền / chuyển trạng thái đơn, email báo đơn mới cho shop, cập nhật `total_spent` / `orders_count`, sửa cài đặt ngân hàng bằng giao diện. Email xác nhận đơn cho khách không có trong spec nên không làm.

## Review Focus

1. **Giỏ cũ gặp dữ liệu mới** (sản phẩm bị ẩn, biến thể bị xoá, kho giảm dưới số đã chọn, giá đổi): giỏ và trang thanh toán báo rõ món nào, không lỗi 500, giá hiển thị là giá hiện tại. *Test ở Task 3 và Task 5.*
2. **Đăng ký tài khoản bằng SĐT của người khác đã từng đặt hàng**: tài khoản mới không xem được đơn và địa chỉ có trước lúc đăng ký. *Test ở Task 9.*
3. **Dò mã đơn**: trang đặt xong không hiện tên, SĐT, địa chỉ; tra cứu sai mã hay sai SĐT trả cùng một câu báo lỗi; cả hai có giới hạn tần suất. *Test ở Task 6 và Task 7.*
4. **Bấm "Đặt hàng" hai lần**: chỉ tạo một đơn, kho chỉ trừ một lần. *Test ở Task 6.*
5. **Dữ liệu thanh toán rác** (phường không thuộc tỉnh đã chọn, mã tỉnh lạ, phương thức thanh toán lạ, chọn chuyển khoản khi shop chưa cấu hình ngân hàng, SĐT dạng `+84 901 234 567`): cái sai bị từ chối bằng lỗi validate, SĐT viết kiểu khác vẫn nhận. *Test ở Task 1 và Task 6.*

---

### Task 1: Khách hàng, guard và chuẩn hoá SĐT

**Files:**
- Create: `database/migrations/2026_11_15_000000_create_customers_table.php`, `app/Models/Customer.php`, `database/factories/CustomerFactory.php`, `app/Support/Phone.php`
- Modify: `config/auth.php`
- Test: `tests/Unit/PhoneTest.php`, `tests/Feature/CustomerTest.php`

**Interfaces:**
- Bảng `customers`: `phone` string(10) unique, `name`, `email` nullable unique, `password` nullable, `cart` json nullable, `registered_at` timestamp nullable, `total_spent` unsignedBigInteger default 0, `orders_count` unsignedInteger default 0, `remember_token`, timestamps.
- `Customer extends Illuminate\Foundation\Auth\User` (trait `HasFactory`, `Notifiable`). Cast: `password` hashed, `cart` array, `registered_at` datetime. Hidden: `password`, `remember_token`.
  - `hasAccount(): bool`: `password !== null`.
  - `static forPhone(string $phone, string $name): self`: `firstOrCreate` theo SĐT đã chuẩn hoá; `name` chỉ ghi khi tạo mới. Ném `InvalidArgumentException` nếu SĐT không hợp lệ.
- `Phone::normalize(?string $raw): ?string`: bỏ mọi ký tự không phải số, đầu `84` (11 số) đổi thành `0`, trả về chuỗi nếu khớp `^0\d{9}$`, ngược lại `null`.
- `config/auth.php`: guard `web` dùng provider `customers` (eloquent, `Customer`); thêm guard `admin` (session, provider `users`) cho Kế hoạch 3; `passwords.customers` (provider `customers`, bảng `password_reset_tokens`, expire 60, throttle 60); `defaults.passwords` = `customers`.
- `CustomerFactory`: `phone` = `'09' . fake()->unique()->numerify('########')`, `password` null. State `registered()`: `password` = `'matkhau123'`, `registered_at` = now.

- [ ] **Step 1: Viết test**

```php
// tests/Unit/PhoneTest.php
public function test_normalizes_common_formats(): void {
    foreach (['0901234567', '+84 901 234 567', '84901234567', '0901.234.567', ' 090-123-4567 '] as $raw) {
        $this->assertSame('0901234567', Phone::normalize($raw), $raw);
    }
}
public function test_rejects_invalid(): void {
    foreach (['12345', '', null, '09012345678', '1901234567', 'abc'] as $raw) {
        $this->assertNull(Phone::normalize($raw));
    }
}
// tests/Feature/CustomerTest.php (RefreshDatabase)
public function test_for_phone_merges_formats_into_one_record(): void {
    $a = Customer::forPhone('0901234567', 'An');
    $b = Customer::forPhone('+84 901 234 567', 'Tên khác');
    $this->assertTrue($a->is($b));
    $this->assertSame('An', $b->name);
    $this->assertFalse($b->hasAccount());
}
public function test_web_guard_authenticates_customers(): void {
    $this->assertSame(Customer::class, auth('web')->getProvider()->getModel());
    $this->assertSame(User::class, auth('admin')->getProvider()->getModel());
}
```

- [ ] **Step 2:** `php artisan test --filter="PhoneTest|CustomerTest"`. Expected: FAIL (class chưa có).
- [ ] **Step 3:** Viết code theo Interfaces.
- [ ] **Step 4:** `php artisan test`. Expected: toàn bộ PASS.
- [ ] **Step 5:** Commit `feat: customers table, customer/admin guards, phone normalization`.

---

### Task 2: Tỉnh/Phường 2 cấp và phí ship

**Files:**
- Create: `database/migrations/2026_11_15_000100_create_location_tables.php`, `app/Models/Province.php`, `app/Models/Ward.php`, `database/seeders/ProvinceSeeder.php`, `database/seeders/data/vn-units.json`, `app/Support/ShippingFee.php`
- Modify: `database/seeders/DatabaseSeeder.php`, `config/shop.php`
- Test: `tests/Feature/LocationTest.php`

**Interfaces:**
- `provinces`: `code` string(5) primary, `name`. `wards`: `code` string(8) primary, `province_code` (index), `name`. Hai model khoá chuỗi, không timestamps. `Province::wards()` (hasMany, sắp theo `name`).
- `vn-units.json`: `[{"code":"01","name":"Thành phố Hà Nội","wards":[{"code":"00004","name":"Phường Ba Đình"}, ...]}, ...]`, dữ liệu sau sáp nhập 01/07/2025 (34 tỉnh/thành).
- `ProvinceSeeder`: `upsert` theo `code`, mỗi lần 500 dòng. Gọi từ `DatabaseSeeder`.
- `config/shop.php` thêm: `shipping_fee_default` = 30000, `shipping_fees` = `[]` (mảng `mã tỉnh => phí`).
- `ShippingFee::for(int $subtotal, ?string $provinceCode): int`: 0 nếu `$subtotal >= shop('free_shipping_threshold')`; ngược lại phí của tỉnh nếu có trong `shop('shipping_fees')` và là số nguyên ≥ 0; còn lại `shop('shipping_fee_default')`.

- [ ] **Step 1: Viết test**

```php
public function test_seeds_two_level_units(): void {
    $this->seed(ProvinceSeeder::class);
    $this->seed(ProvinceSeeder::class); // chạy lại không lỗi, không nhân đôi
    $this->assertSame(34, Province::count());
    $this->assertGreaterThan(3000, Ward::count());
    $this->assertSame(0, Ward::whereNotIn('province_code', Province::pluck('code'))->count());
    $this->assertNotNull(Province::where('name', 'like', '%Hà Nội%')->first());
}
public function test_shipping_fee(): void {
    $this->assertSame(0, ShippingFee::for(500000, '01'));
    $this->assertSame(30000, ShippingFee::for(499000, '01'));
    $this->assertSame(30000, ShippingFee::for(499000, null));
    app(ShopSettings::class)->set('shipping_fees', ['79' => 20000, '01' => 'abc']);
    $this->assertSame(20000, ShippingFee::for(100000, '79'));
    $this->assertSame(30000, ShippingFee::for(100000, '01'));
    app(ShopSettings::class)->set('shipping_fees', 'hỏng');
    $this->assertSame(30000, ShippingFee::for(100000, '79'));
}
```

- [ ] **Step 2:** `php artisan test --filter=LocationTest`. Expected: FAIL.
- [ ] **Step 3:** Tải dữ liệu 2 cấp từ nguồn công khai (thử `https://provinces.open-api.vn/api/v2/?depth=2` trước), chuyển về đúng dạng `vn-units.json` ở trên. Nếu nguồn này không trả 34 tỉnh thì tìm nguồn khác có dữ liệu sau 01/07/2025 và ghi ruling tên nguồn. Không tự bịa tên phường.
- [ ] **Step 4:** Viết migration, model, seeder, `ShippingFee`.
- [ ] **Step 5:** `php artisan test`. Expected: PASS.
- [ ] **Step 6:** Commit `feat: two-level provinces/wards and shipping fee`.

---

### Task 3: Lớp giỏ hàng

**Files:**
- Create: `app/Support/Cart.php`, `app/Support/CartLine.php`
- Test: `tests/Feature/CartTest.php`

**Interfaces:**
- Consumes: `Customer.cart` (Task 1), `Product::isVisible()`, `ProductVariant`.
- Nơi lưu: `auth('web')->user()` có thì đọc/ghi `customers.cart`, không thì `session('cart', [])`. Dạng dữ liệu `[variantId => qty]`.
- `Cart`:
  - `const MAX_QTY = 99`
  - `add(int $variantId, int $qty = 1): bool`: `false` (không đổi gì) nếu biến thể không tồn tại, sản phẩm không visible hoặc `stock = 0`. Số lượng cộng dồn, kẹp trong `1..min(stock, MAX_QTY)`.
  - `setQty(int $variantId, int $qty): void`: `<= 0` thì xoá dòng; kẹp như trên.
  - `remove(int $variantId): void`, `clear(): void`
  - `items(): array`: mảng `[variantId => qty]` thô.
  - `lines(): Collection<int, CartLine>`: eager load `variant.product.images`, `variant.color.images`. Dòng nào biến thể đã mất hoặc sản phẩm không còn visible thì bỏ và xoá luôn khỏi nơi lưu. **Không** tự giảm số lượng khi kho thiếu.
  - `count(): int` (tổng số lượng), `subtotal(): int`, `isEmpty(): bool`, `hasUnavailable(): bool`.
  - `mergeGuestCartInto(Customer $customer): void`: cộng số lượng trong session vào `customers.cart` (kẹp `MAX_QTY`), rồi xoá key session.
- `CartLine` (readonly): `ProductVariant $variant`, `int $qty`; `unitPrice(): int` (`variant.price ?? product.price`), `total(): int`, `isAvailable(): bool` (`stock >= qty`), `imageUrl(): ?string` (ảnh đầu của màu, không có thì ảnh bìa sản phẩm).

- [ ] **Step 1: Viết test** (helper `variant(array $attrs = [], array $product = [])` tạo sản phẩm visible + màu + biến thể bằng factory)

```php
public function test_add_accumulates_and_clamps_to_stock(): void {
    $v = $this->variant(['stock' => 3]);
    $cart = app(Cart::class);
    $this->assertTrue($cart->add($v->id, 2));
    $this->assertTrue($cart->add($v->id, 5));
    $this->assertSame([$v->id => 3], $cart->items());
}
public function test_add_rejects_missing_hidden_and_sold_out(): void {
    $cart = app(Cart::class);
    $this->assertFalse($cart->add(999));
    $this->assertFalse($cart->add($this->variant(['stock' => 0])->id));
    $this->assertFalse($cart->add($this->variant(['stock' => 5], ['is_active' => false])->id));
    $this->assertTrue($cart->isEmpty());
}
public function test_totals_use_current_db_price_and_variant_override(): void {
    $a = $this->variant(['stock' => 5, 'price' => null], ['price' => 200000]);
    $b = $this->variant(['stock' => 5, 'price' => 150000], ['price' => 999000]);
    $cart = app(Cart::class);
    $cart->add($a->id, 2); $cart->add($b->id);
    $a->product->update(['price' => 250000]); // giá đổi sau khi bỏ giỏ
    $this->assertSame(650000, $cart->subtotal());
    $this->assertSame(3, $cart->count());
}
public function test_lines_drop_deleted_and_hidden_but_flag_short_stock(): void {
    $gone = $this->variant(['stock' => 5]); $hidden = $this->variant(['stock' => 5]); $short = $this->variant(['stock' => 5]);
    $cart = app(Cart::class);
    foreach ([$gone, $hidden, $short] as $v) { $cart->add($v->id, 3); }
    $gone->delete(); $hidden->product->update(['is_active' => false]); $short->update(['stock' => 1]);
    $lines = $cart->lines();
    $this->assertSame([$short->id], $lines->map(fn ($l) => $l->variant->id)->all());
    $this->assertFalse($lines->first()->isAvailable());
    $this->assertTrue($cart->hasUnavailable());
    $this->assertSame([$short->id => 3], $cart->items());
}
public function test_set_qty_zero_removes(): void {
    $v = $this->variant(['stock' => 5]);
    $cart = app(Cart::class);
    $cart->add($v->id); $cart->setQty($v->id, 0);
    $this->assertTrue($cart->isEmpty());
}
public function test_guest_cart_merges_into_account(): void {
    $v = $this->variant(['stock' => 10]); $w = $this->variant(['stock' => 10]);
    $customer = Customer::factory()->registered()->create(['cart' => [$v->id => 1]]);
    app(Cart::class)->add($v->id, 2); app(Cart::class)->add($w->id);
    $this->actingAs($customer);
    app(Cart::class)->mergeGuestCartInto($customer);
    $this->assertEquals([$v->id => 3, $w->id => 1], $customer->fresh()->cart);
    $this->assertEquals([$v->id => 3, $w->id => 1], app(Cart::class)->items());
    $this->assertNull(session('cart'));
}
```

- [ ] **Step 2:** `php artisan test --filter=CartTest`. Expected: FAIL.
- [ ] **Step 3:** Viết `Cart` và `CartLine`.
- [ ] **Step 4:** `php artisan test`. Expected: PASS.
- [ ] **Step 5:** Commit `feat: cart stored in session or customer account`.

---

### Task 4: Giao diện giỏ hàng (ngăn kéo + trang)

**Files:**
- Create: `app/Livewire/CartPanel.php`, `resources/views/livewire/cart-panel.blade.php`, `resources/views/cart/index.blade.php`
- Modify: `routes/web.php` (`GET /gio-hang`, tên `cart`), `resources/views/layouts/shop.blade.php`, `resources/views/partials/header.blade.php`, `resources/views/partials/mobile-nav.blade.php`, `resources/js/motion.js`
- Test: `tests/Feature/CartPanelTest.php`

**Interfaces:**
- Consumes: `Cart` (Task 3); event `add-to-cart {variantId, buyNow}` do `VariantPicker` phát.
- `CartPanel`: `public bool $drawer = false`, `public bool $open = false`, `public ?string $notice = null`.
  - `#[On('add-to-cart')] addToCart($variantId, $buyNow = false)`: gọi `Cart::add((int) $variantId)`. Thất bại → `$notice = 'Sản phẩm này vừa hết hàng.'`, mở ngăn kéo. Thành công và `$buyNow` → `redirect('/thanh-toan')`. Thành công → `$open = true`.
  - `#[On('open-cart')] show()`, `close()`
  - `setQty($variantId, $qty)`, `remove($variantId)`: ép kiểu int rồi gọi `Cart`.
  - Sau mỗi thay đổi: `$this->dispatch('cart-updated', count: $cart->count())`.
- View dùng chung cho hai chế độ. Mỗi dòng: ảnh, tên, màu · size, đơn giá, nút − / +, thành tiền, nút xoá; dòng thiếu hàng hiện "Chỉ còn N" hoặc "Hết hàng". Dưới cùng: tạm tính, dòng "Mua thêm {x} để được freeship" (ẩn khi đã đủ ngưỡng), nút "Thanh toán" tới `/thanh-toan` (disable khi `hasUnavailable()`), giỏ rỗng hiện "Giỏ hàng đang trống" + link `/san-pham`.
- Chế độ `drawer`: tấm trượt từ phải, phủ nền, đóng bằng Esc/bấm nền, phần tử gốc có `data-cart-drawer`, `x-data` gắn `$wire.entangle('open')`.
- Layout: render `<livewire:cart-panel :drawer="true" />` trừ khi `request()->routeIs('cart', 'checkout')`.
- Header: thay `<span data-cart-icon>` bằng `<a href="{{ url('/gio-hang') }}" data-cart-icon>` có `x-data="{ count: {{ app(Cart::class)->count() }} }"`, `@cart-updated.window="count = $event.detail.count"`, huy hiệu số (`x-show="count > 0"`), bấm thì mở ngăn kéo nếu trang có `[data-cart-drawer]`, không thì đi theo link.
- `motion.js`: khi có event `cart-updated`, `[data-cart-icon]` nảy nhẹ (scale 1 → 1.25 → 1, 0,35s).

- [ ] **Step 1: Viết test**

```php
public function test_add_to_cart_event_adds_and_opens_drawer(): void {
    $v = $this->variant(['stock' => 5]);
    Livewire::test(CartPanel::class, ['drawer' => true])
        ->dispatch('add-to-cart', variantId: $v->id, buyNow: false)
        ->assertSet('open', true)->assertSee($v->product->name)
        ->assertDispatched('cart-updated', count: 1);
}
public function test_buy_now_redirects_to_checkout(): void {
    $v = $this->variant(['stock' => 5]);
    Livewire::test(CartPanel::class, ['drawer' => true])
        ->dispatch('add-to-cart', variantId: $v->id, buyNow: true)->assertRedirect('/thanh-toan');
}
public function test_sold_out_or_garbage_variant_shows_notice(): void {
    Livewire::test(CartPanel::class, ['drawer' => true])
        ->dispatch('add-to-cart', variantId: 'abc', buyNow: true)
        ->assertNoRedirect()->assertSee('vừa hết hàng');
}
public function test_qty_and_remove(): void {
    $v = $this->variant(['stock' => 5], ['price' => 100000]);
    app(Cart::class)->add($v->id);
    Livewire::test(CartPanel::class)
        ->call('setQty', $v->id, 3)->assertSee('300.000đ')
        ->call('remove', $v->id)->assertSee('Giỏ hàng đang trống');
}
public function test_cart_page_and_header_badge(): void {
    $v = $this->variant(['stock' => 5]);
    app(Cart::class)->add($v->id, 2);
    $this->get('/gio-hang')->assertOk()->assertSee($v->product->name)
        ->assertSee('count: 2', false)->assertDontSee('data-cart-drawer', false);
    $this->get('/')->assertSee('data-cart-drawer', false);
}
public function test_short_stock_line_blocks_checkout_button(): void {
    $v = $this->variant(['stock' => 5]);
    app(Cart::class)->add($v->id, 3);
    $v->update(['stock' => 1]);
    Livewire::test(CartPanel::class)->assertSee('Chỉ còn 1')->assertSeeHtml('data-checkout disabled');
}
```

- [ ] **Step 2:** `php artisan test --filter=CartPanelTest`. Expected: FAIL.
- [ ] **Step 3:** Viết component, view, sửa layout/header/mobile-nav/motion.js.
- [ ] **Step 4:** `php artisan test && npm run build`. Expected: PASS, build không lỗi.
- [ ] **Step 5:** Commit `feat: cart drawer and cart page`.

---

### Task 5: Đơn hàng, trừ kho, tự huỷ đơn quá hạn

**Files:**
- Create: `database/migrations/2026_11_15_000200_create_order_tables.php`, `app/Enums/OrderStatus.php`, `app/Models/{Order,OrderItem,OrderStatusLog,StockMovement}.php`, `database/factories/OrderFactory.php`, `app/Support/Inventory.php`, `app/Services/OrderService.php`, `app/Exceptions/OutOfStockException.php`, `app/Console/Commands/CancelExpiredOrders.php`
- Modify: `routes/console.php` (`Schedule::command('orders:cancel-expired')->hourly()`)
- Test: `tests/Feature/OrderServiceTest.php`

**Interfaces:**
- Consumes: `Customer::forPhone()`, `ShippingFee::for()`, `Province`, `Ward`.
- Bảng (theo spec mục 7):
  - `orders`: `code` unique, `channel` (web|pos|shopee), `external_code` nullable, unique (`channel`, `external_code`), `customer_id` nullable FK, `customer_name`, `customer_phone` (index), `customer_email` nullable, `address` json nullable, `status`, `payment_method` (cod|bank_transfer|cash|qr), `payment_status` (unpaid|paid|refunded) default unpaid, `subtotal`, `discount` default 0, `shipping_fee` default 0, `total`, `staff_id` nullable FK users, `shift_id` unsignedBigInteger nullable (chưa có FK, bảng `shifts` ở Kế hoạch 4), `tracking_code` nullable, `note` text nullable, `paid_at` nullable, timestamps.
  - `order_items`: `order_id` FK cascade, `variant_id` nullable FK null-on-delete, `name`, `color`, `size`, `sku`, `unit_price`, `qty`.
  - `order_status_logs`: `order_id` FK cascade, `from` nullable, `to`, `user_id` nullable, `note` nullable, timestamps.
  - `stock_movements`: `variant_id` FK cascade, `change` integer, `reason` (sale|cancel|return|adjust|import), `order_id` nullable, `user_id` nullable, `note` nullable, timestamps.
- `OrderStatus` (enum chuỗi): `Pending`, `Confirmed`, `Shipping`, `Completed`, `Cancelled`, `Returned`; `label(): string` lần lượt "Chờ xác nhận", "Đã xác nhận", "Đang giao", "Hoàn thành", "Đã huỷ", "Đã hoàn".
- `Order`: cast `status` → `OrderStatus`, `address` → array, `paid_at` → datetime. Quan hệ `items()`, `statusLogs()` (cũ → mới), `customer()`. `getRouteKeyName()` = `'code'`. `const TRANSFER_EXPIRY_HOURS = 24`.
  - `static generateCode(): string`: `'DH'` + 6 ký tự ngẫu nhiên từ `23456789ABCDEFGHJKLMNPQRSTUVWXYZ`, lặp lại nếu trùng. (Chỉ chữ và số vì mã đơn là nội dung chuyển khoản.)
  - `isAwaitingTransfer(): bool`: `bank_transfer` + `unpaid` + `Pending`.
  - `addressLine(): string`: "số nhà, phường, tỉnh".
- `Inventory::take(int $variantId, int $qty, string $reason, ?Order $order = null): bool`: chạy `UPDATE product_variants SET stock = stock - :qty WHERE id = :id AND stock >= :qty`; 0 dòng → `false`; thành công → ghi `stock_movements` (`change = -$qty`).
- `Inventory::putBack(int $variantId, int $qty, string $reason, ?Order $order = null): void`: `increment` + ghi movement (`change = +$qty`). Biến thể đã bị xoá thì bỏ qua.
- `OrderService`:
  - `placeWebOrder(array $items, array $contact, string $paymentMethod, ?Customer $account = null): Order`
    - `$items` = `[variantId => qty]`. `$contact` = `name, phone, email?, province_code, ward_code, street, note?` (đã validate).
    - Trong `DB::transaction`: nạp biến thể + sản phẩm từ DB, tạo đơn (`channel=web`, `Pending`, `unpaid`), `take()` từng dòng. Dòng nào thiếu hàng, mất biến thể hoặc sản phẩm không visible → gom tên rồi ném `OutOfStockException` (rollback hết).
    - `customer_id` = `$account` nếu có, không thì `Customer::forPhone()`.
    - `address` = `{province_code, province, ward_code, ward, street}` (tên lấy từ DB).
    - Ghi log `null → pending`. Rỗng `$items` → `InvalidArgumentException`.
  - `cancel(Order $order, ?string $note = null, ?int $userId = null): bool`: trong transaction, `lockForUpdate` đọc lại đơn; chỉ huỷ khi đang `Pending` hoặc `Confirmed` (khác thì trả `false`, không làm gì); `putBack` từng dòng với reason `cancel`; đổi trạng thái; ghi log.
- `OutOfStockException`: `public readonly array $names` ("Tên sản phẩm (Màu / Size)").
- Lệnh `orders:cancel-expired`: huỷ các đơn `isAwaitingTransfer` có `created_at` cũ hơn 24h, note "Quá 24 giờ chưa nhận được chuyển khoản". In số đơn đã huỷ.

- [ ] **Step 1: Viết test** (`contact()` trả mảng hợp lệ với tỉnh/phường tạo bằng `Province::create`/`Ward::create`)

```php
public function test_places_order_with_server_side_totals(): void {
    $v = $this->variant(['stock' => 5, 'price' => null], ['price' => 200000]);
    $order = app(OrderService::class)->placeWebOrder([$v->id => 2], $this->contact(['phone' => '+84 901 234 567']), 'cod');
    $this->assertMatchesRegularExpression('/^DH[2-9A-HJ-NP-Z]{6}$/', $order->code);
    $this->assertSame([400000, 30000, 430000], [$order->subtotal, $order->shipping_fee, $order->total]);
    $this->assertSame('0901234567', $order->customer_phone);
    $this->assertSame(OrderStatus::Pending, $order->status);
    $this->assertSame(3, $v->fresh()->stock);
    $this->assertDatabaseHas('stock_movements', ['variant_id' => $v->id, 'change' => -2, 'reason' => 'sale', 'order_id' => $order->id]);
    $this->assertDatabaseHas('order_status_logs', ['order_id' => $order->id, 'from' => null, 'to' => 'pending']);
    $this->assertSame($v->sku, $order->items->first()->sku);
    $this->assertSame('0901234567', $order->customer->phone);
}
public function test_free_shipping_at_threshold(): void {
    $v = $this->variant(['stock' => 5], ['price' => 250000]);
    $order = app(OrderService::class)->placeWebOrder([$v->id => 2], $this->contact(), 'cod');
    $this->assertSame(0, $order->shipping_fee);
}
public function test_last_unit_cannot_be_sold_twice(): void {
    $v = $this->variant(['stock' => 1]);
    app(OrderService::class)->placeWebOrder([$v->id => 1], $this->contact(), 'cod');
    try {
        app(OrderService::class)->placeWebOrder([$v->id => 1], $this->contact(['phone' => '0911111111']), 'cod');
        $this->fail('phải ném OutOfStockException');
    } catch (OutOfStockException $e) {
        $this->assertStringContainsString($v->product->name, $e->names[0]);
    }
    $this->assertSame(0, $v->fresh()->stock);
    $this->assertSame(1, Order::count());
}
public function test_one_short_line_rolls_back_whole_order(): void {
    $ok = $this->variant(['stock' => 5]); $short = $this->variant(['stock' => 1]); $hidden = $this->variant(['stock' => 5], ['is_active' => false]);
    try {
        app(OrderService::class)->placeWebOrder([$ok->id => 2, $short->id => 2, $hidden->id => 1, 999 => 1], $this->contact(), 'cod');
        $this->fail();
    } catch (OutOfStockException $e) { $this->assertCount(3, $e->names); }
    $this->assertSame(5, $ok->fresh()->stock);
    $this->assertSame(0, Order::count());
    $this->assertSame(0, StockMovement::count());
}
public function test_cancel_restores_stock_once(): void {
    $v = $this->variant(['stock' => 5]);
    $order = app(OrderService::class)->placeWebOrder([$v->id => 2], $this->contact(), 'cod');
    $this->assertTrue(app(OrderService::class)->cancel($order, 'khách đổi ý'));
    $this->assertFalse(app(OrderService::class)->cancel($order->fresh()));
    $this->assertSame(5, $v->fresh()->stock);
    $this->assertSame(OrderStatus::Cancelled, $order->fresh()->status);
    $this->assertDatabaseHas('stock_movements', ['variant_id' => $v->id, 'change' => 2, 'reason' => 'cancel']);
    $this->assertDatabaseHas('order_status_logs', ['order_id' => $order->id, 'from' => 'pending', 'to' => 'cancelled', 'note' => 'khách đổi ý']);
}
public function test_expired_transfer_orders_are_cancelled(): void {
    $v = $this->variant(['stock' => 10]);
    $svc = app(OrderService::class);
    $old = $svc->placeWebOrder([$v->id => 1], $this->contact(), 'bank_transfer');
    $paid = $svc->placeWebOrder([$v->id => 1], $this->contact(), 'bank_transfer');
    $cod = $svc->placeWebOrder([$v->id => 1], $this->contact(), 'cod');
    $fresh = $svc->placeWebOrder([$v->id => 1], $this->contact(), 'bank_transfer');
    Order::whereKey([$old->id, $paid->id, $cod->id])->update(['created_at' => now()->subHours(25)]);
    $paid->update(['payment_status' => 'paid']);
    $fresh->forceFill(['created_at' => now()->subHours(23)])->save();
    $this->artisan('orders:cancel-expired')->assertSuccessful();
    $this->assertSame(OrderStatus::Cancelled, $old->fresh()->status);
    foreach ([$paid, $cod, $fresh] as $o) { $this->assertSame(OrderStatus::Pending, $o->fresh()->status); }
    $this->assertSame(7, $v->fresh()->stock);
}
```

- [ ] **Step 2:** `php artisan test --filter=OrderServiceTest`. Expected: FAIL.
- [ ] **Step 3:** Viết migration, enum, model, `Inventory`, `OrderService`, lệnh, lịch chạy.
- [ ] **Step 4:** `php artisan test`. Expected: PASS.
- [ ] **Step 5:** Commit `feat: web orders with guarded stock decrement and auto-cancel`.

---

### Task 6: Thanh toán và trang đặt xong (VietQR)

**Files:**
- Create: `app/Livewire/Concerns/HasAddressFields.php`, `resources/views/partials/address-fields.blade.php`, `app/Livewire/Checkout.php`, `resources/views/livewire/checkout.blade.php`, `resources/views/checkout/index.blade.php`, `app/Support/VietQr.php`, `app/Http/Controllers/OrderController.php` (`success`), `resources/views/orders/success.blade.php`
- Modify: `routes/web.php`, `config/shop.php`
- Test: `tests/Feature/CheckoutTest.php`

**Interfaces:**
- Consumes: `Cart`, `OrderService::placeWebOrder()`, `ShippingFee::for()`, `Phone::normalize()`.
- `config/shop.php` thêm `bank_bin` = '', `bank_account_no` = '', `bank_account_name` = ''.
- `VietQr::isConfigured(): bool` (cả `bank_bin` và `bank_account_no` khác rỗng). `VietQr::url(Order $order): ?string`: `https://img.vietqr.io/image/{bin}-{no}-compact2.png?amount={total}&addInfo={code}&accountName={name}` (đã `urlencode`), `null` nếu chưa cấu hình. Ghi chú `// ponytail:` rằng ảnh QR phụ thuộc dịch vụ img.vietqr.io, muốn bỏ phụ thuộc thì tự sinh chuỗi EMVCo + thư viện QR.
- Trait `HasAddressFields`: `public string $provinceCode = '', $wardCode = '', $street = ''`; `updatedProvinceCode()` đặt lại `$wardCode = ''`; `addressRules(): array` (`provinceCode` exists `provinces,code`; `wardCode` exists trong `wards` **với đúng `province_code`**; `street` required max 190); `#[Computed] provinces()`, `#[Computed] wards()`. Partial `address-fields` render 2 `<select>` + ô số nhà/tên đường.
- `Checkout` (dùng trait): `public string $name = '', $phone = '', $email = '', $note = '', $paymentMethod = 'cod', $website = ''` (`website` là ô bẫy).
  - `mount()`: giỏ rỗng → `redirect('/gio-hang')`.
  - `place()`:
    1. `website` có giá trị → `redirect('/')`, không tạo đơn.
    2. Validate: `name` required max 120; `phone` phải `Phone::normalize()` được ("Số điện thoại gồm 10 số, bắt đầu bằng 0."); `email` nullable email; `note` max 500; `paymentMethod` thuộc `cod` hoặc (`bank_transfer` chỉ khi `VietQr::isConfigured()`); cộng `addressRules()`.
    3. Giỏ rỗng → `redirect('/gio-hang')`.
    4. `RateLimiter` khoá `order:{ip}`, tối đa 10 đơn/giờ → lỗi "Bạn đặt hơi nhiều đơn, thử lại sau ít phút nhé."
    5. Gọi `placeWebOrder($cart->items(), ..., auth('web')->user())`. `OutOfStockException` → `addError('cart', 'Rất tiếc, ' . implode(', ', $names) . ' không còn đủ hàng. Bạn kiểm tra lại giỏ nhé.')`.
    6. Thành công → `$cart->clear()`, `redirect('/don-hang/{code}/thanh-cong')`.
  - View: form bên trái, tóm tắt đơn bên phải (các dòng, tạm tính, phí ship tính theo tỉnh đang chọn, tổng). Lựa chọn chuyển khoản chỉ hiện khi `VietQr::isConfigured()`.
- Route: `GET /thanh-toan` (tên `checkout`, view `checkout.index`); `GET /don-hang/{order:code}/thanh-cong` (tên `orders.success`, middleware `throttle:30,1`).
- `OrderController::success(Order $order)`: 404 nếu `channel != web`. Trang chỉ hiện: mã đơn, tổng tiền, phương thức thanh toán, trạng thái; nếu `isAwaitingTransfer()` và có `VietQr::url()`: ảnh QR + số tài khoản + "Nội dung chuyển khoản: {mã đơn}" + "Đơn tự huỷ nếu sau 24 giờ shop chưa nhận được tiền"; link `/tra-cuu-don`. **Không** hiện tên, SĐT, địa chỉ, danh sách món.

- [ ] **Step 1: Viết test** (`fill($component)` điền form hợp lệ; `setUp` tạo tỉnh `01` có phường `00004`, tỉnh `79` có phường `26734`)

```php
public function test_places_cod_order_and_clears_cart(): void {
    $v = $this->variant(['stock' => 5], ['price' => 200000]);
    app(Cart::class)->add($v->id, 2);
    $this->fill(Livewire::test(Checkout::class))->set('phone', '+84 901 234 567')->call('place')
        ->assertRedirect('/don-hang/' . Order::first()->code . '/thanh-cong');
    $order = Order::first();
    $this->assertSame([430000, 'cod', '0901234567'], [$order->total, $order->payment_method, $order->customer_phone]);
    $this->assertSame('01', $order->address['province_code']);
    $this->assertTrue(app(Cart::class)->isEmpty());
}
public function test_empty_cart_redirects_to_cart(): void {
    Livewire::test(Checkout::class)->assertRedirect('/gio-hang');
}
public function test_double_submit_creates_one_order(): void {
    $v = $this->variant(['stock' => 5]);
    app(Cart::class)->add($v->id, 2);
    $c = $this->fill(Livewire::test(Checkout::class));
    $c->call('place'); $c->call('place');
    $this->assertSame(1, Order::count());
    $this->assertSame(3, $v->fresh()->stock);
}
public function test_rejects_garbage_input(): void {
    $v = $this->variant(['stock' => 5]);
    app(Cart::class)->add($v->id);
    $this->fill(Livewire::test(Checkout::class))
        ->set('phone', '12345')->set('provinceCode', '01')->set('wardCode', '26734')   // phường của tỉnh khác
        ->set('paymentMethod', 'hack')->call('place')
        ->assertHasErrors(['phone', 'wardCode', 'paymentMethod']);
    $this->fill(Livewire::test(Checkout::class))->set('provinceCode', 'ZZ')->call('place')->assertHasErrors(['provinceCode']);
    $this->fill(Livewire::test(Checkout::class))->set('paymentMethod', 'bank_transfer')->call('place')
        ->assertHasErrors(['paymentMethod']);   // shop chưa cấu hình ngân hàng
    $this->assertSame(0, Order::count());
}
public function test_out_of_stock_at_submit_shows_item_name(): void {
    $v = $this->variant(['stock' => 2]);
    app(Cart::class)->add($v->id, 2);
    $c = $this->fill(Livewire::test(Checkout::class));
    $v->update(['stock' => 1]);
    $c->call('place')->assertHasErrors('cart')->assertSee($v->product->name)->assertNoRedirect();
    $this->assertSame(0, Order::count());
}
public function test_honeypot_creates_no_order(): void {
    $v = $this->variant(['stock' => 5]);
    app(Cart::class)->add($v->id);
    $this->fill(Livewire::test(Checkout::class))->set('website', 'http://x')->call('place');
    $this->assertSame(0, Order::count());
}
public function test_shipping_updates_with_province(): void {
    app(ShopSettings::class)->set('shipping_fees', ['79' => 20000]);
    $v = $this->variant(['stock' => 5], ['price' => 100000]);
    app(Cart::class)->add($v->id);
    Livewire::test(Checkout::class)->set('provinceCode', '79')->assertSee('20.000đ')->assertSee('120.000đ');
}
public function test_success_page_shows_qr_without_personal_data(): void {
    foreach (['bank_bin' => '970436', 'bank_account_no' => '0123456789', 'bank_account_name' => 'TIEM AO HA'] as $k => $val) {
        app(ShopSettings::class)->set($k, $val);
    }
    $v = $this->variant(['stock' => 5]);
    $order = app(OrderService::class)->placeWebOrder([$v->id => 1], $this->contact(['name' => 'Nguyễn Văn Kín', 'street' => '12 Ngõ Riêng']), 'bank_transfer');
    $this->get("/don-hang/{$order->code}/thanh-cong")->assertOk()
        ->assertSee($order->code)
        ->assertSee("img.vietqr.io/image/970436-0123456789-compact2.png?amount={$order->total}&amp;addInfo={$order->code}", false)
        ->assertDontSee('Nguyễn Văn Kín')->assertDontSee('12 Ngõ Riêng')->assertDontSee($order->customer_phone);
    $this->get('/don-hang/DHKHONGCO/thanh-cong')->assertNotFound();
}
```

- [ ] **Step 2:** `php artisan test --filter=CheckoutTest`. Expected: FAIL.
- [ ] **Step 3:** Viết trait, partial, component, `VietQr`, controller, view, route.
- [ ] **Step 4:** `php artisan test && npm run build`. Expected: PASS.
- [ ] **Step 5:** Commit `feat: checkout with COD and VietQR bank transfer`.

---

### Task 7: Tra cứu đơn

**Files:**
- Create: `resources/views/orders/lookup.blade.php`, `resources/views/partials/order-detail.blade.php`
- Modify: `app/Http/Controllers/OrderController.php` (`lookupForm`, `lookup`), `routes/web.php`, `resources/views/partials/footer.blade.php` (link "Tra cứu đơn")
- Test: `tests/Feature/OrderLookupTest.php`

**Interfaces:**
- Route: `GET /tra-cuu-don` (tên `orders.lookup`); `POST /tra-cuu-don` (middleware `throttle:10,1`).
- `lookup(Request)`: `code` (trim, viết hoa) + `phone` (qua `Phone::normalize()`). Tìm đơn `channel = web` khớp **cả hai**. Không thấy, hoặc SĐT không hợp lệ → quay lại form với lỗi ở `code`: "Không tìm thấy đơn với mã và số điện thoại này." (một câu duy nhất cho mọi trường hợp). Thấy → render `orders.lookup` kèm đơn (không redirect, không đưa SĐT lên URL).
- `partials/order-detail` nhận `Order $order` (dùng lại ở Task 9): mã, nhãn trạng thái (`status->label()`), thanh toán, dòng thời gian từ `statusLogs` (nhãn + giờ `d/m/Y H:i` + note), mã vận đơn nếu có, các món (tên, màu / size, SL × đơn giá), tạm tính, phí ship, tổng, người nhận + `addressLine()`; nếu `isAwaitingTransfer()` thì có link tới trang đặt xong để lấy QR.

- [ ] **Step 1: Viết test**

```php
public function test_finds_order_by_code_and_phone_in_any_format(): void {
    $order = $this->placeOrder(['phone' => '0901234567']);
    $this->post('/tra-cuu-don', ['code' => ' ' . strtolower($order->code) . ' ', 'phone' => '+84 901 234 567'])
        ->assertOk()->assertSee($order->code)->assertSee('Chờ xác nhận')->assertSee($order->items->first()->name);
}
public function test_wrong_phone_and_wrong_code_give_same_error(): void {
    $order = $this->placeOrder(['phone' => '0901234567']);
    $msg = 'Không tìm thấy đơn với mã và số điện thoại này.';
    $this->post('/tra-cuu-don', ['code' => $order->code, 'phone' => '0911111111'])->assertSessionHasErrors(['code' => $msg]);
    $this->post('/tra-cuu-don', ['code' => 'DHXXXXXX', 'phone' => '0901234567'])->assertSessionHasErrors(['code' => $msg]);
    $this->post('/tra-cuu-don', ['code' => $order->code, 'phone' => 'abc'])->assertSessionHasErrors(['code' => $msg]);
}
public function test_lookup_is_throttled(): void {
    foreach (range(1, 10) as $_) { $this->post('/tra-cuu-don', ['code' => 'X', 'phone' => '0901234567']); }
    $this->post('/tra-cuu-don', ['code' => 'X', 'phone' => '0901234567'])->assertStatus(429);
}
public function test_shows_cancel_note_in_timeline(): void {
    $order = $this->placeOrder(['phone' => '0901234567']);
    app(OrderService::class)->cancel($order, 'Quá 24 giờ chưa nhận được chuyển khoản');
    $this->post('/tra-cuu-don', ['code' => $order->code, 'phone' => '0901234567'])
        ->assertSee('Đã huỷ')->assertSee('Quá 24 giờ chưa nhận được chuyển khoản');
}
```

- [ ] **Step 2:** `php artisan test --filter=OrderLookupTest`. Expected: FAIL.
- [ ] **Step 3:** Viết code.
- [ ] **Step 4:** `php artisan test`. Expected: PASS.
- [ ] **Step 5:** Commit `feat: order lookup by code and phone`.

---

### Task 8: Đăng ký, đăng nhập, quên mật khẩu

**Files:**
- Create: `app/Http/Controllers/Account/AuthController.php`, `app/Http/Controllers/Account/PasswordResetController.php`, `resources/views/account/{register,login,forgot-password,reset-password}.blade.php`
- Modify: `routes/web.php`, `bootstrap/app.php`, `app/Providers/AppServiceProvider.php`, `resources/views/partials/header.blade.php`, `resources/views/partials/mobile-nav.blade.php`
- Test: `tests/Feature/AccountAuthTest.php`

**Interfaces:**
- Consumes: `Customer`, `Phone::normalize()`, `Cart::mergeGuestCartInto()`.
- Route (nhóm `guest` trừ đăng xuất):
  - `GET|POST /tai-khoan/dang-ky` (tên `register`)
  - `GET|POST /tai-khoan/dang-nhap` (tên `login`)
  - `POST /tai-khoan/dang-xuat` (tên `logout`, middleware `auth`)
  - `GET /tai-khoan/quen-mat-khau` (`password.request`), `POST` (`password.email`, `throttle:5,1`)
  - `GET /tai-khoan/dat-lai-mat-khau/{token}` (`password.reset`), `POST /tai-khoan/dat-lai-mat-khau` (`password.update`)
- `bootstrap/app.php`: `redirectGuestsTo('/tai-khoan/dang-nhap')`, `redirectUsersTo('/tai-khoan')`.
- Đăng ký: `name` required max 120; `phone` chuẩn hoá được; `email` nullable, email, unique `customers`; `password` min 8, confirmed.
  - SĐT đã có **mật khẩu** → lỗi ở `phone`: "Số điện thoại này đã có tài khoản. Bạn đăng nhập nhé."
  - SĐT đã có bản ghi khách vãng lai → nhận bản ghi đó: ghi `name`, `email`, `password`, `registered_at = now()`.
  - Chưa có → tạo mới với `registered_at = now()`.
  - Xong: đăng nhập, `session()->regenerate()`, gộp giỏ, về `/tai-khoan`.
- Đăng nhập: `phone` + `password` + `remember`. `RateLimiter` khoá `login:{phone}|{ip}`, 5 lần/phút. Sai → lỗi ở `phone`: "Số điện thoại hoặc mật khẩu không đúng." Đúng → regenerate, gộp giỏ, `redirect()->intended('/tai-khoan')`.
- Đăng xuất: logout, invalidate session, về `/`.
- Quên mật khẩu: nhập email → `Password::broker('customers')->sendResetLink()`. Luôn trả cùng một thông báo: "Nếu email này có tài khoản, shop đã gửi link đặt lại mật khẩu." Trang có dòng: "Tài khoản không có email? Nhắn shop qua Zalo/hotline để được hỗ trợ."
- Đặt lại: `token`, `email`, `password` (min 8, confirmed) → `Password::broker('customers')->reset()`; xong về trang đăng nhập kèm thông báo.
- `AppServiceProvider::boot()`: `ResetPassword::toMailUsing(...)` với tiêu đề "Đặt lại mật khẩu · {tên shop}", một dòng hướng dẫn, nút "Đặt lại mật khẩu", dòng "Link có hiệu lực 60 phút."
- Header + mobile-nav: thêm biểu tượng tài khoản, link `/tai-khoan` (khách chưa đăng nhập sẽ được chuyển tới trang đăng nhập).

- [ ] **Step 1: Viết test**

```php
public function test_registers_and_logs_in(): void {
    $this->post('/tai-khoan/dang-ky', ['name' => 'An', 'phone' => '+84 901 234 567', 'password' => 'matkhau123', 'password_confirmation' => 'matkhau123'])
        ->assertRedirect('/tai-khoan');
    $c = Customer::where('phone', '0901234567')->first();
    $this->assertAuthenticatedAs($c);
    $this->assertNotNull($c->registered_at);
}
public function test_register_claims_guest_record_but_refuses_existing_account(): void {
    $guest = Customer::forPhone('0901234567', 'Khách cũ');
    $data = ['name' => 'An', 'phone' => '0901234567', 'password' => 'matkhau123', 'password_confirmation' => 'matkhau123'];
    $this->post('/tai-khoan/dang-ky', $data)->assertRedirect('/tai-khoan');
    $this->assertSame(1, Customer::count());
    $this->assertTrue($guest->fresh()->hasAccount());
    auth()->logout();
    $this->post('/tai-khoan/dang-ky', $data)->assertSessionHasErrors('phone');
}
public function test_login_merges_guest_cart(): void {
    $c = Customer::factory()->registered()->create(['phone' => '0901234567']);
    $v = $this->variant(['stock' => 5]);
    app(Cart::class)->add($v->id, 2);
    $this->post('/tai-khoan/dang-nhap', ['phone' => '0901 234 567', 'password' => 'matkhau123'])->assertRedirect('/tai-khoan');
    $this->assertAuthenticatedAs($c);
    $this->assertEquals([$v->id => 2], $c->fresh()->cart);
}
public function test_wrong_password_and_guest_record_cannot_login_then_throttled(): void {
    Customer::factory()->registered()->create(['phone' => '0901234567']);
    Customer::forPhone('0911111111', 'Vãng lai');
    $this->post('/tai-khoan/dang-nhap', ['phone' => '0911111111', 'password' => ''])->assertSessionHasErrors();
    foreach (range(1, 5) as $_) {
        $this->post('/tai-khoan/dang-nhap', ['phone' => '0901234567', 'password' => 'sai'])->assertSessionHasErrors('phone');
    }
    $this->post('/tai-khoan/dang-nhap', ['phone' => '0901234567', 'password' => 'matkhau123'])->assertSessionHasErrors('phone');
    $this->assertGuest();
}
public function test_password_reset_flow(): void {
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
public function test_logout_and_guest_redirect(): void {
    $this->get('/tai-khoan')->assertRedirect('/tai-khoan/dang-nhap');
    $this->actingAs(Customer::factory()->registered()->create())->post('/tai-khoan/dang-xuat')->assertRedirect('/');
    $this->assertGuest();
}
```

Ghi chú: `test_logout_and_guest_redirect` cần route `/tai-khoan` có middleware `auth`; ở task này tạo route đó trả view tạm `account.index` (chỉ có lời chào + nút đăng xuất), Task 9 hoàn thiện.

- [ ] **Step 2:** `php artisan test --filter=AccountAuthTest`. Expected: FAIL.
- [ ] **Step 3:** Viết controller, view, route, cấu hình redirect, email đặt lại mật khẩu.
- [ ] **Step 4:** `php artisan test`. Expected: PASS.
- [ ] **Step 5:** Commit `feat: customer register, login and password reset`.

---

### Task 9: Khu tài khoản: lịch sử đơn, sổ địa chỉ, yêu thích

**Files:**
- Create: `database/migrations/2026_11_15_000300_create_account_tables.php` (`customer_addresses`, `wishlists`), `app/Models/CustomerAddress.php`, `app/Http/Controllers/Account/AccountController.php` (`index`, `order`, `wishlist`), `app/Livewire/AddressBook.php`, `app/Livewire/WishlistButton.php`, `resources/views/account/{index,order,addresses,wishlist}.blade.php`, `resources/views/livewire/{address-book,wishlist-button}.blade.php`, `resources/views/partials/account-nav.blade.php`
- Modify: `app/Models/Customer.php`, `app/Livewire/Checkout.php` + view, `resources/views/products/show.blade.php`, `routes/web.php`
- Test: `tests/Feature/AccountAreaTest.php`

**Interfaces:**
- Consumes: `HasAddressFields`, `partials/order-detail`, `partials/product-card`.
- `customer_addresses`: `customer_id` FK cascade, `name`, `phone`, `province_code`, `ward_code`, `street`, `is_default` bool. `wishlists`: `customer_id` FK cascade, `product_id` FK cascade, unique cặp, timestamps.
- `Customer`:
  - `addresses(): HasMany` (mặc định lên đầu), `wishlist(): BelongsToMany` (Product, bảng `wishlists`, withTimestamps).
  - `visibleOrders(): HasMany`: đơn có `customer_id` của mình **và** `created_at >= registered_at`. Đơn cũ hơn chỉ xem được qua tra cứu (mã + SĐT).
  - `const MAX_ADDRESSES = 10`.
- Route (middleware `auth`):
  - `GET /tai-khoan` (tên `account`): lời chào, danh sách `visibleOrders()` mới nhất trước (mã, ngày, tổng, trạng thái), phân trang 10.
  - `GET /tai-khoan/don-hang/{order:code}` (tên `account.order`): 404 nếu đơn không nằm trong `visibleOrders()`.
  - `GET /tai-khoan/dia-chi` (tên `account.addresses`): chứa `<livewire:address-book />`.
  - `GET /tai-khoan/yeu-thich` (tên `account.wishlist`): lưới `product-card` các sản phẩm visible đã thích.
- `AddressBook` (dùng `HasAddressFields`): `public ?int $editingId`, `public string $name, $phone`; `edit(int $id)`, `create()`, `save()`, `delete(int $id)`, `makeDefault(int $id)`. Mọi thao tác chỉ trên địa chỉ của `auth('web')->user()` (id của người khác → 404). Địa chỉ đầu tiên tự thành mặc định; xoá địa chỉ mặc định thì địa chỉ mới nhất còn lại lên thay. Quá `MAX_ADDRESSES` → lỗi "Bạn lưu tối đa 10 địa chỉ."
- `WishlistButton`: `#[Locked] public int $productId`, `toggle()`. Khách chưa đăng nhập → `redirect('/tai-khoan/dang-nhap')`. Nút tim có `aria-pressed`. Đặt trên trang chi tiết, cạnh tên sản phẩm.
- `Checkout` khi đã đăng nhập: `mount()` điền `name`, `phone`, `email` từ tài khoản và địa chỉ mặc định; có danh sách "Địa chỉ đã lưu" gọi `useAddress(int $id)` (chỉ địa chỉ của mình); ô đánh dấu `public bool $saveAddress = false` → sau khi đặt xong lưu địa chỉ vừa dùng (bỏ qua nếu đã đủ 10).

- [ ] **Step 1: Viết test**

```php
public function test_account_lists_only_orders_since_registration(): void {
    // Người lạ từng đặt hàng bằng SĐT này trước khi tài khoản được đăng ký
    $old = $this->placeOrder(['phone' => '0901234567', 'name' => 'Chủ cũ', 'street' => '12 Ngõ Riêng']);
    $old->forceFill(['created_at' => now()->subDays(3)])->save();
    $this->post('/tai-khoan/dang-ky', ['name' => 'Người mới', 'phone' => '0901234567', 'password' => 'matkhau123', 'password_confirmation' => 'matkhau123']);
    $new = $this->placeOrder(['phone' => '0901234567'], auth('web')->user());
    $this->get('/tai-khoan')->assertOk()->assertSee($new->code)->assertDontSee($old->code);
    $this->get("/tai-khoan/don-hang/{$new->code}")->assertOk();
    $this->get("/tai-khoan/don-hang/{$old->code}")->assertNotFound();
}
public function test_cannot_open_another_customers_order(): void {
    $other = $this->placeOrder(['phone' => '0911111111']);
    $this->actingAs(Customer::factory()->registered()->create())
        ->get("/tai-khoan/don-hang/{$other->code}")->assertNotFound();
}
public function test_address_book_crud_and_default_rules(): void {
    $c = Customer::factory()->registered()->create();
    $this->actingAs($c);
    $book = fn () => $this->fillAddress(Livewire::test(AddressBook::class)->call('create'));
    $book()->call('save')->assertHasNoErrors();
    $book()->set('street', '34 Phố Hai')->call('save');
    [$first, $second] = $c->addresses()->orderBy('id')->get()->all();
    $this->assertTrue($first->is_default); $this->assertFalse($second->is_default);
    Livewire::test(AddressBook::class)->call('delete', $first->id);
    $this->assertTrue($second->fresh()->is_default);
    $book()->set('provinceCode', '01')->set('wardCode', '26734')->call('save')->assertHasErrors('wardCode');
}
public function test_address_book_blocks_other_customers_rows_and_limit(): void {
    $mine = Customer::factory()->registered()->create();
    $theirs = CustomerAddress::factory()->create();
    $this->actingAs($mine);
    Livewire::test(AddressBook::class)->call('delete', $theirs->id)->assertNotFound();
    $this->assertModelExists($theirs);
    CustomerAddress::factory()->count(10)->for($mine)->create();
    $this->fillAddress(Livewire::test(AddressBook::class)->call('create'))->call('save')->assertHasErrors();
    $this->assertSame(10, $mine->addresses()->count());
}
public function test_checkout_prefills_and_saves_address(): void {
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
public function test_wishlist_toggle_and_page(): void {
    $p = Product::factory()->create(['name' => 'Áo Thích', 'is_active' => true, 'published_at' => now()]);
    Livewire::test(WishlistButton::class, ['productId' => $p->id])->call('toggle')->assertRedirect('/tai-khoan/dang-nhap');
    $c = Customer::factory()->registered()->create();
    $this->actingAs($c);
    Livewire::test(WishlistButton::class, ['productId' => $p->id])->call('toggle')->assertSeeHtml('aria-pressed="true"');
    $this->get('/tai-khoan/yeu-thich')->assertOk()->assertSee('Áo Thích');
    Livewire::test(WishlistButton::class, ['productId' => $p->id])->call('toggle');
    $this->assertSame(0, $c->wishlist()->count());
}
```

- [ ] **Step 2:** `php artisan test --filter=AccountAreaTest`. Expected: FAIL.
- [ ] **Step 3:** Viết migration, model, factory `CustomerAddressFactory`, controller, hai component, view, sửa `Checkout`.
- [ ] **Step 4:** `php artisan test && npm run build`. Expected: PASS.
- [ ] **Step 5:** Commit `feat: account area with orders, address book and wishlist`.

---

### Task 10: Dữ liệu demo, README, kiểm tra tổng

**Files:**
- Create: `database/seeders/DemoCustomerSeeder.php`, `README.md` (thay bản mặc định của Laravel)
- Modify: `database/seeders/DatabaseSeeder.php`
- Test: `tests/Feature/DemoSeedTest.php`

**Interfaces:**
- `DemoCustomerSeeder`: tài khoản khách `0900000001` / `matkhau123` (tên "Khách Demo", `registered_at` 30 ngày trước), 1 địa chỉ mặc định ở Hà Nội, 2 đơn tạo qua `OrderService::placeWebOrder()` (1 COD, 1 COD đã `cancel`), 2 sản phẩm yêu thích. Chạy lại không tạo trùng (bỏ qua nếu tài khoản đã có đơn).
- Thứ tự trong `DatabaseSeeder`: `CatalogSeeder`, `HomeSeeder`, `ProvinceSeeder`, `DemoCustomerSeeder`.
- `README.md` (tiếng Việt): giới thiệu ngắn, yêu cầu (PHP 8.3, MySQL, Node), các bước chạy (`composer install`, `npm install && npm run build`, tạo DB `offtherail_v2`, `php artisan migrate --seed`, `php artisan storage:link`, `php artisan serve`), tài khoản demo, cách chạy test, ghi chú `php artisan schedule:work` để tự huỷ đơn chuyển khoản quá hạn, cách bật chuyển khoản VietQR (3 key `bank_bin`, `bank_account_no`, `bank_account_name` trong bảng `settings`; giao diện sửa có ở Kế hoạch 3).

- [ ] **Step 1: Viết test**

```php
public function test_full_seed_gives_working_demo(): void {
    Storage::fake('public');
    $this->seed(); $this->seed(DemoCustomerSeeder::class);
    $c = Customer::where('phone', '0900000001')->first();
    $this->assertTrue(Hash::check('matkhau123', $c->password));
    $this->assertSame(2, $c->visibleOrders()->count());
    $this->assertSame(1, $c->addresses()->count());
    foreach (['/', '/san-pham', '/gio-hang', '/tra-cuu-don', '/tai-khoan/dang-nhap', '/tai-khoan/dang-ky', '/tai-khoan/quen-mat-khau'] as $url) {
        $this->get($url)->assertOk();
    }
    $this->actingAs($c);
    foreach (['/tai-khoan', '/tai-khoan/dia-chi', '/tai-khoan/yeu-thich'] as $url) { $this->get($url)->assertOk(); }
}
```

- [ ] **Step 2:** `php artisan test --filter=DemoSeedTest`. Expected: FAIL.
- [ ] **Step 3:** Viết seeder và README.
- [ ] **Step 4:** `npm run build && php artisan test`. Expected: build OK, toàn bộ PASS.
- [ ] **Step 5:** Kiểm tra tay trên trình duyệt ở 390px (dùng DB demo): thêm vào giỏ từ trang chi tiết → ngăn kéo mở, số trên biểu tượng tăng; "Mua ngay" sang thẳng thanh toán; đổi tỉnh thì danh sách phường và phí ship đổi; đặt COD → trang đặt xong → tra cứu bằng mã + SĐT; đăng nhập tài khoản demo → thấy đơn, địa chỉ, yêu thích; giỏ còn nguyên sau khi đăng nhập. Nếu không mở được trình duyệt thì ghi ruling như Task 10 của Kế hoạch 1.
- [ ] **Step 6:** Commit `feat: demo customer, README and plan 2 wrap-up`.
