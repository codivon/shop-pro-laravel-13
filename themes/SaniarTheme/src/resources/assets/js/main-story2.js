$(document).ready(function() {
    // ===== تنظیمات اصلی =====
    const $storyModal = $('#story-modal');
    let storyProgressInterval = null;
    let storyIsPaused = false;
    let storyCurrentElapsed = 0;
    let storyCurrentDuration = 10000;

    // ذخیره زمان برای هر استوری
    let storySavedTimes = {};

    // متغیر برای تاخیر در شروع مجدد هاور
    let resumeTimeout = null;

    // ===== توابع اصلی =====

    // دریافت زمان استوری از دیتابیس
    function getStoryDurationFromDB($storyItem) {

        // روش اول: از data-duration
        let duration = $storyItem.data('duration');
        if (duration && !isNaN(parseInt(duration)) && parseInt(duration) > 0) {
            return parseInt(duration);
        }

        // روش دوم: از data-story-duration
        duration = $storyItem.data('story-duration');
        if (duration && !isNaN(parseInt(duration)) && parseInt(duration) > 0) {
            return parseInt(duration);
        }

        // روش سوم: از attribute id استوری
        const storyId = $storyItem.attr('id')?.replace('story-', '');
        if (storyId && window.storyDurations && window.storyDurations[storyId]) {
            return window.storyDurations[storyId];
        }
        // بررسی ویدیو
        let video = $storyItem.find('video')[0];
        if (video && video.duration && isFinite(video.duration) && video.duration > 0) {
            return video.duration * 1000;
        }

        return 10000; // 10 ثانیه پیش‌فرض
    }

    // ساخت نوارهای پیشرفت
    function buildProgressBars() {
        $storyModal.find('.carousel-item').each(function(index) {
            const $this = $(this);
            const $progressContainer = $this.find('.story-progress-bars');

            if ($progressContainer.length) {
                $progressContainer.empty();
                $progressContainer.append(`
                    <div class="progress-bar-item" data-story-index="${index}">
                        <div class="progress-bar-fill" style="width: 0%;"></div>
                    </div>
                `);
            }
        });
    }

    // به‌روزرسانی نوار پیشرفت
    function updateProgressBar(percentage) {
        const $activeItem = $storyModal.find('.carousel-item.active');
        const $fill = $activeItem.find('.progress-bar-fill');
        $fill.css('width', `${Math.min(percentage, 100)}%`);
    }

    // ذخیره زمان استوری فعلی
    function saveCurrentStoryTime() {
        const $activeItem = $storyModal.find('.carousel-item.active');
        const storyId = $activeItem.attr('id');
        if (storyId && storyCurrentElapsed > 0) {
            storySavedTimes[storyId] = storyCurrentElapsed;
        }

    }

    // بازیابی زمان استوری
    function loadStoryTime($storyItem) {
        const storyId = $storyItem.attr('id');
        if (storyId && storySavedTimes[storyId]) {
            storyCurrentElapsed = storySavedTimes[storyId];
        } else {
            storyCurrentElapsed = 0;
        }

        const percentage = (storyCurrentElapsed / storyCurrentDuration) * 100;
        updateProgressBar(percentage);
    }

    // شروع تایمر
    function startProgressTimer() {
        // توقف تایمر قبلی اگر وجود دارد
        if (storyProgressInterval) {
            clearInterval(storyProgressInterval);
            storyProgressInterval = null;
        }

        const $activeItem = $storyModal.find('.carousel-item.active');

        // دریافت مدت زمان استوری
        storyCurrentDuration = getStoryDurationFromDB($activeItem);

        // بازیابی زمان ذخیره شده
        loadStoryTime($activeItem);

        // اگر استوری کامل شده بود، برو به بعدی
        if (storyCurrentElapsed >= storyCurrentDuration) {
            goToNextStory();
            return;
        }


        // اگر در حالت pause نیست، شروع کن
        if (!storyIsPaused) {
            storyProgressInterval = setInterval(() => {
                if (!storyIsPaused) {
                    storyCurrentElapsed += 100;

                    const percentage = (storyCurrentElapsed / storyCurrentDuration) * 100;
                    updateProgressBar(percentage);

                    if (storyCurrentElapsed >= storyCurrentDuration) {
                        clearInterval(storyProgressInterval);
                        storyProgressInterval = null;

                        const storyId = $activeItem.attr('id');
                        if (storyId) {
                            storySavedTimes[storyId] = storyCurrentDuration;
                        }

                        storyIsPaused = false;
                        goToNextStory();
                    }
                }
            }, 100);
        }
    }

    // رفتن به استوری بعدی
    function goToNextStory() {
        const $carousel = $storyModal.find('#carouselExampleIndicatorsStory');
        const totalStories = $storyModal.find('.carousel-item').length;
        const currentIndex = $storyModal.find('.carousel-item.active').index();

        if (currentIndex + 1 < totalStories) {
            if (storyProgressInterval) {
                clearInterval(storyProgressInterval);
                storyProgressInterval = null;
            }

            saveCurrentStoryTime();
            $carousel.carousel('next');

        } else {
            setTimeout(() => {
                $storyModal.modal('hide');
                resetAllStories();
            }, 200);
        }
    }

    // رفتن به استوری قبلی
    function goToPrevStory() {
        const $carousel = $storyModal.find('#carouselExampleIndicatorsStory');
        const currentIndex = $storyModal.find('.carousel-item.active').index();

        if (currentIndex > 0) {
            if (storyProgressInterval) {
                clearInterval(storyProgressInterval);
                storyProgressInterval = null;
            }

            saveCurrentStoryTime();
            $carousel.carousel('prev');
        }
    }

    // ریست همه استوری‌ها
    function resetAllStories() {
        if (storyProgressInterval) {
            clearInterval(storyProgressInterval);
            storyProgressInterval = null;
        }
        storyCurrentElapsed = 0;
        storyIsPaused = false;
        storySavedTimes = {};

        if (resumeTimeout) {
            clearTimeout(resumeTimeout);
            resumeTimeout = null;
        }
    }

    // مدیریت ویدیو
    function handleVideo($video) {
        if (!$video.length) return;

        const videoElement = $video[0];
        const $storyItem = $video.closest('.carousel-item');

        const storyId = $storyItem.attr('id');
        if (storyId && storySavedTimes[storyId]) {
            const savedSeconds = storySavedTimes[storyId] / 1000;
            if (savedSeconds > 0 && savedSeconds < videoElement.duration) {
                videoElement.currentTime = savedSeconds;
            }
        }

        const playPromise = videoElement.play();
        if (playPromise !== undefined) {
            playPromise.catch(error => {
                console.log('Video play error:', error);
            });
        }

        const syncWithVideo = () => {
            if (!storyIsPaused && videoElement.duration && isFinite(videoElement.duration)) {
                storyCurrentElapsed = videoElement.currentTime * 1000;
                const percentage = (storyCurrentElapsed / storyCurrentDuration) * 100;
                updateProgressBar(percentage);
            }
        };

        videoElement.addEventListener('timeupdate', syncWithVideo);
        videoElement.addEventListener('ended', () => {
            goToNextStory();
        });

        $video.data('sync-handler', syncWithVideo);
    }

    // ===== رویدادها =====

    // رویداد تغییر اسلاید
    $storyModal.find('#carouselExampleIndicatorsStory').on('slid.bs.carousel', function() {
        if (storyProgressInterval) {
            clearInterval(storyProgressInterval);
            storyProgressInterval = null;
        }

        storyIsPaused = false;

        const $activeItem = $storyModal.find('.carousel-item.active');
        const storyElementId = $activeItem.attr('id');
        const newStoryId = storyElementId ? storyElementId.replace('story-', '') : null;

        // if (newStoryId && !viewedStories[newStoryId]) {
        //     console.log(viewedStories)
        //     console.log(newStoryId)
        //     // ثبت بازدید
        //     seenStory(newStoryId);
        //     // اضافه کردن کلاس unActive به آیتم مربوطه در لیست
        //     $(`.allStoryIndex .storyItem#${newStoryId}`).addClass('unActive');
        // }
            seenStory(newStoryId);
            // اضافه کردن کلاس unActive به آیتم مربوطه در لیست
            $(`.allStoryIndex .storyItem#${newStoryId}`).addClass('unActive');

        const $video = $storyModal.find('.carousel-item.active video');
        if ($video.length) {
            $storyModal.find('video').each(function() {
                if (this !== $video[0]) {
                    this.pause();
                }
            });
            handleVideo($video);
        }

        startProgressTimer();
    });

    // رویداد قبل از تغییر اسلاید
    $storyModal.find('#carouselExampleIndicatorsStory').on('slide.bs.carousel', function() {
        if (storyProgressInterval) {
            clearInterval(storyProgressInterval);
            storyProgressInterval = null;
        }
        saveCurrentStoryTime();
        storyIsPaused = false;

        const $video = $storyModal.find('.carousel-item.active video');
        if ($video.length) {
            $video[0].pause();
        }
    });

    // هاور موس روی استوری
 /*   $storyModal.on('mouseenter', '.carousel-item', function() {
        if (resumeTimeout) {
            clearTimeout(resumeTimeout);
            resumeTimeout = null;
        }

        if (storyProgressInterval) {
            clearInterval(storyProgressInterval);
            storyProgressInterval = null;
        }
        storyIsPaused = true;
        saveCurrentStoryTime();

        const $video = $(this).find('video');
        if ($video.length && $video[0] && !$video[0].paused) {
            $video[0].pause();
        }
    });

    $storyModal.on('mouseleave', '.carousel-item', function() {
        resumeTimeout = setTimeout(() => {
            if ($storyModal.hasClass('show') && storyIsPaused === true) {
                const $activeItem = $storyModal.find('.carousel-item.active');
                const $fill = $activeItem.find('.progress-bar-fill');
                const currentWidth = parseFloat($fill.css('width')) || 0;

                if (currentWidth < 98) {
                    storyIsPaused = false;
                    startProgressTimer();

                    const $video = $(this).find('video');
                    if ($video.length && $video[0] && !$video[0].ended) {
                        $video[0].play().catch(e => console.log('Video play error:', e));
                    }
                }
            }
            resumeTimeout = null;
        }, 50);
    });*/

    // لمس موبایل
    let touchStartTime = 0;
    let touchTimeout = null;

    $storyModal.on('touchstart', '.carousel-item', function(e) {
        if (touchTimeout) {
            clearTimeout(touchTimeout);
            touchTimeout = null;
        }

        touchStartTime = Date.now();

        if (storyProgressInterval) {
            clearInterval(storyProgressInterval);
            storyProgressInterval = null;
        }
        storyIsPaused = true;
        saveCurrentStoryTime();

        const $video = $(this).find('video');
        if ($video.length && $video[0] && !$video[0].paused) {
            $video[0].pause();
        }
    });

    $storyModal.on('touchend', '.carousel-item', function() {
        touchTimeout = setTimeout(() => {
            if ($storyModal.hasClass('show') && storyIsPaused === true) {
                const $activeItem = $storyModal.find('.carousel-item.active');
                const $fill = $activeItem.find('.progress-bar-fill');
                const currentWidth = parseFloat($fill.css('width')) || 0;

                if (currentWidth < 98) {
                    storyIsPaused = false;
                    startProgressTimer();

                    const $video = $(this).find('video');
                    if ($video.length && $video[0] && !$video[0].ended) {
                        $video[0].play().catch(e => console.log('Video play error:', e));
                    }
                }
            }
            touchTimeout = null;
        }, 100);
    });

    // کلیک روی استوری
    $storyModal.on('click', '.carousel-item', function(e) {
        if ($(e.target).closest('.story-widget-area, .story-product, .close-story, .carousel-control-prev, .carousel-control-next, .story-likes-comments, .story-video-sound').length) {
            return;
        }

        const windowWidth = $(window).width();
        const clickX = e.clientX;

        if (storyProgressInterval) {
            clearInterval(storyProgressInterval);
            storyProgressInterval = null;
        }
        saveCurrentStoryTime();

        if (clickX > windowWidth / 2) {
            goToNextStory();
        } else if (clickX < windowWidth / 2) {
            goToPrevStory();
        }
    });


    // باز شدن مودال
    $storyModal.on('shown.bs.modal', function() {
        buildProgressBars();
        resetAllStories();

        setTimeout(() => {
            const $firstVideo = $storyModal.find('.carousel-item.active video');
            if ($firstVideo.length) {
                handleVideo($firstVideo);
            }
            startProgressTimer();
        }, 100);

        const video = $(this).find('.story-video-player')[0];
        if (video) {
            video.muted = false; // غیرفعال کردن میوت
            video.volume = 1; // صدا روی حداکثر
        }

    });

    // بسته شدن مودال
    $storyModal.on('hide.bs.modal', function() {
        if (storyProgressInterval) {
            clearInterval(storyProgressInterval);
            storyProgressInterval = null;
        }

        if (resumeTimeout) {
            clearTimeout(resumeTimeout);
            resumeTimeout = null;
        }

        if (touchTimeout) {
            clearTimeout(touchTimeout);
            touchTimeout = null;
        }

        $storyModal.find('video').each(function() {
            this.pause();
        });

        storyIsPaused = false;
    });

    // دکمه‌های قبلی و بعدی
    $storyModal.find('.carousel-control-prev').on('click', function(e) {
        e.stopPropagation();
        goToPrevStory();
    });

    $storyModal.find('.carousel-control-next').on('click', function(e) {
        e.stopPropagation();
        goToNextStory();
    });

    $storyModal.find('.close-story').on('click', function() {
        $storyModal.modal('hide');
    });


    $storyModal.find('.story-video-sound').on('click', function() {
        const storyId=$(this).data('story-id');
        const video = document.getElementById('video-' + storyId);

        if (!video) {
            console.error('Video not found');
            return;
        }

        const $icon = $(this).find('i');

        // Toggle mute
        video.muted = !video.muted;

        // تغییر آیکون
        if (video.muted) {
            $icon.removeClass('fa-volume-up').addClass('fa-volume-xmark');
        } else {
            $icon.removeClass('fa-volume-xmark').addClass('fa-volume-up');
        }

        console.log('Volume status:', video.muted ? 'Muted' : 'Unmuted');
    });

    $('.story-likes').click(function(e) {
        e.stopPropagation();

        const $this = $(this);
        const $carouselItem = $this.closest('.carousel-item');
        const storyId = $carouselItem.data('story-id');
        const $likeIcon = $this.find('i');
        const $likeCount = $this.find('span');
        const isCurrentlyLiked = $this.hasClass('liked');

        // جلوگیری از کلیک مکرر
        if ($this.hasClass('loading')) {
            return false;
        }

        $this.addClass('loading');

        // تغییر موقت UI برای تجربه بهتر کاربر (بهبود UX)
        if (!isCurrentlyLiked) {
            $likeIcon.removeClass('fa-heart-o').addClass('fa-heart');
            $likeCount.text(parseInt($likeCount.text()) + 1);
            $this.addClass('liked');
            // انیمیشن قلب
            $this.css('transform', 'scale(1.2)');
            setTimeout(() => {
                $this.css('transform', 'scale(1)');
            }, 200);
        } else {
            $likeIcon.removeClass('fa-heart').addClass('fa-heart-o');
            $likeCount.text(parseInt($likeCount.text()) - 1);
            $this.removeClass('liked');
        }

        $.ajax({
            url: $this.data('action'),
            type: 'POST',
            data: {
                story_id: storyId,
                is_liked: !isCurrentlyLiked ? 1 : 0
            },
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-CSRF-TOKEN', $('meta[name="csrf-token"]').attr('content'));
            },
            success: function(response) {
                if (response.success) {
                    // به‌روزرسانی با تعداد واقعی از سرور
                    $likeCount.text(response.likes_count);

                    if (response.is_liked) {
                        $likeIcon.removeClass('fa-heart-o').addClass('fa-heart');
                        $this.addClass('liked');
                    } else {
                        $likeIcon.removeClass('fa-heart').addClass('fa-heart-o');
                        $this.removeClass('liked');
                    }

                    // ذخیره در localStorage برای همگام‌سازی با سایر المان‌ها (اختیاری)
                    let likedStories = JSON.parse(localStorage.getItem('liked_stories') || '{}');
                    if (response.is_liked) {
                        likedStories[storyId] = true;
                    } else {
                        delete likedStories[storyId];
                    }
                    localStorage.setItem('liked_stories', JSON.stringify(likedStories));

                } else {
                    // برگرداندن تغییرات در صورت خطا
                    if (!isCurrentlyLiked) {
                        $likeIcon.removeClass('fa-heart').addClass('fa-heart-o');
                        $likeCount.text(parseInt($likeCount.text()) - 1);
                        $this.removeClass('liked');
                    } else {
                        $likeIcon.removeClass('fa-heart-o').addClass('fa-heart');
                        $likeCount.text(parseInt($likeCount.text()) + 1);
                        $this.addClass('liked');
                    }

                    // نمایش پیام خطا (اختیاری)
                    if (response.message) {
                        showCustomToast(response.message,'error');
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);

                // برگرداندن تغییرات در صورت خطا
                if (!isCurrentlyLiked) {
                    $likeIcon.removeClass('fa-heart').addClass('fa-heart-o');
                    $likeCount.text(parseInt($likeCount.text()) - 1);
                    $this.removeClass('liked');
                } else {
                    $likeIcon.removeClass('fa-heart-o').addClass('fa-heart');
                    $likeCount.text(parseInt($likeCount.text()) + 1);
                    $this.addClass('liked');
                }

                // نمایش پیام خطا
                showCustomToast('مشکلی در ارتباط با سرور وجود دارد. لطفاً مجدداً تلاش کنید.','error');
            },
            complete: function() {
                $this.removeClass('loading');
            }
        });
    });


    // ===== مدیریت بازدید استوری =====
    let storySeen = [];
    let viewedStories = {}; // برای جلوگیری از ثبت تکراری در یک جلسه

// ثبت بازدید استوری
    function seenStory(storyId) {
        // جلوگیری از ثبت تکراری در یک جلسه
    /*    if (viewedStories[storyId]) {
            console.log('Story already viewed in this session:', storyId);
            return;
        }*/

        viewedStories[storyId] = true;
        storySeen.push(storyId);

        const CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
        const url = $('.allStoryIndex').data('action');
        const data = {
            _token: CSRF_TOKEN,
            story_id: storyId
        };

        $.post(url, data, function(response) {
            if (response.success) {
                //console.log('View recorded for story:', storyId, response.data);

                $(`#carouselExampleIndicatorsStory .carousel-item#story-${storyId} .story-likes span`).text(response.data.likes_count);
                // به‌روزرسانی نمایش تعداد بازدیدها (اختیاری)
                if (response.data.views_count) {
                    $(`.story-views-count[data-story-id="${storyId}"]`).text(response.data.views_count);
                }
            }
        }).fail(function(xhr) {
            console.error('Error recording view:', xhr);
        });
    }

// ===== رویداد کلیک روی استوری در لیست اصلی =====
    $('.allStoryIndex .storyItem').on('click', function(event) {
        const $this = $(this);
        const storyId = $this.attr('id');
        const storyElementId = 'story-' + storyId; // آیدی در مودال

        // ثبت بازدید
        seenStory(storyId);

        // اضافه کردن کلاس unActive به آیتم کلیک شده
        $this.addClass('unActive');

        // فعال کردن استوری مربوطه در مودال
        $('#carouselExampleIndicatorsStory .carousel-item').removeClass('active');
        $(`#carouselExampleIndicatorsStory .carousel-item#${storyElementId}`).addClass('active');

        // ریست ایندکس برای شروع از استوری صحیح
        const newIndex = $(`#carouselExampleIndicatorsStory .carousel-item#${storyElementId}`).index();
        if (newIndex !== -1) {
            $('#carouselExampleIndicatorsStory').carousel(newIndex);
        }

        // ثبت تعامل باز شدن استوری
       // recordStoryOpenInteraction(storyId);
    });

// ===== رویداد تغییر استوری با دکمه‌های قبلی/بعدی =====
    $('#carouselExampleIndicatorsStory .carousel-control-next, #carouselExampleIndicatorsStory .carousel-control-prev').on('click', function(event) {
        const $carousel = $(this).parent('#carouselExampleIndicatorsStory');
        const $activeItem = $carousel.find('.carousel-item.active');
        const storyElementId = $activeItem.attr('id');
        const storyId = storyElementId.replace('story-', '');

        if (storyId) {
            // اضافه کردن کلاس unActive به آیتم مربوطه در لیست
            $(`.allStoryIndex .storyItem#${storyId}`).addClass('unActive');

            // ثبت بازدید
            seenStory(storyId);
        }
    });

// ===== ثبت تعامل باز شدن استوری =====
/*
    function recordStoryOpenInteraction(storyId) {
        const CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');

        $.ajax({
            url: $('.allStoryIndex').data('action-interaction'),
            type: 'POST',
            data: {
                _token: CSRF_TOKEN,
                story_id: storyId,
                type: 'story_open'
            },
            success: function(response) {
                console.log('Story open interaction recorded:', storyId);
            },
            error: function(xhr) {
                console.error('Error recording interaction:', xhr);
            }
        });
    }
*/

// ===== رویداد بستن مودال (توقف ویدیو) =====
    $('#story-modal').on('hide.bs.modal', function() {
        // توقف همه ویدیوها
        $('#carouselExampleIndicatorsStory video').each(function() {
            this.pause();
        });
    });

// ===== در صورت نیاز: ثبت بازدید خودکار هنگام نمایش استوری در مودال =====
    $('#story-modal').on('shown.bs.modal', function() {
        const $activeItem = $('#carouselExampleIndicatorsStory .carousel-item.active');
        const storyElementId = $activeItem.attr('id');
        const storyId = storyElementId ? storyElementId.replace('story-', '') : null;

        if (storyId && !viewedStories[storyId]) {
            seenStory(storyId);
            $(`.allStoryIndex .storyItem#${storyId}`).addClass('unActive');
        }

    });

// ===== ثبت تعامل پیشرفت مشاهده (اختیاری) =====
/*
    let progressRecorded = {};

    function recordProgress(storyId, progressType) {
        const key = `${storyId}_${progressType}`;
        if (progressRecorded[key]) return;

        progressRecorded[key] = true;

        const CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');

        $.ajax({
            url: $('.allStoryIndex').data('action-interaction'),
            type: 'POST',
            data: {
                _token: CSRF_TOKEN,
                story_id: storyId,
                type: progressType
            }
        });
    }
*/


    const $prev = $('.carousel-control-prev');
    const $next = $('.carousel-control-next');

    $prev.insertAfter($next);

    // یا تغییر کلاس‌ها
    $prev.removeClass('carousel-control-prev').addClass('carousel-control-next');
    $next.removeClass('carousel-control-next').addClass('carousel-control-prev');

    // تغییر آیکون‌ها
    $prev.find('.fa-angle-left').removeClass('fa-angle-left').addClass('fa-angle-right');
    $next.find('.fa-angle-right').removeClass('fa-angle-right').addClass('fa-angle-left');


});
