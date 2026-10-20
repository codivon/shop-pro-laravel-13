@if(Auth::check())
    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
    <audio id="alert-sound" src="{{ asset('back/app-assets/sounds/notification.ogg') }}" preload="auto"></audio>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.qrcode/1.0/jquery.qrcode.min.js"></script>

    @php
        $notifications = auth('web')->user()->unreadNotifications;
        $unreadCount = $notifications->count();
        $notificationsRoute = route('front.notifications.index');

        // فیلتر اعلان‌های پاپ‌آپی
        $popupNotifications = $notifications->filter(function($notification) {
            return isset($notification->popup) && $notification->popup == true;
        });
    @endphp

    @if($popupNotifications->count() > 0)
        {{-- پاپ‌آپ اعلان‌های اجباری — در master سایت include کنید --}}
        <div class="npop-overlay" id="npop-overlay">
            <div class="npop" id="npop" role="dialog" aria-modal="true" aria-labelledby="npop-title">
                <span class="npop__banner"></span>
                <span class="npop__counter" id="npop-counter" style="display:none"></span>

                <div class="npop__body">
                    <div class="npop__icon"><i class="mdi mdi-bell-ring" id="npop-icon"></i></div>
                    <span class="npop__badge" id="npop-badge"></span>
                    <h3 class="npop__title" id="npop-title"></h3>
                    <p class="npop__msg" id="npop-msg"></p>
                </div>

                <div class="npop__foot">
                    <button type="button" class="npop__btn" id="npop-ack">
                        <i class="mdi mdi-check-circle-outline"></i>
                        متوجه شدم
                    </button>
                </div>
            </div>
        </div>

        <style>
            .npop-overlay {
                position: fixed; inset: 0; z-index: 9999;
                background: rgba(15, 23, 42, .6);
                backdrop-filter: blur(5px);
                -webkit-backdrop-filter: blur(5px);
                display: flex; align-items: center; justify-content: center;
                padding: 20px;
                opacity: 0; visibility: hidden; transition: opacity .25s, visibility .25s;
            }
            .npop-overlay.is-open { opacity: 1; visibility: visible; }

            .npop {
                width: 100%; max-width: 460px; background: #fff; border-radius: 22px;
                overflow: hidden; position: relative;
                transform: translateY(18px) scale(.95);
                transition: transform .35s cubic-bezier(.34, 1.56, .64, 1);
                box-shadow: 0 30px 60px -15px rgba(15, 23, 42, .4);
                --c1: #34D399; --c2: #059669; --soft: #D1FAE5;
            }
            .npop-overlay.is-open .npop { transform: none; }

            .npop__banner { display: block; height: 7px; background: linear-gradient(90deg, var(--c1), var(--c2)); }

            .npop__counter {
                position: absolute; top: 18px; inset-inline-start: 18px; z-index: 2;
                background: rgba(255, 255, 255, .92); border: 1px solid #eef0f5;
                color: #475569; font-size: 11px; font-weight: 800;
                padding: 4px 12px; border-radius: 99px;
            }

            .npop__body { padding: 30px 26px 8px; text-align: center; }
            .npop__icon {
                width: 78px; height: 78px; border-radius: 50%; margin: 0 auto 16px;
                background: linear-gradient(135deg, var(--c1), var(--c2));
                display: grid; place-items: center; color: #fff; font-size: 36px;
                box-shadow: 0 16px 30px -10px var(--c2);
                animation: npop-bounce 2.6s ease-in-out infinite;
            }
            @keyframes npop-bounce {
                0%, 100% { transform: translateY(0) rotate(0deg); }
                10%  { transform: translateY(-6px) rotate(-6deg); }
                20%  { transform: translateY(0) rotate(0deg); }
                30%  { transform: translateY(-4px) rotate(5deg); }
                40%  { transform: translateY(0) rotate(0deg); }
            }

            .npop__badge {
                display: inline-flex; align-items: center; gap: 5px;
                font-size: 11.5px; font-weight: 800; padding: 4px 14px;
                border-radius: 99px; color: var(--c2); background: var(--soft);
                margin-bottom: 12px;
            }
            .npop__badge i { font-size: 13px; }

            .npop__title { font-size: 18px; font-weight: 800; color: #1e293b; margin: 0 0 8px; }
            .npop__msg {
                font-size: 14px; color: #475569; line-height: 2; margin: 0;
                max-height: 250px; overflow-y: auto; white-space: pre-line; word-break: break-word;
            }
            .npop__msg::-webkit-scrollbar { width: 5px; }
            .npop__msg::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 99px; }

            .npop__foot { padding: 18px 26px 26px; }
            .npop__btn {
                width: 100%; border: none; border-radius: 14px; padding: 13px;
                font-size: 14.5px; font-weight: 800; font-family: inherit;
                color: #fff; background: linear-gradient(135deg, var(--c1), var(--c2));
                cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px;
                transition: .2s;
            }
            .npop__btn i { font-size: 19px; }
            .npop__btn:hover:not(:disabled) { filter: brightness(1.08); transform: translateY(-1px); box-shadow: 0 12px 22px -8px var(--c2); }
            .npop__btn:disabled { opacity: .6; cursor: not-allowed; transform: none; }

            @media (max-width: 480px) {
                .npop__body { padding: 22px 18px 6px; }
                .npop__foot { padding: 14px 18px 20px; }
                .npop__title { font-size: 16px; }
                .npop__msg { font-size: 13px; max-height: 200px; }
            }
        </style>

        <script>
            (function () {
                'use strict';

                // داده‌های اعلان‌های پاپ‌آپی از سرور
                var popupData = @json($popupNotifications->values());

                if (!popupData || popupData.length === 0) {
                    return;
                }

                var popups = popupData.map(function(item) {
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
                if (!$overlay) return;

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

                    if ($el.icon) {
                        $el.icon.className = 'mdi ' + n.icon;
                    }

                    if ($el.badge) {
                        $el.badge.innerHTML = '<i class="mdi ' + n.icon + '"></i> ' + esc(n.label);
                    }

                    if ($el.title) {
                        $el.title.textContent = n.title;
                    }

                    if ($el.msg) {
                        $el.msg.textContent = n.message;
                    }

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
                    if (index < popups.length) {
                        show(popups[index]);
                    } else {
                        hide();
                    }
                }

                // نمایش اولین پاپ‌آپ
                if (popups.length > 0) {
                    show(popups[0]);
                }

                // دکمه متوجه شدم
                if ($el.btn) {
                    $el.btn.addEventListener('click', function () {
                        if (busy) return;
                        busy = true;
                        $el.btn.disabled = true;

                        var n = popups[index];

                        // ارسال درخواست به سرور برای علامت‌گذاری به عنوان خوانده شده
                        var token = document.querySelector('meta[name="csrf-token"]');
                        token = token ? token.content : '';

                        fetch('{{ route("front.notifications.mark-as-read") }}', {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': token,
                                'Content-Type': 'application/json'
                            },
                            credentials: 'same-origin',
                            body: JSON.stringify({
                                notification_id: n.uid,
                            })
                        })
                            .then(function (r) {
                                if (!r.ok) throw new Error('failed');
                                return r.json();
                            })
                            .then(function () {
                                next();
                            })
                            .catch(function () {
                                busy = false;
                                $el.btn.disabled = false;
                            });
                    });
                }

                // غیرفعال‌سازی کلیک بیرون و ESC
                $overlay.addEventListener('click', function (e) {
                    if (e.target === $overlay) {
                        e.stopPropagation();
                    }
                });

                document.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape' && $overlay.classList.contains('is-open')) {
                        e.preventDefault();
                        e.stopPropagation();
                    }
                });

            })();
        </script>
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const pusherKey     = '{{ config('broadcasting.connections.pusher.key') }}';
            const pusherCluster = '{{ config('broadcasting.connections.pusher.options.cluster') }}';
            const userId        = '{{ Auth::id() }}';
            const link          = '{{ $notificationsRoute }}';

            if (!pusherKey || !pusherCluster) {
                console.warn('Pusher credentials are missing.');
                return;
            }

            const pusher  = new Pusher(pusherKey, { cluster: pusherCluster });
            const channel = pusher.subscribe('inbox-user-' + userId);

            channel.bind('send-message-user', function (data) {
                const wrapper = document.querySelector('.front-dropdown-notification');
                if (!wrapper) return;

                const countBell = wrapper.querySelector('.notifications-count');
                const countHead = wrapper.querySelector('.header-count');
                const list      = wrapper.querySelector('.notif-list');
                if (!countBell || !list) return;

                // Update counters
                const currentCount = parseInt(countBell.dataset.count || 0) + 1;
                countBell.dataset.count = currentCount;
                countBell.textContent   = currentCount;
                countBell.style.display = '';
                countBell.classList.remove('pop');
                void countBell.offsetWidth; // restart animation
                countBell.classList.add('pop');
                if (countHead) countHead.textContent = currentCount;

                // Remove the "no notifications" placeholder
                const empty = list.querySelector('.notif-empty');
                if (empty) empty.remove();

                // Escape user-supplied values
                const escapeHtml = (str) => {
                    const div = document.createElement('div');
                    div.textContent = str ?? '';
                    return div.innerHTML;
                };

                const title       = escapeHtml(data.message.title);
                const description = escapeHtml(data.message.description);
                const createdAt   = escapeHtml(data.message.created_at);

                const html = `
                    <a class="notif-card new-notif" href="${link}">
                        <div class="notif-card-icon">
                            <i class="mdi mdi-comment-text-outline"></i>
                        </div>
                        <div class="notif-card-body">
                            <h6 class="notif-card-title">${title}</h6>
                            <p class="notif-card-text">${description}</p>
                            <span class="notif-card-time">
                                <i class="mdi mdi-clock-outline"></i>
                                ${createdAt}
                            </span>
                        </div>
                        <span class="notif-dot"></span>
                    </a>`;

                list.insertAdjacentHTML('afterbegin', html);

                // Play alert sound
                const audio = document.getElementById('alert-sound');
                if (audio) audio.play().catch(() => {});
            });
        });
    </script>

@endif
