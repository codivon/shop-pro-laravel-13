$(document).ready(function() {
    $(document).on('click', '.show-history', function(e) {
        e.preventDefault();

        var $link = $(this);
        var url = $link.data('action');
        var $modal = $('#history-show-modal');
        var $modalBody = $modal.find('.modal-body');

        // نمایش وضعیت در حال بارگذاری
        $modalBody.html('<div class="text-center"><i class="mdi mdi-loading mdi-spin"></i> در حال بارگذاری...</div>');

        // ارسال درخواست GET
        $.ajax({
            url: url,
            type: 'GET',
            success: function(response) {
                // قرار دادن محتوای دریافتی در بدنه مودال
                $modalBody.html(response);
                // نمایش مودال
                $modal.modal('show');
            },
            error: function() {
                $modalBody.html('<div class="alert alert-danger">خطا در بارگذاری اطلاعات.</div>');
            }
        });
    });
});
