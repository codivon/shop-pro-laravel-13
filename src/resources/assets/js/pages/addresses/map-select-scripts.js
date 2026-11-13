

var mapIrApiKey = $('input[name=map_api]').val();
var icon_location = BASE_URL + '/themes/SaniarTheme/img/pin-location.svg'
$('.openMap').click(function () {

    $('#add-edit-address-modal').on('show.bs.modal', function () {
        block('#add-edit-address-modal .modal-body');
        $('#add-edit-address-modal #center-marker').addClass('d-none');
        setTimeout(function () {
            //create map and layers
            $('#map-element').empty();
            var app = [];


            app = new Mapp({
                element: '#map-element',
                presets: {
                    latlng: {
                        lat: 35.73249,
                        lng: 51.42268
                    },
                    zoom: 10
                },
                apiKey: mapIrApiKey,
            });




            $('#map-element .mapp-anchor.bottom.position-middle.reverse.item-set.vertical').html(' ');
            //app.addVectorLayers();


            var drawnMarker = new L.LayerGroup();

            app.addLayers();

            //search object
            const search = {
                params: {
                    text: null
                },
                search: function (options, calback) {
                    if (options.text === null || options.text === '') {
                        return null;
                    }
                    //prepare data
                    const data = {};
                    for (let key in options) {
                        if (options[key] !== null && options[key] !== '') {
                            data[key] = options[key];
                        }
                    }
                    calback(null); ///show results
                    $.ajax({
                        url: `https://map.ir/search/v2/`,
                        data: JSON.stringify(data),
                        method: 'POST',
                        beforeSend: function (request) {
                            request.setRequestHeader('x-api-key', mapIrApiKey);
                            request.setRequestHeader('content-type', 'application/json');
                        },
                        success: function (data, status) {
                            calback(data); ///show results
                        },
                        error: function (error) {
                            calback({'odata.count': 0, value: []}); /// show results
                        }
                    });
                },
                setParams: function (key, value, onUpdate, calback) {
                    this.params[key] = value;
                    if (onUpdate) {
                        this.search(this.params, calback);
                    }
                }
            };

            function showResults(results) {
                if (results === null) {
                    $('.search-results').text('در حال جستجو...');
                    $('.search-results').show();
                } else {
                    let count = results['odata.count'];
                    if (count > 0) {
                        $('.clear-seach').show();
                        let html = '';
                        results['value'].forEach(function (item, index) {
                            html += `<div data-title="${item.title}" data-address="${
                                item.address
                            }" data-lat="${item.geom.coordinates[1]}" data-lon="${
                                item.geom.coordinates[0]
                            }" class="search-result-item">`;
                            html += `<p class="search-result-item-title"><img src="https://map.ir/css/images/marker-default-white.svg"/>${
                                item.title
                            }</p>`;
                            html += `<p class="search-result-item-address">${item.address}</p>`;
                            html += `</div>`;
                        });
                        //show results
                        $('.search-results').html(html);
                        $('.search-result-item').on('click', function (e) {
                            let lat = e.currentTarget.getAttribute('data-lat');
                            let lng = e.currentTarget.getAttribute('data-lon');
                            let title = e.currentTarget.getAttribute('data-title');
                            let address = e.currentTarget.getAttribute('data-address');
                            app.addMarker({
                                name: 'basic-marker',
                                latlng: {
                                    lat,
                                    lng
                                },
                                popup: {
                                    title: {
                                        html: title
                                    },
                                    description: {
                                        html: address
                                    },
                                    open: true
                                }
                            });
                            app.map.flyTo({
                                lat,
                                lng
                            });

                            $('.search-results').hide();
                        });
                        $('.search-results').show();

                    } else {
                        $('.clear-seach').show();
                        $('.search-results').html('<p>نتیجه ای یافت نشد</p>');
                    }
                }
            }

            //clear search
            $('.clear-seach').click(function () {
                search.params = {
                    text: null
                };

                $('.search-results').html('');
                $('.search-results').hide();
                $('.clear-seach').hide();
                $('input#search').val('');
                $('.leaflet-container').css('cursor', '');

                if (app.groups.features !== undefined) {
                    app.removeMarkers({
                        group: app.groups.features.markers
                    });
                }
                drawnMarker.clearLayers();
            });

            //text change event handling
            $('#search').on('keyup paste', function (e) {
                let text = $('input#search').val();
                if (text.length > 1) {
                    search.setParams('text', text, true, showResults);
                }

            });

            let marker = app.addMarker({
                name: 'marker',
                latlng: {
                    lat: 35.73249,
                    lng: 51.42268
                },
                popup: false,
                pan: false,
                draggable: true,
                history: false,
            });
            app.removeMarkers({
                group: app.groups.features.markers,
            });

            var centerMarker = $('#center-marker');

            app.map.on('move', function (e) {
                $('#add-edit-address-modal textarea#address').val('');
                $('#next-add-address-btn').addClass('disabled');
                $('#next-add-address-btn').attr('disabled', true);
                $('.add-address-success').remove();
                $('.add-address-unsuccess').remove();
                $('#add-edit-address-modal .modal-footer').prepend('<span class="add-address-unsuccess">آدرس اضافه نشد!</span>');
            });

            //get address with dragend
            app.map.on('dragend', function () {
                var e = app.map.getCenter();

                $.ajax({
                    url: `https://map.ir/reverse/?lat=${e.lat}&lon=${e.lng}`,
                    method: 'GET',
                    beforeSend: function (request) {
                        request.setRequestHeader('x-api-key', mapIrApiKey);
                        request.setRequestHeader('content-type', 'application/json');
                    },
                    success: function (data, status) {
                        var lat = e.lat;
                        var lng = e.lng;
                        var newLatLng = new L.LatLng(lat, lng);
                        marker.setLatLng(newLatLng);

                        $('#add-edit-address-modal textarea#address').val('');
                        $('#add-edit-address-modal textarea#address').val(data.address);
                        $('#add-edit-address-modal input#lat').val(lat);
                        $('#add-edit-address-modal input#lng').val(lng);
                        $('.add-address-unsuccess').remove();
                        $('.add-address-success').remove();
                        $('#add-edit-address-modal .modal-footer').prepend('<span class="add-address-success">آدرس اضافه شد!</span>');
                        $('#next-add-address-btn').removeClass('disabled');
                        $('#next-add-address-btn').removeAttr('disabled');

                    },
                    error: function (error) {
                        console.log(error);
                    }
                });
            });

            unblock('#add-edit-address-modal .modal-body');
            $('#add-edit-address-modal #center-marker').removeClass('d-none');
        }, 1000);
    });
    $('#add-edit-address-modal').on('hidden.bs.modal', function () {

        $('#add-update-address-form').attr('action', '');
        $('#map-element').html(' ')
        $('#add-edit-address-modal textarea#address').val('');
        $('#next-add-address-btn').addClass('disabled');
        $('#next-add-address-btn').attr('disabled', true);
        $('.add-address-success').remove();
        $('.add-address-unsuccess').remove();
        $('#add-edit-address-modal #showMap').removeClass('d-none');
        $('#add-edit-address-modal #more-information').addClass('d-none');
    })
})

