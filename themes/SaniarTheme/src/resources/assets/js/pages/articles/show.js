$(document).ready(function() {
    const player = new Plyr('.articles-video-player');

    // لایک کردن مقاله
    $('.like-button').on('click', function() {
        let button = $(this);
        let postId = button.data('post-id');
        let icon = button.find('i');
        let likesSpan = button.find('.likes-count');

        block(button)
        $.ajax({
            url: button.data('action'),
            type: 'POST',
            data: {
                post_id: postId,
            },
            success: function(response) {
                console.log(response);
                if (response.liked) {
                    icon.addClass('fa-heart-like');
                } else {
                    icon.removeClass('fa-heart-like');
                }
                likesSpan.text(response.likes_count);
            },
            beforeSend: function(xhr) {
                xhr.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr('content'));
            },
            error: function(xhr) {
                console.error('Error:', xhr);
            },
            complete: function() {
                unblock(button)
            }
        });
    });

    $('#comment-form').on('submit', function(e) {
        e.preventDefault();
        let form = $(this);
        let content = form.find('textarea[name="content"]').val();

        if (content.length<=1){
            showCustomToast('متن دیدگاه نمیتواند خالی باشد','error');
            return
        }

        block('#comment-form')

        $.ajax({
            url: form.data('action'),
            type: 'POST',
            data: {
                content: content,
            },
            success: function(response) {
                if (response.success) {
                    var comment = response.comment;

                    // تعیین نام و آواتار
                    var userName = comment.user ? comment.user.full_name : 'شما';
                    var avatarUrl = comment.user ? comment.user.image_url : '/default-avatar.png';

                    // ساخت HTML
                    var commentHtml = `
                <div class="comment card mb-3 comment-pending-own" id="comment-${comment.id}" style="opacity: 0.6;">
                    <div class="card-body">
                        <div class="comment--avatar">
                            <img class="shadow-1 mb-2" src="${avatarUrl}" alt="${userName}" width="55" height="55" loading="lazy">
                        </div>
                        <div class="comment--meta">
                            <div class="comment--name">
                                <a href="javascript:void(0)" style="text-decoration: none;">
                                    <span>${userName}</span>
                                    <small class="alert-warning">
                                        <i class="fas fa-clock"></i> ${comment.is_pending ? 'این دیدگاه پس از تایید مدیر نمایش داده می‌شود' : ''}
                                    </small>
                                </a>
                            </div>
                            <span class="date">${comment.created_at}</span>
                            <p class="comment--description mb-3">${escapeHtml(comment.body)}</p>
                        </div>
                    </div>
                </div>
            `;

                    // اضافه کردن به صفحه
                    if ($('.user-pending-comments').length === 0) {
                        $('.approved-comments').before(`
                    <div class="user-pending-comments">
                        <h5>دیدگاه‌های در انتظار تایید شما</h5>
                        <div class="comments-list"></div>
                    </div>
                `);
                    }

                    $('.comments-container .user-pending-comments').prepend(commentHtml);

                    // پاک کردن فرم
                    $('#comment-form')[0].reset();

                    // نمایش پیام
                    showCustomToast(response.message,'success');
                    // اسکرول به کامنت جدید
                    setTimeout(function() {
                        $('html, body').animate({
                            scrollTop: $('.user-pending-comments').offset().top - 100
                        }, 500);
                    }, 100);
                }
            },
            beforeSend: function(xhr) {
                xhr.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr('content'));
            },
            error: function(xhr) {
                showCustomToast('خطا در ارسال دیدگاه.','error');
            },
            complete: function(xhr) {
                unblock('#comment-form')
            }
        });
    });

    function escapeHtml(text) {
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
    function showAlert(message, type) {
        var $alert = $('.alert.alert-' + type);
        $alert.html('<i class="fas fa-' + (type === 'success' ? 'circle-check' : 'exclamation-circle') + '"></i> ' + message);
        $alert.removeClass('hide').fadeIn();

        setTimeout(function() {
            $alert.fadeOut(function() {
                $alert.addClass('hide');
            });
        }, 10000);
    }

    // ارسال پاسخ
    $('#send-answer-form').on('submit', function(e) {
        e.preventDefault();
        let form = $(this);
        let commentId = form.data('comment-id');
        let content = form.find('textarea[name="content"]').val();

        if (content.length<=1){
            showCustomToast('متن پاسخ نمیتواند خالی باشد','error');
            return
        }

        block('#send-answer-model')

        if (commentId){
            $.ajax({
                url: form.data('action'),
                type: 'POST',
                data: {
                    comment_id: commentId,
                    content: content,
                },
                success: function(response) {
                    if (response.success) {
                        var comment = response.comment;

                        // تعیین نام و آواتار
                        var userName = comment.user ? comment.user.full_name : 'شما';
                        var avatarUrl = comment.user ? comment.user.image_url : '';

                        // ساخت HTML
                        var commentHtml = `
                <div class="comment comment-answer card mb-2 comment-pending-own" id="comment-${comment.id}" style="opacity: 0.6;">
                    <div class="card-body">
                        <div class="comment--avatar">
                            <img class="shadow-1 mb-2" src="${avatarUrl}" alt="${userName}" width="55" height="55" loading="lazy">
                        </div>
                        <div class="comment--meta">
                            <div class="comment--name">
                                <a href="javascript:void(0)" style="text-decoration: none;">
                                    <span>${userName}</span>
                                    <small class="alert-warning">
                                        <i class="fas fa-clock"></i> ${comment.is_pending ? 'این پاسخ پس از تایید مدیر نمایش داده می‌شود' : ''}
                                    </small>
                                </a>
                            </div>
                            <span class="date">${comment.created_at}</span>
                            <p class="comment--description mb-3">${escapeHtml(comment.body)}</p>
                        </div>
                    </div>
                </div>
            `;

                        // اضافه کردن به صفحه
                        if ($('.user-pending-comments').length === 0) {
                            $('.approved-comments').before(`
                    <div class="user-pending-comments">
                        <h5>دیدگاه‌های در انتظار تایید شما</h5>
                        <div class="comments-list"></div>
                    </div>
                `);
                        }

                        $('.comments-container .user-pending-replies-comments-'+comment.parent_id).append(commentHtml);

                        // پاک کردن فرم
                        $('#send-answer-form')[0].reset();

                        // نمایش پیام
                        showCustomToast(response.message,'success');
                        $('#send-answer-model').modal('hide')

                    }
                },
                beforeSend: function(xhr) {
                    xhr.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr('content'));
                },
                error: function(xhr) {
                    showCustomToast('خطا در ارسال پاسخ.','error');
                },
                complete: function(xhr) {
                    unblock('#send-answer-model')
                }
            });
        }

    });

    $(document).on('click', '.action--child.like', function(e) {
        e.preventDefault();

        let button = $(this);
        let commentId = button.data('comment-id');
        let icon = button.find('i');
        let countSpan = button.find('span');
        let parentDiv = button.closest('.action');
        let dislikeButton = parentDiv.find('.dislike');
        let dislikeIcon = dislikeButton.find('i');
        let dislikeCountSpan = dislikeButton.find('span');

        // غیرفعال کردن دکمه موقتاً
        button.prop('disabled', true);

        $.ajax({
            url: button.data('action'),
            type: 'POST',
            data: {
                type: 'like',
            },
            success: function(response) {
                if (response.success) {

                    if (response.liked) {
                        // لایک جدید
                        icon.removeClass('fa-regular').addClass('fa-solid liked');
                    } else {
                        // آنلایک کردن
                        icon.removeClass('fa-solid liked').addClass('fa-regular');
                    }

                    // بروزرسانی تعدادها
                    countSpan.text(response.likes_count);
                    dislikeCountSpan.text(response.dislikes_count);

                    // ریست کردن آیکون دیسلایک (اگر کاربر قبلاً دیسلایک کرده بود)
                    if (dislikeIcon.hasClass('fa-solid disliked')) {
                        dislikeIcon.removeClass('fa-solid disliked').addClass('fa-regular');
                    }

                    // انیمیشن
                    button.addClass('animate__animated animate__heartBeat');
                    setTimeout(() => {
                        button.removeClass('animate__animated animate__heartBeat');
                    }, 300);
                }
            },
            beforeSend: function(xhr) {
                xhr.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr('content'));
            },
            error: function(xhr) {
                console.error('Error:', xhr);
                showCustomToast('خطا در ثبت لایک','error');
            },
            complete: function() {
                button.prop('disabled', false);
            }
        });
    });

    // دیسلایک کردن کامنت
    $(document).on('click', '.action--child.dislike', function(e) {
        e.preventDefault();

        let button = $(this);
        let commentId = button.data('comment-id');
        let icon = button.find('i');
        let countSpan = button.find('span');
        let parentDiv = button.closest('.action');
        let likeButton = parentDiv.find('.like');
        let likeIcon = likeButton.find('i');
        let likeCountSpan = likeButton.find('span');

        // غیرفعال کردن دکمه موقتاً
        button.prop('disabled', true);

        $.ajax({
            url: button.data('action'),
            type: 'POST',
            data: {
                type: 'dislike',
            },
            success: function(response) {
                if (response.success) {

                    if (response.liked) {
                        // دیسلایک جدید
                        icon.removeClass('fa-regular').addClass('fa-solid disliked');
                    } else {
                        // آندیسلایک کردن
                        icon.removeClass('fa-solid disliked').addClass('fa-regular');
                    }

                    // بروزرسانی تعدادها
                    countSpan.text(response.dislikes_count);
                    likeCountSpan.text(response.likes_count);

                    // ریست کردن آیکون لایک (اگر کاربر قبلاً لایک کرده بود)
                    if (likeIcon.hasClass('fa-solid liked')) {
                        likeIcon.removeClass('fa-solid liked').addClass('fa-regular');
                    }

                    // انیمیشن
                    button.addClass('animate__animated animate__shakeX');
                    setTimeout(() => {
                        button.removeClass('animate__animated animate__shakeX');
                    }, 300);
                }
            },
            beforeSend: function(xhr) {
                xhr.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr('content'));
            },
            error: function(xhr) {
                console.error('Error:', xhr);
                showCustomToast('خطا در ثبت دیسلایک','error');
            },
            complete: function() {
                button.prop('disabled', false);
            }
        });
    });

    // دیسلایک کردن کامنت
    $(document).on('click', '.comments-container .send-answer', function(e) {
        e.preventDefault();

        let button = $(this);
        let commentId = button.data('comment-id');
        let userName = button.data('user-name');
        let modal=$('#send-answer-model');

        modal.find('.replay-user-name').html(userName)
        modal.find('form.reply-form').attr('data-comment-id',commentId)

    });

});
