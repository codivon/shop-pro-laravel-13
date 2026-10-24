/* ============================================================
   پنل کاربر سانیار — فاز F (۵۵)
   یک فایل مشترک برای همه صفحات پنل؛ هر بخش با گاردِ وجودِ
   المنت فعال می‌شود (هم‌الگوی app.js):
   ۱) توست سروری + توست مشترک
   ۲) ویرایش پروفایل (AJAX + redirect)
   ۳) آدرس‌ها (مودال افزودن/ویرایش + شهرها + حذف + پیشفرض)
   ۴) کیف پول (مودال جزئیات تراکنش + ولیدیشن فرم‌ها)
   ۵) پیام‌ها (مودال جزئیات)
   ۶) تیکت‌ها (ثبت/پاسخ با فایل — FormData)
   ۷) نظرات (ویرایش مودال + حذف + نقاط قوت/ضعف)
   ۸) بازدیدهای اخیر (حذف)
   ۹) معرفی‌ها (کپی کد/لینک)
   ۱۰) پیش‌نمایش تصاویر مرجوعی + tooltip
   ============================================================ */
(function () {
    'use strict';

    var $ = window.jQuery;
    if (!$) return;

    function csrfToken() {
        return $('meta[name="csrf-token"]').attr('content');
    }

    /* ================= توست سبک سانیار ================= */
    function toast(message, type) {
        var $t = $('#saniar-toast');
        if (!$t.length) {
            $t = $('<div id="saniar-toast" class="saniar-toast"></div>').appendTo('body');
        }
        $t.text(message)
            .removeClass('saniar-toast-error saniar-toast-success')
            .addClass(type === 'error' ? 'saniar-toast-error' : 'saniar-toast-success')
            .addClass('saniar-toast-show');
        clearTimeout($t.data('timer'));
        $t.data('timer', setTimeout(function () { $t.removeClass('saniar-toast-show'); }, 3600));
    }

    /* ================= ۱) توست سروری (query param) ================= */
    var $serverToast = $('.saniar-panel-toast');
    if ($serverToast.length) {
        setTimeout(function () {
            $serverToast.fadeOut(350, function () { $(this).remove(); });
        }, 5000);
    }

    /* ================= ۲) ویرایش پروفایل ================= */
    var $profileForm = $('#profile-edit-form');
    if ($profileForm.length) {
        $profileForm.on('submit', function (e) {
            e.preventDefault();
            var $form = $(this);
            var $btn = $form.find('button[type="submit"]').prop('disabled', true);

            $.ajax({
                url: $form.attr('action'),
                type: 'POST',
                data: $form.serialize(),
                headers: { 'X-CSRF-TOKEN': csrfToken(), 'X-Requested-With': 'XMLHttpRequest' }
            })
                .done(function () {
                    toast('اطلاعات با موفقیت ذخیره شد.', 'success');
                    setTimeout(function () {
                        window.location.href = $form.attr('data-redirect') || window.location.href;
                    }, 700);
                })
                .fail(function (xhr) {
                    $btn.prop('disabled', false);
                    var msg = 'خطا در ذخیره اطلاعات؛ دوباره تلاش کنید.';
                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        var first = null;
                        $.each(xhr.responseJSON.errors, function (k, v) { first = v[0]; return false; });
                        msg = first || msg;
                    }
                    toast(msg, 'error');
                });
        });
    }

    /* ================= ۳) آدرس‌ها ================= */
    var addressModalEl = document.getElementById('add-edit-address-modal');

    function loadCities(provinceId, selectedCityId) {
        var $city = $('#city');
        if (!provinceId) return $.Deferred().resolve().promise();
        return $.ajax({
            url: '/province/get-cities',
            type: 'GET',
            data: { id: provinceId },
            dataType: 'json'
        })
            .done(function (cities) {
                $city.empty().append('<option value="">انتخاب شهر...</option>');
                $.each(cities, function (i, c) {
                    $('<option>').val(c.id).text(c.name).appendTo($city);
                });
                if (selectedCityId) {
                    $city.val(String(selectedCityId));
                }
            })
            .fail(function () {
                toast('خطا در دریافت شهرها.', 'error');
            });
    }

    function setFormMethod($form, method) {
        var $m = $form.find('input[name="_method"]');
        if (method) {
            if (!$m.length) {
                $m = $('<input type="hidden" name="_method">').prependTo($form);
            }
            $m.val(method);
        } else if ($m.length) {
            $m.remove();
        }
    }

    if (addressModalEl) {
        addressModalEl.addEventListener('show.bs.modal', function (event) {
            var $trigger = $(event.relatedTarget);
            var $form = $('#add-update-address-form');
            var isEdit = !!$trigger.data('updateUrl') && !!$trigger.data('infoUrl');

            // بازنشانی
            $form[0].reset();
            $form.find('.is-invalid').removeClass('is-invalid');
            $('#address-form-error').addClass('d-none').empty();
            $('#city').html('<option value="">ابتدا استان را انتخاب کنید...</option>');
            $form.attr('action', $trigger.data('updateUrl') || $trigger.attr('data-update-url'));
            $('#add-edit-address-modal-label').text(isEdit ? 'ویرایش آدرس' : 'ثبت آدرس جدید');
            $('#address-submit-btn').text(isEdit ? 'ذخیره تغییرات' : 'ثبت آدرس');

            // آدرس جدید = POST (بدون _method)؛ ویرایش = PUT
            setFormMethod($form, isEdit ? 'PUT' : null);

            if (isEdit) {
                $.ajax({
                    url: $trigger.data('infoUrl'),
                    type: 'GET',
                    dataType: 'json'
                })
                    .done(function (res) {
                        var a = res.address;
                        if (!a) return;
                        $('#address-fullname').val(a.fullname);
                        $('#address-mobile').val(a.mobile);
                        $('#address-postal').val(a.postal_code);
                        $('#address-building').val(a.buildingNumber);
                        $('#address-unit').val(a.unit || '');
                        $('#address').val(a.address);
                        $('input[name="lat"]').val(a.lat || '');
                        $('input[name="lng"]').val(a.lng || '');
                        if (a.province_id) {
                            $('#province').val(String(a.province_id));
                            loadCities(a.province_id, a.city_id);
                        }
                    })
                    .fail(function () {
                        toast('خطا در دریافت اطلاعات آدرس.', 'error');
                    });
            }
        });

        $(document).on('change', '#province', function () {
            loadCities(this.value, null);
        });

        $(document).on('submit', '#add-update-address-form', function (e) {
            e.preventDefault();
            var $form = $(this);
            var $err = $('#address-form-error').addClass('d-none').empty();

            var required = ['fullname', 'mobile', 'postal_code', 'buildingNumber', 'province_id', 'city_id', 'address'];
            var missing = [];
            $.each(required, function (i, name) {
                var $f = $form.find('[name="' + name + '"]');
                if (!$f.val() || !$.trim(String($f.val()))) {
                    missing.push(name);
                    $f.addClass('is-invalid');
                } else {
                    $f.removeClass('is-invalid');
                }
            });
            if (missing.length) {
                $err.text('لطفاً همه فیلدهای ستاره‌دار را تکمیل کنید.').removeClass('d-none');
                return;
            }

            var $btn = $('#address-submit-btn').prop('disabled', true);
            $.ajax({
                url: $form.attr('action'),
                type: 'POST',
                data: $form.serialize(),
                headers: { 'X-CSRF-TOKEN': csrfToken() }
            })
                .done(function (res) {
                    if (res && (res.action === 'store' || res.action === 'update')) {
                        toast('آدرس با موفقیت ذخیره شد.', 'success');
                        setTimeout(function () { window.location.reload(); }, 900);
                    } else {
                        $btn.prop('disabled', false);
                        toast('پاسخ نامعتبر از سرور.', 'error');
                    }
                })
                .fail(function (xhr) {
                    $btn.prop('disabled', false);
                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        var msgs = [];
                        $.each(xhr.responseJSON.errors, function (k, v) { msgs.push(v[0]); });
                        $err.text(msgs[0]).removeClass('d-none');
                    } else {
                        $err.text('خطا در ذخیره آدرس؛ دوباره تلاش کنید.').removeClass('d-none');
                    }
                });
        });
    }

    // انتخاب آدرس برای حذف
    $(document).on('click', '.js-address-delete', function () {
        $('#address-remove-form').attr('action', $(this).data('action'));
    });

    // حذف آدرس (AJAX)
    $(document).on('submit', '#address-remove-form', function (e) {
        e.preventDefault();
        var $form = $(this);
        $.ajax({
            url: $form.attr('action'),
            type: 'POST',
            data: $form.serialize(),
            headers: { 'X-CSRF-TOKEN': csrfToken() }
        })
            .done(function () {
                toast('آدرس حذف شد.', 'success');
                setTimeout(function () { window.location.reload(); }, 800);
            })
            .fail(function () {
                toast('خطا در حذف آدرس.', 'error');
            });
    });

    // ثبت به‌عنوان آدرس پیشفرض
    $(document).on('click', '.js-address-active', function () {
        var $btn = $(this).prop('disabled', true);
        $.ajax({
            url: $btn.data('action'),
            type: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .done(function () {
                toast('این آدرس پیشفرض شد.', 'success');
                setTimeout(function () { window.location.reload(); }, 800);
            })
            .fail(function () {
                $btn.prop('disabled', false);
                toast('خطا در ثبت آدرس پیشفرض.', 'error');
            });
    });

    // tooltip ها (bootstrap 5)
    if (window.bootstrap && window.bootstrap.Tooltip) {
        $('[data-bs-toggle="tooltip"]').each(function () {
            new window.bootstrap.Tooltip(this);
        });
    }

    /* ================= ۴) کیف پول ================= */

    // جزئیات تراکنش (AJAX در مودال)
    $(document).on('click', '.js-show-history', function () {
        var $btn = $(this);
        $.ajax({
            url: $btn.data('action'),
            type: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .done(function (data) {
                $('#history-detail').html(data);
                var modal = new window.bootstrap.Modal(document.getElementById('history-show-modal'));
                modal.show();
            })
            .fail(function () {
                toast('خطا در دریافت جزئیات تراکنش.', 'error');
            });
    });

    // افزایش موجودی (سابمیت عادی — پاسخ، رندر درگاه است) + چک کلاینت
    $(document).on('submit', '#wallet-create-form', function (e) {
        var amount = parseInt($('#wallet-amount').val(), 10) || 0;
        var $gateway = $('#wallet-gateway');
        var $err = $('#wallet-deposit-error').addClass('d-none').empty();
        if (amount < 1000 || amount > 500000000) {
            e.preventDefault();
            $err.text('مبلغ باید بین ۱,۰۰۰ تا ۵۰۰,۰۰۰,۰۰۰ تومان باشد.').removeClass('d-none');
            return;
        }
        if (!$gateway.val()) {
            e.preventDefault();
            $err.text('درگاه پرداخت را انتخاب کنید.').removeClass('d-none');
        }
    });

    // برداشت (سابمیت عادی — سرور فلش توست برمی‌گرداند) + چک موجودی کلاینت
    $(document).on('submit', '#wallet-withdraw-form', function (e) {
        var $form = $(this);
        var amount = parseInt($('#withdraw-amount').val(), 10) || 0;
        var balance = parseInt($form.data('balance'), 10) || 0;
        var $err = $('#wallet-withdraw-error').addClass('d-none').empty();
        if (amount < 10000 || amount > 50000000) {
            e.preventDefault();
            $err.text('مبلغ برداشت باید بین ۱۰,۰۰۰ تا ۵۰,۰۰۰,۰۰۰ تومان باشد.').removeClass('d-none');
            return;
        }
        if (amount > balance) {
            e.preventDefault();
            $err.text('مبلغ وارد شده بیشتر از موجودی کیف پول است.').removeClass('d-none');
        }
    });

    /* ================= ۵) پیام‌های دریافتی ================= */
    $(document).on('click', '.js-show-message', function () {
        var $btn = $(this);
        $.ajax({
            url: $btn.data('action'),
            type: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .done(function (data) {
                $('#message-detail').html(data);
                var modal = new window.bootstrap.Modal(document.getElementById('message-show-modal'));
                modal.show();
            })
            .fail(function () {
                toast('خطا در دریافت پیام.', 'error');
            });
    });

    /* ================= ۶) تیکت‌ها (FormData — فایل دارد) ================= */
    function initTicketForm(formSelector, successMsg) {
        $(document).on('submit', formSelector, function (e) {
            e.preventDefault();
            var $form = $(this);
            var $btn = $form.find('button[type="submit"]').prop('disabled', true);
            var $err = $form.find('#ticket-form-error').addClass('d-none').empty();

            var fd = new FormData(this);
            $.ajax({
                url: $form.attr('action'),
                type: 'POST',
                data: fd,
                processData: false,
                contentType: false,
                headers: { 'X-CSRF-TOKEN': csrfToken(), 'X-Requested-With': 'XMLHttpRequest' }
            })
                .done(function () {
                    toast(successMsg, 'success');
                    setTimeout(function () {
                        window.location.href = $form.attr('data-redirect') || window.location.href;
                    }, 800);
                })
                .fail(function (xhr) {
                    $btn.prop('disabled', false);
                    var msg = 'خطا در ارسال؛ دوباره تلاش کنید.';
                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        var first = null;
                        $.each(xhr.responseJSON.errors, function (k, v) { first = v[0]; return false; });
                        msg = first || msg;
                    }
                    $err.text(msg).removeClass('d-none');
                });
        });
    }
    initTicketForm('#ticket-create-form', 'تیکت با موفقیت ثبت شد.');
    initTicketForm('#ticket-update-form', 'پیام شما ارسال شد.');

    /* ================= ۷) نظرات من ================= */

    // حذف دیدگاه
    $(document).on('click', '.js-comment-delete', function () {
        $('#comment-remove-form').attr('action', $(this).data('action'));
    });
    $(document).on('submit', '#comment-remove-form', function (e) {
        e.preventDefault();
        var $form = $(this);
        $.ajax({
            url: $form.attr('action'),
            type: 'POST',
            data: $form.serialize(),
            headers: { 'X-CSRF-TOKEN': csrfToken() }
        })
            .done(function () {
                toast('دیدگاه حذف شد.', 'success');
                setTimeout(function () { window.location.reload(); }, 800);
            })
            .fail(function () {
                toast('خطا در حذف دیدگاه.', 'error');
            });
    });

    // افزودن نقطه قوت/ضعف (hidden input با نام review[advantages][] / review[disadvantages][])
    function addEditPoint(listId, text) {
        if (!text || !$.trim(String(text))) return;
        var name = listId === 'edit-advantages-list' ? 'review[advantages][]' : 'review[disadvantages][]';
        var $chip = $('<span class="badge bg-light text-dark border rounded-pill font-12 d-inline-flex align-items-center gap-1 py-2 px-3"></span>')
            .append($('<i class="bi bi-x-circle text-danger js-edit-point-remove cursor-pointer"></i>'))
            .append($('<input type="hidden" name="' + name + '">').val($.trim(String(text))))
            .append($('<span></span>').text($.trim(String(text))));
        $('#' + listId).append($chip);
    }

    // باز کردن مودال ویرایش با داده‌های دیدگاه
    $(document).on('click', '.js-edit-review', function () {
        var $btn = $(this);
        var productId = $btn.data('productId');
        if (!productId) return;

        var $form = $('#edit-product-review-form');
        $form[0].reset();
        $('#edit-advantages-list, #edit-disadvantages-list').empty();
        $('#edit-review-error').addClass('d-none').empty();
        $('#edit-review-product-id').val(productId);
        $('#edit-review-product-title').text($btn.closest('.order-item').find('.comment-item-content a').first().text() || '');

        // امتیاز
        var rating = parseInt($btn.data('rating'), 10) || 3;
        $form.find('input[name="rating"]').prop('checked', false);
        $form.find('#edit-review-star-' + rating).prop('checked', true);

        // پیشنهاد خرید
        var suggest = String($btn.data('suggest') || '');
        $form.find('input[name="suggest"]').prop('checked', false);
        if (suggest) {
            $form.find('input[name="suggest"][value="' + suggest + '"]').prop('checked', true);
        }

        // عنوان و متن
        $('#edit-review-title').val($btn.data('title') || '');
        $('#edit-review-body').val($btn.data('body') || '');

        // نقاط قوت/ضعف
        var points = [];
        try { points = $btn.data('points') || []; } catch (err) { points = []; }
        $.each(points, function (i, p) {
            if (p && p.text) {
                addEditPoint(p.type === 'positive' ? 'edit-advantages-list' : 'edit-disadvantages-list', p.text);
            }
        });

        var modal = new window.bootstrap.Modal(document.getElementById('edit-product-review-modal'));
        modal.show();
    });

    $(document).on('click', '.js-edit-point-add', function () {
        var listId = $(this).data('list');
        var $input = $(this).closest('.input-group').find('input');
        addEditPoint(listId, $input.val());
        $input.val('').trigger('focus');
    });

    $(document).on('click', '.js-edit-point-remove', function () {
        $(this).closest('.badge').remove();
    });

    // ثبت ویرایش دیدگاه (FormData — فیلد محافظ ربات دارد)
    $(document).on('submit', '#edit-product-review-form', function (e) {
        e.preventDefault();
        var $form = $(this);
        var $btn = $form.find('button[type="submit"]').prop('disabled', true);
        var $err = $('#edit-review-error').addClass('d-none').empty();

        var fd = new FormData(this);
        $.ajax({
            url: $form.attr('action'),
            type: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            headers: { 'X-CSRF-TOKEN': csrfToken(), 'X-Requested-With': 'XMLHttpRequest' }
        })
            .done(function () {
                toast('دیدگاه شما ویرایش و برای تایید ارسال شد.', 'success');
                setTimeout(function () { window.location.reload(); }, 900);
            })
            .fail(function (xhr) {
                $btn.prop('disabled', false);
                var msg = 'خطا در ثبت دیدگاه؛ دوباره تلاش کنید.';
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    var first = null;
                    $.each(xhr.responseJSON.errors, function (k, v) { first = v[0]; return false; });
                    msg = first || msg;
                }
                $err.text(msg).removeClass('d-none');
            });
    });

    /* ================= ۸) بازدیدهای اخیر ================= */
    $(document).on('click', '.js-viewer-remove', function () {
        $('#viewer-remove-form').attr('action', $(this).data('action'));
    });
    $(document).on('submit', '#viewer-remove-form', function (e) {
        e.preventDefault();
        var $form = $(this);
        $.ajax({
            url: $form.attr('action'),
            type: 'POST',
            data: $form.serialize(),
            headers: { 'X-CSRF-TOKEN': csrfToken() }
        })
            .done(function () {
                toast('از بازدیدها حذف شد.', 'success');
                setTimeout(function () { window.location.reload(); }, 800);
            })
            .fail(function () {
                toast('خطا در حذف.', 'error');
            });
    });

    /* ================= ۹) معرفی به دوستان (کپی) ================= */
    $(document).on('click', '.js-copy-referral', function () {
        var text = String($(this).data('copy') || '');
        if (!text) return;

        function fallbackCopy() {
            var $tmp = $('<textarea>').val(text).css('position', 'fixed').css('opacity', '0').appendTo('body');
            $tmp[0].select();
            try {
                document.execCommand('copy');
                toast('با موفقیت کپی شد.', 'success');
            } catch (e) {
                toast('کپی نشد؛ دستی کپی کنید.', 'error');
            }
            $tmp.remove();
        }

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text)
                .then(function () { toast('با موفقیت کپی شد.', 'success'); })
                .catch(fallbackCopy);
        } else {
            fallbackCopy();
        }
    });

    /* ================= ۱۰) پیش‌نمایش تصاویر مرجوعی ================= */
    var $returnImages = $('#return-images');
    if ($returnImages.length) {
        $returnImages.on('change', function () {
            var previews = $('#return-image-previews').empty();
            var files = Array.prototype.slice.call(this.files, 0, 5);
            if (this.files.length > 5) {
                toast('حداکثر ۵ تصویر مجاز است.', 'error');
                this.value = '';
                return;
            }
            $.each(files, function (i, file) {
                if (!file.type || file.type.indexOf('image/') !== 0) return;
                var url = URL.createObjectURL(file);
                $('<img>').attr('src', url).attr('alt', 'پیش‌نمایش ' + (i + 1))
                    .addClass('rounded-3 img-thumbnail')
                    .css({ width: '72px', height: '72px', 'object-fit': 'cover' })
                    .appendTo(previews);
            });
        });
    }
})();
