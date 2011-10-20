var FbGoogleMapViz = new Class({
	Implements: Options,
	options: {
		'lat': 0,
		'lon': 0,
		'clustering': false,
		'maptypecontrol': false,
		'overviewcontrol': false,
		'scalecontrol': false,
		'center': 'middle',
		'ajax_refresh': false,
		'polyline': [],
		'polylinewidth': [],
		'polylinecolour': [],
		'refresh_rate': 10000,
		'use_cookies': true,
		'overlays': [],
		'overlay_urls': [],
		'overlay_labels': [],
		'overlay_events': [],
		'zoom' : 1,
		'zoomStyle': 0
	},
	
	initialize: function (element, options) {
		this.element_map = element;
		this.element = $(element);
		
		this.clusterMarkerCursor = 0;
		this.clusterMarkers = [];
		this.markers = [];
		this.icons = [];
		this.setOptions(options);
		
		if (this.options.ajax_refresh) {
			this.updater = new Request({url: 'index.php',
				data : {
					'option': 'com_fabrik',
					'format': 'raw',
					'view': 'plugin',
					'task': 'pluginAjax',
					'plugin': 'googlemap',
					'method': 'ajax_getMarkers',
					'g': 'visualization',
					'element_id': this.options.id
				},
				onSuccess: function (json) {
					this.options.icons = JSON.decode(json);
					this.addIcons();
					if (this.options.ajax_refresh_center) {
						this.center();
					}
				}.bind(this)
			});
			this.timer = this.update.periodical(this.options.refresh_rate, this);
		}
		
		switch (this.options.maptype) {
		case 'G_NORMAL_MAP':
		/* falls through */
		default:
			this.options.maptype = google.maps.MapTypeId.ROADMAP;
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
		}
		
		head.ready(function () {
			if (typeOf(this.element_map) === 'null') {
				return;
			}
			var mapOpts = {
				center: new google.maps.LatLng(this.options.lat, this.options.lon),
				zoom: this.options.zoomlevel.toInt(),
				mapTypeId: this.options.maptype,
				scaleControl: this.options.scalecontrol,
				mapTypeControl: this.options.maptypecontrol,
				overviewMapControl: this.options.overviewcontrol,
				scrollwheel: this.options.scrollwheel,
				zoomControl: this.options.zoom,
				zoomControlOptions: {style: this.options.zoomStyle} 
			};
			this.map = new google.maps.Map($(this.element_map), mapOpts);

			this.infoWindow = new google.maps.InfoWindow({
				content: ''
			});
			this.bounds = new google.maps.LatLngBounds();
			
			/*
			if (this.options.clustering) {
				this.markerMgr = new MarkerManager(this.map, {trackMarkers: true, maxZoom: 15});
			}
			*/
			
			this.addIcons();
			this.addOverlays();
			
			google.maps.event.addListener(this.map, "click", this.setCookies.bindWithEvent(this));
			google.maps.event.addListener(this.map, "moveend", this.setCookies.bindWithEvent(this));
			google.maps.event.addListener(this.map, "zoomend", this.setCookies.bindWithEvent(this));

			if (this.options.use_cookies) {
				// $$$ jazzbass - get previous stored location
				var mymapzoom = Cookie.read("mymapzoom_" + this.options.id);
				var mymaplat = Cookie.read("mymaplat_" + this.options.id);
				var mymaplng = Cookie.read("mymaplng_" + this.options.id);

				if (mymaplat && mymaplat !== '0' && mymapzoom !== '0') {
					this.map.setCenter(new google.maps.LatLng(mymaplat.toFloat(), mymaplng.toFloat()), mymapzoom.toInt());
				} else {
					this.center();
				}
			}
			else {
				this.center();
			}
			//end
			
			if (typeof(Slimbox) !== 'undefined') {
				Slimbox.scanPage();
			} else if (typeof(Mediabox) !== 'undefined') {
				Mediabox.scanPage();
			}

			//clear filter list
			this.container =  $(this.options.container);
			if (typeOf(this.container) !== 'null') {
				var c = this.container.getElement('.clearFilters');
				if (c) {
					c.addEvent('click', function (e) {
						this.container.getElements('.fabrik_filter').each(function (f) {
							f.value = '';
						});
						new Event(e).stop();
						this.container.getElement('form[name=filter]').submit();
					}.bind(this));
				}
			}
	
			this.options.polyline.each(function (points, c) {
				var glatlng = [];
				points.each(function (p) {
					glatlng.push(new google.maps.LatLng(p[0], p[1]));
				});
				var width = this.options.polylinewidth[c];
				var colour = this.options.polylinecolour[c];
				var polyline = new google.maps.Polyline({path: glatlng, 'strokeColor': colour, 'strokeWeight': width});
				polyline.setMap(this.map);
			}.bind(this));
		}.bind(this));
	},
	
	setCookies: function () {
		if (this.options.use_cookies) {
			Cookie.write("mymapzoom_" + this.options.id, this.map.getZoom(), {duration: 7});
			Cookie.write("mymaplat_" + this.options.id, this.map.getCenter().lat(), {duration: 7});
			Cookie.write("mymaplng_" + this.options.id, this.map.getCenter().lng(), {duration: 7}); 
		}
	},
	
	update: function () {
		this.updater.send();
	},
	
	addIcons: function () {
		this.markers = [];
		this.options.icons.each(function (i) {
			this.bounds.extend(new google.maps.LatLng(i[0], i[1]));
			this.markers.push(this.addIcon(i[0], i[1], i[2], i[3], i[4], i[5], i.groupkey, i.title));
		}.bind(this));
	  
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
					'url': Fabrik.liveSite + "/components/com_fabrik/libs/googlemaps/markerclusterer/images/m" + i + ".png",
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
			this.cluster = new MarkerClusterer(this.map, this.clusterMarkers, {'splits': this.options.cluster_splits, 'icon_increment': this.options.icon_increment, maxZoom: zoom, gridSize: size, styles: styles});
		}
		/*
		if (this.options.clustering) {
			google.maps.event.addListener(this.markerMgr, 'loaded', function () {
				this.markerMgr.addMarkers(this.markers, 0, 15);
				this.markerMgr.refresh();
			}.bind(this));
		}
		*/
		/* this.cluster=new ClusterMarker(this.map, { markers:this.clusterMarkers, 'splits':this.options.cluster_splits, 'icon_increment':this.options.icon_increment});
		this.cluster.fitMapToMarkers();
		this.map.savePosition();	//	enables the large map control centre button to return the map to initial view*/
	}, 
	
	center: function () {
		//set the map to center on the center of all the points
		var c;
		switch (this.options.center) {
		case 'middle':
			c = this.bounds.getCenter();
			break;
		case 'userslocation':
			if (geo_position_js.init()) {
				geo_position_js.getCurrentPosition(this.geoCenter.bind(this), this.geoCenterErr.bind(this), {enableHighAccuracy: true});
			} else {
				fconsole('Geo locaiton functionality not available');
				c = this.bounds.getCenter();
			}
			break;
		default:
			var lasticon = this.options.icons.getLast();
			if (lasticon) {
				c = new google.maps.LatLng(lasticon[0], lasticon[1]);
			} else {
				c = this.bounds.getCenter();
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
	},
	
	addIcon: function (lat, lon, html, img, w, h, groupkey, title) {
		var point = new google.maps.LatLng(lat, lon);
		var markerOptions = {position: point, 'map': this.map};
		if (img !== '') {
			markerOptions.flat = true;
			if (img.substr(0, 7) !== 'http://' && img.substr(0, 8) !== 'https://') {
				markerOptions.icon = Fabrik.liveSite + '/images/' + img;
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
		}.bind(this));
		if (this.options.clustering) {
			this.clusterMarkers.push(marker);
			this.clusterMarkerCursor ++;
		}
		this.periodCounter ++;
		return marker;
	},

	slimboxFunc:  function () {
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
		this.periodCounter ++;
	},
	
	toggleOverlay: function (event) {
		var e = new Event(event);
		if (event.target.id.test(/overlay_chbox_(\d+)/)) {
			var olk = event.target.id.match(/overlay_chbox_(\d+)/)[1].toInt();
			if (this.options.overlays[olk].isHidden()) {
				this.options.overlays[olk].show();
			}
			else {
				this.options.overlays[olk].hide();
			}
		}
	},
    
	addOverlays: function () {
		if (this.options.use_overlays) {
			this.options.overlay_urls.each(function (overlay_url, k) {
				this.options.overlays[k] = new GGeoXml(overlay_url);
				this.map.addOverlay(this.options.overlays[k]);
				this.options.overlay_events[k] = this.toggleOverlay.bindWithEvent(this);
				$('overlay_chbox_' + k).addEvent('click', this.options.overlay_events[k]);
			}.bind(this));
		}
	},
	
	watchSidebar: function () {
		if (this.options.use_overlays) {
			$$('.fabrik_calendar_overlay_chbox').each(function (el) {
			}.bind(this));
		}
	},
	
	renderGroupedSideBar: function () {
		this.grouped = {};
		var c = $(this.options.container).getElement('.grouped_sidebar');
		if (typeOf(c) === 'null') {
			return;
		}
		this.options.icons.each(function (i) {
			if ($type(this.grouped[i.groupkey]) === false) {
				this.grouped[i.groupkey] = [];
				var k = i.listid + i.groupkey.replace(/[^0-9a-zA-Z_]/g, '');
				var h = new Element('div', {'class': 'groupedContainer' + k}).adopt(new Element('a', {
					'events': {
						'click': function (e) {
							var cname = e.target.className.replace('groupedLink', 'groupedContent');
							document.getElements('.groupedContent').hide();
							document.getElements('.' + cname).show();
						}
					},
					'href': '#',
					'class': 'groupedLink' + k
				}).set('text', i.groupkey));
				h.injectInside(c);
			}
			this.grouped[i.groupkey].push(i);
		}.bind(this));
		
		c.addEvent('click:relay(a)', function (event, clicked) {
			event.preventDefault(); //don't follow the link
			this.infoWindow.close();
			var l = clicked.get('text');
			this.toggledGroup = l;
			this.toggleGrouped();
		}.bind(this));
	},
	
	toggleGrouped: function ()
	{
		this.markers.each(function (marker) {
			marker.groupkey === this.toggledGroup ? marker.setVisible(true) : marker.setVisible(false);
			marker.setAnimation(google.maps.Animation.BOUNCE);
			var fn = function () {
				marker.setAnimation(null);
			}; 
			fn.delay(1500);
		}.bind(this));
	}
		
});