/** call back method when maps api is loaded*/
function googlemapload() {
	window.addEvent('domready', function () {
		if (typeOf(Fabrik.googleMapRadius) === 'null') {
			var script2 = document.createElement("script");
			script2.type = "text/javascript";
			script2.src = Fabrik.liveSite + 'components/com_fabrik/libs/googlemaps/distancewidget.js';
			document.body.appendChild(script2);
			Fabrik.googleMapRadius = true;
		}
		if (document.body) {
			window.fireEvent('google.map.loaded');
		} else {
			console.log('no body');
		}	
	});
}

function googleradiusloaded() {
	window.addEvent('domready', function () {
		if (document.body) {
			window.fireEvent('google.radius.loaded');
		} else {
			console.log('no body');
		}	
	});	
}

var FbGoogleMap = new Class({
	Extends : FbElement,

	options : {
		'lat': 0,
		'lat_dms': 0,
		'key': '',
		'lon': 0,
		'lon_dms': 0,
		'zoomlevel': '13',
		'control': '',
		'maptypecontrol': false,
		'overviewcontrol': false,
		'scalecontrol': false,
		'drag': false,
		'maptype': 'G_NORMAL_MAP',
		'geocode': false,
		'latlng': false,
		'latlng_dms': false,
		'staticmap': false,
		'auto_center': false,
		'scrollwheel': false,
		'streetView': false,
		'sensor': false,
		'center': 0,
		'reverse_geocode': false
	},
	
	loadScript: function () {
		var s = this.options.sensor === false ? 'false' : 'true';
		Fabrik.loadGoogleMap(s, 'googlemapload');
	},
	
	initialize: function (element, options) {
		this.parent(element, options);
		this.loadScript();
		
		// @TODO test google object when offline $type(google) isnt working
		if (this.options.center === 1 && this.options.rowid === 0) {
			if (geo_position_js.init()) {
				geo_position_js.getCurrentPosition(this.geoCenter.bind(this), this.geoCenterErr.bind(this), {
					enableHighAccuracy: true
				});
			} else {
				fconsole('Geo locaiton functionality not available');
			}
		}
		window.addEvent('google.map.loaded', function () {
			switch (this.options.maptype) {
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
		}.bind(this));
		window.addEvent('google.radius.loaded', function () {
			this.makeRadius();
		}.bind(this));
	},

	getValue: function () {
		if (typeOf(this.field) !== 'null') {
			return this.field.get('value');
		}
		return false;
	},

	makeMap: function () {
		if (typeOf(this.element) === 'null') {
			return;
		}
		if (typeof(this.map) !== 'undefined') {
			return;
		}
		if (this.options.geocode || this.options.reverse_geocode) {
			this.geocoder = new google.maps.Geocoder();
		}
		this.field = this.element.getElement('input.fabrikinput');
		this.watchGeoCode();
		if (this.options.staticmap) {
			var i = this.element.getElement('img');
			var w = i.getStyle('width').toInt();
			var h = i.getStyle('height').toInt();
		}

		if (!this.options.staticmap) {

			var zoomControl =  this.options.control === '' ? false : true;
			var zoomControlStyle = this.options.control === 'GSmallMapControl' ? google.maps.ZoomControlStyle.SMALL : google.maps.ZoomControlStyle.LARGE;
		
			var mapOpts = {
					center: new google.maps.LatLng(this.options.lat, this.options.lon),
					zoom: this.options.zoomlevel.toInt(),
					mapTypeId: this.options.maptype,
					scaleControl: this.options.scalecontrol,
					mapTypeControl: this.options.maptypecontrol,
					overviewMapControl: this.options.overviewcontrol,
					scrollwheel: this.options.scrollwheel,
					streetViewControl: this.options.streetView,
					zoomControl: true,
					zoomControlOptions: {
						style: zoomControlStyle
					}
				};
			this.map = new google.maps.Map(document.id(this.element).getElement('.map'), mapOpts);
			
			var point = new google.maps.LatLng(this.options.lat, this.options.lon);
			var opts = {
				map: this.map,
				position: point
			};
			opts.draggable = this.options.drag;

			if (this.options.latlng === true) {
				this.element.getElement('.lat').addEvent('blur', this.updateFromLatLng.bindWithEvent(this));
				this.element.getElement('.lng').addEvent('blur', this.updateFromLatLng.bindWithEvent(this));
			}

			if (this.options.latlng_dms === true) {
				this.element.getElement('.latdms').addEvent('blur', this.updateFromDMS.bindWithEvent(this));
				this.element.getElement('.lngdms').addEvent('blur', this.updateFromDMS.bindWithEvent(this));
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

			google.maps.event.addListener(this.marker, "dragend", function () {
				this.field.value = this.marker.getPosition() + ":" + this.map.getZoom();
				if (this.options.latlng === true) {
					this.element.getElement('.lat').value = this.marker.getPosition().lat() + '° N';
					this.element.getElement('.lng').value = this.marker.getPosition().lng() + '° E';
				}
				if (this.options.latlng_dms === true) {
					this.element.getElement('.latdms').value = this.latDecToDMS();
					this.element.getElement('.lngdms').value = this.lngDecToDMS();
				}
				if (this.options.reverse_geocode) {
					this.geocoder.geocode({'latLng': this.marker.getPosition()}, function (results, status) {
						if (status === google.maps.GeocoderStatus.OK) {
							if (results[0]) {
								//infowindow.setContent(results[1].formatted_address);
								//infowindow.open(map, marker);
								//alert(results[0].formatted_address);
								results[0].address_components.each(function (component) {
									component.types.each(function (type) {
										if (type === 'street_number') {
											if (this.options.reverse_geocode_fields.route) {
												document.id(this.options.reverse_geocode_fields.route).value = component.long_name + ' ';
											}
										}
										else if (type === 'route') {
											if (this.options.reverse_geocode_fields.route) {
												document.id(this.options.reverse_geocode_fields.route).value += component.long_name;
											}
										}
										else if (type === 'street_address') {
											if (this.options.reverse_geocode_fields.route) {
												document.id(this.options.reverse_geocode_fields.route).value = component.long_name;
											}
										}	
										else if (type === 'neighborhood') {
											if (this.options.reverse_geocode_fields.neighborhood) {
												document.id(this.options.reverse_geocode_fields.neighborhood).value = component.long_name;
											}
										}	
										else if (type === 'locality') {
											if (this.options.reverse_geocode_fields.city) {
												document.id(this.options.reverse_geocode_fields.locality).value = component.long_name;
											}
										}
										else if (type === 'administrative_area_level_1') {
											if (this.options.reverse_geocode_fields.state) {
												document.id(this.options.reverse_geocode_fields.state).value = component.long_name;
											}
										}
										else if (type === 'postal_code') {
											if (this.options.reverse_geocode_fields.zip) {
												document.id(this.options.reverse_geocode_fields.zip).value = component.long_name;
											}
										}
										else if (type === 'country') {
											if (this.options.reverse_geocode_fields.country) {
												document.id(this.options.reverse_geocode_fields.country).value = component.long_name;
											}
										}
									}.bind(this));
								}.bind(this));
							}
							else {
								alert("No results found");
							}
						} else {
							alert("Geocoder failed due to: " + status);
						}
					}.bind(this));						
				}
			}.bind(this));
			google.maps.event.addListener(this.map, "zoom_changed", function (oldLevel, newLevel) {
				this.field.value = this.marker.getPosition() + ":" + this.map.getZoom();
			}.bind(this));
			if (this.options.auto_center && this.options.editable) {
				google.maps.event.addListener(this.map, "dragend", function () {
					this.marker.setPosition(this.map.getCenter());
					this.field.value = this.marker.getPosition() + ":" + this.map.getZoom();
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
				$(this.options.radius_write_element).fireEvent('change', new Event.Mock($(this.options.radius_write_element), 'change'));
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
				this.options.radius_read_element = this.options.radius_read_element.replace(/_\d+$/, "_" + this.options.repeatCounter);
			}
			if (this.options.radius_write_element && this.options.repeatCounter > 0) {
				this.options.radius_write_element = this.options.radius_write_element.replace(/_\d+$/, "_" + this.options.repeatCounter);
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
				map: this.map,
				marker: this.marker,
				distance: distance, // Starting distance in km.
				maxDistance: 2500, // Twitter has a max distance of 2500km.
				color: '#000000',
				activeColor: '#5599bb',
				sizerIcon: new google.maps.MarkerImage(this.options.radius_resize_off_icon),
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
		var lat = this.element.getElement('.lat').get('value').replace('° N', '').toFloat();
		var lng = this.element.getElement('.lng').get('value').replace('° E', '').toFloat();
		var pnt = new google.maps.LatLng(lat, lng);
		this.marker.setPosition(pnt);
		this.map.setCenter(pnt, this.map.getZoom());
		this.field.value = this.marker.getPosition() + ":" + this.map.getZoom();
		this.element.getElement('.latdms').value = this.latDecToDMS();
		this.element.getElement('.lngdms').value = this.lngDecToDMS();
	},

	updateFromDMS : function () {
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
		this.map.setCenter(pnt, this.map.getZoom());
		this.field.value = this.marker.getPosition() + ":" + this.map.getZoom();
		this.element.getElement('.lat').value = latdms_topnt + '° N';
		this.element.getElement('.lng').value = lngdms_topnt + '° E';
	},

	latDecToDMS : function () {
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

	lngDecToDMS : function () {
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

	geoCode: function (e) {
		var address = '';
		if (this.options.geocode === '2') {
			this.options.geocode_fields.each(function (field) {
				var f = document.id(field);
				var v;
				if (f.get('tag') === 'select') {
					// Hack - if select list then presuming that they want to geocode on the label and not the value
					// if empty value though use that as you dont want to geocode on 'please select'
					v = f.value === '' ? '' : f.options[f.selectedIndex].get('text');
				} else {
					v = f.value;
				}
				address += v + ',';
			});
			address = address.slice(0, -1);
		} else {
			address = this.element.getElement('.geocode_input').value;
		}
		this.geocoder.geocode({'address': address}, function (results, status) {
			if (status !== google.maps.GeocoderStatus.OK || results.length === 0) {
				fconsole(address + " not found!");
			} else {
				this.marker.setPosition(results[0].geometry.location);
				this.map.setCenter(results[0].geometry.location, this.map.getZoom());
				this.field.value = results[0].geometry.location + ":" + this.map.getZoom();
				if (this.options.latlng === true) {
					this.element.getElement('.lat').value = results[0].geometry.location.lat() + '° N';
					this.element.getElement('.lng').value = results[0].geometry.location.lng() + '° E';
				}
				if (this.options.latlng_dms === true) {
					this.element.getElement('.latdms').value = this.latDecToDMS();
					this.element.getElement('.lngdms').value = this.lngDecToDMS();
				}
			}
		}.bind(this));
	},

	watchGeoCode: function () {
		if (!this.options.geocode || !this.options.editable) {
			return false;
		}
		if (this.options.geocode === '2') {
			if (this.options.geocode_event !== 'button') {
				this.options.geocode_fields.each(function (field) {
					var f = document.id(field);
					if (typeOf(f) !== 'null') {
						f.addEvent('keyup', function (e) {
							this.geoCode();
						}.bind(this));
						
						// Select lists, radios whatnots
						f.addEvent('change', function (e) {
							this.geoCode();
						}.bind(this));

					}
				}.bind(this));
			} else {
				if (this.options.geocode_event === 'button') {
					this.element.getElement('.geocode').addEvent('click', this.geoCode.bindWithEvent(this));
				}
			}
		}
		if (this.options.geocode === '1' && document.id(this.element).getElement('.geocode_input')) {
			if (this.options.geocode_event === 'button') {
				this.element.getElement('.geocode').addEvent('click', this.geoCode.bindWithEvent(this));
			} else {
				this.element.getElement('.geocode_input').addEvent('keyup', this.geoCode.bindWithEvent(this));
			}
		}
	},

	unclonableProperties : function () {
		return [ 'form', 'marker', 'map', 'maptype' ];
	},

	cloned: function (c) {
		var f = [];
		this.options.geocode_fields.each(function (field) {
			var bits = $A(field.split('_'));
			var i = bits.getLast();
			if (i !== i.toInt()) {
				return bits.join('_');
			}
			i++;
			bits.splice(bits.length - 1, 1, i);
			f.push(bits.join('_'));
		});
		this.options.geocode_fields = f;
		this.makeMap();
		this.parent(c);
	},

	update : function (v) {
		v = v.split(':');
		if (v.length < 2) {
			v[1] = this.options.zoomlevel;
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
		this.map.setCenter(pnt, this.map.getZoom());
	},

	geoCenter : function (p) {
		var pnt = new google.maps.LatLng(p.coords.latitude, p.coords.longitude);
		this.marker.setPosition(pnt);
		this.map.setCenter(pnt);
	},

	geoCenterErr : function (p) {
		fconsole('geo location error=' + p.message);
	},
	
	redraw : function () {
		google.maps.event.trigger(this.map, 'resize');
		var center = new google.maps.LatLng(this.options.lat, this.options.lon);
		this.map.setCenter(center);
		this.map.setZoom(this.map.getZoom());
	},
	
	/*
	 * Testing some stuff to try and get maps to display properly when they are in the
	 * tab template.  If a map is in a tab which isn't selected on page load, the map
	 * will not render properly, and needs to be refreshed when the tab it is in is selected.
	 * NOTE that this stuff is very specific to the Fabrik tabs template, using J!'s tabs.
	 */
    
	doTab: function (event) {
		(function () {
			//this.map.checkResize();
			google.maps.event.trigger(this.map, 'resize');
			var center = new google.maps.LatLng(this.options.lat, this.options.lon);
			this.map.setCenter(center);
			this.map.setZoom(this.map.getZoom());
			this.options.tab_dt.removeEvent('click', this.doTabBound);
		}.bind(this)).delay(500);
	},
    
	watchTab: function () {
		var tab_div = this.element.getParent('.current');
		if (tab_div) {
			var tab_dl = tab_div.getPrevious('.tabs');
			if (tab_dl) {
				this.options.tab_dd = this.element.getParent('.fabrikGroup');
				if (this.options.tab_dd.style.getPropertyValue('display') === 'none') {
					this.options.tab_dt = tab_dl.getElementById('group' + this.groupid + '_tab');
					if (this.options.tab_dt) {
						this.doTabBound = this.doTab.bindWithEvent(this);
						this.options.tab_dt.addEvent('click', this.doTabBound);
					}
				}
			}
		}
	}

});