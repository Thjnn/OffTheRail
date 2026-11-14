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

Lựa chọn chuyển khoản chỉ hiện khi shop đã có tài khoản ngân hàng. Vào **Admin → Cài đặt**, điền đủ mã BIN ngân hàng (vd. `970436` là Vietcombank), số tài khoản và tên chủ tài khoản. Ảnh mã QR lấy từ dịch vụ `img.vietqr.io`.

### Đổi thông tin shop

Tên, logo, khẩu hiệu, màu chủ đạo, hotline, link Zalo/Messenger, ngưỡng freeship, phí ship theo tỉnh, danh sách size đều sửa trong **Admin → Cài đặt**, không cần sửa code. `config/shop.php` chỉ giữ giá trị mặc định.

## Kiểm thử

```bash
php artisan test
```

Test chạy trên SQLite trong bộ nhớ, không đụng tới database thật.
