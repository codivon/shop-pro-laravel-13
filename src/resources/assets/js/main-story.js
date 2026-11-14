/**
 * SaniarTheme — main-story.js (فاز ۱۶۷)
 * پلیر استوری‌ها: نوار پیشرفت، پیمایش خودکار، وضعیت «دیده‌شده»، صدا و لایک
 *
 * نکته: اندپوینت لایک هسته (stories/like → likeToggle) فقط post_id جدول posts
 * را می‌پذیرد؛ بنابراین لایک استوری به‌صورت بصری (کلاینت‌ساید) تغییر می‌کند.
 * گزارش «دیده شدن» به روت‌های سبک تم (stories/seen و stories/interaction)
 * best-effort ارسال می‌شود و خطای احتمالی نادیده گرفته می‌شود.
 */
(function () {
    'use strict';

    var SEEN_KEY = 'saniar_story_seen';
    var DEFAULT_DURATION = 6000;
    var root = document.querySelector('.product-carousel-story.allStoryIndex');
    if (!root) {
        return;
    }

    var modalEl = document.getElementById('story-modal');
    var carouselEl = document.getElementById('carouselExampleIndicatorsStory');
    if (!modalEl || !carouselEl || typeof bootstrap === 'undefined') {
        return;
    }

    /* ---------------- دیده‌شده‌ها (localStorage) ---------------- */
    function getSeen() {
        try {
            return JSON.parse(localStorage.getItem(SEEN_KEY) || '[]');
        } catch (e) {
            return [];
        }
    }

    function persistSeen(seen) {
        try {
            localStorage.setItem(SEEN_KEY, JSON.stringify(seen.slice(-200)));
        } catch (e) { /* حافظه در دسترس نیست — بی‌خیال */ }
    }

    function markSeen(id) {
        if (!id) return;
        var seen = getSeen();
        if (seen.indexOf(String(id)) === -1) {
            seen.push(String(id));
            persistSeen(seen);
        }
        var item = root.querySelector('.storyItem[id="' + id + '"]');
        if (item) {
            item.classList.add('unActive');
        }
    }

    // بازیابی وضعیت دیده‌شده هنگام بارگذاری صفحه
    getSeen().forEach(function (id) {
        var item = root.querySelector('.storyItem[id="' + id + '"]');
        if (item) {
            item.classList.add('unActive');
        }
    });

    /* ---------------- گزارش به تم (best-effort) ---------------- */
    var csrfToken = document.querySelector('meta[name="csrf-token"]');
    var seenUrl = root.getAttribute('data-action');
    var interactionUrl = root.getAttribute('data-action-interaction');

    function report(url, payload) {
        if (!url) return;
        try {
            fetch(url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken ? csrfToken.getAttribute('content') : ''
                },
                body: JSON.stringify(payload)
            }).catch(function () { /* بی‌اهمیت */ });
        } catch (e) { /* بی‌اهمیت */ }
    }

    /* ---------------- نوار پیشرفت و پیمایش خودکار ---------------- */
    var items = Array.prototype.slice.call(carouselEl.querySelectorAll('.carousel-item'));
    var carousel = bootstrap.Carousel.getOrCreateInstance(carouselEl, {
        interval: false,
        ride: false,
        pause: false,
        touch: true
    });
    var progressTimer = null;

    function activeIndex() {
        for (var i = 0; i < items.length; i++) {
            if (items[i].classList.contains('active')) {
                return i;
            }
        }
        return -1;
    }

    function stopProgress() {
        if (progressTimer) {
            clearTimeout(progressTimer);
            progressTimer = null;
        }
    }

    function slideDuration(slideEl) {
        return parseInt(slideEl.getAttribute('data-duration'), 10) || DEFAULT_DURATION;
    }

    function renderBars(slideEl, index) {
        var bar = slideEl.querySelector('.story-progress-bars');
        if (!bar) return;

        bar.innerHTML = '';
        items.forEach(function (slide, i) {
            var segment = document.createElement('span');
            segment.className = 'story-progress-item';
            if (i < index) {
                segment.classList.add('done');
            }
            if (i === index) {
                segment.classList.add('active');
            }
            var fill = document.createElement('i');
            if (i === index) {
                fill.style.animationDuration = slideDuration(slideEl) + 'ms';
            }
            segment.appendChild(fill);
            bar.appendChild(segment);
        });
    }

    function playActive() {
        var index = activeIndex();
        if (index === -1) return;

        var slideEl = items[index];
        renderBars(slideEl, index);
        stopProgress();

        // ویدیو با رویداد ended جلو می‌رود؛ برای اطمینان سقف ۲۰ ثانیه هم گذاشته می‌شود
        var timeout = slideEl.getAttribute('data-type') === 'video'
            ? Math.max(slideDuration(slideEl), 20000)
            : slideDuration(slideEl);

        progressTimer = setTimeout(function () {
            if (index < items.length - 1) {
                carousel.next();
            } else {
                bootstrap.Modal.getOrCreateInstance(modalEl).hide();
            }
        }, timeout);
    }

    function announceCurrent() {
        var el = items[activeIndex()];
        if (el) {
            var id = el.getAttribute('data-story-id');
            markSeen(id);
            report(seenUrl, { story_id: id });
            report(interactionUrl, { story_id: id });
        }
    }

    carouselEl.addEventListener('slid.bs.carousel', function () {
        announceCurrent();
        playActive();
    });

    modalEl.addEventListener('shown.bs.modal', function () {
        announceCurrent();
        playActive();
    });

    modalEl.addEventListener('hide.bs.modal', stopProgress);
    modalEl.addEventListener('hidden.bs.modal', stopProgress);

    // پایان ویدیو → استوری بعدی
    Array.prototype.forEach.call(carouselEl.querySelectorAll('video.story-video-player'), function (video) {
        video.addEventListener('ended', function () {
            stopProgress();
            carousel.next();
        });
        video.addEventListener('pause', stopProgress);
    });

    /* ---------------- صدا و لایک (delegation داخل مودال) ---------------- */
    modalEl.addEventListener('click', function (event) {
        var soundBtn = event.target.closest('.story-video-sound');
        if (soundBtn) {
            var slide = soundBtn.closest('.carousel-item');
            var video = slide ? slide.querySelector('video.story-video-player') : null;
            if (video) {
                video.muted = !video.muted;
                soundBtn.classList.toggle('muted', video.muted);
            }
            return;
        }

        var likeBtn = event.target.closest('.story-likes');
        if (likeBtn) {
            likeBtn.classList.toggle('liked');
            var counter = likeBtn.querySelector('span');
            if (counter) {
                var value = parseInt(counter.textContent, 10) || 0;
                counter.textContent = Math.max(0, value + (likeBtn.classList.contains('liked') ? 1 : -1));
            }
        }
    });
})();
