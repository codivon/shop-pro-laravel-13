'use strict';
// Class definition

var datatable;

var product_datatable = (function () {
    // Private functions

    var options = {
        // datasource definition
        data: {
            type: 'remote',
            source: {
                read: {
                    url: $('#products_datatable').data('action'),
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr(
                            'content'
                        )
                    },
                    map: function (raw) {
                        // sample data mapping
                        var dataSet = raw;
                        if (typeof raw.data !== 'undefined') {
                            dataSet = raw.data;
                        }

                        $('.c-ui-paginator__total b').html(dataSet.length)
                        return dataSet;
                    },
                    params: {
                        query: $('#filter-products-form').serializeJSON()
                    }
                }
            },
            pageSize: 10,
            serverPaging: true,
            serverFiltering: true,
            serverSorting: true
        },

        layout: {
            scroll: true
        },

        rows: {
            autoHide: false
        },

        // columns definition
        columns: [
            {
                field: 'row',
                sortable: false,
                title: 'ردیف',
                width: 35,
                template: function (row,index) {
                    return index+1;
                }
            },
            {
                field: 'image',
                sortable: false,
                title: 'تصویر',
                width: 120,
                textAlign: 'center',
                template: function (row) {

                    return (
                        '<a href="'+row.links.front+'" target="_blank"><img class="post-thumb w-100" src="' +
                        row.image +
                        '" alt="' +
                        row.title +
                        '"></a>'
                    );
                }
            },
            {
                field: 'title',
                width: 300,
                title: '<span class="text-left">عنوان محصول</span>',
                template: function (row) {
                    return '<a title="مشاهده" href="'+row.links.front +'" target="_blank" style="color: #4a90e2">'+row.title+'</a><div class="uk-flex"><span class="c-mega-campaigns-join-list__container-table-dkpc c-ui--fit c-ui--nowrap">'+row.id+'</span></div>';
                }
            },
            {
                field: 'category',
                sortable: 'desc',
                width: 105,
                title: 'گروه',
                template: function (row) {
                    return '<span class="ltr">' + row.category.title + '</span>';
                }
            },
            {
                field: 'brand',
                sortable: 'desc',
                width: 105,
                title: 'برند کالا',
                template: function (row) {
                    if (row.brand){
                        return '<span class="ltr">' + row.brand.name + '</span>';
                    }else {
                        return '<span class="ltr">بدون برند</span>';
                    }

                }
            },
            {
                field: 'status',
                sortable: 'desc',
                width: 105,
                title: 'وضعيت',
                template: function (row) {
                    if (row.status=="Accept"){
                        return '<div class="c-wallet__body-card-status-no-circle c-wallet__body-card-status-no-circle--active uk-text-nowrap">تایید شده</div>';
                    }else if (row.status=="Waiting"){
                        return '<div class="c-wallet__body-card-status-no-circle c-wallet__body-card-status-no-circle--waiting uk-text-nowrap">در انتضار تایید</div>';
                    }else if (row.status=="Reject"){
                        return '<div class="c-wallet__body-card-status-no-circle c-wallet__body-card-status-no-circle--inactive uk-text-nowrap">در انتضار تایید</div>';
                    }
                }
            },
            {
                field: 'variant_count_seller',
                sortable: 'desc',
                width: 60,
                textAlign: 'center',
                title: 'تعداد تنوع',
                template: function (row) {
                    return row.variant_count_seller
                }
            },

            {
                field: 'actions',
                title: '',
                textAlign: 'center',
                sortable: false,
                width: 113,
                overflow: 'visible',
                autoHide: false,
                template: function (row) {
                    if (row.links.edit_seller){
                        return (
                            '<a href="' + row.variant + '" class="btn waves-effect waves-light c-ui-btn--add-similar btn-outline-primary display-inline-block">افزودن تنوع</a><a href="' + row.links.edit_seller + '" class="btn waves-effect waves-light c-ui-btn--add-similar display-inline-block mt-1">ویرایش</a>'
                        );
                    }else {
                        return (
                            '<a href="' + row.variant + '" class="btn waves-effect waves-light c-ui-btn--add-similar btn-outline-primary display-inline-block">افزودن تنوع</a>'
                        );
                    }



                }
            },


        ],
    };


    var initDatatable = function () {
        // enable extension
        options.extensions = {
            // boolean or object (extension options)
            checkbox: true
        };

        datatable = $('#products_datatable').KTDatatable(options);

        $('#filter-products-form .datatable-filter').on('change', function () {
            formDataToUrl('filter-products-form');
            datatable.setDataSourceQuery(
                $('#filter-products-form').serializeJSON()
            );
            datatable.reload();
        });

        datatable.on('datatable-on-click-checkbox', function (e) {
            var ids = datatable.checkbox().getSelectedId();
            var count = ids.length;

            $('#datatable-selected-rows').html(count);

            if (count > 0) {
                $('.datatable-actions').collapse('show');
            } else {
                $('.datatable-actions').collapse('hide');
            }
        });

        datatable.on('datatable-on-reloaded', function (e) {
            $('.datatable-actions').collapse('hide');
        });
    };

    return {
        // public functions
        init: function () {
            initDatatable();
        }
    };
})();

jQuery(document).ready(function () {
    product_datatable.init();
});

$(document).on('click', '.btn-delete', function () {
    $('#product-delete-form').attr('action', $(this).data('action'));
});

