import Swiper from 'swiper';
import { Autoplay, Pagination } from 'swiper/modules';

// Banner trang chủ. Người dùng bật "giảm chuyển động" thì không tự chạy.
const el = document.querySelector('.js-hero-swiper');

if (el) {
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const slides = el.querySelectorAll('.swiper-slide').length;

    new Swiper(el, {
        modules: [Autoplay, Pagination],
        loop: slides > 1,
        autoplay: reduceMotion || slides < 2 ? false : { delay: 5000, disableOnInteraction: false },
        pagination: { el: el.querySelector('.swiper-pagination'), clickable: true },
    });
}
