(function($) {
    "use strict";

    // Chat user list
    if ($('.chat-application .chat-user-list').length > 0) {
        var chat_user_list = new PerfectScrollbar(".chat-user-list");
    }

    // Chat user profile
    if ($('.chat-application .profile-sidebar-area .scroll-area').length > 0) {
        var chat_user_list = new PerfectScrollbar(".profile-sidebar-area .scroll-area");
    }

    // Chat area
    if ($('.chat-application .user-chats').length > 0) {
        var chat_user = new PerfectScrollbar(".user-chats", {
            wheelPropagation: false
        });
    }

    // User profile right area
    if ($('.chat-application .user-profile-sidebar-area').length > 0) {
        var user_profile = new PerfectScrollbar(".user-profile-sidebar-area");
    }

    // Chat Profile sidebar toggle
    $('.chat-application .sidebar-profile-toggle').on('click', function() {
        $('.chat-profile-sidebar').addClass('show');
        $('.chat-overlay').addClass('show');
    });

    // User Profile sidebar toggle
    $('.chat-application .user-profile-toggle').on('click', function() {
        $('.user-profile-sidebar').addClass('show');
        $('.chat-overlay').addClass('show');
    });

    // autoscroll to bottom of Chat area
    var chatContainer = $(".user-chats");
    $(".chat-users-list-wrapper li").on("click", function() {
        chatContainer.animate({ scrollTop: chatContainer[0].scrollHeight }, 400)
    });

    // Favorite star click
    $(".chat-application .favorite i").on("click", function(e) {
        $(this).parent('.favorite').toggleClass("warning");
        e.stopPropagation();
    });

    // Main menu toggle should hide app menu
    $('.chat-application .menu-toggle').on('click', function(e) {
        $('.app-content .sidebar-left').removeClass('show');
        $('.chat-application .chat-overlay').removeClass('show');
    });

    // Chat sidebar toggle
    if ($(window).width() < 992) {
        $('.chat-application .sidebar-toggle').on('click', function() {
            $('.app-content .sidebar-content').addClass('show');
            $('.chat-application .chat-overlay').addClass('show');
        });
    }

    // For chat sidebar on small screen
    if ($(window).width() > 992) {
        if ($('.chat-application .chat-overlay').hasClass('show')) {
            $('.chat-application .chat-overlay').removeClass('show');
        }
    }

    // Scroll Chat area
    $(".user-chats").scrollTop($(".user-chats > .chats").height());

    // Filter
    $(".chat-application #chat-search").on("keyup", function() {
        var value = $(this).val().toLowerCase();
        if (value != "") {
            $(".chat-user-list .chat-users-list-wrapper li").filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
            });
        } else {
            // If filter box is empty
            $(".chat-user-list .chat-users-list-wrapper li").show();
        }
    });

})(jQuery);
jQuery('#ticket-update-form').validate({
    rules: {
        'message': {
            required: true,
        },
    },
});
$(".user-chats").scrollTop($(".user-chats > .chats").height());
$('#ticket-update-form').submit(function(e) {
    e.preventDefault();

    if ($(this).valid() && !$(this).data('disabled')) {

        var formData = new FormData(this);
        var form = $(this);

        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: formData,
            success: function(data) {
                Swal.fire({
                    text: 'پیام با موفقیت ثبت شد',
                    type: 'success',
                    showCancelButton: false,
                    confirmButtonText: 'باشه',
                }).then(function() {
                    $('#ticket-update-form').data('disabled', true);
                    window.location.href = form.data('redirect');
                });
            },

            beforeSend: function(xhr) {
                block(form);
                xhr.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr('content'));
            },
            complete: function() {
                unblock(form);
            },

            cache: false,
            contentType: false,
            processData: false
        });
    }

});

$(document).ready(function() {
    $(".msg_history").animate({ scrollTop: $('.msg_history').prop("scrollHeight") }, 1000);
});
