(() => {
    'use strict';

    const header = document.querySelector('[data-header]');
    const menuToggle = document.querySelector('[data-menu-toggle]');
    const navigation = document.querySelector('[data-navigation]');

    const updateHeader = () => {
        if (header) {
            header.classList.toggle('is-scrolled', window.scrollY > 24);
        }
    };

    updateHeader();
    window.addEventListener('scroll', updateHeader, { passive: true });

    if (menuToggle && navigation && header) {
        const closeMenu = () => {
            menuToggle.setAttribute('aria-expanded', 'false');
            navigation.classList.remove('is-open');
            header.classList.remove('is-open');
        };

        menuToggle.addEventListener('click', () => {
            const isOpen = menuToggle.getAttribute('aria-expanded') === 'true';
            menuToggle.setAttribute('aria-expanded', String(!isOpen));
            navigation.classList.toggle('is-open', !isOpen);
            header.classList.toggle('is-open', !isOpen);
        });

        navigation.querySelectorAll('a').forEach((link) => link.addEventListener('click', closeMenu));
        window.addEventListener('resize', () => {
            if (window.innerWidth > 760) {
                closeMenu();
            }
        });
    }

    const slider = document.querySelector('[data-slider]');

    if (slider) {
        const slides = Array.from(slider.querySelectorAll('[data-slide]'));
        const dots = Array.from(slider.querySelectorAll('[data-slide-to]'));
        const toggle = slider.querySelector('[data-slider-toggle]');
        const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        let activeIndex = 0;
        let timer = null;
        let paused = reduceMotion;
        let touchStartX = 0;

        const showSlide = (index) => {
            activeIndex = (index + slides.length) % slides.length;
            slides.forEach((slide, slideIndex) => {
                const isActive = slideIndex === activeIndex;
                slide.classList.toggle('is-active', isActive);
                slide.setAttribute('aria-hidden', String(!isActive));
            });
            dots.forEach((dot, dotIndex) => {
                const isActive = dotIndex === activeIndex;
                dot.classList.toggle('is-active', isActive);
                if (isActive) {
                    dot.setAttribute('aria-current', 'true');
                } else {
                    dot.removeAttribute('aria-current');
                }
            });
        };

        const stopTimer = () => {
            if (timer) {
                window.clearInterval(timer);
                timer = null;
            }
        };

        const startTimer = () => {
            stopTimer();
            if (!paused && !document.hidden) {
                timer = window.setInterval(() => showSlide(activeIndex + 1), 6500);
            }
        };

        dots.forEach((dot) => {
            dot.addEventListener('click', () => {
                showSlide(Number(dot.dataset.slideTo));
                startTimer();
            });
        });

        if (toggle) {
            toggle.setAttribute('aria-pressed', String(paused));
            toggle.setAttribute('aria-label', paused ? 'Запустить слайдер' : 'Приостановить слайдер');
            toggle.addEventListener('click', () => {
                paused = !paused;
                toggle.setAttribute('aria-pressed', String(paused));
                toggle.setAttribute('aria-label', paused ? 'Запустить слайдер' : 'Приостановить слайдер');
                startTimer();
            });
        }

        slider.addEventListener('keydown', (event) => {
            if (event.key === 'ArrowRight') {
                showSlide(activeIndex + 1);
                startTimer();
            }
            if (event.key === 'ArrowLeft') {
                showSlide(activeIndex - 1);
                startTimer();
            }
        });

        slider.addEventListener('touchstart', (event) => {
            touchStartX = event.changedTouches[0].clientX;
        }, { passive: true });

        slider.addEventListener('touchend', (event) => {
            const distance = event.changedTouches[0].clientX - touchStartX;
            if (Math.abs(distance) > 50) {
                showSlide(activeIndex + (distance < 0 ? 1 : -1));
                startTimer();
            }
        }, { passive: true });

        document.addEventListener('visibilitychange', startTimer);
        startTimer();
    }

    document.querySelectorAll('[data-accordion-group]').forEach((group) => {
        group.querySelectorAll('details').forEach((item) => {
            item.addEventListener('toggle', () => {
                if (!item.open) {
                    return;
                }
                group.querySelectorAll('details').forEach((other) => {
                    if (other !== item) {
                        other.open = false;
                    }
                });
            });
        });
    });

    const revealItems = document.querySelectorAll('.reveal');
    if ('IntersectionObserver' in window && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });

        revealItems.forEach((item) => observer.observe(item));
    } else {
        revealItems.forEach((item) => item.classList.add('is-visible'));
    }
})();
