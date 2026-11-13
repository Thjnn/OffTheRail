# Off the Rail v2: Kế hoạch 1, nền tảng và web xem hàng

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Dựng dự án Laravel 13 mới và hoàn thiện phần web cho khách **xem hàng**: trang chủ, danh mục có lọc, trang chi tiết kiểu tạp chí, giới thiệu, liên hệ. Có dữ liệu demo và cài đặt shop đổi được.

**Architecture:** Laravel 13 dạng monolith. Blade render trang từ server, Livewire 4 dùng cho chỗ cần tương tác (lọc danh mục, chọn biến thể). Tailwind 4 lấy token màu từ bản cũ, màu nhấn đọc từ bảng `settings`. Ảnh lưu trên disk `public`.

**Tech Stack:** Laravel 13, PHP 8.3, MySQL (Laragon), Livewire 4, Tailwind CSS 4, Vite 8, Swiper, GSAP, PHPUnit (SQLite in-memory).

**Spec:** `docs/superpowers/specs/2026-11-13-offtherail-laravel-design.md`

### Lộ trình (spec chia thành 4 kế hoạch, mỗi kế hoạch cho ra phần mềm chạy được)

| # | Kế hoạch | Kết quả |
|---|---|---|
| **1** | **Nền tảng + web xem hàng** (file này) | Khách xem được toàn bộ sản phẩm; demo đã đẹp |
| 2 | Giỏ hàng, thanh toán, đơn web, tài khoản khách | Khách đặt được hàng COD/VietQR, tra cứu đơn |
| 3 | Admin: đăng nhập nhân sự, phân quyền, tổng quan, đơn, sản phẩm, kho, nội dung, cài đặt, Excel, nhật ký, đơn Shopee | Chủ shop vận hành được |
| 4 | POS, chốt ca, mã vạch | Bán tại quầy |

Kế hoạch 2 đến 4 sẽ viết khi kế hoạch trước đã xong, để bám theo code thật.

## Global Constraints

- Thư mục dự án: `E:\laragon\www\offtherail-laravel` (git repo riêng). Dự án cũ `E:\laragon\www\OffTheRail` chỉ đọc, không sửa.
- PHP: `/e/laragon/bin/php/php-8.3.30-Win32-vs16-x64/php.exe`. Composer: `/e/laragon/bin/composer/composer.phar`. Trong mọi lệnh bên dưới, `php` và `composer` hiểu là hai đường dẫn này (`composer` = `php composer.phar`).
- Tiền VND lưu số nguyên `unsignedBigInteger`. Hiển thị `350.000đ`.
- Toàn bộ chữ giao diện cho khách bằng **tiếng Việt**.
- Slug ASCII bằng `Str::slug()`.
- Web khách theo phong cách "tạp chí": nền `#F4F2ED`, bề mặt `#FFFFFF`, bề mặt 2 `#EDEAE3`, mực `#1B1B1F`, chữ phụ `#6E7078`, viền `#DDD9D0`, nhấn `#2B3360` (đổi được trong cài đặt), sale `#C43236`, bo góc `16px`/`10px`. Bộ màu tối giữ nguyên từ `OffTheRail/public/assets/css/base.css` (`html.dark`).
- Font: Instrument Sans (chữ thường), một font có chân cho tiêu đề (Fraunces, Google Fonts), Yellowtail cho chữ viết tay.
- Mobile-first. Mọi hiệu ứng GSAP tắt khi `prefers-reduced-motion: reduce`.
- Mockup đã duyệt nằm ở `OffTheRail/.superpowers/brainstorm/267-1794585906/content/`: `home-layout.html` (bố cục 2), `product-page.html` (bố cục B).
- Mỗi task kết thúc bằng một commit.

## Review Focus

1. **Sản phẩm thiếu dữ liệu** (không có ảnh, không có biến thể, không có khối nội dung): thẻ sản phẩm và trang chi tiết vẫn hiển thị (ảnh giữ chỗ, "Hết hàng"), không lỗi 500. *Test ở Task 7 và Task 8.*
2. **Tham số lọc rác trên URL** (`?gia_tu=abc&size=ZZ&sap_xep=hack`): bị bỏ qua, trả 200 với danh sách mặc định. *Test ở Task 7.*
3. **Sản phẩm ẩn hoặc chưa đến ngày đăng** truy cập trực tiếp bằng slug: trả 404, không lộ ra. *Test ở Task 8.*
4. **Bản cài mới chưa có dòng nào trong `settings`**: mọi trang dùng giá trị mặc định. Hotline/Zalo/Messenger rỗng thì ẩn nút tương ứng. *Test ở Task 2 và Task 5.*
5. **Spam form liên hệ** (bot điền ô bẫy, hoặc gửi quá 5 lần/giờ/IP): bot bị bỏ qua nhưng vẫn thấy "đã gửi", lần thứ 6 nhận thông báo thử lại sau. *Test ở Task 9.*

---

### Task 1: Dựng dự án và công cụ

**Files:**
- Create: toàn bộ khung Laravel tại `E:\laragon\www\offtherail-laravel`
- Create: `database/seeders/images/*.webp` (copy 30 ảnh từ `OffTheRail/public/assets/img/`)
- Create: `docs/superpowers/specs/2026-11-13-offtherail-laravel-design.md` và `docs/superpowers/plans/` (copy từ dự án cũ)
- Modify: `.env`, `.env.example`, `phpunit.xml`, `.gitignore`

**Interfaces:**
- Produces: dự án chạy được bằng `php artisan serve`; `php artisan test` xanh; DB MySQL `offtherail_v2`.

- [ ] **Step 1:** `cd /e/laragon/www && composer create-project laravel/laravel offtherail-laravel "^13.0"`. Kiểm tra: `php artisan --version` in ra `Laravel Framework 13.x`.
- [ ] **Step 2:** `git init`, rồi thêm `.superpowers/` vào `.gitignore`.
- [ ] **Step 3:** Cài gói. `composer require livewire/livewire:^4.0` và `npm i swiper gsap`. Vite 8 và Tailwind 4 đã có sẵn trong khung Laravel 13; kiểm tra `package.json` có `tailwindcss@^4` và `vite@^8`, nếu thiếu thì cài thêm.
- [ ] **Step 4:** Sửa `.env` và `.env.example`: `APP_NAME="Off the Rail"`, `APP_LOCALE=vi`, `APP_TIMEZONE=Asia/Ho_Chi_Minh`, `DB_CONNECTION=mysql`, `DB_DATABASE=offtherail_v2`, `SESSION_DRIVER=database`, `CACHE_STORE=database`, `QUEUE_CONNECTION=database`. Tạo database `offtherail_v2` trong MySQL của Laragon.
- [ ] **Step 5:** Trong `phpunit.xml`, bật `DB_CONNECTION=sqlite` và `DB_DATABASE=:memory:`.
- [ ] **Step 6:** Copy ảnh và tài liệu như danh sách Files. Chạy `php artisan storage:link`.
- [ ] **Step 7:** Chạy `php artisan migrate` rồi `php artisan test`. Expected: migrate xong không lỗi, test mặc định PASS.
- [ ] **Step 8:** Commit `chore: scaffold Laravel 13 + Livewire 4, Swiper, GSAP`.

---

### Task 2: Cài đặt shop và định dạng tiền

**Files:**
- Create: `config/shop.php`, `database/migrations/xxxx_create_settings_table.php`, `app/Models/Setting.php`, `app/Support/ShopSettings.php`, `app/Support/Money.php`
- Modify: `app/Providers/AppServiceProvider.php` (đăng ký singleton + chia sẻ biến `$shop` cho mọi view)
- Test: `tests/Feature/ShopSettingsTest.php`, `tests/Unit/MoneyTest.php`

**Interfaces:**
- Produces:
  - `ShopSettings::get(string $key, mixed $default = null): mixed`: ưu tiên DB, sau đó `config('shop.'.$key)`, cuối cùng là `$default`. Có cache, tự xoá cache khi `set`.
  - `ShopSettings::set(string $key, mixed $value): void`
  - Hàm toàn cục `shop(string $key, mixed $default = null): mixed` gọi tới hàm trên.
  - `Money::vnd(int $amount): string`
- Bảng `settings`: `key` (string, unique), `value` (json).
- Các key mặc định trong `config/shop.php`:
  - `name` = 'Off the Rail', `tagline` = 'Khác biệt một chút thôi.', `logo` = null
  - `accent` = '#2B3360', `hotline` = '', `zalo_url` = '', `messenger_url` = ''
  - `announcement` = 'FREESHIP ĐƠN TỪ 500K · ĐỔI SIZE 7 NGÀY', `free_shipping_threshold` = 500000
  - `sizes` = ['XS','S','M','L','XL'], `policy_lines` = ['Freeship đơn từ 500k', 'Đổi size trong 7 ngày']

- [ ] **Step 1: Viết test**

```php
// tests/Unit/MoneyTest.php
public function test_formats_vnd(): void {
    $this->assertSame('350.000đ', Money::vnd(350000));
    $this->assertSame('0đ', Money::vnd(0));
    $this->assertSame('1.250.000đ', Money::vnd(1250000));
}
// tests/Feature/ShopSettingsTest.php  (RefreshDatabase)
public function test_falls_back_to_config_when_db_empty(): void {
    $this->assertSame('Off the Rail', shop('name'));
    $this->assertSame(500000, shop('free_shipping_threshold'));
    $this->assertSame('x', shop('khong_ton_tai', 'x'));
}
public function test_db_value_overrides_config_and_cache_is_refreshed(): void {
    shop('name'); // làm ấm cache
    app(ShopSettings::class)->set('name', 'Tiệm Áo Hà');
    $this->assertSame('Tiệm Áo Hà', shop('name'));
}
```

- [ ] **Step 2:** `php artisan test --filter="MoneyTest|ShopSettingsTest"`. Expected: FAIL (class chưa có).
- [ ] **Step 3:** Viết code theo Interfaces. Đăng ký hàm `shop()` bằng mục `autoload.files` trong `composer.json` trỏ tới `app/Support/helpers.php`, rồi chạy `composer dump-autoload`.
- [ ] **Step 4:** Chạy lại test. Expected: PASS.
- [ ] **Step 5:** Commit `feat: shop settings with config fallback + VND money format`.

---

### Task 3: Schema danh mục và model

**Files:**
- Create: migrations cho `categories`, `products`, `product_colors`, `product_images`, `product_blocks`, `product_variants`
- Create: models `Category`, `Product`, `ProductColor`, `ProductImage`, `ProductBlock`, `ProductVariant` kèm factory của từng model
- Test: `tests/Feature/ProductModelTest.php`

**Interfaces:**
- Cột: theo spec mục 7. Thêm `position` (int, default 0) cho categories, colors, images, blocks. `products.price` và `products.compare_at_price` là `unsignedBigInteger` (cột sau nullable). `product_variants.price` nullable, `stock` là `unsignedInteger`. `sku` và `barcode` unique. Bảng variants có thêm unique (`product_id`, `product_color_id`, `size`).
- Quan hệ:
  - `Product`: `category()`, `colors()`, `images()`, `blocks()`, `variants()`. Các quan hệ con sắp theo `position`.
  - `ProductColor`: `images()`, `variants()`.
  - `ProductImage.product_color_id` nullable (ảnh chung, không gắn màu).
- Produces trên `Product`:
  - `scopeVisible(Builder $q)`: `is_active = true` và (`published_at` null hoặc `<= now()`).
  - `totalStock(): int`, tính từ `variants` đã load.
  - `isSoldOut(): bool`
  - `isOnSale(): bool`: `compare_at_price > price`.
  - `discountPercent(): ?int`: `round((compare - price) / compare * 100)`, trả null nếu không giảm giá.
  - `isNew(): bool`: `published_at` trong vòng `Product::NEW_DAYS = 30` ngày.
  - `coverImageUrl(): ?string`: ảnh đầu tiên theo `position`. Giá trị lấy bằng `Storage::disk('public')->url(path)`.
  - `getRouteKeyName()` trả về `'slug'`.
- Produces trên `ProductImage`: `url(): string`.

- [ ] **Step 1: Viết test**

```php
public function test_visible_scope_hides_inactive_and_future(): void {
    $ok = Product::factory()->create(['is_active' => true, 'published_at' => now()->subDay()]);
    Product::factory()->create(['is_active' => false]);
    Product::factory()->create(['is_active' => true, 'published_at' => now()->addDay()]);
    $this->assertEquals([$ok->id], Product::visible()->pluck('id')->all());
}
public function test_stock_sale_and_new_helpers(): void {
    $p = Product::factory()->create(['price' => 280000, 'compare_at_price' => 350000, 'published_at' => now()->subDays(3)]);
    $c = ProductColor::factory()->for($p)->create();
    ProductVariant::factory()->for($p)->for($c, 'color')->create(['size' => 'M', 'stock' => 2]);
    ProductVariant::factory()->for($p)->for($c, 'color')->create(['size' => 'L', 'stock' => 0]);
    $p->load('variants');
    $this->assertSame(2, $p->totalStock());
    $this->assertTrue($p->isOnSale());
    $this->assertSame(20, $p->discountPercent());
    $this->assertTrue($p->isNew());
}
public function test_product_without_images_or_variants_is_safe(): void {
    $p = Product::factory()->create(['compare_at_price' => null]);
    $this->assertNull($p->coverImageUrl());
    $this->assertSame(0, $p->load('variants')->totalStock());
    $this->assertTrue($p->isSoldOut());
    $this->assertNull($p->discountPercent());
}
```

- [ ] **Step 2:** `php artisan test --filter=ProductModelTest`. Expected: FAIL.
- [ ] **Step 3:** Viết migrations, models, factories theo Interfaces. Factory của variant sinh `sku` bằng `Str::upper(Str::random(8))`, `barcode` dạng 12 chữ số.
- [ ] **Step 4:** Chạy lại test. Expected: PASS.
- [ ] **Step 5:** Commit `feat: catalog schema and models`.

---

### Task 4: Dữ liệu demo

**Files:**
- Create: `database/seeders/CatalogSeeder.php`, `database/seeders/data/catalog.php`
- Modify: `database/seeders/DatabaseSeeder.php`
- Test: `tests/Feature/CatalogSeederTest.php`

**Interfaces:**
- Consumes: các model của Task 3.
- `data/catalog.php` chuyển từ `OffTheRail/app/Data/products.php`:
  - Danh mục: `ao-thun` 'Áo thun', `so-mi` 'Sơ mi', `ao-ni` 'Áo nỉ' (lần lượt map từ tee, shirt, crew).
  - Giá = giá bảng Anh × 10.000 (ví dụ 68 → 680000). `oldPrice` → `compare_at_price` cũng nhân như vậy.
  - Tên sản phẩm giữ tiếng Anh, mô tả và tên màu dịch sang tiếng Việt.
  - Mỗi màu dùng `frontImage` làm ảnh gắn màu. `sideImage` là ảnh chung.
  - Mỗi sản phẩm có 2 khối nội dung: (ảnh side + đoạn mô tả chất liệu) và (ảnh alt + đoạn câu chuyện).
  - Có thêm `quote` cho từng sản phẩm.
  - Sản phẩm `upcoming` → `published_at = now()->addDays(7)`. Còn lại `published_at` rải trong 60 ngày gần nhất, 3 món trong 14 ngày gần nhất để có nhãn "Mới".
- Biến thể: mỗi màu × size, tồn kho lấy từ `stock` cũ chia đều cho các màu (làm tròn lên).
  - `sku` = `{SLUG-VIET-HOA-TẮT}-{MÀU-INDEX}-{SIZE}`, ví dụ `FERN-1-M`.
  - `barcode` = `'893' . str_pad(variant_id, 9, '0', STR_PAD_LEFT)`.
- Seeder copy ảnh từ `database/seeders/images/` sang `storage/app/public/products/`. Chạy lại không lỗi: dùng `updateOrCreate` theo slug và sku.

- [ ] **Step 1: Viết test**

```php
public function test_seeds_demo_catalog(): void {
    $this->seed(CatalogSeeder::class);
    $this->assertSame(3, Category::count());
    $this->assertSame(10, Product::count());
    $fern = Product::where('slug', 'fern-tee')->with('colors', 'variants', 'blocks')->first();
    $this->assertSame(440000, $fern->price);
    $this->assertCount(2, $fern->colors);
    $this->assertCount(10, $fern->variants);   // 2 màu × 5 size
    $this->assertCount(2, $fern->blocks);
    Storage::disk('public')->assertExists('products/fern-tee-front.webp');
}
public function test_seeder_is_idempotent(): void {
    $this->seed(CatalogSeeder::class);
    $this->seed(CatalogSeeder::class);
    $this->assertSame(10, Product::count());
}
```

- [ ] **Step 2:** `php artisan test --filter=CatalogSeederTest`. Expected: FAIL.
- [ ] **Step 3:** Viết seeder và file dữ liệu.
- [ ] **Step 4:** Chạy lại test. Expected: PASS. Sau đó chạy `php artisan migrate:fresh --seed` trên MySQL để kiểm tra với DB thật.
- [ ] **Step 5:** Commit `feat: demo catalog seeder (VND, Vietnamese copy)`.

---

### Task 5: Layout, design token, header/footer, nút liên hệ

**Files:**
- Create: `resources/css/app.css` (Tailwind 4 `@theme` + bộ màu tối), `resources/js/app.js`, `resources/views/layouts/shop.blade.php`, `resources/views/partials/{announcement,header,footer,contact-fab,mobile-nav}.blade.php`
- Test: `tests/Feature/ShopLayoutTest.php`

**Interfaces:**
- Consumes: hàm `shop()` của Task 2.
- Layout nhận các section `title`, `meta` (dùng cho thẻ OG), `content`. Trong `<head>` in `<style>:root{--color-accent: {{ shop('accent') }}}</style>` để màu nhấn đổi theo cài đặt.
- Header gồm: menu (Sản phẩm, các danh mục, Giới thiệu, Liên hệ), logo hoặc tên shop, nút tìm kiếm (dẫn tới `/san-pham?q=`), nút đổi sáng/tối (lưu `localStorage['otr_theme']`), biểu tượng giỏ hàng (chưa có link, Kế hoạch 2 sẽ gắn).
- `contact-fab` hiện các nút Zalo, Messenger, Hotline (`tel:`). Nút nào có cài đặt rỗng thì không render.

- [ ] **Step 1: Viết test** (route tạm `/` trả view rỗng dùng layout, Task 6 sẽ thay)

```php
public function test_layout_uses_settings(): void {
    app(ShopSettings::class)->set('name', 'Tiệm Áo Hà');
    app(ShopSettings::class)->set('zalo_url', 'https://zalo.me/0900000000');
    $this->get('/')->assertOk()
        ->assertSee('Tiệm Áo Hà')
        ->assertSee('https://zalo.me/0900000000', false)
        ->assertSee('FREESHIP ĐƠN TỪ 500K');
}
public function test_empty_contact_settings_hide_buttons(): void {
    $this->get('/')->assertOk()
        ->assertDontSee('zalo.me', false)
        ->assertDontSee('tel:', false);
}
```

- [ ] **Step 2:** `php artisan test --filter=ShopLayoutTest`. Expected: FAIL.
- [ ] **Step 3:** Viết layout và các partial. Chuyển CSS header/nav/bộ màu tối từ `OffTheRail/public/assets/css/base.css` sang tiện ích Tailwind và `@theme`.
- [ ] **Step 4:** Chạy lại test, rồi `npm run build`. Expected: PASS, build không lỗi.
- [ ] **Step 5:** Commit `feat: storefront layout with themable accent and contact buttons`.

---

### Task 6: Trang chủ

**Files:**
- Create: migrations `banners` và `home_sections` (cột theo spec mục 7), models `Banner` và `HomeSection`, `app/Http/Controllers/HomeController.php`, `resources/views/home.blade.php`, `resources/views/partials/product-card.blade.php`, `resources/js/hero-swiper.js`, `database/seeders/HomeSeeder.php`
- Modify: `routes/web.php` (`GET /` tên `home`)
- Test: `tests/Feature/HomePageTest.php`

**Interfaces:**
- Consumes: `Product::visible()`, `coverImageUrl()`, `isNew()`, `isOnSale()`, `discountPercent()`, `Money::vnd()`.
- Các loại `HomeSection.type` và `config`:
  - `new_arrivals` {limit: 8}: sản phẩm visible, sắp theo `published_at` mới nhất.
  - `categories` {}: các danh mục có sản phẩm visible.
  - `featured` {limit: 4, title}: lấy sản phẩm `is_featured`. Kế hoạch 3 sẽ đổi sang "bán chạy" theo đơn hàng.
  - `story` {eyebrow, body, link}
- Chỉ render section có `is_active`, theo thứ tự `position`. Loại không biết thì bỏ qua.
- `product-card` (dùng lại ở Task 7) nhận `Product $product`. Hiển thị: ảnh hoặc ô giữ chỗ, tên, giá, giá gạch, nhãn "Mới" hoặc "-20%" hoặc "Hết hàng". Link tới `route('products.show', $product)`.
- `HomeSeeder` tạo 2 banner và 4 section theo mockup `home-layout.html`. Gọi từ `DatabaseSeeder`.

- [ ] **Step 1: Viết test**

```php
public function test_home_renders_sections_in_order(): void {
    $this->seed([CatalogSeeder::class, HomeSeeder::class]);
    $this->get('/')->assertOk()
        ->assertSeeInOrder(['Hàng mới', 'Mua theo loại', 'Bán chạy', 'Về chúng tôi'])
        ->assertSee('Fern Tee');
}
public function test_inactive_section_and_unknown_type_are_skipped(): void {
    HomeSection::create(['type' => 'story', 'title' => 'Ẩn đi', 'config' => [], 'position' => 1, 'is_active' => false]);
    HomeSection::create(['type' => 'khong_ro', 'title' => 'Lạ', 'config' => [], 'position' => 2, 'is_active' => true]);
    $this->get('/')->assertOk()->assertDontSee('Ẩn đi')->assertDontSee('Lạ');
}
```

- [ ] **Step 2:** `php artisan test --filter=HomePageTest`. Expected: FAIL.
- [ ] **Step 3:** Viết code. Banner hero dùng Swiper (`loop`, `autoplay` 5s, có pagination). Hàng mới là danh sách vuốt ngang bằng CSS `overflow-x` + `scroll-snap`, không dùng Swiper.
- [ ] **Step 4:** Chạy lại test. Expected: PASS.
- [ ] **Step 5:** Commit `feat: editable home page sections with hero swiper`.

---

### Task 7: Trang danh sách và lọc (Livewire)

**Files:**
- Create: `app/Livewire/ProductList.php`, `resources/views/livewire/product-list.blade.php`, `resources/views/products/index.blade.php`
- Modify: `routes/web.php`: `GET /san-pham` (tên `products.index`) và `GET /danh-muc/{category:slug}` (tên `categories.show`)
- Test: `tests/Feature/ProductListTest.php`

**Interfaces:**
- Consumes: `product-card` của Task 6.
- Các thuộc tính `#[Url]` của `ProductList`:
  - `public string $danhMuc = ''` (slug)
  - `public string $size = ''`
  - `public string $mau = ''`: lọc theo tên màu, so khớp `like`.
  - `public ?int $giaTu = null`, `public ?int $giaDen = null`
  - `public string $sapXep = 'moi-nhat'`: một trong `moi-nhat | gia-tang | gia-giam | ten`
  - `public string $q = ''`
- Phân trang 12 sản phẩm mỗi trang. Lọc size chỉ tính biến thể còn hàng (`stock > 0`).
- Khi `mount`, giá trị không hợp lệ được đưa về mặc định: size không có trong `shop('sizes')`, `sapXep` lạ, giá âm hoặc không phải số.

- [ ] **Step 1: Viết test**

```php
public function test_filters_by_category_size_and_sorts(): void {
    $this->seed(CatalogSeeder::class);
    Livewire::test(ProductList::class)
        ->set('danhMuc', 'so-mi')
        ->assertSee('Reach Shirt')->assertDontSee('Fern Tee')
        ->set('danhMuc', '')->set('sapXep', 'gia-tang')
        ->assertSeeInOrder(['Blend In Tee', 'Holding On Crew']);
}
public function test_size_filter_excludes_out_of_stock_variants(): void {
    $this->seed(CatalogSeeder::class);
    // Scrawl Tee có XL = 0
    Livewire::test(ProductList::class)->set('size', 'XL')->assertDontSee('Scrawl Tee');
}
public function test_garbage_query_params_fall_back_to_defaults(): void {
    $this->seed(CatalogSeeder::class);
    $this->get('/san-pham?giaTu=abc&size=ZZ&sapXep=hack')->assertOk()->assertSee('Fern Tee');
}
public function test_category_route_preselects_category_and_404s_unknown(): void {
    $this->seed(CatalogSeeder::class);
    $this->get('/danh-muc/ao-ni')->assertOk()->assertSee('Holding On Crew')->assertDontSee('Fern Tee');
    $this->get('/danh-muc/khong-co')->assertNotFound();
}
public function test_card_without_image_renders_placeholder(): void {
    Product::factory()->create(['name' => 'Áo Trơn', 'is_active' => true, 'published_at' => now()]);
    $this->get('/san-pham')->assertOk()->assertSee('Áo Trơn')->assertSee('Hết hàng');
}
```

- [ ] **Step 2:** `php artisan test --filter=ProductListTest`. Expected: FAIL.
- [ ] **Step 3:** Viết component. Trên điện thoại, bộ lọc nằm trong ngăn kéo trượt (Alpine `x-show`). Trên máy tính, bộ lọc là cột bên trái.
- [ ] **Step 4:** Chạy lại test. Expected: PASS.
- [ ] **Step 5:** Commit `feat: product listing with URL-synced filters`.

---

### Task 8: Trang chi tiết sản phẩm (kiểu tạp chí)

**Files:**
- Create: `app/Http/Controllers/ProductController.php` (`show`), `resources/views/products/show.blade.php`, `app/Livewire/VariantPicker.php`, `resources/views/livewire/variant-picker.blade.php`, `resources/views/partials/size-chart-modal.blade.php`
- Modify: migration `categories` thêm cột `size_chart` (json, nullable) nếu Task 3 chưa có. `routes/web.php`: `GET /san-pham/{product:slug}` (tên `products.show`)
- Test: `tests/Feature/ProductPageTest.php`

**Interfaces:**
- Consumes: model của Task 3, layout của Task 5.
- `ProductController::show(Product $product)`: trả 404 nếu sản phẩm không thuộc `visible()`. Eager load `colors.images`, `images`, `blocks`, `variants`, `category`.
- `VariantPicker`:
  - Thuộc tính: `public Product $product`, `public ?int $colorId`, `public ?string $size`.
  - `#[Computed] selectedVariant(): ?ProductVariant`
  - Đổi màu → phát event trình duyệt `color-changed` kèm `{colorId}` để bộ ảnh đổi theo màu.
  - Size có `stock = 0` → nút bị disable và gạch ngang. Size có `stock <= 2` → hiện "chỉ còn N".
  - Nút "Thêm vào giỏ" và "Mua ngay" phát `$dispatch('add-to-cart', {variantId, buyNow})`. Kế hoạch 2 sẽ lắng nghe event này. Ở kế hoạch này hai nút disable khi chưa chọn size.
- Bố cục theo `product-page.html`, phương án B:
  - Ảnh đầu tràn màn hình. Tấm thông tin bo góc gồm: loại · nhãn, tên, `quote` (ẩn nếu rỗng), giá, VariantPicker, link bảng size.
  - Tiếp theo là các khối ảnh ↔ chữ, dòng chính sách (`shop('policy_lines')`), "Có thể bạn thích" (4 sản phẩm visible cùng danh mục, trừ chính nó).
  - Thanh dính đáy: Zalo (`shop('zalo_url')`, ẩn nếu rỗng), Thêm vào giỏ, Mua ngay.
- Fallback: sản phẩm không có khối nội dung thì không render phần xen kẽ. Không có ảnh thì dùng ô giữ chỗ. Bảng size: modal hiện `category.size_chart`, nếu null thì ẩn link.
- Thẻ meta: `og:title` = tên, `og:description` = "{giá} · {quote hoặc mô tả cắt 120 ký tự}", `og:image` = URL tuyệt đối của ảnh bìa, `og:url`.

- [ ] **Step 1: Viết test**

```php
public function test_shows_product_with_og_tags(): void {
    $this->seed(CatalogSeeder::class);
    $this->get('/san-pham/fern-tee')->assertOk()
        ->assertSee('Fern Tee')->assertSee('440.000đ')
        ->assertSee('<meta property="og:title" content="Fern Tee"', false)
        ->assertSee('og:image', false);
}
public function test_hidden_and_future_products_404(): void {
    $hidden = Product::factory()->create(['is_active' => false]);
    $future = Product::factory()->create(['is_active' => true, 'published_at' => now()->addDays(3)]);
    $this->get("/san-pham/{$hidden->slug}")->assertNotFound();
    $this->get("/san-pham/{$future->slug}")->assertNotFound();
}
public function test_bare_product_renders_without_blocks_or_images(): void {
    $p = Product::factory()->create(['is_active' => true, 'published_at' => now(), 'quote' => null]);
    $this->get("/san-pham/{$p->slug}")->assertOk()->assertSee($p->name)->assertSee('Hết hàng');
}
public function test_picker_disables_sold_out_and_warns_low_stock(): void {
    $this->seed(CatalogSeeder::class);
    $p = Product::where('slug', 'scrawl-tee')->first();
    $color = $p->colors()->first();
    $p->variants()->where('product_color_id', $color->id)->where('size', 'L')->update(['stock' => 2]);
    Livewire::test(VariantPicker::class, ['product' => $p])
        ->set('colorId', $color->id)->set('size', 'L')
        ->assertSee('chỉ còn 2')
        ->assertSeeHtml('data-size="XL" disabled');
}
```

- [ ] **Step 2:** `php artisan test --filter=ProductPageTest`. Expected: FAIL.
- [ ] **Step 3:** Viết controller, view và component theo Interfaces.
- [ ] **Step 4:** Chạy lại test. Expected: PASS.
- [ ] **Step 5:** Commit `feat: editorial product page with variant picker and OG tags`.

---

### Task 9: Giới thiệu và Liên hệ

**Files:**
- Create: migration `contact_messages` (`name`, `phone` nullable, `email` nullable, `message`, `ip`), model `ContactMessage`, `app/Http/Controllers/ContactController.php`, `app/Http/Requests/ContactRequest.php`, `resources/views/pages/{about,contact}.blade.php`
- Modify: `routes/web.php`: `GET /gioi-thieu` (tên `about`), `GET /lien-he` (tên `contact`), `POST /lien-he` (tên `contact.store`)
- Test: `tests/Feature/ContactTest.php`

**Interfaces:**
- Validate: `name` bắt buộc, tối đa 120 ký tự. Phải có ít nhất một trong hai: `phone` (regex `^0\d{9}$`) hoặc `email`. `message` dài 5 đến 2000 ký tự. `website` là ô bẫy (input ẩn).
- Ô bẫy có giá trị → không lưu, vẫn redirect kèm thông báo thành công.
- Giới hạn tần suất: `RateLimiter` với khoá `contact:{ip}`, tối đa 5 lần mỗi giờ. Vượt quá → quay lại form với lỗi "Bạn gửi hơi nhiều, thử lại sau ít phút nhé."
- Trang giới thiệu dịch và chuyển thể từ `OffTheRail/app/Views/about/index.php`, theo phong cách tạp chí.

- [ ] **Step 1: Viết test**

```php
public function test_stores_valid_message(): void {
    $this->post('/lien-he', ['name' => 'An', 'phone' => '0901234567', 'message' => 'Còn size M không ạ?'])
        ->assertRedirect('/lien-he')->assertSessionHas('status');
    $this->assertDatabaseHas('contact_messages', ['name' => 'An', 'phone' => '0901234567']);
}
public function test_requires_phone_or_email(): void {
    $this->post('/lien-he', ['name' => 'An', 'message' => 'Xin chào shop'])
        ->assertSessionHasErrors('phone');
}
public function test_honeypot_silently_drops(): void {
    $this->post('/lien-he', ['name' => 'Bot', 'email' => 'b@x.io', 'message' => 'spam spam', 'website' => 'http://x'])
        ->assertRedirect('/lien-he')->assertSessionHas('status');
    $this->assertDatabaseCount('contact_messages', 0);
}
public function test_rate_limited_after_five_per_hour(): void {
    $data = ['name' => 'An', 'email' => 'a@x.io', 'message' => 'Hỏi chút ạ'];
    foreach (range(1, 5) as $_) { $this->post('/lien-he', $data); }
    $this->post('/lien-he', $data)->assertSessionHasErrors();
    $this->assertDatabaseCount('contact_messages', 5);
}
public function test_about_page_renders(): void {
    $this->get('/gioi-thieu')->assertOk()->assertSee(shop('name'));
}
```

- [ ] **Step 2:** `php artisan test --filter=ContactTest`. Expected: FAIL.
- [ ] **Step 3:** Viết code theo Interfaces.
- [ ] **Step 4:** Chạy lại test. Expected: PASS.
- [ ] **Step 5:** Commit `feat: about and contact pages with honeypot + rate limit`.

---

### Task 10: Hiệu ứng GSAP và kiểm tra tổng

**Files:**
- Create: `resources/js/motion.js` (import vào `app.js`)
- Test: kiểm tra tay trên trình duyệt (không có test tự động cho hiệu ứng)

**Interfaces:**
- Dùng `gsap.matchMedia()`, chỉ chạy hiệu ứng trong nhánh `(prefers-reduced-motion: no-preference)`.
- Các hiệu ứng:
  - Phần tử có `[data-reveal]`: hiện dần và trượt lên 24px khi cuộn tới (ScrollTrigger, `once: true`).
  - Lưới sản phẩm: các thẻ hiện lần lượt cách nhau 0,06s.
  - Ảnh đầu trang chi tiết: phóng từ 1.04 về 1 trong 0,8s.
- Sau khi Livewire cập nhật DOM (hook `morphed`), gọi `ScrollTrigger.refresh()`.

- [ ] **Step 1:** Viết `motion.js`, gắn `data-reveal` vào các khối ở trang chủ, giới thiệu và trang chi tiết.
- [ ] **Step 2:** `npm run build && php artisan test`. Expected: build OK, toàn bộ test PASS.
- [ ] **Step 3:** Kiểm tra tay bằng `php artisan serve` và chế độ thiết bị di động của trình duyệt (390px):
  - Trang chủ: banner trượt, hàng mới vuốt ngang được.
  - Lọc ở `/san-pham` cập nhật URL.
  - `/san-pham/fern-tee`: đổi màu thì đổi ảnh, size hết hàng bị khoá, thanh dính đáy hiện đủ nút.
  - Chế độ tối hoạt động.
  - Bật "Reduce motion" trong DevTools thì không còn hiệu ứng.
- [ ] **Step 4:** Commit `feat: restrained GSAP motion with reduced-motion support`.
