
$('input[name=vat_free]').click(function(){
    var item=this;
    if(item.checked){
        var vat_free=$(item).val()
        if (vat_free=='1'){
            $('#vat_free_no').addClass('d-none');
            $('#vat_free_yes').removeClass('d-none');
        }else if (vat_free=='2'){
            $('#vat_free_yes').addClass('d-none');
            $('#vat_free_no').removeClass('d-none');
        }
    }

});

$(document).ready(function() {
    FilePond.registerPlugin(
        FilePondPluginImageResize,
        FilePondPluginImageTransform
    );
    // Register the plugin with FilePond
    FilePond.registerPlugin(FilePondPluginImagePreview);
    var url= $('#seller-register-documents').attr('action');
    var delete_url= $('#seller-register-documents').attr('deta-action');
    // Get a reference to the file input element
    const vat_image = document.querySelector('.upload__origin_vat_image input');
    const card_image = document.querySelector('.upload__origin_card_image input');
    const card_image_back= document.querySelector('.upload__origin_card_image_back input');

    // Create the FilePond instance

    var pond_vat_image = FilePond.create(vat_image, {
        imageResizeTargetWidth: 256,

        // add onpreparefile callback
        onpreparefile: (fileItem, output) => {
            // create a new image object
            const img = new Image();

            // set the image source to the output of the Image Transform plugin
            img.src = URL.createObjectURL(output);

            // add it to the DOM so we can see the result
            $('input[name=vat_image]').val(img);
        }

    });
    pond_vat_image.setOptions({
        server: {
            url: url+'?imageFor=vat_image',
            revert: '',
            headers:{
                'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')
            }
        }
    });

    var pond_card_image = FilePond.create(card_image, {
        imageResizeTargetWidth: 256,


        // add onpreparefile callback
        onpreparefile: (fileItem, output) => {
            // create a new image object
            const img = new Image();

            // set the image source to the output of the Image Transform plugin
            img.src = URL.createObjectURL(output);
            var pondFiles = pond_card_image.getFiles();
            // add it to the DOM so we can see the result
            $('input[name=card_image]').val(output);

        },


    });
    pond_card_image.setOptions({
        server: {
            url: url+'?imageFor=card_image',
            revert: '',
            headers:{
                'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')
            }
        }
    });

    var pond_card_image_back = FilePond.create(card_image_back, {
        imageResizeTargetWidth: 256,

        // add onpreparefile callback
        onpreparefile: (fileItem, output) => {
            // create a new image object
            const img = new Image();

            // set the image source to the output of the Image Transform plugin
            img.src = URL.createObjectURL(output);

            // add it to the DOM so we can see the result
            $('input[name=card_image_back]').val(img);
        }

    });
    pond_card_image_back.setOptions({
        server: {
            url: url+'?imageFor=card_image_back',
            revert: '',
            headers:{
                'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')
            }
        }
    });

    setTimeout(function () {
        $('.upload__origin_vat_image .filepond--root .filepond--drop-label label').html('بارگذاری تصویر');
        $('.upload__origin_card_image .filepond--root .filepond--drop-label label').html('بارگذاری تصویر روی کارت ملی');
        $('.upload__origin_card_image_back .filepond--root .filepond--drop-label label').html('بارگذاری تصویر پشت کارت ملی');
    }, 100);


});


// validate form with jquery validation plugin
jQuery('#seller-register-documents').validate({
    rules: {
        vat_image: {
            required: true,
        },
        card_image: {
            required: true,
        },
        card_image_back: {
            required: true
        },
    },
    messages: {
        vat_image: "لطفا تصویر گواهی ارزش افزوده را وارد نمایید",
        card_image: "انتخاب تصویر کارت ملی اجباری است",
        card_image_back: "انتخاب تصویر کارت ملی اجباری است",
    }
});


$('#seller-register-documents').submit(function (e) {
    e.preventDefault();
    if ($(this).valid() && !$(this).data('disabled')) {

        var formData = new FormData(this);

        $.ajax({
            url: $(this).attr('data-check'),
            type: 'POST',
            data: formData,
            success: function (data) {
                if (data.status=='error'){
                    showCustomToast(data.message,'error');
                }else if(data.status=="success"){
                    window.location.href = data.redirect;
                }

            },
            beforeSend: function (xhr) {
                block('.new-login_seller_main');
                xhr.setRequestHeader(
                    'X-CSRF-TOKEN',
                    $('meta[name="csrf-token"]').attr('content')
                );
            },
            complete: function () {
                unblock('.new-login_seller_main');
            },
            cache: false,
            contentType: false,
            processData: false
        });
    }
});
