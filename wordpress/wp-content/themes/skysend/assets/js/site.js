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
            if (window.innerWidth > 1040) {
                closeMenu();
            }
        });

        window.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && menuToggle.getAttribute('aria-expanded') === 'true') {
                closeMenu();
                menuToggle.focus();
            }
        });
    }

    document.querySelectorAll('[data-provider-tabs]').forEach((container) => {
        const tabs = Array.from(container.querySelectorAll('[data-provider-tab]'));
        const panels = Array.from(container.querySelectorAll('[data-provider-panel]'));

        if (!tabs.length || !panels.length) {
            return;
        }

        const activateTab = (tab, moveFocus = false) => {
            const slug = tab.dataset.providerTab;
            if (!panels.some((panel) => panel.dataset.providerPanel === slug)) {
                return;
            }

            tabs.forEach((item) => {
                const isActive = item === tab;
                item.classList.toggle('is-active', isActive);
                item.setAttribute('aria-selected', String(isActive));
                item.tabIndex = isActive ? 0 : -1;
            });

            panels.forEach((panel) => {
                const isActive = panel.dataset.providerPanel === slug;
                panel.hidden = !isActive;
                panel.classList.toggle('is-active', isActive);
            });

            if (moveFocus) {
                tab.focus();
            }
        };

        tabs.forEach((tab, tabIndex) => {
            tab.addEventListener('click', () => activateTab(tab));
            tab.addEventListener('keydown', (event) => {
                if (event.altKey || event.ctrlKey || event.metaKey) {
                    return;
                }

                let nextIndex;
                switch (event.key) {
                    case 'ArrowUp':
                    case 'ArrowLeft':
                        nextIndex = (tabIndex - 1 + tabs.length) % tabs.length;
                        break;
                    case 'ArrowDown':
                    case 'ArrowRight':
                        nextIndex = (tabIndex + 1) % tabs.length;
                        break;
                    case 'Home':
                        nextIndex = 0;
                        break;
                    case 'End':
                        nextIndex = tabs.length - 1;
                        break;
                    default:
                        return;
                }

                event.preventDefault();
                activateTab(tabs[nextIndex], true);
            });
        });

        activateTab(tabs[0]);
    });

    const slider = document.querySelector('[data-slider]');

    if (slider) {
        const slides = Array.from(slider.querySelectorAll('[data-slide]'));
        const dots = Array.from(slider.querySelectorAll('[data-slide-to]'));
        const motionPreference = window.matchMedia('(prefers-reduced-motion: reduce)');
        let activeIndex = 0;
        let timer = null;
        let isHovered = slider.matches(':hover');
        let isFocused = slider.contains(document.activeElement);
        let touchStartX = null;
        let touchStartY = null;

        const showSlide = (index) => {
            if (!slides.length || !Number.isInteger(index)) {
                return;
            }

            const nextIndex = ((index % slides.length) + slides.length) % slides.length;
            const nextDot = dots.find((dot) => Number(dot.dataset.slideTo) === nextIndex);
            const focusedSlide = slides.find((slide) => slide.contains(document.activeElement));

            if (focusedSlide && focusedSlide !== slides[nextIndex] && nextDot) {
                nextDot.focus();
            }

            activeIndex = nextIndex;
            slides.forEach((slide, slideIndex) => {
                const isActive = slideIndex === activeIndex;
                slide.classList.toggle('is-active', isActive);
                slide.setAttribute('aria-hidden', String(!isActive));
                slide.toggleAttribute('inert', !isActive);
            });
            dots.forEach((dot) => {
                const isActive = Number(dot.dataset.slideTo) === activeIndex;
                dot.classList.toggle('is-active', isActive);
                if (isActive) {
                    dot.setAttribute('aria-current', 'true');
                } else {
                    dot.removeAttribute('aria-current');
                }
            });
        };

        const stopTimer = () => {
            if (timer !== null) {
                window.clearInterval(timer);
                timer = null;
            }
        };

        const syncTimer = () => {
            stopTimer();
            if (slides.length > 1 && !isHovered && !isFocused && !motionPreference.matches && !document.hidden) {
                timer = window.setInterval(() => showSlide(activeIndex + 1), 6500);
            }
        };

        const moveSlide = (index) => {
            showSlide(index);
            syncTimer();
        };

        dots.forEach((dot) => {
            dot.addEventListener('click', () => moveSlide(Number(dot.dataset.slideTo)));
        });

        slider.addEventListener('keydown', (event) => {
            if (event.altKey || event.ctrlKey || event.metaKey || (event.key !== 'ArrowRight' && event.key !== 'ArrowLeft')) {
                return;
            }

            event.preventDefault();
            moveSlide(activeIndex + (event.key === 'ArrowRight' ? 1 : -1));
        });

        slider.addEventListener('mouseenter', () => {
            isHovered = true;
            syncTimer();
        });
        slider.addEventListener('mouseleave', () => {
            isHovered = false;
            syncTimer();
        });
        slider.addEventListener('focusin', () => {
            isFocused = true;
            syncTimer();
        });
        slider.addEventListener('focusout', (event) => {
            isFocused = Boolean(event.relatedTarget && slider.contains(event.relatedTarget));
            syncTimer();
        });

        slider.addEventListener('touchstart', (event) => {
            const touch = event.changedTouches[0];
            if (touch) {
                touchStartX = touch.clientX;
                touchStartY = touch.clientY;
            }
        }, { passive: true });

        slider.addEventListener('touchend', (event) => {
            const touch = event.changedTouches[0];
            if (touch && touchStartX !== null && touchStartY !== null) {
                const distanceX = touch.clientX - touchStartX;
                const distanceY = touch.clientY - touchStartY;
                if (Math.abs(distanceX) > 50 && Math.abs(distanceX) > Math.abs(distanceY)) {
                    moveSlide(activeIndex + (distanceX < 0 ? 1 : -1));
                }
            }
            touchStartX = null;
            touchStartY = null;
        }, { passive: true });
        slider.addEventListener('touchcancel', () => {
            touchStartX = null;
            touchStartY = null;
        }, { passive: true });

        document.addEventListener('visibilitychange', () => {
            isHovered = slider.matches(':hover');
            isFocused = slider.contains(document.activeElement);
            syncTimer();
        });
        if (motionPreference.addEventListener) {
            motionPreference.addEventListener('change', syncTimer);
        } else {
            motionPreference.addListener(syncTimer);
        }

        showSlide(0);
        syncTimer();
    }

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
