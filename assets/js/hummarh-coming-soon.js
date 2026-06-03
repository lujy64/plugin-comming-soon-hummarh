(function () {
    'use strict';

    var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var mobileQuery = window.matchMedia ? window.matchMedia('(max-width: 760px)') : null;
    var carousels = document.querySelectorAll('[data-hcs-carousel]');

    if (!carousels.length) {
        return;
    }

    function getGap(track) {
        var styles = window.getComputedStyle(track);
        var gap = styles.gap || styles.rowGap || styles.columnGap || '0';

        return parseFloat(gap) || 0;
    }

    function isMobile() {
        return mobileQuery ? mobileQuery.matches : window.innerWidth <= 760;
    }

    function setupCarousel(carousel) {
        var track = carousel.querySelector('.hcs-carousel__track');
        var slides = track ? Array.prototype.slice.call(track.children) : [];
        var originalCount = slides.length;
        var index = 0;
        var timer = null;

        if (!track || originalCount < 2) {
            return;
        }

        slides.forEach(function (slide) {
            track.appendChild(slide.cloneNode(true));
        });
        slides = Array.prototype.slice.call(track.children);

        function peekAmount(slideSize) {
            return isMobile()
                ? Math.round(slideSize * 0.28)
                : Math.round(slideSize * 0.42);
        }

        function syncViewport() {
            var firstSlide = slides[0];
            var slideRect = firstSlide.getBoundingClientRect();
            var slideHeight = slideRect.height;
            var gap = getGap(track);

            if (isMobile()) {
                carousel.style.height = slideHeight + 'px';
            } else {
                carousel.style.height = ((slideHeight * 2) + gap + peekAmount(slideHeight)) + 'px';
            }
        }

        function applyPosition(animate) {
            var firstSlide = slides[0];
            var gap = getGap(track);
            var mobile = isMobile();
            var slideRect = firstSlide.getBoundingClientRect();
            var distance = (mobile ? slideRect.width : slideRect.height) + gap;
            var amount = -index * distance;

            if (!animate) {
                track.style.transition = 'none';
            }

            track.style.transform = mobile
                ? 'translate3d(' + amount + 'px, 0, 0)'
                : 'translate3d(0, ' + amount + 'px, 0)';

            if (!animate) {
                track.getBoundingClientRect();
                track.style.transition = '';
            }
        }

        function next() {
            index += 1;
            applyPosition(true);
        }

        function start() {
            if (reduceMotion) {
                return;
            }

            stop();
            timer = window.setInterval(next, 3600);
        }

        function stop() {
            if (timer) {
                window.clearInterval(timer);
                timer = null;
            }
        }

        function reset() {
            index = index % originalCount;
            syncViewport();
            applyPosition(false);
        }

        track.addEventListener('transitionend', function () {
            if (index < originalCount) {
                return;
            }

            index = 0;
            applyPosition(false);
        });

        carousel.addEventListener('mouseenter', stop);
        carousel.addEventListener('mouseleave', start);
        window.addEventListener('resize', reset);

        reset();
        start();
    }

    carousels.forEach(setupCarousel);
})();
