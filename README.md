# Off the Rail v2

Hệ thống bán hàng cho một cửa hàng quần áo ở Việt Nam, viết bằng Laravel: web cho khách xem và đặt hàng, admin cho chủ shop và nhân viên, và máy bán tại quầy (POS) có chốt ca.

Thiết kế đầy đủ: [`docs/superpowers/specs/2026-11-13-offtherail-laravel-design.md`](docs/superpowers/specs/2026-11-13-offtherail-laravel-design.md)

## Đã có

- **Xem hàng:** trang chủ chỉnh được, danh mục có bộ lọc (trạng thái lọc nằm trên URL), trang sản phẩm kiểu tạp chí có thẻ Open Graph để gửi link qua Zalo/Facebook.
- **Giỏ hàng:** ngăn kéo trượt và trang `/gio-hang`. Giỏ của khách vãng lai lưu theo phiên, đăng nhập thì gộp vào tài khoản.
- **Thanh toán:** COD hoặc chuyển khoản VietQR. Địa chỉ 2 cấp Tỉnh/Thành → Phường/Xã (áp dụng từ 01/07/2025). Giá và phí ship luôn tính lại trên server.
- **Kho:** trừ ngay khi đặt bằng câu `UPDATE ... WHERE stock >= qty`, nên không bán vượt được chiếc cuối. Mỗi lần đổi kho ghi một dòng `stock_movements`.
- **Đơn chuyển khoản quá 24 giờ chưa trả** tự huỷ và hoàn kho.
- **Tra cứu đơn** bằng mã đơn + SĐT, có giới hạn tần suất.
- **Tài khoản khách (tuỳ chọn):** đăng ký bằng SĐT, quên mật khẩu qua email, lịch sử đơn, sổ địa chỉ, danh sách yêu thích.

- **Admin `/admin`** (đăng nhập riêng cho nhân sự, hai vai trò Chủ shop / Nhân viên):
  - Tổng quan: doanh thu, số đơn, đơn chờ xử lý, hàng sắp hết, biểu đồ doanh thu theo kênh (Web / Tại quầy / Shopee). Nhân viên chỉ thấy doanh số của mình.
  - Đơn hàng: lọc theo trạng thái/kênh/ngày/nhân viên, chuyển trạng thái, nhập mã vận đơn, xác nhận đã nhận tiền, huỷ/hoàn (đơn đã trả tiền chỉ chủ shop), in phiếu giao hàng, xuất Excel.
  - Nhập tay đơn Shopee: trừ chung kho với web, chặn nhập trùng mã đơn.
  - Sản phẩm: ma trận màu × size (SKU, mã vạch, giá riêng), ảnh kéo thả gắn theo màu, khối ảnh ↔ chữ. Không xoá được sản phẩm/biến thể đã có đơn.
  - Kho: nhập thêm / đếm lại có ghi lý do, lịch sử biến động, xuất Excel tồn kho.
  - Khách hàng, Nhân viên (tạo, khoá, đổi quyền, doanh số 30 ngày), Nội dung web (banner, khối trang chủ, trang giới thiệu, bảng size), Cài đặt (tên, logo, màu, liên hệ, phí ship theo tỉnh, ngân hàng VietQR, size).
  - Nhật ký thao tác nhạy cảm: sửa giá, sửa kho, huỷ/hoàn đơn, đổi quyền, khoá tài khoản.
  - Báo đơn web mới: số đếm trên menu (tự cập nhật 30 giây), tiếng "ting", email về địa chỉ trong Cài đặt.

- **Bán tại quầy `/admin/ban-tai-quay`** (chủ shop và nhân viên):
  - Mở ca với tiền đầu ca; phải có ca đang mở mới bán được. Cả shop dùng chung một két, nên chỉ có một ca mở tại một thời điểm.
  - Quét mã vạch bằng máy quét USB (máy quét gõ như bàn phím) hoặc gõ mã / SKU rồi Enter. Mã được tra ngay trên trình duyệt nên quét liên tục không bị trễ; mã chưa có trong danh sách mới hỏi server.
  - Bấm sản phẩm trên lưới để chọn màu / size, lọc theo danh mục.
  - Gắn khách bằng SĐT (tự nhận khách quen, khách mới thì tạo hồ sơ), giảm giá tay (nhân viên bị giới hạn theo % trong Cài đặt).
  - Thu tiền mặt (nhập tiền khách đưa, nút mệnh giá nhanh, tiền thối) hoặc QR (VietQR hiện trên màn hình, bấm "Đã nhận tiền"). Bấm thanh toán hai lần vẫn chỉ ra một đơn.
  - Xong là trừ kho ngay và in hoá đơn khổ 80mm.
  - Chốt ca: nhập tiền đếm được trong két. Nhân viên chốt "mù" (không thấy tiền kỳ vọng); chủ shop xem chênh lệch từng ca ở mục **Ca bán hàng**.
  - Trả hàng tại quầy: chủ shop huỷ nguyên đơn ở trang chi tiết đơn (hoàn kho), rồi lập đơn mới.
- **In tem mã vạch** (Code128, tem 50 × 30 mm): nút "In tem" ở danh sách sản phẩm, mặc định in bằng số đang tồn.

## Công nghệ

Laravel 13 · PHP 8.3 · MySQL · Livewire 4 · Tailwind CSS 4 · Vite · Swiper · GSAP · ApexCharts · OpenSpout · JsBarcode · PHPUnit

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
| Chủ shop | `owner@demo.test` tại `/admin/dang-nhap` | `matkhau123` |
| Nhân viên | `nhanvien1@demo.test`, `nhanvien2@demo.test` | `matkhau123` |

Tài khoản khách có sẵn 2 đơn (1 đơn đã huỷ), 1 địa chỉ và 2 món yêu thích. Dữ liệu demo có thêm khoảng 200 đơn Web, Tại quầy và Shopee trải đều từ lúc sản phẩm đầu tiên lên kệ (gần 60 ngày) để biểu đồ có số liệu. Đơn tại quầy nằm trong các ca đã chốt (vài ca có lệch tiền); nếu hôm nay có đơn quầy thì ca hôm nay để mở sẵn.

**Thử bán tại quầy:** đăng nhập nhân viên → **Bán tại quầy** → (mở ca nếu chưa có) → gõ mã vạch `893000000001` rồi Enter (thay máy quét) → chọn Tiền mặt, bấm mệnh giá → **Thanh toán** → trang hoá đơn 80mm tự mở hộp in → **Chốt ca**.

### Email báo đơn mới

Điền "Email nhận báo đơn web mới" trong **Admin → Cài đặt** và cấu hình `MAIL_*` trong `.env`. Gửi mail lỗi không làm hỏng việc đặt hàng (lỗi chỉ ghi vào log).

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
