/* ============================================================
   فاز ۱۳۳ — مودال آپدیت ماژول
   جریان: کلیک «آپدیت ماژول» → مودال (نام/تصویر/نسخه) →
   «بررسی نسخه جدید» (اتصال به API) → اگر نسخه جدید بود دکمه
   به «بروزرسانی» تبدیل می‌شود → کلیک → صف آپدیت + progress
   ============================================================ */
(function ($) {
    "use strict";

    const csrfToken = window.csrfToken || $('meta[name="csrf-token"]').attr('content');

    const STATE = {
        IDLE: 'idle',
        CHECKING: 'checking',
        HAS_UPDATE: 'has_update',
        UP_TO_DATE: 'up_to_date',
        ERROR: 'error',
    };

    let currentState = STATE.IDLE;
    let currentSlug = null;
    let currentName = null;
    let latestVersion = null;

    function route(name, slug) {
        return (window.routes[name] || '').replace(':slug', slug);
    }

    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }

    function nl2br(str) { return (str || '').replace(/\n/g, '<br>'); }

    /* ============================================================
       باز/بستن مودال (با fallback دستی مثل modal.js)
       ============================================================ */
    function showModal($modal) {
        try {
            if (typeof $.fn.modal === 'function') {
                $modal.modal('show');
                return;
            }
        } catch (e) { /* fallback */ }
        $modal.addClass('show').css('display', 'block').attr('aria-hidden', 'false');
        $('body').addClass('modal-open');
        if ($('.modal-backdrop').length === 0) {
            $('<div class="modal-backdrop fade show"></div>').appendTo('body');
        }
    }

    function hideModal($modal) {
        try {
            if (typeof $.fn.modal === 'function') {
                $modal.modal('hide');
                return;
            }
        } catch (e) { /* fallback */ }
        $modal.removeClass('show').css('display', 'none').attr('aria-hidden', 'true');
        $('body').removeClass('modal-open');
        $('.modal-backdrop').remove();
    }

    /* ============================================================
       پر کردن اطلاعات پایه پکیج (نام/تصویر/نسخه)
       ============================================================ */
    function fillPackage(data) {
        const $thumb = $('#upm-thumb');
        const thumb = (data.thumbnail || '').trim();

        $thumb.css('background-image', '');
        $thumb.html('<i class="feather icon-package"></i>');
        if (thumb) {
            const $img = $('<img>', { src: thumb, alt: data.name || '' });
            $img.on('error', function () {
                $(this).remove();
                $thumb.html('<i class="feather icon-package"></i>');
            });
            $thumb.html($img);
        }

        $('#upm-name').text(data.name || data.slug || '—');
        $('#upm-version').text(data.version || '—');
    }

    /* ============================================================
       رندر ناحیه وضعیت بر اساس state
       ============================================================ */
    function renderState(state, payload) {
        currentState = state;
        const $state = $('#upm-state');
        const $changelog = $('#upm-changelog');

        switch (state) {

            case STATE.IDLE:
                $state.html(
                    '<div class="pkg-upm-hint">' +
                        '<div class="pkg-upm-hint-icon"><i class="feather icon-search"></i></div>' +
                        '<div class="pkg-upm-hint-text">' +
                            '<strong>بررسی نسخه جدید</strong>' +
                            '<span>برای اتصال به سرور پکیج‌ها و اطلاع از وجود نسخه جدید، روی دکمه «بررسی نسخه جدید» کلیک کنید.</span>' +
                        '</div>' +
                    '</div>'
                );
                $changelog.hide();
                setCheckButton('check', 'بررسی نسخه جدید');
                break;

            case STATE.CHECKING:
                $state.html(
                    '<div class="pkg-upm-checking">' +
                        '<div class="pkg-upm-checking-icon"><i class="feather icon-refresh-cw"></i></div>' +
                        '<div class="pkg-upm-checking-text">' +
                            '<strong>در حال بررسی نسخه جدید...</strong>' +
                            '<span>در حال اتصال به سرور پکیج‌ها، لطفاً صبر کنید.</span>' +
                        '</div>' +
                    '</div>'
                );
                $changelog.hide();
                setCheckButton('checking', 'در حال بررسی...');
                break;

            case STATE.HAS_UPDATE: {
                latestVersion = payload.latestVersion;
                const current = payload.currentVersion || $('#upm-version').text();

                // چیپ نسخه جدید کنار چیپ نسخه فعلی
                $('#upm-version-chip').addClass('new-version');
                if ($('#upm-new-chip').length === 0) {
                    $('#upm-version-chip').after(
                        '<span class="pkg-upm-version-chip new-version" id="upm-new-chip">' +
                            '<i class="feather icon-arrow-up-circle"></i>' +
                            'نسخه جدید: <strong>v' + escapeHtml(latestVersion) + '</strong>' +
                        '</span>'
                    );
                } else {
                    $('#upm-new-chip').find('strong').text('v' + latestVersion);
                }

                $state.html(
                    '<div class="pkg-upm-found">' +
                        '<div class="pkg-upm-found-icon"><i class="feather icon-download-cloud"></i></div>' +
                        '<div class="pkg-upm-found-text">' +
                            '<strong>نسخه جدید یافت شد! (v' + escapeHtml(latestVersion) + ')</strong>' +
                            '<span>با کلیک روی «بروزرسانی»، آخرین نسخه در صف دانلود و نصب قرار می‌گیرد.</span>' +
                        '</div>' +
                    '</div>'
                );

                renderChangelog(payload.changelog);
                setCheckButton('update', 'بروزرسانی به v' + latestVersion);
                break;
            }

            case STATE.UP_TO_DATE:
                $state.html(
                    '<div class="pkg-upm-uptodate">' +
                        '<div class="pkg-upm-uptodate-icon"><i class="feather icon-check-circle"></i></div>' +
                        '<div class="pkg-upm-uptodate-text">' +
                            '<strong>شما آخرین نسخه را نصب دارید</strong>' +
                            '<span>هم‌اکنون نسخه v' + escapeHtml(payload.currentVersion || '') + ' به‌روزترین نسخه این پکیج است.</span>' +
                        '</div>' +
                    '</div>'
                );
                $changelog.hide();
                setCheckButton('uptodate', 'به‌روز است');
                break;

            case STATE.ERROR:
                $state.html(
                    '<div class="pkg-upm-error">' +
                        '<div class="pkg-upm-error-icon"><i class="feather icon-alert-triangle"></i></div>' +
                        '<div class="pkg-upm-error-text">' +
                            '<strong>بررسی ناموفق بود</strong>' +
                            '<span>' + escapeHtml(payload.message || 'خطا در ارتباط با سرور پکیج‌ها. لطفاً دوباره تلاش کنید.') + '</span>' +
                        '</div>' +
                    '</div>'
                );
                $changelog.hide();
                setCheckButton('check', 'تلاش مجدد');
                break;
        }
    }

    /* ============================================================
       رندر changelog (آرایه‌ای یا شیء نسخه‌ها)
       ============================================================ */
    function renderChangelog(changelog) {
        const $box = $('#upm-changelog');
        const $body = $('#upm-changelog-body');

        if (!changelog || (Array.isArray(changelog) && changelog.length === 0) ||
            (!Array.isArray(changelog) && typeof changelog === 'object' && Object.keys(changelog).length === 0)) {
            $box.hide();
            return;
        }

        let html = '<ul>';
        if (Array.isArray(changelog)) {
            changelog.forEach(function (c) {
                html += '<li>' + nl2br(escapeHtml(String(c))) + '</li>';
            });
        } else {
            Object.keys(changelog).forEach(function (ver) {
                const changes = changelog[ver];
                html += '<li><strong>v' + escapeHtml(ver) + ':</strong> ' +
                    nl2br(escapeHtml(Array.isArray(changes) ? changes.join(' — ') : String(changes))) + '</li>';
            });
        }
        html += '</ul>';

        $body.html(html);
        $box.show();
    }

    /* ============================================================
       مدیریت دکمه اصلی فوتر (بررسی / بروزرسانی / به‌روز)
       ============================================================ */
    function setCheckButton(mode, text) {
        const $btn = $('#upm-btn-check');
        const $spinner = $btn.find('.pkg-upm-btn-spinner');
        const $icon = $btn.find('i.feather');
        const $text = $('#upm-btn-check-text');

        $btn.removeClass('is-update');
        $btn.prop('disabled', false);
        $spinner.hide();
        $btn.attr('data-mode', mode);

        if (mode === 'checking') {
            $btn.prop('disabled', true);
            $spinner.show();
            $icon.removeClass().addClass('feather');
            $text.text(text || 'در حال بررسی...');
            return;
        }

        if (mode === 'update') {
            $btn.addClass('is-update');
            $icon.removeClass().addClass('feather icon-arrow-up-circle');
            $text.text(text || 'بروزرسانی');
            return;
        }

        if (mode === 'uptodate') {
            $btn.prop('disabled', true);
            $icon.removeClass().addClass('feather icon-check-circle');
            $text.text(text || 'به‌روز است');
            return;
        }

        // check
        $icon.removeClass().addClass('feather icon-search');
        $text.text(text || 'بررسی نسخه جدید');
    }

    /* ============================================================
       اکشن‌ها
       ============================================================ */

    /** بررسی نسخه جدید — اتصال به API */
    function checkVersion(slug) {
        renderState(STATE.CHECKING, {});

        $.ajax({
            url: route('check-version', slug),
            method: 'GET',
            success: function (resp) {
                if (!resp.success) {
                    renderState(STATE.ERROR, { message: resp.message });
                    return;
                }

                // اگر API تصویر/نام تازه برگرداند، کارت به‌روزرسانی شود
                if (resp.package) {
                    if (resp.package.name) $('#upm-name').text(resp.package.name);
                    if (resp.package.thumbnail_url) {
                        const $thumb = $('#upm-thumb');
                        $thumb.html(
                            $('<img>', { src: resp.package.thumbnail_url, alt: '' })
                                .on('error', function () { $(this).remove(); $thumb.html('<i class="feather icon-package"></i>'); })
                        );
                    }
                }

                if (resp.has_update) {
                    renderState(STATE.HAS_UPDATE, {
                        latestVersion: resp.latest_version,
                        currentVersion: resp.current_version,
                        changelog: resp.changelog,
                    });
                } else {
                    renderState(STATE.UP_TO_DATE, { currentVersion: resp.current_version });
                }
            },
            error: function (xhr) {
                renderState(STATE.ERROR, {
                    message: (xhr.responseJSON && xhr.responseJSON.message) || 'خطا در ارتباط با سرور پکیج‌ها.',
                });
            },
        });
    }

    /** بروزرسانی — صف آپدیت + progress */
    function startUpdate(slug) {
        const $btn = $('#upm-btn-check');
        $btn.prop('disabled', true);
        $btn.find('.pkg-upm-btn-spinner').show();
        $('#upm-btn-check-text').text('در حال قرارگیری در صف...');

        $.ajax({
            url: route('update', slug),
            method: 'POST',
            data: { _token: csrfToken },
            success: function (resp) {
                if (resp.success) {
                    hideModal($('#package-update-modal'));

                    if (window.Swal) {
                        Swal.fire({
                            icon: 'success',
                            title: 'در صف بروزرسانی قرار گرفت',
                            text: resp.message || 'آخرین نسخه به‌صورت خودکار دانلود و نصب می‌شود.',
                            timer: 2200,
                            showConfirmButton: false,
                        });
                    }

                    // progress bar + polling وضعیت
                    const packageName = currentName || slug;
                    if (window.PkgProgress) {
                        window.PkgProgress.startPolling(slug, packageName);
                    } else {
                        setTimeout(function () { location.reload(); }, 2200);
                    }
                } else {
                    $btn.prop('disabled', false);
                    $btn.find('.pkg-upm-btn-spinner').hide();
                    $('#upm-btn-check-text').text('بروزرسانی');
                    if (window.Swal) {
                        Swal.fire({ icon: 'error', title: 'خطا', text: resp.message || 'عملیات ناموفق بود.', confirmButtonText: 'بستن' });
                    }
                }
            },
            error: function (xhr) {
                $btn.prop('disabled', false);
                $btn.find('.pkg-upm-btn-spinner').hide();
                $('#upm-btn-check-text').text('بروزرسانی');
                if (window.Swal) {
                    Swal.fire({
                        icon: 'error',
                        title: 'خطا',
                        text: (xhr.responseJSON && xhr.responseJSON.message) || 'خطا در ارتباط با سرور.',
                        confirmButtonText: 'بستن',
                    });
                }
            },
        });
    }

    /* ============================================================
       اتصال رویدادها
       ============================================================ */
    $(function () {

        // باز کردن مودال با دکمه «آپدیت ماژول» روی کارت‌ها
        $(document).on('click', '.btn-update-module', function () {
            if ($(this).prop('disabled')) return;

            currentSlug = $(this).data('slug');
            currentName = $(this).data('name') || currentSlug;
            latestVersion = null;

            fillPackage({
                slug: currentSlug,
                name: currentName,
                version: $(this).data('version'),
                thumbnail: $(this).data('thumbnail'),
            });

            $('#upm-btn-check').data('slug', currentSlug);
            $('#upm-new-chip').remove();
            $('#upm-version-chip').removeClass('new-version');

            renderState(STATE.IDLE, {});
            showModal($('#package-update-modal'));
        });

        // دکمه اصلی فوتر: بررسی یا بروزرسانی (بسته به state)
        $(document).on('click', '#upm-btn-check', function () {
            const slug = $(this).data('slug') || currentSlug;
            if (!slug) return;

            if (currentState === STATE.HAS_UPDATE) {
                startUpdate(slug);
                return;
            }

            if (currentState === STATE.IDLE || currentState === STATE.ERROR) {
                checkVersion(slug);
            }
        });

        // API عمومی برای استفاده از modal.js (مودال جزئیات)
        window.PkgUpdateModal = {
            open: function (slug) {
                const $btn = $('.btn-update-module[data-slug="' + slug + '"]').first();
                if ($btn.length) {
                    $btn.trigger('click');
                } else {
                    currentSlug = slug;
                    currentName = slug;
                    fillPackage({ slug: slug, name: slug, version: '', thumbnail: '' });
                    $('#upm-btn-check').data('slug', slug);
                    $('#upm-new-chip').remove();
                    $('#upm-version-chip').removeClass('new-version');
                    renderState(STATE.IDLE, {});
                    showModal($('#package-update-modal'));
                }
            },
        };
    });

})(jQuery);
