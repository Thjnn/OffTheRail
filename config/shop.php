<?php

// Giá trị mặc định của shop. Bảng `settings` (sửa trong admin) ghi đè lên các giá trị này.
return [
    'name' => 'Off the Rail',
    'tagline' => 'Khác biệt một chút thôi.',
    'logo' => null,
    'accent' => '#2B3360',
    'hotline' => '',
    'zalo_url' => '',
    'messenger_url' => '',
    'announcement' => 'FREESHIP ĐƠN TỪ 500K · ĐỔI SIZE 7 NGÀY',
    'free_shipping_threshold' => 500000,
    'sizes' => ['XS', 'S', 'M', 'L', 'XL'],
    'policy_lines' => ['Freeship đơn từ 500k', 'Đổi size trong 7 ngày'],

    // Nội dung trang giới thiệu (các đoạn cách nhau một dòng trống). Trống thì dùng nội dung mẫu.
    'about_body' => '',

    // Phí ship: mức mặc định và mức riêng theo mã tỉnh, ví dụ ['79' => 20000]
    'shipping_fee_default' => 30000,
    'shipping_fees' => [],

    // Tài khoản nhận chuyển khoản (VietQR). Để trống thì ẩn lựa chọn chuyển khoản.
    'bank_bin' => '',
    'bank_account_no' => '',
    'bank_account_name' => '',
];
