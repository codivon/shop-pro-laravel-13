$('.favorite-remove-btn').on('click', function() {
    $('#favorite-remove-form').attr('action', $(this).data('action'));
});
$('#add-address-modal').click(function () {
    $('#add-edit-address-modal').on('show.bs.modal', function () {
        $('#add-update-address-form').attr('action', $('#add-address-modal').data('updateurl'));
        $('#add-update-address-form input[name=_method]').remove();
    });
});

$('#next-add-address-btn').click(function () {
    var address=$('#add-edit-address-modal textarea#address').val();
    if (address!=""){
        $('#add-edit-address-modal #showMap').addClass('d-none');
        $('#add-edit-address-modal #more-information').removeClass('d-none');
    }

});

$('#back-to-map').click(function () {
    $('#add-edit-address-modal #showMap').removeClass('d-none');
    $('#add-edit-address-modal #more-information').addClass('d-none');
    $('#add-edit-address-modal form').trigger('reset');
    $('#next-add-address-btn').addClass('disabled');
    $('#next-add-address-btn').attr('disabled', true);
    $('.add-address-success').remove();
    $('.add-address-unsuccess').remove();
    $('#add-edit-address-modal .modal-footer').prepend('<span class="add-address-unsuccess">آدرس اضافه نشد!</span>');
})

$('.edit-address-link').click(function () {
    var item=this;

    $('#add-edit-address-modal').on('show.bs.modal', function () {
        var updateurl=$(item).data('updateurl');
        $('#add-update-address-form').attr('action',updateurl);
        $('#add-update-address-form').prepend('<input type="hidden" name="_method" value="put">');
        var url=$(item).data('url');

        $.ajax({
            url: url,
            type: 'GET',
            success: function(value) {
                $('#add-edit-address-modal form').trigger('reset');
                $('#add-edit-address-modal input[name=buildingNumber]').val(value.address.buildingNumber);
                $('#add-edit-address-modal input[name=unit]').val(value.address.unit);
                $('#add-edit-address-modal input[name=postal_code]').val(value.address.postal_code);
                $('#add-edit-address-modal input[name=fullname]').val(value.address.fullname);
                $('#add-edit-address-modal input[name=mobile]').val(value.address.mobile);
                $('#add-edit-address-modal #province').val(value.address.province_id).change();
                $('.custom-select-ui select').niceSelect('update');


                $.get('#province',function () {
                    var thisItem=this;
                    $('#city').empty();
                    $('#city').append('<option value="">انتخاب کنید</option>');
                    $('#city').trigger('change');
                    $('.custom-select-ui select').niceSelect('update');

                    if (!$('#province').val()) {
                        return;
                    }

                    var id = $('#province').val();
                    var selected="";
                    $.ajax({
                        type: 'get',
                        url: '/province/get-cities',
                        data: {id: id},
                        success: function (data) {
                            $(data).each(function () {
                                $('#city').append(
                                    '<option value="' +
                                    $(this)[0].id +
                                    '">' +
                                    $(this)[0].name +
                                    '</option>'
                                );
                            });

                            $('.custom-select-ui select').niceSelect('update');
                            $('#add-edit-address-modal #city').val(value.address.city_id).change();
                        },
                        beforeSend: function () {
                            //
                        }
                    });
                });




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
    })
})


$('.profile-address-content .profile-address-info').click(function () {
    var item=this;
    $.ajax({
        url: $(item).data('action'),
        type: 'GET',
        success: function(data) {
            if (data.active=="success"){
                $('.profile-stats').removeClass('active')
                $(item).parents('.profile-stats').addClass('active');
                Swal.fire({
                    text: 'آدرس به عنوان آدرس پیشفرض انتخاب شد.',
                    type: 'success',
                    showCancelButton: false,
                    confirmButtonText: 'باشه',
                });
            }

        },

        beforeSend: function(xhr) {
            xhr.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr('content'));
            block('.profile-content');
        },
        complete: function() {
            unblock('.profile-content');
        },

        cache: false,
        contentType: false,
        processData: false
    });

})
