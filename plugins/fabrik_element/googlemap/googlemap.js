/**
 * Googlemap Element
 *
 * @copyright: Copyright (C) 2005-2013, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

/** call back method when maps api is loaded*/
function googlemapload() {
    if (typeOf(Fabrik.googleMapRadius) === 'null') {
        var script2 = document.createElement('script'),
            l = document.location,
            path = l.pathname.split('/'),
            index = path.indexOf('index.php');

        // For URLs such as /index.php/elements/form/4/97 - we only want the segment before index.php
        if (index !== -1) {
            path = path.slice(0, index);
        }
        path.shift();
        path = path.join('/');
        script2.type = 'text/javascript';
        //script2.src = l.protocol + '//' + l.host + '/' + path + '/components/com_fabrik/libs/googlemaps/distancewidget.js';
        script2.src = Fabrik.liveSite + '/components/com_fabrik/libs/googlemaps/distancewidget.js';
        document.body.appendChild(script2);
        Fabrik.googleMapRadius = true;
    }
    if (document.body) {
        window.fireEvent('google.map.loaded');
    } else {
        console.log('no body');
    }
}

function googleradiusloaded() {
    if (document.body) {
        window.fireEvent('google.radius.loaded');
    } else {
        console.log('no body');
    }
}


define(['jquery', 'fab/element', 'lib/debounce/jquery.ba-throttle-debounce', 'fab/fabrik'],
    function (jQuery, FbElement, Debounce, Fabrik) {
    window.FbGoogleMap = new Class({
        Extends: FbElement,

        watchGeoCodeDone: false,

        options: {
            'lat'                 : 0,
            'lat_dms'             : 0,
            'lon'                 : 0,
            'lon_dms'             : 0,
            'zoomlevel'           : '13',
            'control'             : '',
            'maptypecontrol'      : false,
            'overviewcontrol'     : false,
            'scalecontrol'        : false,
            'drag'                : false,
            'maptype'             : 'G_NORMAL_MAP',
            'geocode'             : false,
            'latlng'              : false,
            'latlng_dms'          : false,
            'staticmap'           : false,
            'auto_center'         : false,
            'scrollwheel'         : false,
            'streetView'          : false,
            'sensor'              : false,
            'center'              : 0,
            'reverse_geocode'     : false,
            'use_radius'          : false,
            'geocode_on_load'     : false,
            'traffic'             : false,
            'debounceDelay'       : 500,
            'styles'              : [],
            'directionsFrom'      : false,
            'directionsFromLat'   : 0,
            'directionsFromLon'   : 0,
            'reverse_geocode_fields': {},
            'key'                 : false,
            'mapShown'            : true
        },

        loadScript: function () {
            Fabrik.loadGoogleMap(this.options.key, 'googlemapload');
        },

        initialize: function (element, options) {
            this.mapMade = false;
            this.parent(element, options);

            if (!this.options.mapShown) {
                return;
            }

            this.loadFn = function () {
                // experimental support for OSM rendering
                this.mapTypeIds = [];
                for (var type in google.maps.MapTypeId) {
                    this.mapTypeIds.push(google.maps.MapTypeId[type]);
                }
                this.mapTypeIds.push('OSM');

                switch (this.options.maptype) {
                    case 'OSM':
                        this.options.maptype = 'OSM';
                        break;
                    case 'G_SATELLITE_MAP':
                        this.options.maptype = google.maps.MapTypeId.SATELLITE;
                        break;
                    case 'G_HYBRID_MAP':
                        this.options.maptype = google.maps.MapTypeId.HYBRID;
                        break;
                    case 'TERRAIN':
                        this.options.maptype = google.maps.MapTypeId.TERRAIN;
                        break;
                    default:
                    /* falls through */
                    case 'G_NORMAL_MAP':
                        this.options.maptype = google.maps.MapTypeId.ROADMAP;
                        break;
                }
                this.makeMap();

                // @TODO test google object when offline typeOf(google) isnt working
                if (this.options.center === 1 && (this.options.rowid === '' || this.options.rowid === 0)) {
                    if (geo_position_js.init()) {
                        geo_position_js.getCurrentPosition(this.geoCenter.bind(this), this.geoCenterErr.bind(this), {
                            enableHighAccuracy: true
                        });
                    } else {
                        fconsole('Geo location functionality not available');
                    }
                }

            }.bind(this);

            this.radFn = function () {
                this.makeRadius();
            }.bind(this);

            window.addEvent('google.map.loaded', this.loadFn);
            window.addEvent('google.radius.loaded', this.radFn);

            this.loadScript();
        },

        /**
         * Called when form closed in ajax window
         */
        destroy: function () {
            window.removeEvent('google.map.loaded', this.loadFn);
            window.removeEvent('google.radius.loaded', this.radFn);
        },

        getValue: function () {
            if (typeOf(this.field) !== 'null') {
                return this.field.get('value');
            }
            return false;
        },

        makeMap: function () {
            if (this.mapMade === true) {
                return;
            }

            this.mapMade = true;
            var self = this;

            if (typeof(this.map) !== 'undefined' && this.map !== null) {
                return;
            }
            if (typeOf(this.element) === 'null') {
                return;
            }

            if (this.options.geocode || this.options.reverse_geocode) {
                this.geocoder = new google.maps.Geocoder();
            }

            // Need to use this.options.element as if loading from ajax popup win in list view for some reason
            // this.element refers to the first loaded row, which should have been removed from the dom
            this.element = document.id(this.options.element);
            if (typeOf(this.element) === 'null') {
                return;
            }
            this.field = this.element.getElement('input.fabrikinput');

	        /**
             * watchGeoCode() needs to run after all the elements have been added to the form, but
             * the elements.added event may have already fired.  Typically on first load, where we
             * waited for the maps API to load, it will have already fired.  But (say) opening a popup
             * for a second time, API is already loaded, so no delay.  So call it direct, AND from the event,
             * and watchGeoCode() will keep track of whether it can / has run.
             */
            this.watchGeoCode();
            Fabrik.addEvent('fabrik.form.elements.added', function (form) {
                if (form === self.form) {
                    self.watchGeoCode();
                }
            });

            if (this.options.staticmap) {
                var i = this.element.getElement('img');
                var w = i.getStyle('width').toInt();
                var h = i.getStyle('height').toInt();
            }

            if (!this.options.staticmap) {

                var zoomControlStyle = this.options.control === 'GSmallMapControl' ? google.maps.ZoomControlStyle.SMALL : google.maps.ZoomControlStyle.LARGE;
				var vzoomControl = this.options.control !== 'none';

                var mapOpts = {
                    center               : new google.maps.LatLng(this.options.lat, this.options.lon),
                    zoom                 : this.options.zoomlevel.toInt(),
                    mapTypeId            : this.options.maptype,
                    scaleControl         : this.options.scalecontrol,
                    mapTypeControl       : this.options.maptypecontrol,
                    overviewMapControl   : this.options.overviewcontrol,
                    scrollwheel          : this.options.scrollwheel,
                    streetViewControl    : this.options.streetView,
                    zoomControl          : vzoomControl,
                    zoomControlOptions   : {
                        style: zoomControlStyle
                    },
                    mapTypeControlOptions: {
                        mapTypeIds: this.mapTypeIds
                    }
                };
                this.map = new google.maps.Map(document.id(this.element).getElement('.map'), mapOpts);
                this.map.setOptions({'styles': this.options.styles});

                /**
                 * Experimental support for OSM tile rendering, see ...
                 * http://wiki.openstreetmap.org/wiki/Google_Maps_Example
                 */
                if (this.options.maptype === 'OSM') {
                    this.map.mapTypes.set('OSM', new google.maps.ImageMapType({
                        getTileUrl: function (coord, zoom) {
                            // See above example if you need smooth wrapping at 180th meridian
                            return 'http://tile.openstreetmap.org/' + zoom + '/' + coord.x + '/' + coord.y + '.png';
                        },
                        tileSize  : new google.maps.Size(256, 256),
                        name      : 'OpenStreetMap',
                        maxZoom   : 18
                    }));
                }

                if (this.options.traffic) {
                    var trafficLayer = new google.maps.TrafficLayer();
                    trafficLayer.setMap(this.map);
                }

                var point = new google.maps.LatLng(this.options.lat, this.options.lon);
                var opts = {
                    map     : this.map,
                    position: point
                };
                opts.draggable = this.options.drag;

                if (this.options.latlng === true) {
                    this.element.getElement('.lat').addEvent('blur', function (e) {
                        this.updateFromLatLng(e);
                    }.bind(this));
                    this.element.getElement('.lng').addEvent('blur', function (e) {
                        this.updateFromLatLng(e);
                    }.bind(this));
                }

                if (this.options.latlng_dms === true) {
                    this.element.getElement('.latdms').addEvent('blur', function (e) {
                        this.updateFromDMS(e);
                    }.bind(this));
                    this.element.getElement('.lngdms').addEvent('blur', function (e) {
                        this.updateFromDMS(e);
                    }.bind(this));
                }

                this.marker = new google.maps.Marker(opts);

                if (this.options.latlng === true) {
                    this.element.getElement('.lat').value = this.marker.getPosition().lat() + '° N';
                    this.element.getElement('.lng').value = this.marker.getPosition().lng() + '° E';
                }

                if (this.options.latlng_dms === true) {
                    this.element.getElement('.latdms').value = this.latDecToDMS();
                    this.element.getElement('.lngdms').value = this.lngDecToDMS();
                }

                if (this.options.directionsFrom) {
                    this.directionsService = new google.maps.DirectionsService();
                    this.directionsDisplay = new google.maps.DirectionsRenderer();
                    this.directionsDisplay.setMap(this.map);
                    this.directionsFromPoint = new google.maps.LatLng(
                        this.options.directionsFromLat,
                        this.options.directionsFromLon
                    );
                    this.calcRoute();
                }

                google.maps.event.addListener(this.marker, 'dragend', function () {
                    if (this.options.auto_center) {
                        this.map.setCenter(this.marker.getPosition());
                    }
                    this.field.value = this.marker.getPosition() + ':' + this.map.getZoom();
                    if (this.options.latlng === true) {
                        this.element.getElement('.lat').value = this.marker.getPosition().lat() + '° N';
                        this.element.getElement('.lng').value = this.marker.getPosition().lng() + '° E';
                    }
                    if (this.options.latlng_dms === true) {
                        this.element.getElement('.latdms').value = this.latDecToDMS();
                        this.element.getElement('.lngdms').value = this.lngDecToDMS();
                    }
                    if (this.options.latlng_osref === true) {
                        this.element.getElement('.osref').value = this.latLonToOSRef();
                    }
                    if (this.options.reverse_geocode) {
                        this.reverseGeocode();
                    }
                    if (this.options.directionsFrom) {
                        this.calcRoute();
                    }
                    Fabrik.fireEvent('fabrik.map.marker.moved', this);
                }.bind(this));

                //google.maps.event.addListener(map, 'drag', centerMarker);
                google.maps.event.addListener(this.map, 'zoom_changed', function (oldLevel, newLevel) {
                    this.field.value = this.marker.getPosition() + ':' + this.map.getZoom();
                }.bind(this));

                if (this.options.auto_center && this.options.editable) {
                    google.maps.event.addListener(this.map, 'center_changed', function () {
                        this.marker.setPosition(this.map.getCenter());
                        this.field.value = this.marker.getPosition() + ':' + this.map.getZoom();
                        if (this.options.latlng === true) {
                            this.element.getElement('.lat').value = this.marker.getPosition().lat() + '° N';
                            this.element.getElement('.lng').value = this.marker.getPosition().lng() + '° E';
                        }
                        if (this.options.latlng_dms === true) {
                            this.element.getElement('.latdms').value = this.latDecToDMS();
                            this.element.getElement('.lngdms').value = this.lngDecToDMS();
                        }
                    }.bind(this));
                }
            }

            this.watchTab();

            Fabrik.addEvent('fabrik.form.page.change.end', function (form) {
                this.redraw();
            }.bind(this));

            Fabrik.fireEvent('fabrik.map.make.end', this);
        },

        calcRoute: function () {
            var request = {
                origin     : this.directionsFromPoint,
                destination: this.marker.getPosition(),
                travelMode : google.maps.TravelMode.DRIVING
            };
            this.directionsService.route(request, function (result, status) {
                if (status == google.maps.DirectionsStatus.OK) {
                    this.directionsDisplay.setDirections(result);
                }
            }.bind(this));
        },

        radiusUpdatePosition: function () {

        },

        radiusUpdateDistance: function () {
            if (this.options.radius_write_element) {
                var distance = this.distanceWidget.get('distance');
                if (this.options.radius_unit === 'm') {
                    distance = distance / 1.609344;
                }
                $(this.options.radius_write_element).value = parseFloat(distance).toFixed(2);
                //$(this.options.radius_write_element).fireEvent('change', new Event.Mock($(this.options.radius_write_element), 'change'));

            }
        },

        radiusActiveChanged: function () {
            // fired by the radius widget when move / drag operation is complete
            // so let's fire the write element's change event.  Don't do this in updateDistance,
            // as it'll keep firing as they drag.  We don't want to fire 'change' until the changing is finished
            if (this.options.radius_write_element) {
                if (!this.distanceWidget.get('active')) {
                    document.id(this.options.radius_write_element).fireEvent('change', new Event.Mock(document.id(this.options.radius_write_element), 'change'));
                }
            }
        },

        radiusSetDistance: function () {
            if (this.options.radius_read_element) {
                var distance = document.id(this.options.radius_read_element).value;
                if (this.options.radius_unit === 'm') {
                    distance = distance * 1.609344;
                }
                var pos = this.distanceWidget.get('sizer_position');
                this.distanceWidget.set('distance', distance);
                var center = this.distanceWidget.get('center');
                this.distanceWidget.set('center', center);
            }
        },

        makeRadius: function () {
            if (this.options.use_radius) {
                if (this.options.radius_read_element && this.options.repeatCounter > 0) {
                    this.options.radius_read_element = this.options.radius_read_element.replace(/_\d+$/, '_' + this.options.repeatCounter);
                }
                if (this.options.radius_write_element && this.options.repeatCounter > 0) {
                    this.options.radius_write_element = this.options.radius_write_element.replace(/_\d+$/, '_' + this.options.repeatCounter);
                }
                var distance = this.options.radius_default;
                if (!this.options.editable) {
                    distance = this.options.radius_ro_value;
                }
                else {
                    if (this.options.radius_read_element) {
                        distance = document.id(this.options.radius_read_element).value;
                    }
                    else if (this.options.radius_write_element) {
                        distance = document.id(this.options.radius_write_element).value;
                    }
                }
                if (this.options.radius_unit === 'm') {
                    distance = distance * 1.609344;
                }
                this.distanceWidget = new DistanceWidget({
                    map            : this.map,
                    marker         : this.marker,
                    distance       : distance, // Starting distance in km.
                    maxDistance    : 2500, // Twitter has a max distance of 2500km.
                    color          : '#000000',
                    activeColor    : '#5599bb',
                    sizerIcon      : new google.maps.MarkerImage(this.options.radius_resize_off_icon),
                    activeSizerIcon: new google.maps.MarkerImage(this.options.radius_resize_icon)
                });

                google.maps.event.addListener(this.distanceWidget, 'distance_changed', this.radiusUpdateDistance.bind(this));
                google.maps.event.addListener(this.distanceWidget, 'position_changed', this.radiusUpdatePosition.bind(this));
                google.maps.event.addListener(this.distanceWidget, 'active_changed', this.radiusActiveChanged.bind(this));

                if (this.options.radius_fitmap) {
                    this.map.setZoom(20);
                    this.map.fitBounds(this.distanceWidget.get('bounds'));
                }
                this.radiusUpdateDistance();
                this.radiusUpdatePosition();
                this.radiusAddActions();
            }
        },

        radiusAddActions: function () {
            if (this.options.radius_read_element) {
                document.id(this.options.radius_read_element).addEvent('change', this.radiusSetDistance.bind(this));
            }
        },

        updateFromLatLng: function () {
            var lat = this.element.getElement('.lat').get('value').replace('° N', '');
            lat = lat.replace(',', '.').toFloat();
            var lng = this.element.getElement('.lng').get('value').replace('° E', '');
            lng = lng.replace(',', '.').toFloat();
            var pnt = new google.maps.LatLng(lat, lng);
            this.marker.setPosition(pnt);
            this.doSetCenter(pnt, this.map.getZoom(), true);
        },

        updateFromDMS: function () {
            var dms = this.element.getElement('.latdms');
            var latdms = dms.get('value').replace('S', '-');
            latdms = latdms.replace('N', '');
            dms = this.element.getElement('.lngdms');
            var lngdms = dms.get('value').replace('W', '-');
            lngdms = lngdms.replace('E', '');

            var latdms_d_ms = latdms.split('°');
            var latdms_topnt = latdms_d_ms[0];
            var latdms_m_s = latdms_d_ms[1].split('\'');
            var latdms_m = latdms_m_s[0].toFloat() * 60;
            var latdms_ms = (latdms_m + latdms_m_s[1].replace('"', '').toFloat()) / 3600;
            latdms_topnt = Math.abs(latdms_topnt.toFloat()) + latdms_ms.toFloat();
            if (latdms_d_ms[0].toString().indexOf('-') !== -1) {
                latdms_topnt = -latdms_topnt;
            }

            var lngdms_d_ms = lngdms.toString().split('°');
            var lngdms_topnt = lngdms_d_ms[0];
            var lngdms_m_s = lngdms_d_ms[1].split('\'');
            var lngdms_m = Math.abs(lngdms_m_s[0].toFloat()) * 60;
            var lngdms_ms = (lngdms_m + Math.abs(lngdms_m_s[1].replace('"', '').toFloat())) / 3600;
            lngdms_topnt = Math.abs(lngdms_topnt.toFloat()) + lngdms_ms.toFloat();
            if (lngdms_d_ms[0].toString().indexOf('-') !== -1) {
                lngdms_topnt = -lngdms_topnt;
            }

            var pnt = new google.maps.LatLng(latdms_topnt.toFloat(), lngdms_topnt.toFloat());
            this.marker.setPosition(pnt);
            this.doSetCenter(pnt, this.map.getZoom(), true);
        },

        latDecToDMS: function () {
            var latdec = this.marker.getPosition().lat();
            var dmslat_d = parseInt(Math.abs(latdec), 10);
            var dmslat_m_float = 60 * (Math.abs(latdec).toFloat() - dmslat_d.toFloat());
            var dmslat_m = parseInt(dmslat_m_float, 10);
            var dmslat_s_float = 60 * (dmslat_m_float.toFloat() - dmslat_m.toFloat());
            // var dmslat_s = Math.round(dmslat_s_float.toFloat()*100)/100;
            var dmslat_s = dmslat_s_float.toFloat();

            if (dmslat_s === 60) {
                dmslat_m = dmslat_m.toFloat() + 1;
                dmslat_s = 0;
            }
            if (dmslat_m === 60) {
                dmslat_d = dmslat_d.toFloat() + 1;
                dmslat_m = 0;
            }

            var dmslat_dir = 'N';
            if (latdec.toString().indexOf('-') !== -1) {
                dmslat_dir = 'S';
            } else {
                dmslat_dir = 'N';
            }

            return dmslat_dir + dmslat_d + '°' + dmslat_m + '\'' + dmslat_s + '"';

        },

        lngDecToDMS: function () {
            var lngdec = this.marker.getPosition().lng();
            var dmslng_d = parseInt(Math.abs(lngdec), 10);
            var dmslng_m_float = 60 * (Math.abs(lngdec).toFloat() - dmslng_d.toFloat());
            var dmslng_m = parseInt(dmslng_m_float, 10);
            var dmslng_s_float = 60 * (dmslng_m_float.toFloat() - dmslng_m.toFloat());
            // var dmslng_s = Math.round(dmslng_s_float.toFloat()*100)/100;
            var dmslng_s = dmslng_s_float.toFloat();

            if (dmslng_s === 60) {
                dmslng_m.value = dmslng_m.toFloat() + 1;
                dmslng_s.value = 0;
            }
            if (dmslng_m === 60) {
                dmslng_d.value = dmslng_d.toFloat() + 1;
                dmslng_m.value = 0;
            }

            var dmslng_dir = '';
            if (lngdec.toString().indexOf('-') !== -1) {
                dmslng_dir = 'W';
            } else {
                dmslng_dir = 'E';
            }

            return dmslng_dir + dmslng_d + '°' + dmslng_m + '\'' + dmslng_s + '"';

        },

        latLonToOSRef: function () {
            var ll2 = new LatLng(this.marker.getPosition().lng(), this.marker.getPosition().lng());
            var OSRef = ll2.toOSRef();
            return OSRef.toSixFigureString();
        },

        geoCode: function (e) {
            var address = '';
            if (this.options.geocode === '2') {
                this.options.geocode_fields.each(function (field) {
                    var f = this.form.formElements.get(field);
                    if (f) {
                        address += f.get('value') + ',';
                    }
                }.bind(this));
                address = address.slice(0, -1);
            } else {
                address = this.element.getElement('.geocode_input').value;
            }
            // Strip HTML
            var d = new Element('div').set('html', address);
            address = d.get('text');
            this.geocoder.geocode({'address': address}, function (results, status) {
                if (status !== google.maps.GeocoderStatus.OK || results.length === 0) {
                    fconsole(address + ' not found!');
                } else {
                    this.marker.setPosition(results[0].geometry.location);
                    this.doSetCenter(results[0].geometry.location, this.map.getZoom(), false);

                    if (this.options.reverse_geocode)
                    {
                        if (this.options.reverse_geocode_fields.formatted_address) {
                            this.form.formElements.get(this.options.reverse_geocode_fields.formatted_address).update(
                                results[0].formatted_address
                            );
                        }
                    }
                }
            }.bind(this));
        },

        watchGeoCode: function () {
            if (!this.options.geocode || !this.options.editable) {
                return;
            }
            if (typeof this.form === 'undefined') {
                return;
            }
            if (this.watchGeoCodeDone) {
                return;
            }
            if (this.options.geocode === '2') {
                if (this.options.geocode_event !== 'button') {
                    this.options.geocode_fields.each(function (field) {
                        var f = document.id(field);
                        if (typeOf(f) !== 'null') {
                            var that = this;
                            var el = this.form.formElements.get(field);
                            // if it's a field element with geocomplete, don't do keyup, wait for element to fire change
                            if (!el.options.geocomplete) {
                                jQuery(f).on('keyup', Debounce(this.options.debounceDelay, function (e) {
                                    that.geoCode(e);
                                }));
                                // Select lists, radios whatnots
                                f.addEvent('change', function (e) {
                                    this.geoCode();
                                }.bind(this));
                            }
                            else {
                                Fabrik.addEvent('fabrik.element.field.geocode', function(el, results) {
                                   //fconsole('fired: ' + el.element.id);
                                    this.geoCode();
                                }.bind(this));
                            }
                        }
                    }.bind(this));

                    if (this.options.reverse_geocode_fields.formatted_address) {
                        var el = this.form.formElements.get(this.options.reverse_geocode_fields.formatted_address);
                        if (el.options.geocomplete) {
                            Fabrik.addEvent('fabrik.element.field.geocode', function (el, result) {
                                if (el.element.id === this.options.reverse_geocode_fields.formatted_address) {
                                    var pnt = new google.maps.LatLng(
                                        result.geometry.location.lat(),
                                        result.geometry.location.lng()
                                    );
                                    this.marker.setPosition(pnt);
                                    this.doSetCenter(pnt, this.map.getZoom(), false);
                                    this.fillReverseGeocode(result);
                                }
                            }.bind(this));
                        }
                    }
                } else {
                    if (this.options.geocode_event === 'button') {
                        this.element.getElement('.geocode').addEvent('click', function (e) {
                            this.geoCode(e);
                        }.bind(this));
                    }
                }
            }
            if (this.options.geocode === '1' && document.id(this.element).getElement('.geocode_input')) {
                if (this.options.geocode_event === 'button') {
                    this.element.getElement('.geocode').addEvent('click', function (e) {
                        this.geoCode(e);
                    }.bind(this));

                    // Stop enter in geocode field submitting the form.
                    this.element.getElement('.geocode_input').addEvent('keypress', function (e) {
                        if (e.key === 'enter') {
                            e.stop();
                        }
                    }.bind(this));
                } else {
                    /*
                     this.element.getElement('.geocode_input').addEvent('keyup', function (e) {
                     e.stop();
                     this.geoCode(e);
                     }.bind(this));
                     */
                    var that = this;
                    jQuery(this.element.getElement('.geocode_input')).on('keyup',
                        Debounce(this.options.debounceDelay, function (e) {
                            that.geoCode(e);
                        }));
                }
            }
            this.watchGeoCodeDone = true;
        },

        unclonableProperties: function () {
            return ['form', 'marker', 'map', 'maptype'];
        },

        cloned: function (c) {
            var f = [];
            this.options.geocode_fields.each(function (field) {
                var bits = field.split('_');
                var i = bits.getLast();
                if (typeOf(i.toInt()) === 'null') {
                    return bits.join('_');
                }
                bits.splice(bits.length - 1, 1, c);
                f.push(bits.join('_'));
            });
            this.options.geocode_fields = f;
            this.mapMade = false;
            this.map = null;
            this.makeMap();
            this.parent(c);
        },

        update: function (v) {
            v = v.split(':');
            if (v.length < 2) {
                v[1] = this.options.zoomlevel;
            }
            if (!this.map) {
                return;
            }
            var zoom = v[1].toInt();
            this.map.setZoom(zoom);
            v[0] = v[0].replace('(', '');
            v[0] = v[0].replace(')', '');
            var pnts = v[0].split(',');
            if (pnts.length < 2) {
                pnts[0] = this.options.lat;
                pnts[1] = this.options.lon;
            }
            // $$$ hugh - updateFromLatLng blows up if not displayinbg lat lng
            // also, not sure why we would do this, as all we want to do is set map back
            // to default
            // location, not read location from lat lng fields?
            // this.updateFromLatLng(pnts[0], pnts[1]);
            // So instead, lets just set marker to default and recenter
            var pnt = new google.maps.LatLng(pnts[0], pnts[1]);
            this.marker.setPosition(pnt);
            this.doSetCenter(pnt, this.map.getZoom(), true);
        },

        geoCenter: function (p) {
            var pnt = new google.maps.LatLng(p.coords.latitude, p.coords.longitude);
            this.marker.setPosition(pnt);
            this.doSetCenter(pnt, this.map.getZoom(), true);
        },

        geoCenterErr: function (p) {
            fconsole('geo location error=' + p.message);
        },

        /**
         * Redraw the map when inside a tab, and the tab is activated. Triggered from element.watchTab()
         */
        redraw: function () {
            google.maps.event.trigger(this.map, 'resize');
            var center = new google.maps.LatLng(this.options.lat, this.options.lon);
            this.map.setCenter(center);
            this.map.setZoom(this.map.getZoom());
        },

        fillReverseGeocode: function(result) {
            if (this.options.reverse_geocode_fields.formatted_address) {
                this.form.formElements.get(this.options.reverse_geocode_fields.formatted_address).update(result.formatted_address);
            }

            var streetAddress = '';
            var streetNumber = '';
            var streetRoute = '';

            result.address_components.each(function (component) {
                component.types.each(function (type) {
                    if (type === 'street_number') {
                        if (this.options.reverse_geocode_fields.street_number || this.options.reverse_geocode_fields.route) {
                            streetNumber = component.long_name;
                        }
                    }
                    else if (type === 'route') {
                        if (this.options.reverse_geocode_fields.route) {
                            streetRoute = component.long_name;
                        }
                    }
                    else if (type === 'street_address') {
                        if (this.options.reverse_geocode_fields.route) {
                            streetAddress = component.long_name;
                        }
                    }
                    else if (type === 'neighborhood') {
                        if (this.options.reverse_geocode_fields.neighborhood) {
                            this.form.formElements.get(this.options.reverse_geocode_fields.neighborhood).update(component.long_name);
                        }
                    }
                    else if (type === 'locality') {
                        if (this.options.reverse_geocode_fields.locality) {
                            this.form.formElements.get(this.options.reverse_geocode_fields.locality).updateByLabel(component.long_name);
                        }
                    }
                    else if (type === 'administrative_area_level_1') {
                        if (this.options.reverse_geocode_fields.administrative_area_level_1) {
                            this.form.formElements.get(this.options.reverse_geocode_fields.administrative_area_level_1).updateByLabel(component.long_name);
                        }
                    }
                    else if (type === 'postal_code') {
                        if (this.options.reverse_geocode_fields.postal_code) {
                            this.form.formElements.get(this.options.reverse_geocode_fields.postal_code).updateByLabel(component.long_name);
                        }
                    }
                    else if (type === 'country') {
                        if (this.options.reverse_geocode_fields.country) {
                            this.form.formElements.get(this.options.reverse_geocode_fields.country).updateByLabel(component.long_name);
                        }
                    }
                }.bind(this));
            }.bind(this));

            if (this.options.reverse_geocode_fields.street_number) {
                this.form.formElements.get(this.options.reverse_geocode_fields.street_number).update(streetNumber);
                this.form.formElements.get(this.options.reverse_geocode_fields.route).update(streetRoute);            }
            else if (this.options.reverse_geocode_fields.route) {
                /**
                 * Create the street address.  I'm really not sure what the difference between 'route'
                 * and 'street_address' is in Google's component types, so for now just use 'street_address'
                 * as the preference, use 'route' if no 'street_address', and prepend 'street_number'
                 */
                if (streetRoute !== '')
                {
                    if (streetAddress === '')
                    {
                        streetAddress = streetRoute;
                    }
                }

                if (streetNumber !== '')
                {
                    streetAddress = streetNumber + ' ' + streetAddress;
                }

                this.form.formElements.get(this.options.reverse_geocode_fields.route).update(streetAddress);
            }
        },

        reverseGeocode: function () {
            this.geocoder.geocode({'latLng': this.marker.getPosition()}, function (results, status) {
                if (status === google.maps.GeocoderStatus.OK) {
                    if (results[0]) {
                        this.fillReverseGeocode(results[0]);
                    }
                    else {
                        window.alert('No results found');
                    }
                } else {
                    window.alert('Geocoder failed due to: ' + status);
                }
            }.bind(this));
        },

        doSetCenter: function (pnt, zoom, doReverseGeocode) {
            this.map.setCenter(pnt, zoom);
            this.field.value = this.marker.getPosition() + ':' + this.map.getZoom();
            if (this.options.latlng === true) {
                this.element.getElement('.lat').value = pnt.lat() + '° N';
                this.element.getElement('.lng').value = pnt.lng() + '° E';
            }
            if (this.options.latlng_dms === true) {
                this.element.getElement('.latdms').value = this.latDecToDMS();
                this.element.getElement('.lngdms').value = this.lngDecToDMS();
            }
            if (doReverseGeocode && this.options.reverse_geocode) {
                this.reverseGeocode();
            }
        },

        attachedToForm: function () {
            if (this.options.geocode && this.options.geocode_on_load) {
                this.geoCode();
            }
            this.parent();
        }

    });

    return window.FbGoogleMap;
});