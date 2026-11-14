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
    /* باز/بسته شدن کل پنل مگا منو با ورود و خروج موس از دکمهٔ «دسته‌بندی کالاها» */
    $(document).on('mouseenter', '.top-menu-menu .main-menu-head', function () {
        $(this).children().addClass('active');
    })
    $(document).on('mouseleave', '.top-menu-menu .main-menu-head', function () {
        $(this).children().removeClass('active');
    })
    /* هاور روی ردیف‌های مگا منو — به‌جای بایند مستقیم روی li (که با هاور روی
       آیتم‌های ستون فرعی می‌شکست)، روی خود پنل delegate می‌شود و همیشه به
       «ردیف» سطح اول (‎> li) تحویل می‌شود؛ ورود موس به ستون فرعی هم ردیف
       و ستون خودش را باز نگه می‌دارد.

       فاز ۱۵۹ — ستون فرعی با position:fixed هم‌ترازِ لبهٔ پنل جای‌گذاری می‌شود
       تا با اسکرولِ مستقلِ ستون دسته‌ها (سمت راست) جابجا نشود؛ اسکرول ستون
       فرعی (سمت چپ) هم کاملاً جدا می‌ماند. استایل‌های inline عمداً پاک
       نمی‌شوند تا در لحظهٔ محو‌شدن، منو نپرد. */
    function placeMegaFlyout(sub) {
        var panel = sub.closest('ul.main-menu');
        if (!panel) { return; }
        var rect = panel.getBoundingClientRect();
        if (!rect.width) { return; }
        /* ستون ردیف‌ها ۲۰۰px از لبهٔ راست پنل است؛ ستون فرعی باید دقیقاً
           از همان‌جا به سمت چپ باز شود (هم‌ترازِ چیدمان اصلی سانیار) */
        sub.style.position = 'fixed';
        sub.style.top = Math.round(rect.top) + 'px';
        sub.style.right = Math.round(window.innerWidth - rect.right + 200) + 'px';
    }

    $(document).on('mouseover', '.top-menu-menu ul.main-menu', function (event) {
        var row = event.target.closest('.top-menu-menu ul.main-menu > li');
        if (!row || !this.contains(row)) { return; }

        var $row = $(row);
        if ($row.hasClass('main-menu-sub-active-li') &&
            $row.children('ul').hasClass('main-menu-sub-active')) { return; }

        $row.parent().children('li').removeClass('main-menu-sub-active-li');
        $row.addClass('main-menu-sub-active-li');
        $('.top-menu-menu .main-menu-sub').removeClass('main-menu-sub-active');
        var sub = $row.children('ul').addClass('main-menu-sub-active').get(0);
        if (sub) { placeMegaFlyout(sub); }
    });

    /* باز شدن اولین ستون فرعی هنگام هاور روی دکمهٔ «دسته‌بندی کالاها» */
    $(document).on('mouseenter', '.top-menu-menu .main-menu-head', function () {
        var sub = $(this).children('ul.main-menu').children('li').children('ul.main-menu-sub').first().get(0);
        if (sub) {
            placeMegaFlyout(sub);
            $(sub).addClass('main-menu-sub-active');
        }
    })
    /* خروج موس از ستون فرعی → بستن همان ستون */
    $(document).on('mouseleave', '.top-menu-menu .main-menu-sub-active', function () {
        $(this).removeClass('main-menu-sub-active');
    })
    /* خروج موس از کل پنل مگا منو → پاک‌سازی کامل ردیف فعال و ستون‌های باز */
    $(document).on('mouseleave', '.top-menu-menu ul.main-menu', function () {
        $(this).find('> li').removeClass('main-menu-sub-active-li');
        $(this).find('.main-menu-sub').removeClass('main-menu-sub-active');
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
