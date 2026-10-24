(function () {
    'use strict';

    document.querySelectorAll('.wra-page').forEach(function (page) {
        var hero = page.querySelector('.wra-hero');

        if (hero) {
            var slides = Array.prototype.slice.call(hero.querySelectorAll('.wra-hero-slide'));
            var dotsContainer = hero.querySelector('.wra-hero-dots');
            var previous = hero.querySelector('.wra-prev');
            var next = hero.querySelector('.wra-next');
            var current = 0;
            var timer;

            if (slides.length && dotsContainer) {
                var dots = slides.map(function (slide, index) {
                    var dot = document.createElement('button');
                    dot.type = 'button';
                    dot.className = 'wra-hero-dot' + (index === 0 ? ' active' : '');
                    dot.setAttribute('aria-label', 'Show slide ' + (index + 1));
                    dot.setAttribute('aria-current', index === 0 ? 'true' : 'false');
                    dotsContainer.appendChild(dot);
                    dot.addEventListener('click', function () {
                        showSlide(index);
                        restartTimer();
                    });
                    return dot;
                });

                function showSlide(index) {
                    current = (index + slides.length) % slides.length;
                    slides.forEach(function (slide, slideIndex) {
                        var active = slideIndex === current;
                        slide.classList.toggle('active', active);
                        slide.setAttribute('aria-hidden', active ? 'false' : 'true');
                        dots[slideIndex].classList.toggle('active', active);
                        dots[slideIndex].setAttribute('aria-current', active ? 'true' : 'false');
                    });
                }

                function stopTimer() {
                    if (timer) {
                        window.clearInterval(timer);
                        timer = null;
                    }
                }

                function restartTimer() {
                    stopTimer();
                    if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches && slides.length > 1) {
                        timer = window.setInterval(function () {
                            showSlide(current + 1);
                        }, 6000);
                    }
                }

                if (previous) {
                    previous.addEventListener('click', function () {
                        showSlide(current - 1);
                        restartTimer();
                    });
                }
                if (next) {
                    next.addEventListener('click', function () {
                        showSlide(current + 1);
                        restartTimer();
                    });
                }

                hero.addEventListener('mouseenter', stopTimer);
                hero.addEventListener('mouseleave', restartTimer);
                restartTimer();
            }
        }

        page.querySelectorAll('.wra-attraction-toggle').forEach(function (button) {
            button.addEventListener('click', function () {
                var card = button.closest('.wra-attraction-card');
                var expanded = card.classList.toggle('expanded');
                button.setAttribute('aria-expanded', expanded ? 'true' : 'false');
            });
        });
    });
}());
