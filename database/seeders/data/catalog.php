<?php

/*
 * Dữ liệu demo, chuyển từ bản PHP cũ (giá bảng Anh × 10.000 = VND).
 * Ảnh: {slug}-front.webp (màu 1), {slug}-alt.webp (màu 2), {slug}-side.webp (ảnh chung).
 * days_ago: số ngày kể từ khi đăng bán; số âm = sắp ra mắt (chưa hiển thị).
 */

$sizeChartTop = [
    'columns' => ['Size', 'Dài áo (cm)', 'Ngang ngực (cm)', 'Chiều cao (cm)', 'Cân nặng (kg)'],
    'rows' => [
        ['XS', '66', '50', '150–158', '42–50'],
        ['S', '68', '52', '158–165', '50–58'],
        ['M', '70', '54', '165–172', '58–66'],
        ['L', '72', '56', '172–178', '66–75'],
        ['XL', '74', '58', '178–185', '75–85'],
    ],
];

return [
    'categories' => [
        ['slug' => 'ao-thun', 'name' => 'Áo thun', 'size_chart' => $sizeChartTop],
        ['slug' => 'so-mi', 'name' => 'Sơ mi', 'size_chart' => $sizeChartTop],
        ['slug' => 'ao-ni', 'name' => 'Áo nỉ', 'size_chart' => $sizeChartTop],
    ],

    'products' => [
        [
            'slug' => 'reach-shirt', 'sku' => 'REACH', 'name' => 'Reach Shirt', 'category' => 'so-mi',
            'price' => 680000, 'compare_at_price' => null, 'days_ago' => 3, 'is_featured' => false,
            'quote' => 'Một bàn tay vươn ra, vẽ bằng cọ thật.',
            'description' => 'Hoạ tiết vẽ tay đậm nét trên dáng sơ mi cổ trại thoải mái.',
            'material' => 'Vải rayon pha cotton mát, rũ nhẹ. Dáng rộng vừa, cổ trại, cúc gỗ. Giặt máy chế độ nhẹ, phơi trong bóng râm.',
            'story' => 'Bản vẽ gốc là một nét cọ duy nhất trên giấy dó. Chúng tôi giữ nguyên cả những chỗ mực loang, vì đó là phần thật nhất.',
            'colors' => [['Trắng', '#f8f7f3'], ['Đen / ngà', '#202125']],
            'stock' => ['XS' => 9, 'S' => 8, 'M' => 6, 'L' => 5, 'XL' => 2],
        ],
        [
            'slug' => 'scrawl-tee', 'sku' => 'SCRAWL', 'name' => 'Scrawl Tee', 'category' => 'ao-thun',
            'price' => 420000, 'compare_at_price' => null, 'days_ago' => 34, 'is_featured' => true,
            'quote' => 'Vài nét nguệch ngoạc, không nét nào tính trước.',
            'description' => 'Cotton dày dáng rộng, điểm những nét vẽ ngẫu hứng.',
            'material' => 'Cotton 100% định lượng 250gsm, dáng oversize, bo cổ dày. Giặt lộn trái để giữ hình in.',
            'story' => 'Những nét vẽ này được ghi lại trong mười giây, lúc đang nghe điện thoại. Đôi khi thứ ít cố gắng nhất lại đúng nhất.',
            'colors' => [['Đen / cam', '#1a1b1e'], ['Phấn / xanh coban', '#f2f0e9']],
            'stock' => ['XS' => 6, 'S' => 6, 'M' => 5, 'L' => 2, 'XL' => 0],
        ],
        [
            'slug' => 'blend-in-tee', 'sku' => 'BLEND', 'name' => 'Blend In Tee', 'category' => 'ao-thun',
            'price' => 380000, 'compare_at_price' => 480000, 'days_ago' => 52, 'is_featured' => false,
            'quote' => 'Không sinh ra để lẫn vào đám đông.',
            'description' => 'Không làm ra để biến mất. Một thông điệp đáng mặc lên người.',
            'material' => 'Cotton 100% định lượng 230gsm, dáng suông. Hình in lụa, bền màu sau nhiều lần giặt.',
            'story' => 'Chữ trên áo được viết tay rồi scan lại, giữ cả độ run của ngòi bút.',
            'colors' => [['Kem', '#efeadb'], ['Đen / đỏ', '#18191c']],
            'stock' => ['XS' => 8, 'S' => 6, 'M' => 4, 'L' => 2, 'XL' => 0],
        ],
        [
            'slug' => 'two-faces-tee', 'sku' => 'TWO', 'name' => 'Two Faces Tee', 'category' => 'ao-thun',
            'price' => 420000, 'compare_at_price' => null, 'days_ago' => 41, 'is_featured' => true,
            'quote' => 'Hai gương mặt, một nét liền.',
            'description' => 'Tranh một nét liền trên nền cotton wash mang cảm giác vintage.',
            'material' => 'Cotton wash 240gsm, mềm ngay từ lần mặc đầu. Màu có thể nhạt nhẹ theo thời gian, đúng chất vintage.',
            'story' => 'Vẽ không nhấc bút: hai gương mặt nhìn về hai phía nhưng dùng chung một đường nét.',
            'colors' => [['Than wash', '#5c5b5c'], ['Cát', '#ccbba0']],
            'stock' => ['XS' => 10, 'S' => 8, 'M' => 5, 'L' => 3, 'XL' => 1],
        ],
        [
            'slug' => 'unbothered-tee', 'sku' => 'UNBOTHERED', 'name' => 'Unbothered Tee', 'category' => 'ao-thun',
            'price' => 400000, 'compare_at_price' => null, 'days_ago' => 47, 'is_featured' => false,
            'quote' => 'Ồn hơn một chút, bận tâm ít hơn một chút.',
            'description' => 'Ồn ào hơn một chút, bận tâm ít đi một chút.',
            'material' => 'Cotton 100% định lượng 230gsm, dáng rộng vừa. Hình in lụa nổi nhẹ.',
            'story' => 'Dành cho những ngày bạn quyết định không giải thích gì thêm.',
            'colors' => [['Đỏ rượu', '#652f42'], ['Xanh rừng', '#24483b']],
            'stock' => ['XS' => 7, 'S' => 5, 'M' => 4, 'L' => 3, 'XL' => 2],
        ],
        [
            'slug' => 'ink-drip-shirt', 'sku' => 'INK', 'name' => 'Ink Drip Shirt', 'category' => 'so-mi',
            'price' => 720000, 'compare_at_price' => null, 'days_ago' => 38, 'is_featured' => true,
            'quote' => 'Mực loang in tay, không chiếc nào giống chiếc nào.',
            'description' => 'Những vệt cọ phóng khoáng trải khắp dáng sơ mi in rộng rãi.',
            'material' => 'Vải viscose mềm, thoáng. Dáng relaxed, tay lỡ. Giặt tay hoặc giặt máy túi lưới.',
            'story' => 'Mỗi tấm vải được nhỏ mực riêng trước khi cắt, nên vị trí vệt loang trên từng chiếc áo đều khác nhau.',
            'colors' => [['Ô liu', '#757a49'], ['Mộc / xanh navy', '#e5dcc9']],
            'stock' => ['XS' => 7, 'S' => 6, 'M' => 3, 'L' => 2, 'XL' => 0],
        ],
        [
            'slug' => 'fern-tee', 'sku' => 'FERN', 'name' => 'Fern Tee', 'category' => 'ao-thun',
            'price' => 440000, 'compare_at_price' => null, 'days_ago' => 6, 'is_featured' => false,
            'quote' => 'Một nhành dương xỉ, in bằng ánh nắng.',
            'description' => 'Bản in cyanotype hình lá gặp chất cotton oversize hợp xuống phố.',
            'material' => 'Cotton 100% định lượng 250gsm, dáng oversize. Hình in theo kỹ thuật cyanotype nên mỗi chiếc đậm nhạt khác nhau đôi chút.',
            'story' => 'Lá dương xỉ thật được ép lên giấy cảm quang rồi phơi nắng. Bóng của nó thành hình in trên áo.',
            'colors' => [['Xanh Phổ', '#153e78'], ['Đất nung', '#aa6244']],
            'stock' => ['XS' => 10, 'S' => 8, 'M' => 6, 'L' => 3, 'XL' => 1],
        ],
        [
            'slug' => 'holding-on-crew', 'sku' => 'HOLDING', 'name' => 'Holding On Crew', 'category' => 'ao-ni',
            'price' => 780000, 'compare_at_price' => null, 'days_ago' => 44, 'is_featured' => true,
            'quote' => 'Cho những ngày muốn níu lại chút gì đó.',
            'description' => 'Nỉ dày với hình vẽ tay mang cảm giác thêu móc xích.',
            'material' => 'Nỉ bông 380gsm, mặt trong chải lông ấm. Bo tay và gấu dày, không xù lông.',
            'story' => 'Hai bàn tay nắm lấy nhau, vẽ bằng một đường chỉ. Chiếc áo ấm nhất của bộ sưu tập, theo cả hai nghĩa.',
            'colors' => [['Đen', '#222326'], ['Xám muối tiêu', '#b5b3b0']],
            'stock' => ['XS' => 5, 'S' => 4, 'M' => 3, 'L' => 2, 'XL' => 0],
        ],
        [
            'slug' => 'cloud-nine-tee', 'sku' => 'CLOUD', 'name' => 'Cloud Nine Tee', 'category' => 'ao-thun',
            'price' => 460000, 'compare_at_price' => null, 'days_ago' => 10, 'is_featured' => false,
            'quote' => 'Đầu trên mây, chân dưới phố.',
            'description' => 'Đầu ở trên mây, chân vẫn trên vỉa hè.',
            'material' => 'Cotton 100% định lượng 230gsm, màu nhuộm pastel. Dáng rộng vừa.',
            'story' => 'Vẽ trong một buổi chiều nằm nhìn trời. Không có ý nghĩa gì sâu xa hơn thế, và vậy là đủ.',
            'colors' => [['Xanh trời', '#88b8d8'], ['Tím lilac', '#bdb3dd']],
            'stock' => ['XS' => 10, 'S' => 9, 'M' => 5, 'L' => 4, 'XL' => 2],
        ],
        [
            'slug' => 'one-of-one-tee', 'sku' => 'ONE', 'name' => 'One of One Tee', 'category' => 'ao-thun',
            'price' => 400000, 'compare_at_price' => null, 'days_ago' => -7, 'is_featured' => false,
            'quote' => 'Một dấu riêng. Chỉ một, như bạn.',
            'description' => 'Một dấu ấn riêng. Độc bản, như chính bạn.',
            'material' => 'Cotton 100% định lượng 250gsm, dáng oversize.',
            'story' => 'Mỗi chiếc được đóng một con dấu tay ở vị trí khác nhau.',
            'colors' => [['Đá', '#c1b5a2'], ['Đen / xanh chanh', '#222326']],
            'stock' => ['XS' => 0, 'S' => 0, 'M' => 0, 'L' => 0, 'XL' => 0],
        ],
    ],
];
