/**
 * SaniarTheme — پاپ‌آپ اعلان‌ها + کانتر بلادرنگ (Pusher)
 * استخراج‌شده از popup-message.blade.php — داده‌ها از data-island با id="npop-data" خوانده می‌شود.
 */
(function () {
    'use strict';

    var island = document.getElementById('npop-data');
    if (!island) return;

    var cfg = island.dataset || {};

    // ---------- پاپ‌آپ اعلان‌های سروری ----------
    var popupData = [];
    try { popupData = JSON.parse(island.textContent || '[]'); } catch (e) { popupData = []; }

    if (popupData && popupData.length > 0) {

        var popups = popupData.map(function (item) {
            return {
                uid: item.id,
                title: item.title || 'اعلان جدید',
                message: item.message || '',
                icon: item.icon || 'mdi-bell-ring',
                label: item.label || 'اطلاعیه',
                c1: item.color1 || '#34D399',
                c2: item.color2 || '#059669',
                soft: item.soft || '#D1FAE5'
            };
        });

        var index = 0;
        var busy = false;

        var $overlay = document.getElementById('npop-overlay');
        if ($overlay) {
            var $el = {
                counter: document.getElementById('npop-counter'),
                icon: document.getElementById('npop-icon'),
                badge: document.getElementById('npop-badge'),
                title: document.getElementById('npop-title'),
                msg: document.getElementById('npop-msg'),
                btn: document.getElementById('npop-ack'),
                box: document.getElementById('npop'),
                banner: document.querySelector('.npop__banner')
            };

            function esc(s) {
                var d = document.createElement('div');
                d.textContent = s == null ? '' : String(s);
                return d.innerHTML;
            }

            function show(n) {
                if ($el.box) {
                    $el.box.style.setProperty('--c1', n.c1);
                    $el.box.style.setProperty('--c2', n.c2);
                    $el.box.style.setProperty('--soft', n.soft);
                }
                if ($el.banner) {
                    $el.banner.style.background = 'linear-gradient(90deg, ' + n.c1 + ', ' + n.c2 + ')';
                }
                if ($el.icon) { $el.icon.className = 'mdi ' + n.icon; }
                if ($el.badge) { $el.badge.innerHTML = '<i class="mdi ' + n.icon + '"></i> ' + esc(n.label); }
                if ($el.title) { $el.title.textContent = n.title; }
                if ($el.msg) { $el.msg.textContent = n.message; }
                if (popups.length > 1 && $el.counter) {
                    $el.counter.style.display = 'inline-flex';
                    $el.counter.textContent = (index + 1) + ' از ' + popups.length;
                }
                $overlay.classList.add('is-open');
                document.body.style.overflow = 'hidden';
            }

            function hide() {
                $overlay.classList.remove('is-open');
                document.body.style.overflow = '';
            }

            function next() {
                index++;
                if (index < popups.length) { show(popups[index]); } else { hide(); }
            }

            if (popups.length > 0) { show(popups[0]); }

            if ($el.btn) {
                $el.btn.addEventListener('click', function () {
                    if (busy) return;
                    busy = true;
                    $el.btn.disabled = true;

                    var n = popups[index];
                    var tokenEl = document.querySelector('meta[name="csrf-token"]');
                    var token = tokenEl ? tokenEl.content : '';

                    fetch(cfg.markReadUrl, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': token,
                            'Content-Type': 'application/json'
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({ notification_id: n.uid })
                    })
                        .then(function (r) { if (!r.ok) throw new Error('failed'); return r.json(); })
                        .then(function () { next(); })
                        .catch(function () { busy = false; $el.btn.disabled = false; });
                });
            }

            $overlay.addEventListener('click', function (e) {
                if (e.target === $overlay) { e.stopPropagation(); }
            });

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && $overlay.classList.contains('is-open')) {
                    e.preventDefault();
                    e.stopPropagation();
                }
            });
        }
    }

    // ---------- کانتر بلادرنگ اعلان‌ها (Pusher) ----------
    var pusherKey = cfg.pusherKey;
    var pusherCluster = cfg.pusherCluster;
    var userId = cfg.userId;
    var link = cfg.notificationsRoute;

    if (!pusherKey || !pusherCluster || typeof Pusher === 'undefined') {
        if (!pusherKey || !pusherCluster) { console.warn('Pusher credentials are missing.'); }
        return;
    }

    var pusherInstance = new Pusher(pusherKey, { cluster: pusherCluster });
    var channel = pusherInstance.subscribe('inbox-user-' + userId);

    channel.bind('send-message-user', function (data) {
        var wrapper = document.querySelector('.front-dropdown-notification');
        if (!wrapper) return;

        var countBell = wrapper.querySelector('.notifications-count');
        var countHead = wrapper.querySelector('.header-count');
        var list = wrapper.querySelector('.notif-list');
        if (!countBell || !list) return;

        var currentCount = parseInt(countBell.dataset.count || 0) + 1;
        countBell.dataset.count = currentCount;
        countBell.textContent = currentCount;
        countBell.style.display = '';
        countBell.classList.remove('pop');
        void countBell.offsetWidth;
        countBell.classList.add('pop');
        if (countHead) countHead.textContent = currentCount;

        var empty = list.querySelector('.notif-empty');
        if (empty) empty.remove();

        var escapeHtml = function (str) {
            var div = document.createElement('div');
            div.textContent = str == null ? '' : str;
            return div.innerHTML;
        };

        var title = escapeHtml(data.message.title);
        var description = escapeHtml(data.message.description);
        var createdAt = escapeHtml(data.message.created_at);

        var html = '<a class="notif-card new-notif" href="' + link + '">' +
            '<div class="notif-card-icon"><i class="mdi mdi-comment-text-outline"></i></div>' +
            '<div class="notif-card-body">' +
            '<h6 class="notif-card-title">' + title + '</h6>' +
            '<p class="notif-card-text">' + description + '</p>' +
            '<span class="notif-card-time"><i class="mdi mdi-clock-outline"></i>' + createdAt + '</span>' +
            '</div>' +
            '<span class="notif-dot"></span>' +
            '</a>';

        list.insertAdjacentHTML('afterbegin', html);

        var audio = document.getElementById('alert-sound');
        if (audio) audio.play().catch(function () {});
    });
})();
