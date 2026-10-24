

$('.table-responsive .dropdown-action').each(function() {
    var $dropdownMenu = $(this).find('.dropdown-menu');

    // بررسی می‌کند آیا dropdown-menu محتوایی دارد یا خیر
    // محتوا می‌تواند متن، تگ‌های HTML یا هر چیزی به جز space خالی باشد
    var hasContent = $dropdownMenu.contents().filter(function() {
        return this.nodeType === Node.TEXT_NODE ? $.trim(this.textContent).length > 0 : true;
    }).length > 0;

    // اگر محتوایی وجود ندارد، کل dropdown را مخفی کن
    if (!hasContent) {
        $(this).hide();
    }
});

$('li.title').each(function() {
    var $title = $(this);
    var $following = $title.nextUntil('li.title');

    // فیلتر کردن آیتم‌هایی که مخفی نیستند و تایتل نیستند
    var hasAnyItem = $following.filter(function() {
        return $(this).css('display') !== 'none' && !$(this).hasClass('title');
    }).length > 0;

    if (!hasAnyItem) {
        $title.hide();
    }
});

function block(el) {
    var $body = $('body');

    var block_ele = $(el);

    var reloadActionOverlay;
    if ($body.hasClass('dark-layout')) {
        var reloadActionOverlay = '#10163a';
    } else {
        var reloadActionOverlay = '#fff';
    }

    // Block Element
    block_ele.block({
        message:
            '<div class="feather icon-refresh-cw icon-spin font-medium-2 text-primary"></div>',
        overlayCSS: {
            backgroundColor: reloadActionOverlay,
            cursor: 'wait'
        },
        css: {
            border: 0,
            padding: 0,
            backgroundColor: 'none'
        }
    });
}

function unblock(el) {
    $(el).unblock();
}

$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    },
    tryCount: 0,
    retryLimit: 3,
    error: async function (data, textStatus, errorThrown) {
        if (data.status == 403) {
            showCustomToast('اجازه ی دسترسی ندارید','error');
            return;
        } else if (data.status == 419) {
            this.tryCount++;
            if (this.tryCount <= this.retryLimit) {
                //try again

                await refreshCsrf();

                this.headers = {
                    ...this.headers,
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                };

                if (this.data && this.data.set) {
                    this.data.set(
                        '_token',
                        $('meta[name="csrf-token"]').attr('content')
                    );
                }

                $.ajax(this);

                return;
            }
            return;
        } else if (data.status == 429) {
            showCustomToast('تعداد درخواست ها بیش از حد مجاز است لطفا پس از دقایقی مجدد تلاش کنید','error');
        } else if (data.status == 423) {
            if ($('#password-confirm-modal').length) {
                $('#password-confirm-modal').modal('show');
                $('#password-confirm-modal').data('ajax', this);
            } else {
                showCustomToast('لطفا رمز عبور خود را وارد کنید','error');
            }
            return;
        } else if (!data.responseJSON?.errors) {
            showCustomToast('خطایی رخ داده است','error')

            if ($('#main-errors-modal').length) {
                if (data.responseJSON) {
                    new JsonEditor(
                        '#main-errors-modal .modal-body .error-content',
                        data.responseJSON
                    );

                    setTimeout(() => {
                        $(
                            '#main-errors-modal .modal-body .error-content .json-toggle'
                        )
                            .first()
                            .trigger('click');
                    }, 500);
                } else {
                    $('#main-errors-modal .modal-body .error-content').html(
                        data.responseText
                    );
                }

                $('#main-errors-modal').modal('show');
            }

            return;
        }

        for (var key in data.responseJSON.errors) {
            // skip loop if the property is from prototype
            if (!data.responseJSON.errors.hasOwnProperty(key)) continue;

            var obj = data.responseJSON.errors[key];
            for (var prop in obj) {
                // skip loop if the property is from prototype
                if (!obj.hasOwnProperty(prop)) continue;
                showCustomToast(obj[prop],'warning')
            }
        }
    }
});

function reloadDiv(el) {
    //get current url
    var url = window.location.href;

    //refresh comments list
    $(el).load(url + ' ' + el + ' > *');
}

String.prototype.toEnglishDigit = function () {
    var find = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    var replace = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
    var replaceString = this;
    var regex;
    for (var i = 0; i < find.length; i++) {
        regex = new RegExp(find[i], 'g');
        replaceString = replaceString.replace(regex, replace[i]);
    }
    return replaceString;
};

$('#video-helpe-modal').on('show.bs.modal', function () {
    $('#video-helpe-modal iframe').each(function () {
        var src = $(this).data('src');
        $(this).attr('src', src);
    });
});

$('#video-helpe-modal').on('hide.bs.modal', function () {
    $('#video-helpe-modal iframe').each(function () {
        var src = $(this).attr('src');
        $(this).attr('src', src);
    });
});

$('#video-helpe-modal .collapse-header').on('click', function () {
    $('#video-helpe-modal iframe').each(function () {
        var src = $(this).attr('src');
        $(this).attr('src', src);
    });
});

//---------------- amount input
$('.amount-input').attr('autocomplete', 'off');

$(document).on('keyup', '.amount-input', function () {
    if (!$(this).val()) {
        $(this).next('.form-text').remove();
        return;
    }

    if (!$(this).next('.form-text').length) {
        $(this).after(
            '<small class="form-text text-success amount-helper"></small>'
        );
    }

    var text =
        number_format($(this).val()) +
        ' ' +
        ($(this).data('unit') ? $(this).data('unit') : 'تومان');

    $(this).next('.form-text').text(text);
});

$('.amount-input').trigger('keyup');

if (typeof persianDate != 'undefined') {
    $('.persian-date-picker').customPersianDate();
}

$('#password-confirm-form').on('submit', function (e) {
    e.preventDefault();

    var form = $(this);
    var formData = new FormData(this);

    $.ajax({
        url: $(this).attr('action'),
        type: 'POST',
        data: formData,
        success: function (data) {
            $('#password-confirm-modal').modal('hide');
            $.ajax($('#password-confirm-modal').data('ajax'));
            form.trigger('reset');
        },
        beforeSend: function (xhr) {
            block(form);
        },
        complete: function () {
            unblock(form);
        },
        cache: false,
        contentType: false,
        processData: false
    });
});

function refreshCsrf() {
    return $.ajax({
        url: $('meta[name="csrf-token"]').data('refresh-url'),
        type: 'GET',
        success: function (data) {
            $('input[name="_token"]').val(data);
            $('meta[name="csrf-token"]').attr('content', data);
        },
        cache: false,
        contentType: false,
        processData: false
    });
}

$('.card-header.filter-card').on('click', function (e) {
    if (!$(e.target).closest('a[data-action="collapse"]').length) {
        $(this)
            .find('.heading-elements a[data-action="collapse"]')
            .trigger('click');
    }
});

$('#province, #city').select2({
    rtl: true,
    width: '100%'
});

$('#province').change(function () {
    var id = $(this).find(':selected').val();

    if (!id) return;

    $('#city').empty();

    $.ajax({
        type: 'get',
        url: $('#province').data('action'),
        data: {id: id},
        success: function (data) {
            $(data).each(function () {
                selected =
                    $(this)[0].id == $('#city').data('id') ? 'selected' : '';

                $('#city').append(
                    `<option value="${$(this)[0].id}" ${selected}>${
                        $(this)[0].name
                    }</option>`
                );
            });
        },
        beforeSend: function () {
            block('#city-div');
        },
        complete: function () {
            unblock('#city-div');
        }
    });
});


// اضافه کردن آیکون 'x' کنار اینپوت و مدیریت عملکرد آن
// فیکس: در صفحاتی که سایدبار قدیمی (sidebar-search-input) ندارند — مثل صفحات
// پکیج‌ها داخل کروم پنل جدید — اینپوت وجود ندارد و نباید خطای null بدهیم
var inputElement = document.getElementById('sidebar-search-input');

if (inputElement) {
    var clearIconHTML = '<span class="clear-sidebar-search-icon" style="display:none; cursor:pointer;">&times;</span>';
    inputElement.insertAdjacentHTML('afterend', clearIconHTML);
    var clearIcon = document.querySelector('.clear-sidebar-search-icon');

    // مدیریت عملکرد آیکون 'x' برای خالی کردن اینپوت
    if (clearIcon) {
        clearIcon.addEventListener('click', function () {
            inputElement.value = '';
            inputElement.dispatchEvent(new Event('input'));
        });
    }

    // اضافه کردن listener برای نمایش و مخفی کردن آیکون 'x'
    inputElement.addEventListener('input', function () {
        var searchTerm = this.value.toLowerCase();
        var logsItems = document.getElementsByClassName('nav-item');

        Array.from(logsItems).forEach(function (item) {
            var originalText = item.dataset.originalText;

            if (!originalText) {
                item.dataset.originalText = item.innerHTML;
                originalText = item.innerHTML;
            }

            var logsText = item.textContent.toLowerCase();
            var regex = new RegExp(`(${searchTerm})`, 'gi');

            if (logsText.includes(searchTerm) && searchTerm.trim() !== "") {
                item.innerHTML = originalText.replace(regex, '<span class="highlight">$1</span>');
            } else {
                item.innerHTML = item.dataset.originalText;
            }

            if (item.querySelector('.highlight') && item.classList.contains('has-sub')) {
                item.classList.add('open');
            } else {
                item.classList.remove('open');
            }
        });

        // مدیریت نمایش و مخفی کردن آیکون 'x'
        if (typeof clearIcon !== 'undefined' && clearIcon) {
            if (searchTerm.trim() !== "") {
                clearIcon.style.display = 'inline';
            } else {
                clearIcon.style.display = 'none';
            }
        }
    });
}


// ── Toast ─────────────────────────────────────────────────────
function customToast(icon, msg) {
    const t = document.getElementById('custom-toast');
    if (!t) {
        // اگر toast وجود نداشت، با alert ساده
        alert(icon + ' ' + msg);
        return;
    }
    document.getElementById('custom-toast-icon').textContent = icon;
    document.getElementById('custom-toast-msg').textContent = msg;
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 3000);
}

$(".btn-fullscreen").on("click", function(e) {
    e.preventDefault();
    var toggledPanel = $(this).parents(".box-fullScreen")[0];
    screenfull.toggle(toggledPanel);
});

