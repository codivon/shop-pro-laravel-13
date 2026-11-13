$('#filter-notifications-form select').change(function() {
    $('#filter-notifications-form').submit();
});
$('#filter-notifications-form input').change(function() {
    $('#filter-notifications-form').submit();
});


$('li.active').addClass('open').children('ul').show();
$("li.has-sub > a").on('click', function () {
    var item=$(this);
    $(this).removeAttr('href');
    var e = $(this).parent('li');
    if (e.hasClass('open')) {
        e.removeClass('open');
        e.find('li').removeClass('opne');
        e.find('ul').slideUp(200);
    }
    else {
        e.addClass('open');
        e.children('ul').slideDown(200);
        e.siblings('li').children('ul').slideUp(200);
        e.siblings('li').removeClass('open');
        e.siblings('li').find('li').removeClass('open');
        e.siblings('li').find('ul').slideUp(200);

        var read=$(item).data('read');
        if (read=="no"){
            $.ajax({
                url: $(item).data('action'),
                type: 'POST',
                success: function(data) {

                    if (data.status=="success"){
                        $(item).attr('data-read','yes');
                        $('.notifications-count-number').html(data.count);
                    }
                },

                beforeSend: function(xhr) {
                    xhr.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr('content'));
                },
                complete: function() {
                },

                cache: false,
                contentType: false,
                processData: false
            });
        }


    }
});
