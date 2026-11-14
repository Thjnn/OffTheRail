import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';

gsap.registerPlugin(ScrollTrigger);

/*
 * Hiệu ứng tiết chế cho web khách. Tất cả nằm trong nhánh "no-preference":
 * người bật "giảm chuyển động" sẽ thấy trang tĩnh, nội dung vẫn hiện đầy đủ.
 */
const mm = gsap.matchMedia();

mm.add('(prefers-reduced-motion: no-preference)', () => {
    // Khối hiện dần khi cuộn tới
    gsap.utils.toArray('[data-reveal]').forEach((el) => {
        gsap.from(el, {
            autoAlpha: 0,
            y: 24,
            duration: 0.7,
            ease: 'power2.out',
            scrollTrigger: { trigger: el, start: 'top 88%', once: true },
        });
    });

    // Lưới sản phẩm: các thẻ hiện lần lượt
    gsap.utils.toArray('[data-stagger]').forEach((grid) => {
        gsap.from(grid.children, {
            autoAlpha: 0,
            y: 18,
            duration: 0.5,
            stagger: 0.06,
            ease: 'power2.out',
            scrollTrigger: { trigger: grid, start: 'top 90%', once: true },
        });
    });

    // Ảnh đầu trang chi tiết
    const hero = document.querySelector('[data-hero-image]');
    if (hero) {
        gsap.from(hero, { scale: 1.04, duration: 0.8, ease: 'power2.out' });
    }
});

// Phản hồi khi giỏ hàng thay đổi: biểu tượng giỏ nảy nhẹ
window.addEventListener('cart-updated', () => {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    gsap.fromTo('[data-cart-icon]', { scale: 1.25 }, { scale: 1, duration: 0.35, ease: 'back.out(3)', overwrite: true });
});

// Livewire thay đổi DOM (lọc, phân trang) làm lệch vị trí các điểm kích hoạt.
document.addEventListener('livewire:init', () => {
    window.Livewire.hook('morphed', () => ScrollTrigger.refresh());
});
