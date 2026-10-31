/* ============================================================
   Package Detail Modal - Shared logic
   ============================================================ */
(function ($) {
    "use strict";
    let modalLicenseMode = false;
    const csrfToken = window.csrfToken || $('meta[name="csrf-token"]').attr('content');

    function route(name, slug) {
        return (window.routes[name] || '').replace(':slug', slug);
    }
    function number_format(n) {
        return (n || 0).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }
    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }
    function nl2br(str) { return (str || '').replace(/\n/g, '<br>'); }

    /* ============================================================
       Modal Show / Hide (با fallback دستی)
       ============================================================ */
    function showModal($modal) {
        try {
            // روش استاندارد Bootstrap
            if (typeof $.fn.modal === 'function') {
                $modal.modal('show');
                return;
            }
        } catch (e) {
            console.warn('Bootstrap modal not available, using fallback', e);
        }
        // Fallback دستی
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
        } catch (e) {
            console.warn('Bootstrap modal not available, using fallback', e);
        }
        // Fallback دستی
        $modal.removeClass('show').css('display', 'none').attr('aria-hidden', 'true');
        $('body').removeClass('modal-open');
        $('.modal-backdrop').remove();
    }

    /* ============================================================
       Init
       ============================================================ */
    $(function () {
        // باز کردن مدال
        $(document).on('click', '.btn-show-modal', function (e) {
            e.preventDefault();
            e.stopPropagation();
            openDetailModal($(this).data('slug'));
        });

        // کلیک روی کارت
        $(document).on('click', '.pkg-card', function (e) {
            if ($(e.target).closest('button, a').length === 0) {
                const slug = $(this).data('slug');
                if (slug) openDetailModal(slug);
            }
        });

        // بستن مدال با دکمه close (fallback)
        $(document).on('click', '#package-detail-modal .close, #package-detail-modal .pkg-modal-close-btn', function (e) {
            e.preventDefault();
            hideModal($('#package-detail-modal'));
        });

        // بستن مدال با کلیک روی backdrop
        $(document).on('click', '#package-detail-modal', function (e) {
            if (e.target === this) {
                hideModal($(this));
            }
        });

        // ESC برای بستن
        $(document).on('keydown', function (e) {
            if (e.keyCode === 27 && $('#package-detail-modal').hasClass('show')) {
                hideModal($('#package-detail-modal'));
            }
        });

        // delegate اکشن‌های داخل مدال
        // نصب مستقیم از مدال جزئیات (بدون باز کردن مدال تأیید مجدد)
        $(document).on('click', '#modal-btn-install', function () {
            const $btn = $(this);
            const slug = $btn.data('slug');
            const useSubscription = String($btn.attr('data-use-subscription') || '') === '1';
            const useLicense = String($btn.attr('data-use-license') || '') === '1';
            const planId = useLicense ? null : $btn.data('plan-id');
            const originalHtml = $btn.html();

            $btn.prop('disabled', true)
                .html('<span class="spinner-border spinner-border-sm"></span> ' +
                    (useSubscription ? 'در حال بررسی اشتراک...' : (useLicense ? 'در حال بررسی و نصب...' : 'در حال ارسال...')));



            const data = { _token: csrfToken };

            if (useSubscription) {
                data.use_subscription = 1;
            } else if (useLicense) {
                data.use_license = 1;
            } else if (planId) {
                data.pricing_plan_id = planId;
            }

            $.ajax({
                url: route('install', slug),
                method: 'POST',
                data: data,
                success: function (resp) {
                    if (resp.success && resp.redirect_url) {
                        window.location.href = resp.redirect_url;
                    } else if (resp.success) {
                        hideModal($('#package-detail-modal'));
                        const packageName = $('#modal-pkg-title').text().trim() || slug;
                        if (window.PkgProgress) {
                            window.PkgProgress.startPolling(slug, packageName);
                        }
                    } else if (resp.needs_payment) {
                        // لایسنس بین لحظه بررسی و کلیک منقضی شد → مودال با جریان خرید بازنویسی می‌شود
                        modalLicenseMode = false;
                        Swal.fire({
                            icon: 'warning',
                            title: 'لایسنس منقضی شده است',
                            text: resp.message || 'برای نصب، ابتدا پلن موردنظر را انتخاب و پرداخت کنید.',
                            confirmButtonText: 'باشه'
                        }).then(function () { openDetailModal(slug); });
                    } else {
                        Swal.fire({ icon: 'error', title: 'خطا', text: resp.message || 'عملیات ناموفق بود.', confirmButtonText: 'بستن' });
                        $btn.prop('disabled', false).html(originalHtml).removeAttr('data-use-license');
                    }
                },
                error: function (xhr) {
                    Swal.fire({ icon: 'error', title: 'خطا', text: xhr.responseJSON?.message || 'خطا در ارتباط با سرور.', confirmButtonText: 'بستن' });
                    $btn.prop('disabled', false).html(originalHtml).removeAttr('data-use-license');
                }
            });
        });

        $(document).on('click', '#modal-btn-update', function () {
            const slug = $(this).data('slug');
            // فاز ۱۳۳ — جریان یکپارچه: دکمهٔ آپدیت مودال جزئیات هم مودال
            // «بررسی نسخه جدید ← بروزرسانی» را باز می‌کند (نه dispatch مستقیم)
            hideModal($('#package-detail-modal'));
            setTimeout(function () {
                if (window.PkgUpdateModal) {
                    window.PkgUpdateModal.open(slug);
                } else {
                    // fallback: رفتار قدیمی
                    Swal.fire({ title: 'در حال ارسال...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
                    $.ajax({
                        url: route('update', slug), method: 'POST', data: { _token: csrfToken },
                        success: function (resp) {
                            Swal.close();
                            if (resp.success) {
                                Swal.fire({ icon: 'success', title: 'موفق', text: resp.message, timer: 1500, showConfirmButton: false });
                                setTimeout(function () { openDetailModal(slug); }, 1600);
                            } else { Swal.fire({ icon: 'error', title: 'خطا', text: resp.message }); }
                        },
                        error: function (xhr) { Swal.close(); Swal.fire({ icon: 'error', title: 'خطا', text: xhr.responseJSON?.message }); }
                    });
                }
            }, 300);
        });

        $(document).on('click', '#modal-btn-toggle', function () {
            const slug = $(this).data('slug');
            $.ajax({
                url: route('toggle', slug), method: 'POST', data: { _token: csrfToken },
                success: function (resp) {
                    if (resp.success) {
                        Swal.fire({ icon: 'success', title: 'موفق', text: resp.message, timer: 1200, showConfirmButton: false });
                        setTimeout(function () { openDetailModal(slug); }, 1300);
                    } else { Swal.fire({ icon: 'error', title: 'خطا', text: resp.message }); }
                }
            });
        });

        $(document).on('click', '#modal-btn-uninstall', function () {
            const slug = $(this).data('slug');
            const name = $(this).data('name');
            Swal.fire({
                title: 'حذف ماژول',
                html: 'آیا از حذف <strong>' + escapeHtml(name) + '</strong> مطمئن هستید؟<br><small class="text-danger">تمام فایل‌ها حذف و جداول rollback می‌شوند.</small>',
                icon: 'warning', showCancelButton: true,
                confirmButtonText: 'بله، حذف شود', cancelButtonText: 'انصراف', confirmButtonColor: '#d33'
            }).then(function (result) {
                if (result.value) {
                    Swal.fire({ title: 'در حال حذف...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
                    $.ajax({
                        url: route('uninstall', slug), method: 'POST', data: { _token: csrfToken },
                        success: function (resp) {
                            Swal.close();
                            if (resp.success) {
                                Swal.fire({ icon: 'success', title: 'موفق', text: resp.message, timer: 1500, showConfirmButton: false });
                                hideModal($('#package-detail-modal'));
                                setTimeout(function () { location.reload(); }, 1600);
                            } else { Swal.fire({ icon: 'error', title: 'خطا', text: resp.message }); }
                        },
                        error: function (xhr) { Swal.close(); Swal.fire({ icon: 'error', title: 'خطا', text: xhr.responseJSON?.message }); }
                    });
                }
            });
        });
    });

    /* ============================================================
       باز کردن مدال و دریافت اطلاعات
       ============================================================ */
    function openDetailModal(slug) {
        const $modal = $('#package-detail-modal');
        const $body = $('#modal-pkg-body');
        const $footer = $('#modal-pkg-footer');
        const $title = $('#modal-pkg-title');

        $title.html('');
        $body.html(renderLoading());
        $footer.html('');

        showModal($modal);

        $.ajax({
            url: route('show', slug), method: 'GET',
            success: function (resp) {
                if (!resp.success) {
                    $body.html(renderError(resp.message || 'خطا در دریافت اطلاعات'));
                    return;
                }
                renderDetail(resp.data, resp.logs);
            },
            error: function (xhr) {
                $body.html(renderError(xhr.responseJSON?.message || 'خطا در ارتباط با سرور'));
            }
        });
    }

    /* ============================================================
       رندر محتوا
       ============================================================ */
    function renderDetail(pkg, logs) {
        modalLicenseMode = false;   // ★ ریست برای هر باز شدن مودال

        const $body = $('#modal-pkg-body');
        const $footer = $('#modal-pkg-footer');
        const $title = $('#modal-pkg-title');

        const slug = pkg.slug || '';
        const name = pkg.name || slug;
        const latestVersion = pkg.latestVersion;
        const isFree = pkg.is_free || false;
        const price = pkg.price || pkg.default_price || 0;
        const plans = pkg.active_pricing_plans || pkg.pricing_plans || pkg.plans || [];
        const installed = pkg.installed;
        const hasUpdate = pkg.has_update;
        const thumbnail = pkg.thumbnail_url || pkg.thumbnail || '';
        const subscription = pkg.subscription || null;
        const hasActiveSubscription = !!(subscription && subscription.is_free_with_subscription);
        // عنوان در header
        $title.html('<i class="feather icon-package"></i> ' + escapeHtml(name));

        let html = '';

        // ---- Hero ----
        html += '<div class="pkg-modal-hero">';
        html += '<div class="pkg-modal-hero-content">';
        if (thumbnail) {
            html += '<div class="pkg-modal-thumb" style="background-image: url(\'' + thumbnail + '\')"></div>';
        } else {
            html += '<div class="pkg-modal-thumb pkg-modal-thumb-icon"><i class="feather icon-package"></i></div>';
        }
        html += '<div class="pkg-modal-hero-info">';
        html += '<h3 class="pkg-modal-hero-title">' + escapeHtml(name) + ' <span class="pkg-modal-version">v' + escapeHtml(latestVersion) + '</span></h3>';
        html += '<div class="pkg-modal-badges">';
        html += isFree
            ? '<span class="pkg-modal-badge pkg-modal-badge-free">رایگان</span>'
            : '<span class="pkg-modal-badge pkg-modal-badge-paid">پولی</span>';
        if (installed && installed.status === 'installed') {
            html += '<span class="pkg-modal-badge" style="background:rgba(255,255,255,0.25);">نصب‌شده v' + escapeHtml(installed.version) + '</span>';
        }

        if (hasActiveSubscription) {
            html += '<span class="pkg-modal-badge pkg-modal-badge-subscription"><i class="feather icon-award"></i> رایگان با اشتراک</span>';
        }

        html += '</div>';

        html += '<div class="pkg-modal-meta">';
        if (pkg.author) html += '<span class="pkg-modal-meta-chip"><i class="feather icon-user"></i> ' + escapeHtml(pkg.author) + '</span>';
        if (pkg.category) html += '<span class="pkg-modal-meta-chip"><i class="feather icon-tag"></i> ' + escapeHtml(pkg.category) + '</span>';
        if (pkg.downloads) html += '<span class="pkg-modal-meta-chip"><i class="feather icon-download"></i> ' + number_format(pkg.downloads) + ' دانلود</span>';
        html += '</div>';

        if (pkg.short_description) {
            // استفاده از jQuery برای append
            var $desc = $('<p class="pkg-modal-desc"></p>').html(pkg.short_description);
            html += $desc[0].outerHTML;
        }
        html += '</div></div></div>';

        // ---- Content Area ----
        html += '<div class="pkg-modal-content-area">';

        // gallery
        if (pkg.images && pkg.images.length > 0) {
            html += '<div class="pkg-section">';
            html += '<h6 class="pkg-section-title"><i class="feather icon-grid"></i> گالری تصاویر <small class="text-muted">(' + pkg.images.length + ')</small></h6>';
            html += '<div class="pkg-gallery">';
            pkg.images.forEach(function (img) {
                const url = img.url || img.path || img;
                html += '<a href="' + url + '" target="_blank" class="pkg-gallery-item">';
                html += '<img src="' + url + '" alt="' + escapeHtml(img.alt || img.original_name || '') + '" loading="lazy">';
                html += '</a>';
            });
            html += '</div></div>';
        }

        // long description
        if (pkg.long_description || pkg.description) {
            var descContent = pkg.long_description || pkg.description;

            // استفاده از jQuery برای تنظیم HTML
            var $desc = $('<div class="pkg-long-desc"></div>').html(descContent);

            html += '<div class="pkg-section">';
            html += '<h6 class="pkg-section-title"><i class="feather icon-file-text"></i> توضیحات کامل</h6>';
            html += $desc[0].outerHTML;
            html += '</div>';
        }
        // changelog
        if (pkg.changelog && (Object.keys(pkg.changelog).length > 0 || Array.isArray(pkg.changelog))) {
            html += '<div class="pkg-section">';
            html += '<h6 class="pkg-section-title"><i class="feather icon-git-commit"></i> تاریخچه نسخه‌ها</h6>';
            html += '<ul class="pkg-changelog">';
            if (Array.isArray(pkg.changelog)) {
                pkg.changelog.forEach(function (c) {
                    html += '<li><strong>•</strong><span>' + nl2br(escapeHtml(c)) + '</span></li>';
                });
            } else {
                Object.keys(pkg.changelog).forEach(function (ver) {
                    const changes = pkg.changelog[ver];
                    html += '<li><strong>v' + escapeHtml(ver) + '</strong><span>' + nl2br(escapeHtml(Array.isArray(changes) ? changes.join('<br>') : changes)) + '</span></li>';
                });
            }
            html += '</ul></div>';
        }

        // what_added / changed / fixed
        const versions = pkg.versions || [];
        if (versions.length > 0) {
            const latest = versions[0];
            if (latest.what_added || latest.what_changed || latest.what_fixed) {
                html += '<div class="pkg-section">';
                html += '<h6 class="pkg-section-title"><i class="feather icon-info"></i> تغییرات نسخه v' + escapeHtml(latest.version || latestVersion) + '</h6>';
                if (latest.what_added) {
                    html += '<div class="pkg-change-block pkg-change-added"><strong><i class="feather icon-plus-circle"></i> قابلیت‌های جدید</strong><p>' + nl2br(escapeHtml(latest.what_added)) + '</p></div>';
                }
                if (latest.what_changed) {
                    html += '<div class="pkg-change-block pkg-change-changed"><strong><i class="feather icon-edit-3"></i> تغییرات</strong><p>' + nl2br(escapeHtml(latest.what_changed)) + '</p></div>';
                }
                if (latest.what_fixed) {
                    html += '<div class="pkg-change-block pkg-change-fixed"><strong><i class="feather icon-check-circle"></i> رفع باگ</strong><p>' + nl2br(escapeHtml(latest.what_fixed)) + '</p></div>';
                }
                html += '</div>';
            }
        }

        // requirements
        if (pkg.requirements && Object.keys(pkg.requirements).length > 0) {
            html += '<div class="pkg-section">';
            html += '<h6 class="pkg-section-title"><i class="feather icon-info"></i> پیش‌نیازها</h6>';
            html += '<ul class="pkg-requirements">';
            Object.keys(pkg.requirements).forEach(function (req) {
                html += '<li><code>' + escapeHtml(req) + '</code> &gt;= ' + escapeHtml(pkg.requirements[req]) + '</li>';
            });
            html += '</ul></div>';
        }

        // pricing plans (اگه نصب نشده و پلن داره)
        let cheapestPlanId = null;
        if (!installed && plans && plans.length > 0 && !hasActiveSubscription) {
            // پیدا کردن ارزان‌ترین پلن
            let cheapest = null;
            plans.forEach(function (p) {
                const fp = parseInt(p.discount_price ?? p.price ?? 0);
                if (!cheapest || fp < parseInt(cheapest.discount_price ?? cheapest.price ?? 0)) {
                    cheapest = p;
                }
            });
            cheapestPlanId = cheapest ? cheapest.id : null;

            html += '<div class="pkg-section" id="modal-plans-section">';
            html += '<h6 class="pkg-section-title"><i class="feather icon-tag"></i> طرح‌های قیمت‌گذاری <small class="text-muted">یک طرح انتخاب کنید</small></h6>';
            html += '<div class="pkg-plans-list row" id="modal-plans-list">';
            plans.forEach(function (plan) {
                const finalPrice = parseInt(plan.discount_price ?? plan.price ?? 0);
                const planIsFree = (finalPrice === 0);
                const hasDiscount = plan.discount_price !== null && plan.discount_price !== undefined && plan.discount_price < plan.price;
                const isOneTime = plan.is_one_time === true || plan.is_one_time === 1 || plan.is_one_time === '1';
                const isCheapest = cheapest && plan.id === cheapest.id;
                const months = plan.duration_months || 0;
                const durationLabel = months === 0 ? 'نامحدود' : (months < 12 ? months + ' ماه' : (months / 12) + ' سال');

                // badges
                let badges = '';
                if (isOneTime) {
                    badges += '<span class="pkg-plan-badge pkg-plan-badge-one-time">یک‌بار مصرف</span>';
                }
                if (isCheapest) {
                    badges += '<span class="pkg-plan-badge pkg-plan-badge-cheapest">ارزان‌ترین</span>';
                }

                // price
                let priceHtml = '';
                if (planIsFree) {
                    priceHtml = '<div class="pkg-plan-price"><span class="pkg-plan-price-free">رایگان</span></div>';
                } else {
                    priceHtml = '<div class="pkg-plan-price">';
                    if (hasDiscount) {
                        priceHtml += '<span class="pkg-plan-price-original">' + number_format(plan.price) + '</span>';
                    }
                    priceHtml += '<span class="pkg-plan-price-final">' + number_format(finalPrice) + '</span>';
                    priceHtml += '<span class="pkg-plan-price-unit">تومان</span>';
                    priceHtml += '</div>';
                }

                const descHtml = plan.description
                    ? '<div class="pkg-plan-desc">' + escapeHtml(plan.description) + '</div>'
                    : '';

                html += `
                    <div class="col-md-3 mr-1 pkg-plan-card ${isCheapest ? 'selected' : ''}" data-plan-id="${plan.id}" data-plan-price="${finalPrice}" data-plan-free="${planIsFree ? '1' : '0'}">
                        <div class="pkg-plan-radio"></div>
                        <div class="pkg-plan-info">
                            <div class="pkg-plan-name">${escapeHtml(plan.name)}</div>
                            <div class="pkg-plan-duration"><i class="feather icon-clock"></i> ${durationLabel}</div>
                            ${badges ? '<div class="pkg-plan-badges">' + badges + '</div>' : ''}
                        </div>
                        ${priceHtml}
                        ${descHtml}
                    </div>
                `;
            });
            html += '</div></div>';
        }

        // status
        if (installed) {
            html += '<div class="pkg-section">';
            html += '<h6 class="pkg-section-title"><i class="feather icon-settings"></i> وضعیت نصب</h6>';
            html += '<div class="pkg-status-box ';
            if (installed.status === 'updating') html += 'status-running';
            else if (installed.status === 'failed') html += 'status-failed';
            else html += 'status-installed';
            html += '">';
            if (installed.status === 'updating') {
                html += '<div class="spinner-border spinner-border-sm"></div><div><strong>در حال نصب/آپدیت...</strong></div>';

                if (installed.status !== 'installed') {
                    setTimeout(function() {
                        openDetailModal(slug);
                    }, 10000);
                }

            } else if (installed.status === 'failed') {
                html += '<i class="feather icon-alert-triangle"></i><div><strong>نصب با خطا مواجه شد</strong>';
                if (installed.last_error) html += '<br><small>' + escapeHtml(installed.last_error) + '</small>';
                html += '</div>';
            } else {
                html += '<i class="feather icon-check-circle"></i><div><strong>نصب‌شده - نسخه ' + escapeHtml(installed.version) + '</strong>';
                if (installed.license_expires_at) {
                    html += '<br><small><i class="feather icon-clock"></i> انقضا: ' + escapeHtml(installed.license_expires_at) + ' (' + escapeHtml(installed.license_expires_human || '') + ')';
                    if (installed.is_expired) html += ' <span class="text-danger">(منقضی)</span>';
                    html += '</small>';
                }
                html += '<br><small>' + (installed.is_active
                    ? '<span class="text-success"><i class="feather icon-check"></i> فعال</span>'
                    : '<span class="text-warning"><i class="feather icon-eye-off"></i> غیرفعال</span>') + '</small>';
                html += '</div>';
            }
            html += '</div></div>';
        }

        // logs
        if (logs && logs.length > 0) {
            html += '<div class="pkg-section">';
            html += '<h6 class="pkg-section-title"><i class="feather icon-list"></i> لاگ‌های اخیر</h6>';
            html += '<div class="table-responsive"><table class="pkg-logs-table">';
            html += '<thead><tr>';
            html += '<th>عملیات</th><th>از</th><th>به</th><th>وضعیت</th><th>تاریخ</th><th>پیام</th>';
            html += '</tr></thead><tbody>';
            logs.forEach(function (log) {
                html += '<tr>';
                const actionLabels = { install: 'نصب', update: 'آپدیت', uninstall: 'حذف', activate: 'فعال‌سازی', deactivate: 'غیرفعال‌سازی' };
                html += '<td><span class="channel-pill ch-notif">' + escapeHtml(actionLabels[log.action] || log.action) + '</span></td>';
                html += '<td>' + escapeHtml(log.from_version || '—') + '</td>';
                html += '<td>' + escapeHtml(log.to_version || '—') + '</td>';
                const statusPills = {
                    running: '<span class="status-pill status-pending"><span class="dot"></span> در حال اجرا</span>',
                    success: '<span class="status-pill status-sent"><span class="dot"></span> موفق</span>',
                    failed:  '<span class="status-pill status-failed"><span class="dot"></span> ناموفق</span>'
                };
                html += '<td>' + (statusPills[log.status] || log.status) + '</td>';
                html += '<td>' + escapeHtml(log.created_at) + '</td>';
                html += '<td style="max-width:220px;"><span class="d-inline-block text-truncate" style="max-width:220px" title="' + escapeHtml(log.message || '') + '">' + escapeHtml(log.message || '—') + '</span></td>';
                html += '</tr>';
            });
            html += '</tbody></table></div></div>';
        }

        html += '</div>'; // pkg-modal-content-area

        $body.html(html);

        // ---- Footer ----
        // محاسبه کمترین قیمت از پلن‌ها
        let minPrice = price;
        let initialPlanId = null;
        let initialPlanPrice = null;
        let initialPlanIsFree = isFree;

        if (plans && plans.length > 0) {
            const planPrices = plans.map(function (p) {
                return parseInt(p.discount_price ?? p.price ?? 0);
            });
            minPrice = Math.min.apply(null, planPrices);

            // پیدا کردن پلن ارزان‌ترین
            const cheapestPlan = plans.find(function (p) {
                return parseInt(p.discount_price ?? p.price ?? 0) === minPrice;
            });
            if (cheapestPlan) {
                initialPlanId = cheapestPlan.id;
                initialPlanPrice = minPrice;
                initialPlanIsFree = (minPrice === 0);
            }
        }

        let footerHtml = '<div class="pkg-modal-price p-1" id="modal-price-display">';
        if (!installed && hasActiveSubscription) {
            footerHtml += '<span class="pkg-modal-price-free"><i class="feather icon-award"></i> رایگان با اشتراک</span>';
        } else if (isFree) {
            footerHtml += '<span class="pkg-modal-price-free">رایگان</span>';
        } else if (initialPlanIsFree) {
            footerHtml += '<span class="pkg-modal-price-free">رایگان</span>';
        } else {
            footerHtml += '<span class="pkg-modal-price-value" id="modal-price-value">' + number_format(initialPlanPrice ?? minPrice) + '</span> <span class="text-muted">تومان</span>';
        }
        footerHtml += '</div>';

        footerHtml += '<div class="pkg-modal-actions">';

        if (!installed) {
            const subMode = hasActiveSubscription;
            footerHtml += '<button type="button" id="modal-btn-install" class="pkg-modal-btn ' +
                ((subMode || initialPlanIsFree) ? 'pkg-modal-btn-primary' : 'pkg-modal-btn-warning') +
                '" data-slug="' + slug + '" data-plan-id="' + (initialPlanId ?? '') + '"' +
                (subMode ? ' data-use-subscription="1"' : '') + '>';
            footerHtml += '<i class="feather ' + (subMode ? 'icon-award' : (initialPlanIsFree ? 'icon-download-cloud' : 'icon-credit-card')) + '"></i> <span id="modal-install-text">' +
                (subMode ? 'نصب با اشتراک' : (initialPlanIsFree ? 'نصب پکیج' : 'پرداخت و نصب')) + '</span>';
            footerHtml += '</button>';
        } else if (installed.status === 'updating') {
            footerHtml += '<button type="button" class="pkg-modal-btn pkg-modal-btn-warning" disabled><span class="spinner-border spinner-border-sm"></span> در حال نصب...</button>';
        } else {
            if (hasUpdate) {
                const expired = installed.is_expired;
                footerHtml += '<button type="button" id="modal-btn-update" class="pkg-modal-btn pkg-modal-btn-warning" data-slug="' + slug + '" ' + (expired ? 'disabled' : '') + '>';
                footerHtml += '<i class="feather icon-arrow-up"></i> آپدیت به v' + escapeHtml(latestVersion);
                footerHtml += '</button>';
                if (expired) footerHtml += '<small class="text-danger align-self-center">لایسنس منقضی</small>';
            } else {
                footerHtml += '<span class="pkg-modal-alert-success"><i class="feather icon-check-circle"></i> آخرین نسخه نصب شده</span>';
            }
            footerHtml += '<button type="button" id="modal-btn-toggle" class="pkg-modal-btn ' + (installed.is_active ? 'pkg-modal-btn-outline' : 'pkg-modal-btn-success') + '" data-slug="' + slug + '">';
            footerHtml += '<i class="feather icon-' + (installed.is_active ? 'eye-off' : 'eye') + '"></i> ' + (installed.is_active ? 'غیرفعال‌سازی' : 'فعال‌سازی');
            footerHtml += '</button>';
            footerHtml += '<button type="button" id="modal-btn-uninstall" class="pkg-modal-btn pkg-modal-btn-danger" data-slug="' + slug + '" data-name="' + escapeHtml(name) + '">';
            footerHtml += '<i class="feather icon-trash-2"></i> حذف';
            footerHtml += '</button>';
        }
        footerHtml += '</div>';

        $footer.html(footerHtml);


        // اتصال event listener برای انتخاب پلن در مدال جزئیات
        if (!installed && plans && plans.length > 0 && !hasActiveSubscription) {
            $('#modal-pkg-body').off('click', '.pkg-plan-card').on('click', '.pkg-plan-card', function () {
                // ★ در حالت نصب مجدد با لایسنس، پلن‌ها معنا ندارند
                if (modalLicenseMode) return;

                // آپدیت selected
                $('#modal-pkg-body .pkg-plan-card').removeClass('selected');
                $(this).addClass('selected');

                const planId = $(this).data('plan-id');
                const planPrice = parseInt($(this).data('plan-price')) || 0;
                const planIsFree = $(this).data('plan-free') === '1' || planPrice === 0;

                // آپدیت قیمت نمایش‌داده‌شده در footer
                const $priceDisplay = $('#modal-price-display');
                if (planIsFree) {
                    $priceDisplay.html('<span class="pkg-modal-price-free">رایگان</span>');
                } else {
                    $priceDisplay.html('<span class="pkg-modal-price-value">' + number_format(planPrice) + '</span> <span class="text-muted">تومان</span>');
                }

                // آپدیت دکمه‌ی نصب
                const $btn = $('#modal-btn-install');
                const $icon = $btn.find('i');
                const $text = $('#modal-install-text');
                $btn.removeAttr('data-use-license');   // ★ خروج از حالت لایسنس (اگر بوده)
                $btn.data('plan-id', planId);

                if (planIsFree) {
                    $btn.removeClass('pkg-modal-btn-warning').addClass('pkg-modal-btn-primary');
                    $icon.removeClass('icon-credit-card icon-rotate-ccw').addClass('icon-download-cloud');
                    $text.text('نصب پکیج');
                } else {
                    $btn.removeClass('pkg-modal-btn-primary').addClass('pkg-modal-btn-warning');
                    $icon.removeClass('icon-download-cloud icon-rotate-ccw').addClass('icon-credit-card');
                    $text.text('پرداخت و نصب');
                }
            });
        }

        // ★★★ بررسی خرید قبلی — باید بعد از رندر footer باشد
        const isPurchased = !!(pkg.purchased || pkg.is_purchased);

        // ★ اولویت: اشتراک فعال → خرید قبلی
        if (!installed && hasActiveSubscription) {
            applyModalSubscriptionMode(subscription);
        } else if (!installed && !isFree && isPurchased) {
            checkModalLicense(slug);
        }
    }

    /* ============================================================
   نصب مجدد با لایسنس (پس از خرید قبلی)
   ============================================================ */
    function checkModalLicense(slug) {
        const $btn = $('#modal-btn-install');
        if (!$btn.length) return;

        const originalHtml = $btn.html();
        $btn.prop('disabled', true)
            .html('<span class="spinner-border spinner-border-sm"></span> در حال بررسی لایسنس...');

        $.ajax({
            url: route('checkPurchase', slug),
            method: 'POST',
            data: { _token: csrfToken }
        }).done(function (resp) {
            if (resp.purchased && resp.valid) {
                applyModalLicenseMode(resp);
                return;
            }
            if (resp.purchased) {
                applyModalLicenseExpired(resp.message);
            }
        }).fail(function () {
            $btn.prop('disabled', false).html(originalHtml);
        });
    }

    function applyModalLicenseMode(resp) {
        modalLicenseMode = true;

        // ★ هندلر انتخاب پلن از کار افتد + پلن‌ها حذف شوند
        $('#modal-pkg-body').off('click', '.pkg-plan-card');
        $('#modal-plans-section').remove();

        let expiryText = 'نامحدود';
        if (resp.expires_at) {
            const days = daysRemaining(resp.expires_at);
            expiryText = toJalali(resp.expires_at) +
                (days !== null ? ' — ' + number_format(Math.max(days, 0)) + ' روز باقی‌مانده' : '');
        }
        let subLine = 'لایسنس شما فعال است <span class="text-muted">(اعتبار تا ' + escapeHtml(expiryText) + ')</span>';
        if (resp.version) subLine += ' — نسخه قابل نصب: v' + escapeHtml(resp.version);

        const box =
            '<div class="pkg-section" id="modal-license-section">' +
            '<h6 class="pkg-section-title"><i class="feather icon-shield"></i> وضعیت لایسنس</h6>' +
            '<div class="pkg-license-box">' +
            '<div class="pkg-license-icon"><i class="feather icon-check-circle"></i></div>' +
            '<div class="pkg-license-body">' +
            '<strong>این پکیج قبلاً خریداری شده — بدون پرداخت نصب می‌شود</strong>' +
            '<p class="mb-0">' + subLine + '</p>' +
            '</div>' +
            '</div>' +
            '</div>';

        $('#modal-pkg-body .pkg-modal-content-area').prepend(box);

        // دکمه → «بررسی و نصب»
        $('#modal-btn-install')
            .prop('disabled', false)
            .html('بررسی و نصب')
            .removeClass('pkg-modal-btn-warning').addClass('pkg-modal-btn-primary')
            .attr('data-use-license', '1')
            .find('i').removeClass('icon-credit-card icon-download-cloud').addClass('icon-rotate-ccw');

        // قیمت → بدون پرداخت
        $('#modal-price-display').html(
            '<span class="pkg-modal-price-free"><i class="feather icon-check-circle"></i> لایسنس فعال — بدون پرداخت</span>'
        );
    }

    function applyModalLicenseExpired(message) {
        const $plans = $('#modal-plans-section');
        if (!$plans.length) return;

        $plans.find('.pkg-license-warning').remove();
        $plans.prepend(
            '<div class="pkg-license-warning mb-2">' +
            '<i class="feather icon-alert-triangle"></i>' +
            '<span>' + escapeHtml(message || 'لایسنس قبلی شما منقضی شده است؛ برای نصب مجدد، پلن را انتخاب و پرداخت کنید.') + '</span>' +
            '</div>'
        );

        // ★ در جریان خرید معمولی می‌مانیم — دکمه به «پرداخت و نصب» با پلن ارزان برگردد
        modalLicenseMode = false;
        const $btn = $('#modal-btn-install').prop('disabled', false).removeAttr('data-use-license');
        const planPrice = parseInt($('#modal-pkg-body .pkg-plan-card.selected').data('plan-price')) || 0;
        if (planPrice === 0) {
            $btn.html('نصب پکیج').removeClass('pkg-modal-btn-warning').addClass('pkg-modal-btn-primary')
                .find('i').removeClass('icon-credit-card icon-rotate-ccw').addClass('icon-download-cloud');
            $('#modal-install-text').text('نصب پکیج');
        } else {
            $btn.html('پرداخت و نصب').removeClass('pkg-modal-btn-primary').addClass('pkg-modal-btn-warning')
                .find('i').removeClass('icon-download-cloud icon-rotate-ccw').addClass('icon-credit-card');
            $('#modal-install-text').text('پرداخت و نصب');
        }
    }

    function toJalali(dateStr) {
        if (!dateStr) return '';
        const d = new Date(dateStr);
        if (isNaN(d.getTime())) return dateStr;
        let gy = d.getFullYear(), gm = d.getMonth() + 1, gd = d.getDate();
        const g_d_m = [0,31,59,90,120,151,181,212,243,273,304,334];
        let jy = (gy <= 1600) ? 0 : 979;
        gy -= (gy <= 1600) ? 621 : 1600;
        const gy2 = (gm > 2) ? (gy + 1) : gy;
        let days = (365*gy) + Math.floor((gy2+3)/4) - Math.floor((gy2+99)/100) + Math.floor((gy2+399)/400) - 80 + gd + g_d_m[gm-1];
        jy += 33 * Math.floor(days/12053); days %= 12053;
        jy += 4 * Math.floor(days/1461);   days %= 1461;
        if (days > 365) { jy += Math.floor((days-1)/365); days = (days-1)%365; }
        const jm = (days < 186) ? 1 + Math.floor(days/31) : 7 + Math.floor((days-186)/30);
        const jd = 1 + ((days < 186) ? (days%31) : ((days-186)%30));
        return jy + '/' + String(jm).padStart(2,'0') + '/' + String(jd).padStart(2,'0');
    }

    function daysRemaining(dateStr) {
        const d = new Date(dateStr);
        if (isNaN(d.getTime())) return null;
        return Math.ceil((d.getTime() - Date.now()) / 86400000);
    }

    /* ============================================================
       رندر حالت‌های loading و error
       ============================================================ */
    function renderLoading() {
        return '<div class="pkg-modal-loading"><div class="spinner-border" role="status"></div><p>در حال دریافت اطلاعات پکیج...</p></div>';
    }

    function renderError(msg) {
        return '<div class="pkg-modal-content-area"><div class="alert alert-danger m-3"><i class="feather icon-alert-octagon"></i> ' + escapeHtml(msg) + '</div></div>';
    }

    function applyModalSubscriptionMode(sub) {
        modalLicenseMode = false;

        let expiryText = 'نامحدود';
        if (sub.subscription_expires_at) {
            let days = (sub.days_remaining !== undefined && sub.days_remaining !== null)
                ? parseInt(sub.days_remaining)
                : daysRemaining(sub.subscription_expires_at);
            /* فاز ۱۰۰ — به‌جای «۰ روز» عبارت «کمتر از ۱ روز» (اشتراک فعال ولی کمتر از ۲۴ ساعت مانده) */
            const expDate = new Date(sub.subscription_expires_at);
            const stillActive = !isNaN(expDate.getTime()) && expDate.getTime() > Date.now();
            if (stillActive && days !== null && !isNaN(days) && Math.max(days, 0) === 0) {
                expiryText = toJalali(sub.subscription_expires_at) + ' — کمتر از ۱ روز باقی‌مانده';
            } else {
                expiryText = toJalali(sub.subscription_expires_at) +
                    (days !== null && !isNaN(days) ? ' — ' + number_format(Math.max(days, 0)) + ' روز باقی‌مانده' : '');
            }
        }

        const box =
            '<div class="pkg-section" id="modal-subscription-section">' +
            '<h6 class="pkg-section-title"><i class="feather icon-award"></i> وضعیت اشتراک</h6>' +
            '<div class="pkg-sub-box">' +
            '<div class="pkg-sub-icon"><i class="feather icon-award"></i></div>' +
            '<div class="pkg-sub-body">' +
            '<strong>این پکیج با اشتراک شما رایگان است — بدون پرداخت نصب می‌شود</strong>' +
            '<p class="mb-0">طرح: <strong>' + escapeHtml(sub.plan_name || 'اشتراک') + '</strong>' +
            ' <span class="text-muted">(اعتبار تا ' + escapeHtml(expiryText) + ')</span></p>' +
            '</div>' +
            '</div>' +
            '</div>';

        $('#modal-pkg-body .pkg-modal-content-area').prepend(box);
        // دکمه و قیمت در footer از قبل با حالت subscription رندر شده‌اند
    }

})(jQuery);
