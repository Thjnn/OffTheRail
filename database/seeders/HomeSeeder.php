<?php

namespace Database\Seeders;

use App\Models\Banner;
use App\Models\HomeSection;
use Illuminate\Database\Seeder;

class HomeSeeder extends Seeder
{
    public function run(): void
    {
        // Ảnh banner dùng lại ảnh sản phẩm demo (CatalogSeeder đã copy vào disk public).
        $banners = [
            ['image' => 'products/holding-on-crew-front.webp', 'subtitle' => 'Bộ sưu tập Thu', 'title' => 'Holding On.', 'link' => '/san-pham/holding-on-crew', 'link_label' => 'Xem bộ sưu tập'],
            ['image' => 'products/fern-tee-front.webp', 'subtitle' => 'Mẫu mới mỗi thứ Sáu', 'title' => 'Khác biệt một chút thôi.', 'link' => '/san-pham', 'link_label' => 'Xem hàng mới'],
        ];

        foreach ($banners as $position => $banner) {
            Banner::updateOrCreate(['image' => $banner['image']], $banner + ['position' => $position, 'is_active' => true]);
        }

        $sections = [
            ['type' => 'new_arrivals', 'title' => 'Hàng mới tuần này', 'config' => ['limit' => 8]],
            ['type' => 'categories', 'title' => 'Mua theo loại', 'config' => []],
            ['type' => 'featured', 'title' => 'Bán chạy', 'config' => ['limit' => 4]],
            ['type' => 'story', 'title' => 'Mỗi mẫu bắt đầu từ một bức vẽ tay.', 'config' => [
                'eyebrow' => 'Về chúng tôi',
                'body' => 'Chúng tôi in từng đợt nhỏ, mỗi mẫu chỉ vài chục chiếc. Bán hết là thôi, để chiếc áo bạn mặc luôn hiếm hơn một chút.',
                'link' => '/gioi-thieu',
                'link_label' => 'Đọc câu chuyện',
            ]],
        ];

        foreach ($sections as $position => $section) {
            HomeSection::updateOrCreate(['type' => $section['type']], $section + ['position' => $position, 'is_active' => true]);
        }
    }
}
