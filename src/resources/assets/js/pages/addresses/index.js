/* ============================================================
   index.js — فاز ۱۶۱ (SaniarTheme)
   صفحهٔ آدرس‌های پنل مشتری + بخش آدرس تک‌صفحهٔ پرداخت

   فاز ۱۶۱ — رفع الگوی «شنوندهٔ دیررس»: قبلاً شنوندهٔ show.bs.modal
   داخل کلیکِ ویرایش/افزودن وصل می‌شد و بار اول اجرا نمی‌شد (همان
   ریشهٔ «نقشه با کلیک دوم» و پر نشدن فرم ویرایش). حالا کلیک فقط
   «حالت» (ویرایش/افزودن) را روی مودال ثبت می‌کند و شنوندهٔ show
   یک‌بار در لود صفحه وصل می‌شود.
   ============================================================ */

$(function () {

    var $modal = $('#add-edit-address-modal');
    var $form = $('#add-update-address-form');

    /* مسیر ذخیرهٔ آدرس — از مودال یا باکس افزودن (رفع ۴۰۵) */
    function storeUrl() {
        var url = $modal.data('store-url');
        if (!url) {
            var $box = $('.user-add-address--box[data-updateurl], #add-address-modal[data-updateurl]').first();
            url = $box.length ? $box.data('updateurl') : $form.attr('action');
        }
        return url;
    }

    /* ------------------- انتخاب حالت مودال ------------------- */

    /* کلیک روی «افزودن آدرس» → حالت ثبت (اکشن = ذخیره، بدون _method) */
    $(document).on('click', '#add-address-modal, .user-add-address--box', function () {
        $modal.removeData('edit-url');
        $modal.removeData('update-url');
    });

    /* کلیک روی «ویرایش» → حالت ویرایش (اکشن = آپدیت + _method=put) */
    $(document).on('click', '.edit-address-link', function () {
        $modal.data('edit-url', $(this).data('url'));
        $modal.data('update-url', $(this).data('updateurl'));
    });

    /* ------------------- آماده‌سازی فرم هنگام باز شدن ------------------- */

    $modal.on('show.bs.modal', function () {
        var editUrl = $modal.data('edit-url');

        if (editUrl) {
            /* حالت ویرایش */
            $form.attr('action', $modal.data('update-url') || $form.attr('action'));
            if (!$form.find('input[name=_method]').length) {
                $form.prepend('<input type="hidden" name="_method" value="put">');
            }
            fillForEdit(editUrl);
        } else {
            /* حالت ثبت جدید */
            var url = storeUrl();
            if (url) { $form.attr('action', url); }
            $form.find('input[name=_method]').remove();
        }
    });

    /* ------------------- پر کردن فرم برای ویرایش ------------------- */

    function fillForEdit(url) {
        $.ajax({
            url: url,
            type: 'GET',
            success: function (value) {
                $form.trigger('reset');
                $('#add-edit-address-modal input[name=buildingNumber]').val(value.address.buildingNumber);
                $('#add-edit-address-modal input[name=unit]').val(value.address.unit);
                $('#add-edit-address-modal input[name=postal_code]').val(value.address.postal_code);
                $('#add-edit-address-modal input[name=fullname]').val(value.address.fullname);
                $('#add-edit-address-modal input[name=mobile]').val(value.address.mobile);

                $('#province').val(value.address.province_id).change();
                if ($.fn.niceSelect) { try { $('.custom-select-ui select').niceSelect('update'); } catch (e) {} }

                $('#city').empty();
                $('#city').append('<option value="">انتخاب کنید</option>');
                $('#city').trigger('change');
                if ($.fn.niceSelect) { try { $('.custom-select-ui select').niceSelect('update'); } catch (e) {} }

                if (!$('#province').val()) {
                    return;
                }

                var id = $('#province').val();
                $.ajax({
                    type: 'get',
                    url: '/province/get-cities',
                    data: { id: id },
                    success: function (data) {
                        $(data).each(function () {
                            $('#city').append(
                                '<option value="' +
                                $(this)[0].id +
                                '">' +
                                $(this)[0].name +
                                '</option>'
                            );
                        });

                        if ($.fn.niceSelect) { try { $('.custom-select-ui select').niceSelect('update'); } catch (e) {} }
                        $('#add-edit-address-modal #city').val(value.address.city_id).change();
                    }
                });
            },
            beforeSend: function (xhr) {
                xhr.setRequestHeader('X-CSRF-TOKEN', $('meta[name="csrf-token"]').attr('content'));
            },
            cache: false,
            contentType: false,
            processData: false
        });
    }

    /* ------------------- مرحلهٔ فرم (بعد از نقشه) ------------------- */

    $('#next-add-address-btn').click(function () {
        var address = $('#add-edit-address-modal textarea#address').val();
        if (address != '') {
            $('#add-edit-address-modal #showMap').addClass('d-none');
            $('#add-edit-address-modal #more-information').removeClass('d-none');
        }
    });

    $('#back-to-map').click(function () {
        $('#add-edit-address-modal #showMap').removeClass('d-none');
        $('#add-edit-address-modal #more-information').addClass('d-none');
        $('#add-edit-address-modal form').trigger('reset');
        $('#next-add-address-btn').addClass('disabled');
        $('#next-add-address-btn').attr('disabled', true);
        $('.add-address-success').remove();
        $('.add-address-unsuccess').remove();
        $('#add-edit-address-modal .modal-footer').prepend('<span class="add-address-unsuccess">آدرس اضافه نشد!</span>');
    });

    /* ------------------- حذف آدرس (مودال) ------------------- */

    $('.favorite-remove-btn').on('click', function () {
        $('#favorite-remove-form').attr('action', $(this).data('action'));
    });

    /* ------------------- آدرس پیش‌فرض ------------------- */

    $(document).on('click', '.profile-address-content .profile-address-info', function () {
        var item = this;
        $.ajax({
            url: $(item).data('action'),
            type: 'GET',
            success: function (data) {
                if (data.active == 'success') {
                    $('.profile-stats').removeClass('active');
                    $(item).parents('.profile-stats').addClass('active');
                    Swal.fire({
                        text: 'آدرس به عنوان آدرس پیشفرض انتخاب شد.',
                        type: 'success',
                        showCancelButton: false,
                        confirmButtonText: 'باشه',
                    });
                }
            },
            beforeSend: function (xhr) {
                xhr.setRequestHeader('X-CSRF-TOKEN', $('meta[name="csrf-token"]').attr('content'));
                block('.profile-content');
            },
            complete: function () {
                unblock('.profile-content');
            },
            cache: false,
            contentType: false,
            processData: false
        });
    });

    /* ------------------- تغییر استان → بارگذاری شهرها ------------------- */

    $(document).on('change', '#province', function () {
        var id = $(this).val();
        var $city = $('#city');
        $city.empty();
        $city.append('<option value="">انتخاب کنید</option>');

        if (!id) {
            if ($.fn.niceSelect) { try { $city.niceSelect('update'); } catch (e) {} }
            return;
        }

        $.ajax({
            type: 'get',
            url: '/province/get-cities',
            data: { id: id },
            success: function (data) {
                $(data).each(function () {
                    $city.append(
                        '<option value="' + $(this)[0].id + '">' + $(this)[0].name + '</option>'
                    );
                });

                /* فاز ۱۶۱ — اگر «شهر در انتظار» از نقشه ثبت شده باشد،
                   بعد از لود شهرها خودکار انتخاب می‌شود */
                var pendingCity = $('#add-edit-address-modal').data('pending-city');
                if (pendingCity) {
                    $('#add-edit-address-modal').removeData('pending-city');
                    var wanted = String(pendingCity);
                    var matched = null;
                    $city.find('option').each(function () {
                        if (!matched) {
                            var label = String($(this).text() || '')
                                .replace(/\u064A/g, '\u06CC').replace(/\u0649/g, '\u06CC')
                                .replace(/\u0643/g, '\u06A9').replace(/\u200C/g, ' ')
                                .replace(/\s+/g, ' ').trim();
                            if (label === wanted) { matched = $(this).val(); }
                        }
                    });
                    if (matched) {
                        $city.val(matched);
                        $city.trigger('change');
                    }
                }

                if ($.fn.niceSelect) { try { $city.niceSelect('update'); } catch (e) {} }
            }
        });
    });
});
