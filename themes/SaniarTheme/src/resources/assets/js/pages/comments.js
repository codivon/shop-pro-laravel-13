$('#comments-form').submit(function(e) {
    e.preventDefault();
    var form = $(this);
    var btn = $('.comment-submit-btn');

    var formData = new FormData(this);

    $.ajax({
        url: form.attr('action'),
        type: 'POST',
        data: formData,
        success: function(data) {
            Swal.fire({
                text: 'نظر شما با موفقیت ثبت شد و پس از تایید نمایش داده خواهد شد.',
                type: 'success',
                showCancelButton: false,
                confirmButtonText: 'باشه',
            });

            form.trigger('reset');

            $('.comment-replay-to').hide();
        },

        beforeSend: function(xhr) {
            xhr.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr('content'));
            block(btn);
        },
        complete: function() {
            unblock(btn);
        },

        cache: false,
        contentType: false,
        processData: false
    });

});

$('.comment-replay').click(function(e) {
    e.preventDefault();
    var Auth=$('meta[name="Auth"]').attr('content');
    if (Auth=='No'){
        showCustomToast('لطفا وارد حساب کاربری خود شوید','error');
    }else{
        var a = $(this);
        var userName=$(a).parent('.article').find('.question-body').text();
        $('.comment-replay-to').find('span').html("<b>در پاسخ به سوال :</b> " + userName);
        $('#comments-form input[name="comment_id"]').val(a.data('id'));
        $('.comment-replay-to').show();

        $('html, body').animate({
            scrollTop: $(".box-tabs-main").offset().top
        }, 700);

        $('#comments-form textarea').focus();
    }

});

$('.comment-replay-to a').click(function(e) {
    e.preventDefault();
    $('#comments-form input[name="comment_id"]').val('');
    $('.comment-replay-to').hide();
})
$(document).ready(function () {

    $('.js-faq-container .filter-items.nav.nav-tabs li.nav-item').click(function () {
        var GetSortComment= $(this).attr('data-id');
        $('input[name=sortQuestions]').val(GetSortComment);
        fetch_question_products(1);
    })

    $('#question-answer .paginationPager ul.pagination li.page-item.next-item .page-link').append('<i class="fa fa-angle-double-left"></i>')
    $('#question-answer .paginationPager ul.pagination li.page-item.next-item:last').prepend('<div class="pager-items-partition"></div>')

    $('#question-answer .paginationPager ul.pagination li.page-item.prev-item .page-link').append('<i class="fa fa-angle-double-right"></i>')
    $('#question-answer .paginationPager ul.pagination li.page-item.prev-item:last').append('<div class="pager-items-partition"></div>')


    $('#question-answer .paginationPager a').on('click', function(e) {
        e.preventDefault();
        var pageNumber=$(this).attr('href').split('page=')[1];
        //$(this).off("click").attr('href', "javascript: void(0);");
        fetch_question_products(pageNumber);
    });

    function fetch_question_products(pageNumber)
    {
        block('#myTabContent');
        var product_id=$('.footer-product-id').attr('data-id');
        var sortComment=$('input[name=sortQuestions]').val();
        var url=$('input[name=UrlGetQuestions]').val()+'?page='+pageNumber+'&product_id='+product_id+'&sortComment='+sortComment;
        $.ajax({
            url: url,
            success: function (data) {
                $('#question-answer').html(data);
            },
            complete: function () {
                unblock('#myTabContent');
                $('#question-answer .paginationPager ul.pagination li.page-item.next-item .page-link').append('<i class="fa fa-angle-double-left"></i>')
                $('#question-answer .paginationPager ul.pagination li.page-item.next-item:last').prepend('<div class="pager-items-partition"></div>')
                $('#question-answer .paginationPager ul.pagination li.page-item.prev-item .page-link').append('<i class="fa fa-angle-double-right"></i>')
                $('#question-answer .paginationPager ul.pagination li.page-item.prev-item:last').append('<div class="pager-items-partition"></div>')


                $('#question-answer .paginationPager a').on('click', function(e) {
                    e.preventDefault();
                    var pageNumber=$(this).attr('href').split('page=')[1];
                    //$(this).off("click").attr('href', "javascript: void(0);");
                    fetch_question_products(pageNumber);
                });

                $('.comments-likes button').on('click', function (e) {
                    let btn = $(this);

                    $.ajax({
                        url: $(this).data('action'),
                        type: 'POST',
                        success: function (data) {
                            btn.closest('.comments-likes')
                                .find('.likes-count')
                                .attr('data-counter',data.review.likes_count);

                            btn.closest('.comments-likes')
                                .find('.dislikes-count')
                                .attr('data-counter',data.review.dislikes_count);
                        },

                        beforeSend: function (xhr) {
                            block(btn);
                            xhr.setRequestHeader(
                                'X-CSRF-TOKEN',
                                $('meta[name="csrf-token"]').attr('content')
                            );
                        },
                        complete: function () {
                            unblock(btn);
                        }
                    });
                });
                $('.comment-replay').click(function(e) {
                    e.preventDefault();
                    var Auth=$('meta[name="Auth"]').attr('content');
                    if (Auth=='No'){
                        showCustomToast('لطفا وارد حساب کاربری خود شوید','error');
                    }else{
                        var a = $(this);
                        var userName=$(a).parent('.article').find('.question-body').text();
                        $('.comment-replay-to').find('span').html("<b>در پاسخ به سوال :</b> " + userName);
                        $('#comments-form input[name="comment_id"]').val(a.data('id'));
                        $('.comment-replay-to').show();

                        $('html, body').animate({
                            scrollTop: $(".box-tabs-main").offset().top
                        }, 700);

                        $('#comments-form textarea').focus();
                    }

                });
            },
            cache: false,
            contentType: false,
            processData: false
        });

    }
});
