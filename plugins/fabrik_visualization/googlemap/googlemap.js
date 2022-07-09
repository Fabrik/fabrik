/**
 * Google Maps Visualization
 *
 * @copyright: Copyright (C) 2005-2013, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var FbGoogleMapViz;
FbGoogleMapViz = new Class({
	Implements: Options,
	options   : {
		'lat'               : 0,
		'lon'               : 0,
		'clustering'        : false,
		'maptypecontrol'    : false,
		'scrollwheel'       : false,
		'overviewcontrol'   : false,
		'scalecontrol'      : false,
		'center'            : 'middle',
		'ajax_refresh'      : false,
		'ajaxDefer'         : false,
		'use_polygon'       : false,
		'polyline'          : [],
		'polylinewidth'     : [],
		'polylinecolour'    : [],
		'polygonopacity'    : [],
		'polygonfillcolour' : [],
		'refresh_rate'      : 10000,
		'use_cookies'       : true,
		'use_groups'        : false,
		'overlays'          : [],
		'overlay_urls'      : [],
		'overlay_labels'    : [],
		'overlay_events'    : [],
		'zoom'              : 1,
		'zoomStyle'         : 0,
		'radius_fill_colors': [],
		'streetView'        : false,
		'traffic'           : false,
		'key'               : false,
		'language'           : '',
		'showLocation'      : false,
		'styles'            : [],
		'heatmap'           : false
	},

	initialize: function (element, options) {
			this.element_map = element;
			this.element = document.id(element);

			this.plugins = [];
			this.clusterMarkerCursor = 0;
			this.clusterMarkers = [];
			this.markers = [];
			this.points = [];
			this.heatmap = false;
			this.weightedLocations = [];
			this.distanceWidgets = [];
			this.icons = [];
			this.setOptions(options);
			this.container = document.id(this.options.container);
			this.subContainer = document.id(this.options.container + '_sub');

			this.updater = new Request.JSON({
				url      : '',
				data     : {
					'option'         : 'com_fabrik',
					'format'         : 'raw',
					'task'           : 'ajax_getMarkers',
					'view'           : 'visualization',
					'controller'     : 'visualization.googlemap',
					'visualizationid': this.options.id
				},
				onSuccess: function (json) {
					this.clearIcons();
					this.clearPolyLines();
					this.options.icons = json;
					this.addIcons();
                    if (this.options.heatmap)
                    {
                        this.heatmap = new google.maps.visualization.HeatmapLayer({
                            data: this.weightedLocations
                        });
                        this.heatmap.setMap(this.map);
                    }
					this.setPolyLines();
					if (this.options.ajax_refresh_center) {
						this.center();
					}
					Fabrik.fireEvent('fabrik.viz.googlemap.ajax.refresh', [this]);
					Fabrik.loader.stop(this.subContainer);
				}.bind(this)
			});

			if (this.options.ajax_refresh) {
				this.timer = this.update.periodical(this.options.refresh_rate, this);
			}

			if (typeof(Slimbox) !== 'undefined') {
				Slimbox.scanPage();
			} else if (typeof(Mediabox) !== 'undefined') {
				Mediabox.scanPage();
			}

			// Clear filter list
			if (typeOf(this.container) !== 'null') {
				var form = this.container.getElement('form[name=filter]');
				var c = this.container.getElement('.clearFilters');
				if (c) {
					c.addEvent('click', function (e) {
						this.container.getElements('.fabrik_filter').each(function (f) {
							f.value = '';
						});
						e.stop();
						form.submit();
					}.bind(this));
				}

				// Watch filter submit
				var submit = this.container.getElements('input.fabrik_filter_submit');
				if (typeOf(submit) !== 'null') {
					submit.addEvent('click', function (e) {
						var res = Fabrik.fireEvent('list.filter', [this]).eventResults;
						if (typeOf(res) === 'null' || res.length === 0 || !res.contains(false)) {
							form.submit();
						} else {
							e.stop();
						}
					});
				}

			}

			Fabrik.loadGoogleMap(
				this.options.key,
				function () {
					this.iniGMap();
				}.bind(this),
				this.options.language
			);

		},

	iniGMap: function () {
			switch (this.options.maptype) {
				case 'G_NORMAL_MAP':
				/* falls through */
				default:
					this
						.options.maptype = google.maps.MapTypeId.ROADMAP;
					break;
				case 'G_SATELLITE_MAP':
					this.options.maptype = google.maps.MapTypeId.SATELLITE;
					break;
				case 'G_HYBRID_MAP':
					this.options.maptype = google.maps.MapTypeId.HYBRID;
					break;
				case 'G_TERRAIN_MAP':
					this.options.maptype = google.maps.MapTypeId.TERRAIN;
					break;
			}

			if (typeOf(this.element_map) === 'null') {
				return;
			}

			this.mapTypeIds = [];

			if (typeOf(this.options.maptypeids) !== 'array') {
				for (var type in google.maps.MapTypeId) {
					this.mapTypeIds.push(google.maps.MapTypeId[type]);
				}
			}
			else
			{
				for (var type in this.options.maptypeids) {
					this.mapTypeIds.push(this.options.maptypeids[type]);
				}
			}

			var mapOpts = {
				center            : new google.maps.LatLng(this.options.lat, this.options.lon),
				zoom              : this.options.zoomlevel.toInt(),
				mapTypeId         : this.options.maptype,
				scaleControl      : this.options.scalecontrol,
				mapTypeControl    : this.options.maptypecontrol,
				overviewMapControl: this.options.overviewcontrol,
				scrollwheel       : this.options.scrollwheel,
				zoomControl       : this.options.zoom,
				streetViewControl : this.options.streetView,
				zoomControlOptions: {style: this.options.zoomStyle},
				mapTypeControlOptions: {
					mapTypeIds: this.mapTypeIds
				}
			};

			this.map = new google.maps.Map(document.id(this.element_map), mapOpts);
			this.map.setOptions({'styles': this.options.styles});

			if (this.options.traffic) {
				var trafficLayer = new google.maps.TrafficLayer();
				trafficLayer.setMap(this.map);
			}

			this.infoWindow = new google.maps.InfoWindow({
				content: ''
			});
			this.bounds = new google.maps.LatLngBounds();

			this.addIcons();
			this.addOverlays();

			if (this.options.heatmap)
			{
                this.heatmap = new google.maps.visualization.HeatmapLayer({
                    data: this.weightedLocations
                });
                this.heatmap.setMap(this.map);
			}

			google.maps.event.addListener(this.map, "click", function (e) {
				this.setCookies(e);
			}.bind(this));

			google.maps.event.addListener(this.map, "moveend", function (e) {
				this.setCookies(e);
			}.bind(this));

			google.maps.event.addListener(this.map, "zoomend", function (e) {
				this.setCookies(e);
			}.bind(this));

			this.infoWindow = new google.maps.InfoWindow({
				content: ''
			});

			if (this.options.use_cookies) {
				// $$$ jazzbass - get previous stored location
				var mymapzoom = Cookie.read("mymapzoom_" + this.options.id);
				var mymaplat = Cookie.read("mymaplat_" + this.options.id);
				var mymaplng = Cookie.read("mymaplng_" + this.options.id);

				if (mymaplat && mymaplat !== '0' && mymapzoom !== '0') {
					this.map.setCenter(new google.maps.LatLng(mymaplat.toFloat(), mymaplng.toFloat()));
					this.map.setZoom(mymapzoom.toInt());
				} else {
					this.center();
				}
			} else {
				this.center();
			}
			this.setPolyLines();


			if (this.options.showLocation) {
				var self = this;
				requirejs(['lib/geolocation-marker/geolocation-marker-min'], function (GeolocationMarker) {
					self.geoMarker = new GeolocationMarker(
						self.map,
						{
							'icon': {
								'url': Fabrik.liveSite + 'media/com_fabrik/images/gpsloc.png',
								'size': new google.maps.Size(34, 34),
								'scaledSize': new google.maps.Size(17, 17),
								'origin': new google.maps.Point(0, 0),
								'anchor': new google.maps.Point(8, 8)
							}
						}
					);
				});
			}

			if (this.options.ajaxDefer)
			{
				this.update();
			}

			Fabrik.fireEvent('fabrik.viz.googlemap.init.end', this);
		},

	setPolyLines: function () {
		this.polylines = [];
		this.polygons = [];
		this.options.polyline.each(function (points, c) {
			var glatlng = [];
			points.each(function (p) {
				glatlng.push(new google.maps.LatLng(p[0], p[1]));
			});
			var width = this.options.polylinewidth[c];
			var colour = this.options.polylinecolour[c];
			var opacity = this.options.polygonopacity[c];
			var fillColor = this.options.polygonfillcolour[c];
			if (!this.options.use_polygon) {
				var polyline = new google.maps.Polyline({path: glatlng, 'strokeColor': colour, 'strokeWeight': width});
				polyline.setMap(this.map);
				this.polylines.push(polyline);
			}
			else {
				var polygon = new google.maps.Polygon({
					paths         : glatlng,
					'strokeColor' : colour,
					'strokeWeight': width,
					strokeOpacity : opacity,
					fillColor     : fillColor
				});
				polygon.setMap(this.map);
				this.polygons.push(polygon);
			}
		}.bind(this));
	},

	clearPolyLines: function () {
		this.polylines.each(function (polyline) {
			polyline.setMap(null);
		});
		this.polygons.each(function (polygon) {
			polygon.setMap(null);
		});
	},

	setCookies: function () {
		if (this.options.use_cookies) {
			Cookie.write("mymapzoom_" + this.options.id, this.map.getZoom(), {duration: 7});
			Cookie.write("mymaplat_" + this.options.id, this.map.getCenter().lat(), {duration: 7});
			Cookie.write("mymaplng_" + this.options.id, this.map.getCenter().lng(), {duration: 7});
		}
	},

	update: function () {
		Fabrik.loader.start(this.subContainer);
		this.updater.send();
	},

	clearIcons: function () {
		this.markers.each(function (marker) {
			marker.setMap(null);
		});
		if (this.options.clustering) {
			this.cluster.clearMarkers();
		}
        if (this.options.heatmap)
        {
            this.weightedLocations = [];
        	if (this.heatmap !== false) {
                this.heatmap.setMap(null);
            }
        }
		this.bounds = new google.maps.LatLngBounds(null);
	},

	noData: function () {
		return this.options.icons.length === 0;
	},

    showRadiusFilterLocation: function() {
        if (typeOf(this.container) !== 'null') {
            var form = this.container.getElement('form[name=filter]');
            var self = this;
            jQuery(form).find('.radius_search').each(function (k, v) {
                var active = jQuery(v).find('input[name="radius_search_active' + k + '[]"]');
                if (active.length > 0 && active[0].value === '1')
                {
                    var lat = jQuery(v).find('input[name="radius_search_geocomplete_lat' + k + '"]');
                    var lon = jQuery(v).find('input[name="radius_search_geocomplete_lon' + k + '"]');
                    if (lat.length > 0 && lon.length > 0) {
                        if (lat[0].value !== '' && lon[0].value !== '') {
                            var point = new google.maps.LatLng(lat[0].value, lon[0].value);
                            var addr = jQuery(v).find('input[name="radius_search_geocomplete_field' + k + '"]');

                            var markerOptions = {
                                position: point,
                                map: self.map
                            };

                            if (addr.length > 0) {
                                markerOptions.title = addr[0].value;
                            }

                            var marker = new google.maps.Marker(markerOptions);
                            self.bounds.extend(new google.maps.LatLng(lat[0].value, lon[0].value));
                        }
                    }
                }
            });
        }
    },

	addIcons: function () {
		if (this.options.heatmap) {
            this.options.icons.each(function (i) {
            	var point = new google.maps.LatLng(i[0], i[1]);
                this.bounds.extend(point);
                this.points.push(point);

                if (i.heatmapWeighting !== 1) {
                	this.weightedLocations.push({
                		location: point,
						weight: i.heatmapWeighting
					});
				}
				else {
                    this.weightedLocations.push(point);
				}
            }.bind(this));
		}
		else {
            this.markers = [];
            this.clusterMarkers = [];
            this.options.icons.each(function (i) {
                this.bounds.extend(new google.maps.LatLng(i[0], i[1]));
                this.markers.push(this.addIcon(i[0], i[1], i[2], i[3], i[4], i[5], i.groupkey, i.title, i.radius, i.c));
            }.bind(this));
            this.showRadiusFilterLocation();
            this.renderGroupedSideBar();
            if (this.options.clustering) {
                // Using MarkerClusterer, http://gmaps-utility-library.googlecode.com/svn/trunk/markerclusterer/1.0/docs/reference.html
                // @TODO - add a way of providing user defined styles
                // The following just duplicates some code in markerclusterer.js which builds their default styles array.
                // Building a replacement here so it uses local images rather than pulling from Google API site.
                var styles = [];
                var sizes = [53, 56, 66, 78, 90];
                var i = 0;
                for (i = 1; i <= 5; ++i) {
                    styles.push({
                        'url': Fabrik.liveSite + "components/com_fabrik/libs/googlemaps/markerclustererplus/images/m" + i + ".png",
                        'height': sizes[i - 1],
                        'width': sizes[i - 1]
                    });
                }
                var zoom = null;
                // for now, overloading icon_increment setting to be maxZoom
                if (this.options.icon_increment !== '') {
                    zoom = parseInt(this.options.icon_increment, 10);
                    if (zoom > 14) {
                        zoom = 14;
                    }
                }
                var size = 60;
                // for now, overloading original cluster_splits setting to be gridSize
                if (this.options.cluster_splits !== '') {
                    if (this.options.cluster_splits.test('/,/')) {
                        // they probably left it as the default 10,60 (group size in number of markers) for ClusterMarker params,
                        // for MarkerClusterer we need a single number, gridSize in pixels, so just use default
                        size = 60;
                    } else {
                        size = parseInt(this.options.cluster_splits, 10);
                    }
                }
                this.cluster = new MarkerClusterer(this.map, this.clusterMarkers, {
                    'splits': this.options.cluster_splits,
                    'icon_increment': this.options.icon_increment,
                    maxZoom: zoom,
                    gridSize: size,
                    styles: styles
                });
            }
        }
        if (this.options.fitbounds) {
            this.map.fitBounds(this.bounds);
        }
	},

	center: function () {
		//set the map to center on the center of all the points
		var c;
		switch (this.options.center) {
			case 'middle':
				if (this.noData()) {
					c = new google.maps.LatLng(this.options.lat, this.options.lon);
				}
				else {
					c = this.bounds.getCenter();
				}
				break;
			case 'userslocation':
				if (geo_position_js.init()) {
					geo_position_js.getCurrentPosition(this.geoCenter.bind(this), this.geoCenterErr.bind(this), {enableHighAccuracy: true});
				} else {
					fconsole('Geo location functionality not available');
					c = this.bounds.getCenter();
				}
				break;
			case 'querystring':
				c = new google.maps.LatLng(this.options.lat, this.options.lon);
				break;
			default:
				if (this.noData()) {
					c = new google.maps.LatLng(this.options.lat, this.options.lon);
				}
				else {
					var lasticon = this.options.icons.getLast();
					if (lasticon) {
						c = new google.maps.LatLng(lasticon[0], lasticon[1]);
					} else {
						c = this.bounds.getCenter();
					}
				}
				break;
		}
		this.map.setCenter(c);
	},

	geoCenter: function (p) {
		this.map.setCenter(new google.maps.LatLng(p.coords.latitude.toFixed(2), p.coords.longitude.toFixed(2)));
	},

	geoCenterErr: function (p) {
		fconsole('geo location error=' + p.message);
		var c;
		if (this.noData()) {
			c = new google.maps.LatLng(this.options.lat, this.options.lon);
		}
		else {
			var lasticon = this.options.icons.getLast();
			if (lasticon) {
				c = new google.maps.LatLng(lasticon[0], lasticon[1]);
			} else {
				c = this.bounds.getCenter();
			}
		}
		this.map.setCenter(c);
	},

	addIcon: function (lat, lon, html, img, w, h, groupkey, title, radius, c) {
		var point = new google.maps.LatLng(lat, lon);
		var markerOptions = {position: point, 'map': this.map};
		if (img !== '') {
			markerOptions.flat = true;
			if (img.substr(0, 7) !== 'http://' && img.substr(0, 8) !== 'https://') {
				//markerOptions.icon = Fabrik.liveSite + '/images/stories/' + img;
				//markerOptions.icon = Fabrik.liveSite + 'media/com_fabrik/images/' + img;
				markerOptions.icon = Fabrik.liveSite + img;
			} else {
				markerOptions.icon = img;
			}
		}
		markerOptions.title = title;
		var marker = new google.maps.Marker(markerOptions);
		marker.groupkey = groupkey;
		google.maps.event.addListener(marker, "click", function () {
			// $$$ jazzbass
			this.setCookies();
			//end
			this.infoWindow.setContent(html);

			this.infoWindow.open(this.map, marker);
			this.periodCounter = 0;
			this.timer = this.slimboxFunc.periodical(1000, this); //adds the number of seconds at the Site.

			// Create tips in bubble text
			Fabrik.tips.attach('.fabrikTip');
			Fabrik.fireEvent('fabrik.viz.googlemap.info.opened', [this, marker]);
		}.bind(this));
		if (this.options.clustering) {
			this.clusterMarkers.push(marker);
			this.clusterMarkerCursor++;
		}
		if (this.options.show_radius) {
			this.addRadius(marker, radius, c);
		}
		this.periodCounter++;
		return marker;
	},

	addRadius: function (marker, radius, c) {
		if (this.options.show_radius && radius > 0) {
			var circle = new google.maps.Circle({
				map      : this.map,
				radius   : radius,
				fillColor: this.options.radius_fill_colors[c]
			});
			circle.bindTo('center', marker, 'position');
		}
	},

	slimboxFunc: function () {
		// periodical function to observe the infowindow html to apply lightbox fx to images
		var links = $$("a").filter(function (el) {
			return el.rel && el.rel.test(/^lightbox/i);
		});
		if (links.length > 0 || this.periodCounter > 15) {
			clearInterval(this.timer);
			if (typeof(Slimbox) !== 'undefined') {
				$$(links).slimbox({/* Put custom options here */}, null, function (el) {
					return (this === el) || ((this.rel.length > 8) && (this.rel === el.rel));
				});
			}
			else if (typeof(Mediabox) !== 'undefined') {
				$$(links).mediabox({/* Put custom options here */}, null, function (el) {
					return (this === el) || ((this.rel.length > 8) && (this.rel === el.rel));
				});
			}
		}
		this.periodCounter++;
	},

	toggleOverlay: function (e) {
		if (e.target.id.test(/overlay_chbox_(\d+)/)) {
			var olk = e.target.id.match(/overlay_chbox_(\d+)/)[1].toInt();
			if (e.target.checked) {
				this.options.overlays[olk].setMap(this.map);
			} else {
				this.options.overlays[olk].setMap(null);
			}
		}
	},

	addOverlays: function () {
		if (this.options.use_overlays) {
			this.options.overlay_urls.each(function (overlay_url, k) {
				var pv = this.options.overlay_preserveviewports[k] === '1';
				var so = this.options.overlay_suppressinfowindows[k] === '1';
				this.options.overlays[k] = new google.maps.KmlLayer({
					url                : overlay_url,
					preserveViewport   : pv,
					suppressInfoWindows: so
				});
				this.options.overlays[k].setMap(this.map);
				this.options.overlay_events[k] = function (e) {
					this.toggleOverlay(e);
				}.bind(this);
				if (typeOf(document.id('overlay_chbox_' + k)) !== 'null') {
					document.id('overlay_chbox_' + k).addEvent('click', this.options.overlay_events[k]);
				}
			}.bind(this));

			Fabrik.fireEvent('fabrik.viz.googlemap.overlays.added', [this]);
		}
	},

	watchSidebar: function () {
		if (this.options.use_overlays) {
			$$('.fabrik_calendar_overlay_chbox').each(function (el) {
			}.bind(this));
		}
	},

	renderGroupedSideBar: function () {
		var a, linkText, c, label = '';
		if (!this.options.use_groups) {
			return;
		}
		this.grouped = {};
		c = document.id(this.options.container).getElement('.grouped_sidebar');
		if (typeOf(c) === 'null') {
			return;
		}
		c.empty();
		// Iterate over the map icons to find the group by info
		this.options.icons.each(function (i) {
			if (typeOf(this.grouped[i.groupkey]) === 'null') {

				linkText = i.groupkey;

				var lookup = i.groupkey;

				if (typeOf(lookup) === 'string') {
					lookup = lookup.replace(/[^0-9a-zA-Z_]/g, '');
				}

				// Allow for images as group by text, (Can't have nested <a>'s so parse the label for content inside possible <a>)
				if (typeOf(this.options.groupTemplates[i.listid]) !== 'null') {
					label = this.options.groupTemplates[i.listid][lookup];
				}
				var d = new Element('div').set('html', label);
				if (d.getElement('a')) {
					d = d.getElement('a');
				}
				linkText = d.get('html');

				this.grouped[i.groupkey] = [];
				var k = i.listid + lookup;
				k += ' ' + i.groupClass;

				// Build the group by toggle link
				var a = new Element('a', {
					'events': {
						'click': function (e) {
							var cname = e.target.className.replace('groupedLink', 'groupedContent');
							cname = cname.split(' ')[0];
							document.getElements('.groupedContent').hide();
							document.getElements('.' + cname).show();
						}
					},
					'href'  : '#',
					'class' : 'groupedLink' + k
				}).set('html', linkText);

				// Store the group key for later use in the toggle co
				a.store('data-groupkey', i.groupkey);
				var h = new Element('div', {'class': 'groupedContainer' + k}).adopt(a);
				h.inject(c);
			}
			this.grouped[i.groupkey].push(i);
		}.bind(this));

		if (!this.watchingSideBar) {
			c.addEvent('click:relay(a)', function (event, clicked) {

				// Don't follow the link
				event.preventDefault();
				this.toggleGrouped(clicked);
			}.bind(this));

			// Clear the grouped by icon selection
			var clear = this.container.getElements('.clear-grouped');
			if (typeOf(clear) !== 'null') {
				clear.addEvent('click', function (e) {
					e.stop();
					this.toggleGrouped(false);
				}.bind(this));
			}
			this.watchingSideBar = true;
		}
	},

	/**
	 * Toggle grouped icons.
	 *
	 * @param   mixed  clicked  False to show all, otherwise DOM node for selected group by
	 *
	 * @return  void
	 */
	toggleGrouped: function (clicked) {
		this.infoWindow.close();
		document.id(this.options.container).getElement('.grouped_sidebar').getElements('a').removeClass('active');
		if (clicked) {
			clicked.addClass('active');
			this.toggledGroup = clicked.retrieve('data-groupkey');
		}

		this.markers.each(function (marker) {
			marker.groupkey === this.toggledGroup || clicked === false ? marker.setVisible(true) : marker.setVisible(false);
			marker.setAnimation(google.maps.Animation.BOUNCE);
			var fn = function () {
				marker.setAnimation(null);
			};
			fn.delay(1500);
		}.bind(this));
	},

	/**
	 * Required for use with plugin's clear filters plugin code.
	 */
	addPlugins: function (plugins) {
		this.plugins = plugins;
	}

});
