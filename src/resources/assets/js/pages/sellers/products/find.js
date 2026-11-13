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
                field: 'image',
                sortable: false,
                title: 'تصویر',
                width: 55,
                template: function (row) {

                    return (
                        '<a href="'+row.links.front+'" target="_blank"><img class="post-thumb" src="' +
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
                    return '<a title="مشاهده" href="'+row.links.front +'" target="_blank" style="color: #4a90e2">'+row.title+'</a>';
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
                field: 'variant_count',
                sortable: 'desc',
                width: 60,
                title: 'تعداد تنوع',
                textAlign: 'center',
                template: function (row) {
                    return row.variant_count;
                }
            },
            {
                field: 'price',
                sortable: 'desc',
                width: 100,
                title: 'کمترین قیمت ',
                template: function (row) {
                    if (row.addableToCart){
                        return '<span class="ltr color-red">' + row.price_discount + '</span><br><span>تومان</span>';
                    }else {
                        return '<span>ناموجود</span>';
                    }
                }
            },

            {
                field: 'actions',
                title: '',
                textAlign: 'center',
                sortable: false,
                width: 132,
                overflow: 'visible',
                autoHide: false,
                template: function (row) {
                    if (row.seller){
                        return (
                            '<div class="search-results__success-label js-success-label-btn">فروشنده هستید</div>'
                        );
                    }else {
                        return (
                            '<a href="' + row.variant + '" class="btn waves-effect waves-light c-ui-btn--add-similar">شما هم بفروشید</a>'
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

