/**
 * سانیار فاز K — صفحهٔ مقاله: لایک + ثبت دیدگاه + پاسخ + لایک/دیسلایک دیدگاه
 * + صفحه‌بندی AJAX نظرات + کپی لینک اشتراک‌گذاری.
 * همهٔ اندپوینت‌ها از data-attr روی [data-saniar-article] خوانده می‌شوند.
 */
(function () {
    'use strict';

    var wrap = document.querySelector('[data-saniar-article]');
    if (!wrap || wrap.dataset.saniarInit === '1') return;
    wrap.dataset.saniarInit = '1';

    var CSRF = document.querySelector('meta[name="csrf-token"]');
    var likeUrl = wrap.dataset.likeUrl;
    var commentStoreUrl = wrap.dataset.commentStoreUrl;
    var commentReplyUrl = wrap.dataset.commentReplyUrl;
    var commentsListUrl = wrap.dataset.commentsListUrl;

    function toast(message, type) {
        var el = document.createElement('div');
        el.className = 'saniar-toast alert alert-' + (type === 'error' ? 'danger' : 'success') +
            ' position-fixed shadow-box rounded-3 px-3 py-2 font-14';
        el.style.cssText = 'top:84px;left:18px;z-index:2000;max-width:340px;';
        el.textContent = message;
        document.body.appendChild(el);
        setTimeout(function () { el.remove(); }, 3200);
    }

    function ajax(url, data, done, fail) {
        return $.ajax({
            url: url,
            type: 'POST',
            data: data,
            headers: function (xhr) {
                if (CSRF) xhr.setRequestHeader('X-CSRF-TOKEN', CSRF.content);
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            },
            success: done,
            error: function (xhr) {
                if (xhr.statusText === 'abort') return;
                var msg = 'خطایی رخ داد؛ دوباره تلاش کنید';
                if (xhr.responseJSON) {
                    if (xhr.responseJSON.message) msg = xhr.responseJSON.message;
                    else if (xhr.responseJSON.errors) msg = Object.values(xhr.responseJSON.errors)[0][0];
                }
                toast(msg, 'error');
                if (fail) fail(xhr);
            }
        });
    }

    /* ---------- ۱) لایک مقاله ---------- */
    var likeBtn = wrap.querySelector('.like-btn');
    if (likeBtn) {
        var postId = null;
        // شناسهٔ پست از روت استور (آخرین سگمنت) یا اتریبیوت اختیاری
        var storeUrlParts = (commentStoreUrl || '').split('/');
        var slugCandidate = storeUrlParts[storeUrlParts.length - 1];
        var commentsBox = document.getElementById('article-comments');

        likeBtn.addEventListener('click', function () {
            var liked = likeBtn.classList.contains('active');
            // post_id از کامنت‌بوکس نیازمند id عددی است؛ از likes-count DOM و endpoint لایک
            // پارامتر post_id را کنترلر با validation می‌خواهد — از data-attr کمکی:
            var data = new FormData();
            data.append('post_id', wrap.dataset.postId || slugCandidate);

            $.ajax({
                url: likeUrl,
                type: 'POST',
                data: { post_id: wrap.dataset.postId || slugCandidate, _token: CSRF ? CSRF.content : '' },
                headers: function (xhr) {
                    if (CSRF) xhr.setRequestHeader('X-CSRF-TOKEN', CSRF.content);
                    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                },
                success: function (res) {
                    if (!res.success) return;
                    likeBtn.classList.toggle('active', res.liked);
                    likeBtn.setAttribute('aria-pressed', res.liked ? 'true' : 'false');
                    var icon = likeBtn.querySelector('i');
                    if (icon) icon.className = res.liked ? 'bi bi-heart-fill' : 'bi bi-heart';
                    var count = likeBtn.querySelector('.likes-count');
                    if (count) count.textContent = new Intl.NumberFormat('fa-IR').format(res.likes_count);
                },
                error: function (xhr) {
                    if (xhr.statusText !== 'abort') toast('خطا در ثبت لایک', 'error');
                }
            });
        });
    }

    /* ---------- ۲) فرم دیدگاه جدید ---------- */
    var commentForm = wrap.querySelector('[data-saniar-comment-form]');
    if (commentForm) {
        commentForm.addEventListener('submit', function (e) {
            e.preventDefault();
            var textarea = commentForm.querySelector('textarea[name="content"]');
            var body = (textarea.value || '').trim();
            if (body.length < 3) {
                toast('متن دیدگاه بسیار کوتاه است', 'error');
                textarea.focus();
                return;
            }
            var btn = commentForm.querySelector('.comment-submit-btn');
            if (btn) btn.disabled = true;
            ajax(commentStoreUrl, { content: body }, function (res) {
                if (res.success) {
                    toast(res.message || 'دیدگاه شما ثبت شد و پس از تایید نمایش داده می‌شود', 'success');
                    textarea.value = '';
                    // نمایش فوری دیدگاه در انتظار تایید بالای لیست
                    var list = document.querySelector('[data-saniar-comments-list]');
                    if (list && res.comment) {
                        var pending = document.createElement('div');
                        pending.className = 'comment-card pending-comment';
                        pending.innerHTML =
                            '<div class="d-flex gap-2">' +
                            '<div class="flex-grow-1">' +
                            '<div class="comment-head d-flex align-items-center gap-2 flex-wrap">' +
                            '<span class="fw-bold font-14">' + (res.comment.user ? res.comment.user.full_name : 'شما') + '</span>' +
                            '<span class="badge bg-warning text-dark font-11"><i class="bi bi-clock"></i> در انتظار تایید</span>' +
                            '</div>' +
                            '<p class="comment-body font-14 mb-0 mt-1">' + res.comment.body + '</p>' +
                            '</div></div>';
                        list.prepend(pending);
                    }
                }
                if (btn) btn.disabled = false;
            }, function () {
                if (btn) btn.disabled = false;
            });
        });
    }

    /* ---------- ۳) پاسخ‌ها + لایک دیدگاه‌ها (delegated — HTML عوض می‌شود) ---------- */
    var commentsSection = document.getElementById('article-comments') || wrap;

    commentsSection.addEventListener('submit', function (e) {
        var form = e.target.closest('.reply-form');
        if (!form) return;
        e.preventDefault();
        var textarea = form.querySelector('textarea[name="content"]');
        var body = (textarea.value || '').trim();
        if (body.length < 3) {
            toast('متن پاسخ بسیار کوتاه است', 'error');
            textarea.focus();
            return;
        }
        ajax(commentReplyUrl, { comment_id: form.dataset.parentId, content: body }, function (res) {
            if (res.success) {
                toast(res.message || 'پاسخ شما ثبت شد و پس از تایید نمایش داده می‌شود', 'success');
                textarea.value = '';
                var collapseEl = document.getElementById('reply-form-' + form.dataset.parentId);
                if (collapseEl && window.bootstrap) {
                    window.bootstrap.Collapse.getOrCreateInstance(collapseEl).hide();
                }
            }
        });
    });

    commentsSection.addEventListener('click', function (e) {
        var likeBtnC = e.target.closest('.comment-like-btn');
        if (!likeBtnC) return;
        var commentId = likeBtnC.dataset.commentId;
        var type = likeBtnC.dataset.type;
        // آدرس لایک با ساختار روت: /blog/articles/comments/{comment}/like
        var url = (commentsListUrl || '').replace(/\/\d+$/, '') + '/' + commentId + '/like';

        $.ajax({
            url: url,
            type: 'POST',
            data: { type: type, _token: CSRF ? CSRF.content : '' },
            headers: function (xhr) {
                if (CSRF) xhr.setRequestHeader('X-CSRF-TOKEN', CSRF.content);
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            },
            success: function (res) {
                if (!res.success) return;
                // حالت‌ها: فعال→غیرفعال، غیرفعال→فعال (و غیرفعال‌سازی ضدّ نوع دیگر)
                var card = likeBtnC.closest('.comment-card') || document;
                card.querySelectorAll('.comment-like-btn[data-comment-id="' + commentId + '"]').forEach(function (b) {
                    var isActive = b === likeBtnC && res.liked;
                    b.classList.toggle('active', isActive);
                    if (b.dataset.type === 'like') b.classList.toggle('text-success', isActive);
                    if (b.dataset.type === 'dislike') b.classList.toggle('text-danger', isActive);
                    var count = b.querySelector('.count');
                    if (count) {
                        count.textContent = new Intl.NumberFormat('fa-IR').format(
                            b.dataset.type === 'like' ? res.likes_count : res.dislikes_count
                        );
                    }
                });
            },
            error: function (xhr) {
                if (xhr.statusText !== 'abort') toast('خطا در ثبت امتیاز', 'error');
            }
        });
    });

    /* ---------- ۴) صفحه‌بندی AJAX نظرات ---------- */
    var commentsPagination = document.getElementById('comments-pagination-container');
    if (commentsPagination && commentsListUrl) {
        commentsPagination.addEventListener('click', function (e) {
            var link = e.target.closest('a');
            if (!link) return;
            e.preventDefault();
            var page = new URL(link.href, window.location.origin).searchParams.get('page') || '1';

            $.ajax({
                url: commentsListUrl,
                type: 'GET',
                data: { page: page },
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                success: function (data) {
                    var listBox = document.getElementById('comments-list-container');
                    if (listBox && data.data) listBox.innerHTML = data.data;
                    commentsPagination.innerHTML = data.pagination || '';
                    var commentsTop = document.getElementById('article-comments');
                    if (commentsTop) {
                        window.scrollTo({ top: commentsTop.getBoundingClientRect().top + window.scrollY - 120, behavior: 'smooth' });
                    }
                },
                error: function () {
                    toast('خطا در دریافت دیدگاه‌ها', 'error');
                }
            });
        });
    }

    /* ---------- ۵) کپی لینک اشتراک‌گذاری ---------- */
    wrap.addEventListener('click', function (e) {
        var copyBtn = e.target.closest('[data-copy-url]');
        if (!copyBtn) return;
        var url = copyBtn.dataset.copyUrl;
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(url).then(function () {
                toast('لینک مقاله کپی شد', 'success');
            }, function () {
                toast('کپی لینک ممکن نشد', 'error');
            });
        }
    });
})();
