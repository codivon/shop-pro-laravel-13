$(function () {

    ///نمایش زیر منو
    $(".showSubMenu").click(function () {
        $(this).nextAll("ul").toggleClass("show");
        $(this).toggleClass('open');
    })
    
    ///نمایش مگامنو آپدیدت جدید — فاز ۱۵۸:
    ///۱) همهٔ سلکتورها به .top-menu-menu محدود شدند تا نوار مدیریت بالای سایت
    ///   (.admin-loggedin-navbar که کلاس قدیمی main-menu را داشت) دیگر تحت
    ///   تأثیر این کدها قرار نگیرد — علت منوهای بازماندهٔ نوار ادمین همین بود.
    ///۲) mouseleave با event delegation متصل می‌شود؛ قبلاً هنگام لود صفحه هیچ
    ///   عنصری کلاس main-menu-sub-active را نداشت، پس هندلر هرگز متصل نمی‌شد
    ///   و منوها بعد از خروج موس باز می‌ماندند.
    /* فاز ۱۷۰ — ستون فرعی مگامنو = «یک پنل ثابت واحد» (درخواست کاربر):
       قبلاً به‌ازای هر ردیف، ul فرعیِ خودِ ردیف با position:fixed جابه‌جا و با
       transition «all» نمایش داده می‌شد؛ با هر حرکت موس بین دسته‌ها، پنل از بالا
       دوباره انیمیت می‌شد (پرش صفحه). حالا یک پنل fixed با مختصات ثابت ساخته
       می‌شود و فقط محتوایش (کپی ستون فرعی ردیف فعال) عوض می‌شود؛ اگر ردیف
       تصویر پس‌زمینه داشته باشد (data-cat-image) همان تصویر پایین-چپ پنل
       نمایش داده می‌شود. هیچ transition موقعیتی وجود ندارد. */

    function ensureMegaFlyout(panel) {
        if (!panel) { return null; }
        if (panel.__snrFlyout) { return panel.__snrFlyout; }

        var flyout = document.createElement('ul');
        flyout.className = 'main-menu-sub snr-mega-flyout';
        flyout.setAttribute('aria-label', 'زیرمنوی دسته‌بندی‌ها');
        panel.appendChild(flyout);
        panel.__snrFlyout = flyout;

        return flyout;
    }

    function placeMegaFlyout(panel) {
        var flyout = panel.__snrFlyout;
        if (!flyout) { return; }

        var rect = panel.getBoundingClientRect();
        if (!rect.width) { return; }

        /* ستون ردیف‌ها ۲۰۰px از لبهٔ راست پنل است؛ پنل فرعی دقیقاً از همان‌جا
           به سمت چپ باز می‌شود و هم‌قدِ خود پنل می‌ماند (یک صفحهٔ ثابت) */
        flyout.style.top = Math.round(rect.top) + 'px';
        flyout.style.right = Math.round(window.innerWidth - rect.right + 200) + 'px';
        flyout.style.height = Math.round(rect.height) + 'px';
    }

    function showMegaFlyoutRow(panel, row) {
        var flyout = ensureMegaFlyout(panel);
        if (!flyout) { return; }

        var sub = row.querySelector(':scope > ul.main-menu-sub');
        flyout.innerHTML = sub ? sub.innerHTML : '';

        var img = row.getAttribute('data-cat-image');
        flyout.style.backgroundImage = img ? 'url("' + img + '")' : '';

        placeMegaFlyout(panel);
        flyout.classList.add('main-menu-sub-active');
    }

    /* جابه‌جایی پنل‌های باز هنگام اسکرول/ریسایز (بدون انیمیشن) */
    var repositionOpenFlyouts = function () {
        document.querySelectorAll('.top-menu-menu ul.main-menu.active').forEach(function (panel) {
            if (panel.__snrFlyout) { placeMegaFlyout(panel); }
        });
    };
    window.addEventListener('scroll', repositionOpenFlyouts, { passive: true });
    window.addEventListener('resize', repositionOpenFlyouts);

    /* باز/بسته شدن کل پنل مگا منو با ورود و خروج موس از دکمهٔ «دسته‌بندی کالاها» */
    $(document).on('mouseenter', '.top-menu-menu .main-menu-head', function () {
        $(this).children().addClass('active');

        var panel = $(this).children('ul.main-menu').get(0);
        var firstRow = panel ? panel.querySelector(':scope > li') : null;
        if (firstRow) { showMegaFlyoutRow(panel, firstRow); }
    })
    $(document).on('mouseleave', '.top-menu-menu .main-menu-head', function () {
        $(this).children().removeClass('active');

        var panel = $(this).children('ul.main-menu').get(0);
        if (panel && panel.__snrFlyout) {
            $(panel.__snrFlyout).removeClass('main-menu-sub-active');
            $(panel).find('> li').removeClass('main-menu-sub-active-li');
        }
    })

    /* هاور روی ردیف‌های مگا منو → فقط محتوای پنل ثابت فرعی عوض می‌شود.
       با delegation روی خود پنل، ورود موس به پنل فرعی هم ردیف فعال و پنل را
       باز نگه می‌دارد (target داخل flyout به هیچ ردیفی resolve نمی‌شود). */
    $(document).on('mouseover', '.top-menu-menu ul.main-menu', function (event) {
        var row = event.target.closest('.top-menu-menu ul.main-menu > li');
        if (!row || !this.contains(row)) { return; }

        var $row = $(row);
        if ($row.hasClass('main-menu-sub-active-li') &&
            this.__snrFlyout &&
            this.__snrFlyout.classList.contains('main-menu-sub-active')) { return; }

        $row.parent().children('li').removeClass('main-menu-sub-active-li');
        $row.addClass('main-menu-sub-active-li');

        showMegaFlyoutRow(this, row);
    });

    /* خروج موس از کل پنل مگا منو → پاک‌سازی کامل ردیف فعال و پنل فرعی */
    $(document).on('mouseleave', '.top-menu-menu ul.main-menu', function () {
        $(this).find('> li').removeClass('main-menu-sub-active-li');
        if (this.__snrFlyout) {
            $(this.__snrFlyout).removeClass('main-menu-sub-active');
        }
    })

    ///شمارنده محصول برای اضافه کردن به سبد خرید
    /* فاز ۱۶۳ — ورودی‌های سبد کشویی هدر (.js-mini-qty) نباید TouchSpin شوند:
       TouchSpin با markup خودش input-group تزریق می‌کند و ظاهر سایدبار
       پس از رفرش صفحه به‌هم می‌ریزد و دکمه‌های +/− ای‌جکسی آن از کار
       می‌افتند. شمارندهٔ سبد کشویی منطق اختصاصی خودش را دارد (main.js). */
    $("input[name='count']").not('.js-mini-qty').not('.cart-canvas input').TouchSpin({
        min: 1,
        max: '1000000000000000',
        buttondown_class: "btn-counter waves-effect waves-light",
        buttonup_class: "btn-counter waves-effect waves-light"
    });
 
    ///انتخاب گر رنگ
    $(".category-sort .form-checks .form-check").click(function () {
        $(".category-sort .form-checks .form-check").removeClass("active");
        $(this).addClass('active');
        $(".category-sort .form-checks .form-check").children("input[type=radio]").attr('checked', false);
        $(this).children("input[type=radio]").attr('checked', true);
    })


    ///انتخاب زمان ارسال
    $(".send-item").click(function () {
        $(".send-item").removeClass("active");
        $(this).addClass('active');
    })

    ///انتخاب روش پستی
    $(".shipping-item").click(function () {
        $(".shipping-item").removeClass("active");
        $(this).addClass('active');
    })

    ///انتخاب روش پرداخت
    $(".bank-item").click(function () {
        $(".bank-item").removeClass("active");
        $(this).addClass('active');
    })
    


    jQuery('[data-bs-toggle="tooltip"]').tooltip();
    jQuery('[data-bs-toggle="modal"][title]').tooltip();
    
});



// When the user clicks on the button, scroll to the top of the document
function topFunction() {
    document.body.scrollTop = 0; // For Safari
    document.documentElement.scrollTop = 0; // For Chrome, Firefox, IE and Opera
  }


////video play button

  $(document).ready(function () {
    var ctrlVideo = document.getElementById("aboutVideo");

    $('.play-btn').click(function () {
        if ($('.play-btn').hasClass("active")) {
            
            $('.play-btn').toggleClass("play-opacity");

            $("#aboutVideo").toggleClass('vd-style');

            ctrlVideo.play();

            $('#play-icon').removeClass("bi bi-play-fill");
            $('#play-icon').addClass("bi bi-pause-fill");

            $('.play-btn').toggleClass("active");
        } else {

            $('.play-btn').toggleClass("play-opacity");

            $("#aboutVideo").toggleClass('vd-style');
            ctrlVideo.pause();

            $('#play-icon').removeClass("bi bi-pause-fill");
            $('#play-icon').addClass("bi bi-play-fill");

            $('.play-btn').toggleClass("active");
        }
    });
});



/*
* فرم چند مرحله ای ورود / ثبت نام
*/

$(document).ready(function (){
    ///disable fild password in load form
    $(".step-passwd").hide();
    ///disable button submit in step one
    $(".step-two").hide();

    ///show filed password in step two form
    $(".step-one").click(function (){

        ///check empty fild username
        if($(".step-username #username").val() != ""){
            ///hide username filed
            $(".step-username").hide();
            ///show password filed
            $(".step-passwd").show();
            ///hide button step one
            $(this).hide();
            ///show button submit
            $(".step-two").show();
        }else{
            $(".step-username #username").addClass("border-danger border-2");
        }


        ///check empty fild password

        $(".btnForm").click(function(){
            if($(".step-passwd #passwd").val() !=""){
                $("#form-auth").submit();
            }else{
                $(".step-passwd #passwd").addClass("border-danger border-2");
            }
        })


    })

})
