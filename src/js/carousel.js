import Swiper from 'swiper'
import { Navigation, Pagination, Keyboard, A11y, Autoplay } from 'swiper/modules'

import 'swiper/css'
import 'swiper/css/navigation'
import 'swiper/css/pagination'

export function initCarousels() {
    document.querySelectorAll('[data-carousel]').forEach((el) => {
        new Swiper(el, {
            modules: [Navigation, Pagination, Keyboard, A11y, Autoplay],
            loop: true,
            keyboard: { enabled: true },
            navigation: {
                prevEl: el.querySelector('.swiper-button-prev'),
                nextEl: el.querySelector('.swiper-button-next')
            },
            pagination: {
                el: el.querySelector('.swiper-pagination'),
                clickable: true
            },
            autoplay: el.dataset.autoplay === 'true'
                ? { delay: 5000, pauseOnMouseEnter: true }
                : false,
            slidesPerView: 'auto',
            centeredSlides: true,
            spaceBetween: 24,
            slideToClickedSlide: true
        })
    })
}