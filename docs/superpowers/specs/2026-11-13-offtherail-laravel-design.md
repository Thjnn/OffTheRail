# Off the Rail v2 — Thiết kế hệ thống (Laravel)

Ngày: 2026-11-13 · Trạng thái: chờ duyệt (đã chốt: Shopee nhập tay)

## 1. Mục đích và lý do

Viết lại storefront "Off the Rail" (hiện là PHP MVC tự viết, tiếng Anh, giỏ hàng chỉ ở trình duyệt) thành một hệ thống bán hàng hoàn chỉnh cho **một cửa hàng quần áo ở Việt Nam**:

- web cho khách xem và đặt hàng,
- admin cho chủ shop quản lý web + cửa hàng,
- máy POS bán tại quầy cho nhân viên.

Hai mục tiêu song song:

1. **Dự án CV** — người xem mở demo vài phút phải thấy giao diện chỉn chu, công nghệ hiện đại, có test.
2. **Bán được cho một shop thật** — chủ shop tự vận hành không cần biết code; đổi thương hiệu được để bán cho shop khác.

Shop mục tiêu hiện bán qua Zalo, Facebook, tại cửa hàng và một phần trên Shopee. Web đóng vai trò: (a) danh mục có link riêng từng sản phẩm để gửi qua Zalo/FB, (b) kênh tự chốt đơn, (c) **kho hàng chung** cho web, quầy và Shopee (nhập tay).

## 2. Mục tiêu / Không làm / Thất bại nếu

**Mục tiêu (v1)**
- Khách: xem sản phẩm, lọc, đặt hàng COD hoặc chuyển khoản VietQR, tra cứu đơn, tài khoản tuỳ chọn.
- Admin: tổng quan có biểu đồ, đơn hàng, sản phẩm + biến thể + tồn kho, khách hàng, nhân viên, nội dung web, xuất Excel.
- POS: bán tại quầy, quét mã vạch, tiền mặt/QR, in hoá đơn 80mm, chốt ca.
- Nhập tay đơn Shopee để trừ kho chung và tính vào doanh thu.
- Phân quyền Chủ shop / Nhân viên; nhật ký thao tác nhạy cảm.

**Không làm (v1)**
- Mọi tính năng AI (chatbot Gemini, nhận diện khuôn mặt) — đã bỏ theo yêu cầu.
- Nối API Shopee, GHN/GHTK, cổng thanh toán (VNPay/MoMo).
- Mã giảm giá, tích điểm/VIP, đánh giá sản phẩm, đa ngôn ngữ, nhiều chi nhánh.
- Một bản cài phục vụ nhiều shop (SaaS). **Mỗi shop = một bản cài riêng.**

**Thất bại nếu** (dù chạy được)
- Bán vượt tồn kho (web và quầy cùng bán một chiếc cuối).
- Link sản phẩm gửi qua Zalo mở ra trang trống/không đúng món, hoặc chậm trên 4G.
- Chủ shop phải sửa code để đổi tên shop, logo, màu, hotline, banner.
- Nhân viên xem được doanh thu tổng hoặc sửa được giá/kho.
- Demo CV cần cài đặt phức tạp hoặc chạy lên trống trơn không có dữ liệu.

## 3. Ràng buộc

- Stack (do người dùng chọn): **Laravel 13, PHP 8.3, MySQL** (session/cache/queue dùng driver `database`), **Blade + Tailwind CSS 4 + Vite 8**, **ApexCharts**, **Swiper**, **OpenSpout**, **PHPUnit** (SQLite in-memory).
- Môi trường dev: Laragon trên Windows, PHP 8.3.30 có sẵn ở `E:\laragon\bin\php\php-8.3.30-Win32-vs16-x64`, Composer trong `E:\laragon\bin\composer`.
- OpenSpout bản mới nhất (5.13) đòi PHP 8.4 → Composer phải resolve về bản còn hỗ trợ 8.3 (5.3.x hoặc 4.30.x). Không nâng PHP.
- Tiền tệ VND: lưu **số nguyên** (`unsignedBigInteger`), không dùng decimal.
- Địa chỉ Việt Nam theo đơn vị hành chính **2 cấp (Tỉnh/Thành → Phường/Xã)** áp dụng từ 01/07/2025 — không còn cấp quận/huyện.
- Hiệu ứng: GSAP, tiết chế, tôn trọng `prefers-reduced-motion` (theo hướng dẫn chung của người dùng).

## 4. Các cách đã cân nhắc

| Cách | Mô tả | Đánh đổi |
|---|---|---|
| 1. Blade + Alpine.js, tự viết hết | JS nhẹ trong HTML, API JSON cho từng thao tác | Toàn quyền, nhẹ; nhưng bảng admin, form biến thể, POS tốn rất nhiều JS + API |
| **2. Blade + Livewire 4 (kèm Alpine), tự viết admin** ✅ | Tương tác viết bằng PHP, server cập nhật từng phần trang | Ít JS, admin/POS gọn, phổ biến ở VN → tốt cho CV; mỗi thao tác là một round-trip, POS cần xử lý quét mã khéo |
| 3. Filament cho admin + Blade/Alpine cho web khách | Bộ admin dựng sẵn | Nhanh nhất; nhưng admin "trông như Filament", khó thể hiện tay nghề, POS vẫn phải tự làm |

**Đã chọn: Cách 2** (người dùng chọn). Web khách vẫn là Blade render phía server (link chia sẻ mở nhanh, Google đọc được); Livewire chỉ dùng ở chỗ cần tương tác (giỏ hàng, lọc, checkout) và toàn bộ admin/POS.

Không chuyển dần code cũ: router/view/repository tự viết đều có tương đương trong Laravel. **Dựng dự án mới tại `E:\laragon\www\offtherail-laravel`**, giữ dự án cũ làm tham chiếu; chuyển ảnh, bảng màu, font, dữ liệu demo sang seeder.

## 5. Phạm vi chức năng

### 5.1 Web khách hàng

| Trang | Route | Ghi chú |
|---|---|---|
| Trang chủ | `/` | Thanh thông báo (freeship) → banner Swiper → Hàng mới (vuốt ngang) → Mua theo loại → Bán chạy → Câu chuyện ngắn. Các khối chỉnh trong admin |
| Danh mục | `/san-pham`, `/danh-muc/{slug}` | Lọc loại/size/màu/khoảng giá, sắp xếp, phân trang; trạng thái lọc nằm trên URL |
| Chi tiết | `/san-pham/{slug}` | Xem 5.1.1 |
| Giỏ hàng | `/gio-hang` + ngăn kéo trượt | Livewire; giỏ của khách vãng lai lưu theo session, đăng nhập thì gộp vào tài khoản |
| Thanh toán | `/thanh-toan` | Họ tên, SĐT (bắt buộc), email (tuỳ chọn), Tỉnh → Phường/Xã + số nhà, ghi chú; COD hoặc chuyển khoản |
| Đặt xong | `/don-hang/{code}/thanh-cong` | Mã đơn; nếu chuyển khoản: mã VietQR có sẵn số tiền + nội dung = mã đơn |
| Tra cứu đơn | `/tra-cuu-don` | Mã đơn + SĐT → trạng thái + lịch sử |
| Tài khoản (tuỳ chọn) | `/tai-khoan/*` | Đăng ký/đăng nhập/quên mật khẩu, địa chỉ đã lưu, lịch sử đơn, yêu thích |
| Giới thiệu, Liên hệ | `/gioi-thieu`, `/lien-he` | Liên hệ lưu vào DB, có honeypot + giới hạn tần suất |

Mọi trang: nút nổi Zalo · Messenger · Hotline (link lấy từ cài đặt), chỉ để khách liên hệ — không có luồng đặt hàng qua chat.

#### 5.1.1 Trang chi tiết sản phẩm (kiểu "tạp chí")
- Ảnh đầu tràn màn hình; tấm thông tin bo góc đè lên mép ảnh: loại · nhãn (Mới/Giảm giá), tên, **câu trích nghiêng** (tuỳ chọn), giá + giá gốc gạch, chọn màu (đổi bộ ảnh theo màu), chọn size (size hết → gạch; còn ≤ 2 → "chỉ còn N"), link Bảng size (modal theo danh mục).
- Cuộn xuống: các cặp **ảnh ↔ đoạn chữ** xen kẽ (khối nội dung của sản phẩm), rồi chính sách ship/đổi trả, rồi "Có thể bạn thích".
- Thanh dính đáy: **Zalo** (mở chat, kèm link sản phẩm) · **Thêm vào giỏ** · **Mua ngay** (thêm + sang thanh toán).
- Fallback: sản phẩm chỉ có 1 ảnh / không có khối nội dung → bỏ phần xen kẽ, không để khoảng trống.
- Có thẻ Open Graph (ảnh, tên, giá) để link dán vào Zalo/FB hiện preview đẹp.

### 5.2 Admin (`/admin`)

Menu: Tổng quan · Bán tại quầy · Đơn hàng · Sản phẩm · Khách hàng · Nhân viên · Nội dung web · Cài đặt. Nhân viên chỉ thấy mục được phép.

- **Tổng quan**: lời chào + tên shop; thẻ số liệu (doanh thu, số đơn, chờ xử lý, sắp hết) có % so với kỳ trước; biểu đồ doanh thu theo kênh (Web/Quầy/Shopee) theo ngày; đơn mới nhất; biến thể sắp hết; chọn khoảng thời gian.
- **Đơn hàng**: danh sách lọc theo trạng thái/kênh/ngày/nhân viên; **form nhập nhanh đơn Shopee** (mã đơn Shopee, chọn biến thể + số lượng, tổng tiền thực nhận); chi tiết đơn; chuyển trạng thái; nhập mã vận đơn; xác nhận đã nhận tiền chuyển khoản; huỷ/hoàn (chỉ chủ shop với đơn đã thanh toán); in phiếu; **xuất Excel**.
- **Sản phẩm**: CRUD; ảnh (kéo thả thứ tự, gắn theo màu); khối nội dung ảnh ↔ chữ; ma trận biến thể màu × size với SKU, mã vạch, tồn kho, giá riêng (tuỳ chọn); nhập/điều chỉnh kho có lý do; **in tem mã vạch**; xuất Excel tồn kho.
- **Khách hàng**: danh sách theo SĐT, tổng chi tiêu, số đơn, lịch sử.
- **Nhân viên** (chủ shop): tạo/khoá tài khoản; doanh số theo nhân viên.
- **Nội dung web**: banner, khối trang chủ, trang giới thiệu, bảng size theo danh mục.
- **Cài đặt** (chủ shop): tên shop, logo, màu chủ đạo, hotline, link Zalo/Messenger, tài khoản ngân hàng cho VietQR, ngưỡng freeship, phí ship theo tỉnh, mức giảm giá tối đa nhân viên được cho.
- **Nhật ký**: sửa giá, sửa kho, huỷ/hoàn đơn, đổi quyền — ai, lúc nào, giá trị cũ/mới.

### 5.3 POS (`/admin/pos`)

- Bố cục: trái = ô tìm/quét (luôn focus) + lọc danh mục + lưới sản phẩm (bấm → chọn màu/size); phải = hoá đơn đang lập.
- Máy quét USB = bàn phím: nhập mã + Enter → tìm biến thể theo mã vạch → thêm 1. Xử lý phía Alpine trước (tra trong danh sách biến thể đã tải sẵn của phiên POS) rồi đồng bộ server, để quét liên tục không trễ.
- Gắn khách bằng SĐT (tuỳ chọn) → tìm hoặc tạo hồ sơ khách.
- Giảm giá tay (nhân viên bị giới hạn theo cài đặt).
- Thanh toán: tiền mặt (nhập tiền khách đưa → tiền thối) hoặc QR (VietQR hiện trên màn hình, nhân viên bấm "đã nhận").
- Hoàn tất → trừ kho ngay, đơn kênh **Quầy** trạng thái hoàn thành, in hoá đơn 80mm (trang in riêng + CSS `@page`).
- **Chốt ca**: mở ca (tiền đầu ca) → bán → đóng ca (nhập tiền đếm được) → hệ thống so với tiền mặt kỳ vọng, ghi chênh lệch. Phải có ca đang mở mới bán được.

## 6. Người dùng và phân quyền

Hai hệ đăng nhập tách biệt (Laravel guards):

- `web` → **khách hàng** (bảng `customers`): tài khoản tuỳ chọn; khách vãng lai vẫn đặt được.
- `admin` → **nhân sự** (bảng `users`), `role ∈ {owner, staff}`.

| Quyền | owner | staff |
|---|---|---|
| POS, xem & xử lý đơn web, xem kho | ✅ | ✅ |
| Sửa sản phẩm/giá/kho | ✅ | ❌ |
| Giảm giá tay | không giới hạn | ≤ X% (cài đặt) |
| Huỷ/hoàn đơn đã thanh toán | ✅ | ❌ |
| Doanh thu, biểu đồ, xuất Excel | ✅ | chỉ doanh số của mình |
| Nội dung web, cài đặt, nhân viên | ✅ | ❌ |

Chỉ 2 vai trò cố định, kiểm tra bằng Gate/Policy — không làm hệ thống phân quyền tự cấu hình.

**SĐT là khoá hợp nhất khách hàng**: đơn web, đơn quầy, tài khoản cùng SĐT → cùng một bản ghi `customers` (chuẩn hoá về dạng `0xxxxxxxxx`).

## 7. Mô hình dữ liệu (chính)

```
settings            key, value(json)                              — cấu hình shop
users               name, email, password, role, is_active         — nhân sự
customers           phone(unique), name, email?, password?, total_spent, orders_count
customer_addresses  customer_id, name, phone, province_code, ward_code, street, is_default
provinces, wards    code, name, (ward → province_code)            — seed từ dữ liệu 2 cấp
categories          slug, name, position, size_chart(json)
products            category_id, slug, name, quote?, description, price, compare_at_price?,
                    is_active, is_featured, published_at
product_colors      product_id, name, hex, position
product_images      product_id, product_color_id?, path, position
product_blocks      product_id, image_path?, body?, position      — khối ảnh ↔ chữ
product_variants    product_id, product_color_id, size, sku(unique), barcode(unique),
                    price?, stock(unsigned)
stock_movements     variant_id, change(+/-), reason(sale|cancel|return|adjust|import),
                    order_id?, user_id?, note
orders              code(unique), channel(web|pos|shopee), external_code?(mã đơn Shopee, unique theo kênh), customer_id?, customer snapshot
                    (name, phone, email, address json), status, payment_method
                    (cod|bank_transfer|cash|qr), payment_status(unpaid|paid|refunded),
                    subtotal, discount, shipping_fee, total, staff_id?, shift_id?,
                    tracking_code?, note, paid_at?
order_items         order_id, variant_id, snapshot(name, color, size, sku), unit_price, qty
order_status_logs   order_id, from, to, user_id?, note
shifts              user_id, opened_at, closed_at?, opening_cash, counted_cash?, expected_cash?
banners             image, title, subtitle, link, position, is_active
home_sections       type, title, config(json), position, is_active
activity_logs       user_id, action, subject_type/id, old(json), new(json)
contact_messages    name, phone/email, message
```

Size lưu dạng chuỗi trên biến thể (danh sách size cho phép đặt trong cài đặt), để shop bán quần (29, 30…) cũng dùng được.

## 8. Luồng nghiệp vụ quan trọng

**Trừ kho (chống bán vượt)** — áp dụng cho cả web và POS, trong một transaction:
```sql
UPDATE product_variants SET stock = stock - :qty WHERE id = :id AND stock >= :qty
-- 0 dòng bị ảnh hưởng → hết hàng → rollback cả đơn, báo khách món nào hết
```
Mỗi lần thay đổi kho ghi một dòng `stock_movements`.

**Đơn web**
1. Đặt hàng → server tính lại giá/phí ship từ DB (không tin giá trình duyệt) → **trừ kho ngay khi đặt** → đơn `pending`.
2. Chuyển khoản: hiện VietQR; chủ shop/nhân viên bấm "đã nhận tiền" → `payment_status=paid`. Đơn chuyển khoản chưa trả sau 24h → lệnh định kỳ tự huỷ + hoàn kho.
3. `pending → confirmed → shipping (nhập mã vận đơn) → completed`; nhánh `cancelled` (hoàn kho) và `returned` (khách bom hàng/hoàn — hoàn kho).
4. Mọi chuyển trạng thái ghi `order_status_logs`.

**Đơn POS**: tạo + trừ kho + `completed` + `paid` trong một bước; gắn `staff_id`, `shift_id`.

**Đơn Shopee (nhập tay)**: owner hoặc staff nhập → trừ kho bằng cùng câu UPDATE có điều kiện → `completed` + `paid`, không gắn ca (tiền không đi qua két). Trùng `external_code` → từ chối. Huỷ (owner) → hoàn kho.

**Phí ship**: miễn phí khi tạm tính ≥ ngưỡng; ngược lại lấy theo tỉnh (bảng phí trong cài đặt, có mức mặc định).

## 9. Giao diện

### Web khách — phong cách "tạp chí" (giữ chất bản hiện tại)
- Token từ `public/assets/css/base.css` cũ: nền `#F4F2ED`, mực `#1B1B1F`, nhấn `#2B3360`, sale `#C43236`; chữ có chân cho tiêu đề, Instrument Sans cho chữ thường; bo góc 16px. Đưa vào Tailwind 4 qua `@theme`; **màu nhấn đọc từ cài đặt** (CSS variable in ra trong layout).
- Chế độ tối giữ lại (đã có sẵn bộ màu tối).
- Mobile-first; giao diện tiếng Việt hoàn toàn.
- Hiệu ứng GSAP: chữ hiện dần khi cuộn, ảnh trượt nhẹ khi vào trang, phản hồi khi thêm vào giỏ; tắt khi `prefers-reduced-motion`.
- Mockup đã duyệt: `.superpowers/brainstorm/267-1794585906/content/` (`visual-style.html` — A, `home-layout.html` — 2, `product-page.html` — B).

### Admin + POS — "gọn kiểu công cụ"
- Nền trắng/xám nhạt, chữ không chân, icon, viền mảnh, bảng dày thông tin; màu nhấn = màu thương hiệu. Lời chào + tên shop ở đầu Tổng quan.
- Tối ưu cho màn hình máy tính; POS dùng được trên máy tính bảng ngang.
- Mockup đã duyệt: `admin-style.html` — B.

## 10. Kiểm thử

PHPUnit, SQLite in-memory. Ưu tiên test tính năng (feature test) cho:
- trừ kho đồng thời / hết hàng giữa chừng, huỷ hoàn kho, tự huỷ đơn chuyển khoản quá hạn;
- tính tiền: giá từ DB, freeship, phí theo tỉnh, giảm giá vượt giới hạn nhân viên;
- phân quyền: staff bị chặn ở từng route/action của owner;
- POS: quét mã, chốt ca và chênh lệch tiền;
- đơn Shopee: trừ kho, chặn trùng mã đơn, không ảnh hưởng tiền mặt của ca;
- hợp nhất khách theo SĐT;
- route công khai trả 200 với dữ liệu seed.

## 11. Dữ liệu demo (cho CV)

`php artisan migrate --seed` tạo: 10 sản phẩm hiện có (ảnh chuyển từ dự án cũ, đổi giá sang VND), 1 owner + 2 staff (thông tin đăng nhập ghi trong README), ~200 đơn trải đều 60 ngày qua ở cả hai kênh để biểu đồ có số liệu, vài ca đã chốt, banner và khối trang chủ.

## 12. Quyết định do mình đặt (người dùng có thể đổi)

- Thư mục dự án mới `E:\laragon\www\offtherail-laravel`; dự án cũ giữ nguyên.
- Trừ kho lúc đặt hàng; tự huỷ đơn chuyển khoản sau 24h.
- Bỏ mã giảm giá khỏi v1.
- In tem mã vạch bằng thư viện JS phía trình duyệt (Code128 từ SKU).
- Tự viết đăng nhập bằng Auth có sẵn của Laravel (không dùng starter kit) để giữ đúng giao diện đã duyệt.
- Mỗi shop một bản cài.
- Báo đơn web mới: số đếm trên menu admin + âm báo khi admin đang mở + email về địa chỉ của shop (nếu đã cấu hình mail). Chưa nối Zalo OA.
- Đổi/trả tại quầy v1: chỉ trả nguyên đơn (owner, hoàn kho) rồi lập đơn mới; chưa có luồng "đổi size" riêng.

## 13. Để người xây dựng tự quyết

Cấu trúc thư mục Livewire component, tên class, thư viện icon, cách tối ưu ảnh (resize/WebP khi upload), chi tiết phân trang, nội dung email quên mật khẩu, cách triển khai lên hosting thật.
