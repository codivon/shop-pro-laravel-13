Dropzone.autoDiscover = false;
/* config dropzone uploader for uploading images */
var physicalDropzone = new Dropzone('div#product-images', {
    url: BASE_URL + '/products/image-store',
    addRemoveLinks: true,
    acceptedFiles: 'image/*',

    dictInvalidFileType: 'آپلود فایل با این فرمت ممکن نیست',
    dictRemoveFile: 'حذف',
    dictCancelUpload: 'لغو آپلود',
    dictResponseError: 'خطایی در بارگذاری فایل رخ داده است',

    init: function () {
        this.on('success', function (file, response) {
            file.upload.filename = response.imagename;

            $(file.previewElement).data('name', response.imagename);
            $(file.previewElement).attr('id', response.imagename);
        });
    },

    removedfile: function (file) {
        var name = file.upload.filename;

        if (file.accepted) {
            $.ajax({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                type: 'POST',
                url: BASE_URL + '/products/image-delete',
                data: {filename: name},
                success: function (data) {
                    // console.log("File has been successfully removed!!");
                },
                error: function (e) {
                    // console.log(e);
                }
            });
        }

        var fileRef;
        return (fileRef = file.previewElement) != null
            ? fileRef.parentNode.removeChild(file.previewElement)
            : void 0;
    },

    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});

$('#product-create-form').submit(function (e) {
    e.preventDefault();

    if ($(this).valid() && !$(this).data('disabled')) {
        if (physicalDropzone.getUploadingFiles().length) {
            showCustomToast('لطفا تا اتمام آپلود تصاویر منتظر بمانید','error');
            return;
        }


        var images = $('.dropzone-area').sortable('toArray');

        var formData = new FormData(this);
        formData.append(
            'description',
            CKEDITOR.instances['description'].getData()
        );
        formData.append('images', images);

        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: formData,
            success: function (data) {
                $('#product-create-form').data('disabled', true);
                window.location.href = BASE_URL + '/products';
            },
            beforeSend: function (xhr) {
                block('#main-card');
                xhr.setRequestHeader(
                    'X-CSRF-TOKEN',
                    $('meta[name="csrf-token"]').attr('content')
                );
            },
            complete: function () {
                unblock('#main-card');
            },
            cache: false,
            contentType: false,
            processData: false
        });
    }
});

$('#publish_date_picker').pDatepicker({
    timePicker: {
        enabled: true,
        meridian: {
            enabled: false
        },
        second: {
            enabled: false
        }
    },
    toolbox: {
        // enabled: true,
        calendarSwitch: {
            enabled: false
        }
    },
    initialValue: false,
    altField: '#publish_date',
    altFormat: 'YYYY-MM-DD HH:mm:ss',

    onSelect: function (unixDate) {
        var date = $('#publish_date').val();
        $('#publish_date').val(date.toEnglishDigit());
    }
});
