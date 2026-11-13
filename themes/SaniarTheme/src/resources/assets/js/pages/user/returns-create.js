/**
 * پیش‌نمایش تصاویر آپلودی فرم درخواست مرجوعی (user/returns/create)
 * انتقال‌یافته از بلید به فایل js (بدون <script> داخل ویو)
 */
(function () {
    'use strict';

    var fileInput = document.getElementById('file-input');
    var previewContainer = document.getElementById('image-previews');
    var uploadArea = document.getElementById('file-upload-area');
    var maxFiles = 5;

    if (fileInput) {
        fileInput.addEventListener('change', function () {
            previewContainer.innerHTML = '';
            var files = Array.from(this.files).slice(0, maxFiles);

            if (files.length > maxFiles) {
                alert('حداکثر ' + maxFiles + ' تصویر می‌توانید آپلود کنید.');
                this.value = '';
                return;
            }

            files.forEach(function (file, index) {
                var reader = new FileReader();
                reader.onload = function (e) {
                    var preview = document.createElement('div');
                    preview.className = 'return-image-preview';
                    preview.innerHTML = '<img src="' + e.target.result + '">' +
                        '<button type="button" class="return-image-preview-remove" data-index="' + index + '"><i class="fas fa-times"></i></button>';
                    previewContainer.appendChild(preview);

                    preview.querySelector('.return-image-preview-remove').addEventListener('click', function () {
                        preview.remove();
                    });
                };
                reader.readAsDataURL(file);
            });
        });
    }

    // Drag and drop
    if (uploadArea) {
        uploadArea.addEventListener('dragover', function (e) {
            e.preventDefault();
            this.style.borderColor = '#6366f1';
            this.style.background = '#eef2ff';
        });
        uploadArea.addEventListener('dragleave', function (e) {
            e.preventDefault();
            this.style.borderColor = '#cbd5e1';
            this.style.background = '#f8fafc';
        });
        uploadArea.addEventListener('drop', function (e) {
            e.preventDefault();
            this.style.borderColor = '#cbd5e1';
            this.style.background = '#f8fafc';
            if (fileInput) {
                fileInput.files = e.dataTransfer.files;
                fileInput.dispatchEvent(new Event('change'));
            }
        });
    }
})();
