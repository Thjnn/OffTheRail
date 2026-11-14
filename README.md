# Off the Rail v2

Hệ thống bán hàng cho một cửa hàng quần áo ở Việt Nam, viết bằng Laravel: web cho khách xem và đặt hàng, sau này thêm admin và máy bán tại quầy (POS).

Thiết kế đầy đủ: [`docs/superpowers/specs/2026-11-13-offtherail-laravel-design.md`](docs/superpowers/specs/2026-11-13-offtherail-laravel-design.md)

## Đã có

- **Xem hàng:** trang chủ chỉnh được, danh mục có bộ lọc (trạng thái lọc nằm trên URL), trang sản phẩm kiểu tạp chí có thẻ Open Graph để gửi link qua Zalo/Facebook.
- **Giỏ hàng:** ngăn kéo trượt và trang `/gio-hang`. Giỏ của khách vãng lai lưu theo phiên, đăng nhập thì gộp vào tài khoản.
- **Thanh toán:** COD hoặc chuyển khoản VietQR. Địa chỉ 2 cấp Tỉnh/Thành → Phường/Xã (áp dụng từ 01/07/2025). Giá và phí ship luôn tính lại trên server.
- **Kho:** trừ ngay khi đặt bằng câu `UPDATE ... WHERE stock >= qty`, nên không bán vượt được chiếc cuối. Mỗi lần đổi kho ghi một dòng `stock_movements`.
- **Đơn chuyển khoản quá 24 giờ chưa trả** tự huỷ và hoàn kho.
- **Tra cứu đơn** bằng mã đơn + SĐT, có giới hạn tần suất.
- **Tài khoản khách (tuỳ chọn):** đăng ký bằng SĐT, quên mật khẩu qua email, lịch sử đơn, sổ địa chỉ, danh sách yêu thích.

Còn làm tiếp: admin (Kế hoạch 3), POS và chốt ca (Kế hoạch 4).

## Công nghệ

Laravel 13 · PHP 8.3 · MySQL · Livewire 4 · Tailwind CSS 4 · Vite · Swiper · GSAP · PHPUnit

## Chạy thử

Cần PHP 8.3, Composer, MySQL và Node.js.

```bash
composer install
npm install && npm run build
cp .env.example .env && php artisan key:generate
# tạo database tên offtherail_v2 trong MySQL, rồi:
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

Mở http://127.0.0.1:8000.

### Tài khoản demo

| Vai trò | Đăng nhập | Mật khẩu |
|---|---|---|
| Khách hàng | SĐT `0900000001` tại `/tai-khoan/dang-nhap` | `matkhau123` |

Tài khoản này có sẵn 2 đơn (1 đơn đã huỷ), 1 địa chỉ và 2 món yêu thích.

### Lệnh định kỳ

Đơn chuyển khoản quá hạn được huỷ bởi lệnh `orders:cancel-expired`, chạy mỗi giờ. Khi chạy thử, để một cửa sổ chạy:

```bash
php artisan schedule:work
```

Trên máy chủ thật, thêm một dòng cron gọi `php artisan schedule:run` mỗi phút.

### Bật chuyển khoản VietQR

Lựa chọn chuyển khoản chỉ hiện khi shop đã có tài khoản ngân hàng. Giao diện cài đặt có ở Kế hoạch 3; tạm thời ghi ba giá trị vào bảng `settings`:

```bash
php artisan tinker
>>> $s = app(App\Support\ShopSettings::class);
>>> $s->set('bank_bin', '970436');            // mã BIN ngân hàng (970436 = Vietcombank)
>>> $s->set('bank_account_no', '0123456789');
>>> $s->set('bank_account_name', 'NGUYEN VAN A');
```

Ảnh mã QR lấy từ dịch vụ `img.vietqr.io`.

### Đổi thông tin shop

Tên, khẩu hiệu, màu chủ đạo, hotline, link Zalo/Messenger, ngưỡng freeship, phí ship theo tỉnh đều nằm trong `config/shop.php` (giá trị mặc định) và có thể ghi đè bằng `ShopSettings::set()` mà không sửa code.

## Kiểm thử

```bash
php artisan test
```

Test chạy trên SQLite trong bộ nhớ, không đụng tới database thật.
