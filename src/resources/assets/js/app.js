//========= start home slider =========///
var swiper = new Swiper("#homeSlider", {
  spaceBetween: 30,
  centeredSlides: true,
  loop: true,
  autoplay: {
    delay: 3000,
    disableOnInteraction: false,
  },
  effect: "fade",
  pagination: {
    el: ".swiper-pagination",
    clickable: true,
  },
  navigation: {
    nextEl: ".swiper-button-next",
    prevEl: ".swiper-button-prev",
  },
});


///=============end home slider ============/

//========= start fullscreen slider =========///
// سانیار — فاز B: اسلایدر تمام‌صفحه (دسکتاپ/موبایل هرکدام سوایپر خودشان را دارند)
document.querySelectorAll(".fullscreen-slider-swiper").forEach(function (el) {
  var config = {
    spaceBetween: 0,
    speed: 800,
    autoplay: {
      delay: 4000,
      disableOnInteraction: false,
    },
  };

  if (el.querySelectorAll(".swiper-slide").length > 1) {
    config.rewind = true; // به‌جای loop: با هر تعداد اسلاید (حتی ۲) بدون هشدار کار می‌کند
    config.effect = "fade";
  }

  var paginationEl = el.querySelector(".swiper-pagination");
  if (paginationEl) {
    config.pagination = { el: paginationEl, clickable: true };
  }

  var navNext = el.querySelector(".swiper-button-next");
  var navPrev = el.querySelector(".swiper-button-prev");
  if (navNext && navPrev) {
    config.navigation = { nextEl: navNext, prevEl: navPrev };
  }

  new Swiper(el, config);
});
//========= end fullscreen slider ============/

//============= sugget moment =============//

var swiperSugget = new Swiper("#suggetMoment", {
  slidesPerView: 1,
  spaceBetween: 30,
  loop: true,
  autoplay: {
    delay: 3000,
    disableOnInteraction: false,
  },
  speed: 500,
  on: {
    init: function () {
      $(".swiper-progress-bar").removeClass("animate");
      $(".swiper-progress-bar").removeClass("active");
      $(".swiper-progress-bar").eq(0).addClass("animate");
      $(".swiper-progress-bar").eq(0).addClass("active");
    },
    slideChangeTransitionStart: function () {
      $(".swiper-progress-bar").removeClass("animate");
      $(".swiper-progress-bar").removeClass("active");
      $(".swiper-progress-bar").eq(0).addClass("active");
    },
    slideChangeTransitionEnd: function () {
      $(".swiper-progress-bar").eq(0).addClass("animate");
    }
  }
});

//============= end sugget moment =============//

//============= start amazing =============//

// سانیار — فاز B: هر بخش شگفت‌انگیز سوایپرهای خودش را دارد (پشتیبانی چند ویجت در یک
// صفحه) و بدون ستون thumbs هم کار می‌کند؛ loop فقط با بیش از یک اسلاید فعال می‌شود.
document.querySelectorAll(".amazing-slider-slider").forEach(function (mainEl) {
  var scope = mainEl.closest(".amazing-slider");
  var thumbsEl = scope ? scope.querySelector(".amazing-slider-link") : null;

  var thumbsSwiper = null;
  if (thumbsEl && thumbsEl.querySelectorAll(".swiper-slide").length > 1) {
    thumbsSwiper = new Swiper(thumbsEl, {
      spaceBetween: 10,
      slidesPerView: 4,
      freeMode: true,
      watchSlidesProgress: true,
      allowTouchMove: false,
    });
  }

  var mainConfig = {
    effect: "fade",
    speed: 1000,
    spaceBetween: 10,
    autoplay: {
      delay: 5000,
      disableOnInteraction: false,
    },
  };

  if (thumbsSwiper) {
    mainConfig.thumbs = { swiper: thumbsSwiper };
  }

  if (mainEl.querySelectorAll(".swiper-slide").length > 1) {
    mainConfig.loop = true;
  }

  var paginationEl = mainEl.querySelector(".swiper-pagination");
  if (paginationEl) {
    mainConfig.pagination = { el: paginationEl, clickable: true };
  }

  new Swiper(mainEl, mainConfig);
});

//============= end amazing =============//

//========= start product box ==============/

// سانیار — فاز B: در یک صفحه می‌تواند چند ویجت اسلایدر محصول باشد؛ قبلاً فقط
// اولین نمونه سوایپر می‌شد (باگ قالب اصلی). ناوبری فقط اگر داخل همان کانتینر
// موجود باشد به کانفیگ اضافه می‌شود.
document.querySelectorAll(".product-slider-swiper").forEach(function (el) {
  var config = {
    slidesPerView: 5,
    spaceBetween: 10,
    breakpoints: {
      100: {
        slidesPerView: 1,
        spaceBetween: 20,
      },
      576: {
        slidesPerView: 2,
        spaceBetween: 20,
      },
      768: {
        slidesPerView: 2,
        spaceBetween: 20,
      },
      1024: {
        slidesPerView: 3,
        spaceBetween: 20,
      },
      1200: {
        slidesPerView: 4,
        spaceBetween: 20,
      },
      1400: {
        slidesPerView: 5,
        spaceBetween: 20,
      }},
  };

  var navNext = el.querySelector(".swiper-button-next");
  var navPrev = el.querySelector(".swiper-button-prev");
  if (navNext && navPrev) {
    config.navigation = { nextEl: navNext, prevEl: navPrev };
  }

  new Swiper(el, config);
});

//========= end product box ==============/


//=========== product gallery ===================//

var proSwiper = new Swiper(".product-gallery-thumb", {
  spaceBetween: 10,
  slidesPerView: 4,
  freeMode: true,
  watchSlidesProgress: true,
  breakpoints: {
    // when window width is >= 320px
    320: {
      slidesPerView: 3,
      spaceBetween: 10
    },
    // when window width is >= 480px
    400: {
      slidesPerView: 4,
      spaceBetween: 10
    },
  },
});
var proThumbswiper = new Swiper(".product-gallery", {
  spaceBetween: 10,
  navigation: {
    nextEl: ".swiper-button-next",
    prevEl: ".swiper-button-prev",
  },
  pagination: {
    el: ".swiper-pagination",
    clickable: true,
  },
  zoom: {
    maxRatio: 3,
    minRation: 1
  },
  thumbs: {
    swiper: proSwiper,
  },
});

//=========== end product gallery ===================//

//========= start new category ==============/
// سانیار — فاز B: چند نمونه در یک صفحه پشتیبانی می‌شود
document.querySelectorAll(".free-mode").forEach(function (el) {
  new Swiper(el, {
    slidesPerView: "auto",
    spaceBetween: 10,
    freeMode: true,
  });
});
//========= end new category ==============/