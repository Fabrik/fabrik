var doGeoCode = function (btn) {
	var uberC = btn.retrieve('uberC');
	var fld = btn.retrieve('fld');
	var address = fld.value;
	var geocoder = new google.maps.Geocoder();
	geocoder.geocode({'address': address}, function (results, status) {
		if (status === google.maps.GeocoderStatus.OK) {
			var mapid = btn.retrieve('mapid');
			var loc = results[0].geometry.location;
			uberC.getElement('input[name^=radius_search_geocode_lat]').value = loc.lat();
			uberC.getElement('input[name^=radius_search_geocode_lon]').value = loc.lng();
			var pos = results[0].geometry.location;
			Fabrik.radiusSearch[mapid].map.setCenter(pos);
			Fabrik.radiusSearch[mapid].marker.setPosition(pos);
			//uberC.getElement('input[name=radius_search_lat]').value = '';
		} else {
			alert("Geocode was not successful for the following reason: " + status);
		}
	});
};

function geoCode() {
	window.addEvent('domready', function () {
		var latlng = new google.maps.LatLng(Fabrik.radiusSearch.geocode_default_lat, Fabrik.radiusSearch.geocode_default_long);
		var mapOptions = {
			zoom: 4,
			mapTypeId: google.maps.MapTypeId.ROADMAP
		};
		Fabrik.radiusSearch = typeOf(Fabrik.radiusSearch) === 'null' ? {} : Fabrik.radiusSearch;
		var radiusSearchMaps = document.getElements('.radius_search_geocode_map');
		radiusSearchMaps.each(function (map) {
			var c = map.getParent('.radius_search_geocode');
			var btn = c.getElement('button');
			var trigger = btn ? btn : c.getElement('.radius_search_geocode_field');
			if (trigger.retrieve('events-added', 0).toInt() !== 1) {
				Fabrik.radiusSearch[map.id] = typeOf(Fabrik.radiusSearch[map.id]) === 'null' ? {} : Fabrik.radiusSearch[map.id];
				Fabrik.radiusSearch[map.id].map = new google.maps.Map(map, mapOptions);
			
				var uberC = c.getParent('.radius_search_options');
				
				trigger.store('events-added', 1);
				trigger.store('uberC', uberC);
				trigger.store('mapid', map.id);
				
				
				var fld = c.getElement('.radius_search_geocode_field');
				trigger.store('fld', fld);
				
				if (typeOf(btn) !== 'null') {
					btn.addEvent('click', function (e) {
						e.stop();
						doGeoCode(trigger);
					});
					fld.addEvent('keyup', function (e) {
						if (e.key === 'enter') {
							doGeoCode(trigger);
						}
					});
				} else {
					
					fld.addEvent('keyup', function (e) {
						doGeoCode(trigger);
					});
				}
				
				var zoom = uberC.getElement('input[name=geo_code_def_zoom]').get('value').toInt();
				var lat = uberC.getElement('input[name=geo_code_def_lat]').get('value').toFloat();
				var lon = uberC.getElement('input[name=geo_code_def_lon]').get('value').toFloat();
				Fabrik.fireEvent('google.radiusmap.loaded', [map.id, zoom, lat, lon]);
			}
		});
	});
}

	
var FbListRadiusSearch = new Class({
	Extends : FbListPlugin,
	
	options: {
		geocode_default_lat: '0',
		geocode_default_long: '0',
		geocode_default_zoom: 4,
		prefilter: true,
		prefilterDistance: 1000,
		prefilterDone: false
	},
	
	geocoder: null,
	map: null,
	

	initialize : function (options) {
		this.parent(options);
		Fabrik.radiusSearch = Fabrik.radiusSearch ? Fabrik.radiusSearch  : {};
		
		var mapid = 'radius_search_geocode_map' + this.options.renderOrder;
		if (typeOf(Fabrik.radiusSearch[mapid]) === 'null') {
			Fabrik.radiusSearch[mapid] = {};
		
			Fabrik.radiusSearch[mapid].geocode_default_lat = this.options.geocode_default_lat;
			Fabrik.radiusSearch[mapid].geocode_default_long = this.options.geocode_default_long;
			Fabrik.radiusSearch[mapid].geocode_default_zoom = this.options.geocode_default_zoom;
		
			head.ready(function () {
				
				Fabrik.addEvent('google.radiusmap.loaded', function (mapid, zoom, lat, lon) {
					
					var latlng = new google.maps.LatLng(lat, lon);
					if (Fabrik.radiusSearch[mapid].loaded) {
						return;
					}
					Fabrik.radiusSearch[mapid].loaded = true;
					Fabrik.radiusSearch[mapid].map.setCenter(latlng);
					Fabrik.radiusSearch[mapid].map.setZoom(zoom);
					Fabrik.radiusSearch[mapid].marker = new google.maps.Marker({
						map: Fabrik.radiusSearch[mapid].map,
						draggable: true,
						position: latlng
					});
					
					google.maps.event.addListener(Fabrik.radiusSearch[mapid].marker, "dragend", function () {
						var loc = Fabrik.radiusSearch[mapid].marker.getPosition();
						var uberC = document.id(mapid).getParent('.radius_search_options');
						var geocodeLat = uberC.getElement('input[name=radius_search_geocode_lat]');
						if (typeOf(geocodeLat) !== 'null') {
							geocodeLat.value = loc.lat();
							uberC.getElement('input[name=radius_search_geocode_lon]').value = loc.lng();
						}
					});
					
					this.makeWin(mapid);
				}.bind(this));
				
				Fabrik.loadGoogleMap(true, 'geoCode');
				
				if (typeOf(this.options.value) === 'null') {
					this.options.value = 0;
				}
				
				
				if (typeOf(this.listform) !== 'null') {
					this.watchActivate();
					this.listform = this.listform.getElement('#radius_search' + this.options.renderOrder);
					if (typeOf(this.listform) === 'null') {
						fconsole('didnt find element #radius_search' + this.options.renderOrder);
						return;
					}
				
				
					this.listform.getElements('input[name^=radius_search_type]').addEvent('click', function (e) {
						this.toggleFields(e);
					}.bind(this));
				}
				
				this.options.value = this.options.value.toInt();
				if (typeOf(this.listform) === 'null') {
					return;
				}
				var output = this.listform.getElement('.radius_search_distance');
				var output2 = this.listform.getElement('.slider_output');
				this.mySlide = new Slider(this.listform.getElement('.fabrikslider-line'), this.listform.getElement('.knob'), {
					onChange : function (pos) {
						output.value = pos;
						output2.set('text', pos + this.options.unit);
					}.bind(this),
					steps : this.options.steps
				}).set(0);
	
				this.mySlide.set(this.options.value);
				output.value = this.options.value;
				output2.set('text', this.options.value);
	
				if (this.options.myloc && !this.options.prefilterDone) {
					if (geo_position_js.init()) {
						geo_position_js.getCurrentPosition(function (p) {
							this.setGeoCenter(p);
						}.bind(this), 
						function (e) {
							this.geoCenterErr(e);
						}.bind(this), {
							enableHighAccuracy : true
						});
					}
				}
			}.bind(this));
		}
	},
	
	makeWin: function (mapid) {
		var c = document.id(mapid).getParent('.radus_search');
		var b = new Element('button.btn.button').set('text', Joomla.JText._('COM_FABRIK_SEARCH'));
		c.getParent().adopt(b);
		var winOpts = {
				'id': 'win_' + mapid,
				'title': Joomla.JText._('PLG_LIST_RADIUS_SEARCH'),
				'loadMethod': 'html',
				'content': c,
				'width': 500,
				'height': 540,
				'visible': false,
				'destroy': false,
				'onContentLoaded': function () {
					this.center();
				}
			};
		var win = Fabrik.getWindow(winOpts);
		
		b.addEvent('click', function (e) {
			e.stop();
			
			// Show the map.
			c.setStyles({'position': 'relative', 'left': 0});
			var w = b.retrieve('win'); 
			w.open();
		}.bind(this));
		
		b.store('win', win);
		this.button = b;
		this.win = win;
		
		// When submitting the filter re-injet the window content back into the <form>
		Fabrik.addEvent('list.filter', function (list) {
			return this.injectIntoListForm();
		}.bind(this));
	},
	
	/**
	 * Re-inject the radius search form back into the list's form. Needed when filtering or 
	 * clearing filters
	 */
	injectIntoListForm: function () {
		var win = this.button.retrieve('win');
		win.close();
		var c = win.contentEl;
		c.hide();
		this.button.getParent().adopt(c);
		return true;
	},
	
	watchActivate: function () {
		var c = this.listform.getElement('.radius_search_options');
		this.listform.getElements('input[name^=radius_search_active]').addEvent('click', function (e) {
			switch (e.target.get('value')) {
			case '1':
				c.show();
				c.setStyles({'position': 'relative', 'left': '0'});
				break;
			case '0':
				c.hide();
				c.setStyles({'position': 'absolute', 'left': '-100000px'});
				break;
			}
		}.bind(this));
		var a = this.listform.getElements('input[name^=radius_search_active]').filter(function (f) {
			return f.checked === true;
		});
		if (a.length > 0 && a[0].get('value') === '0') {
			c.setStyles({'position': 'absolute', 'left': '-100000px'});
		}
		
	},

	setGeoCenter: function (p) {
		this.geocenterpoint = p;
		this.geoCenter(p);
		this.prefilter();
	},
	
	/**
	 * The list is set to prefilter
	 */
	prefilter: function () {
		if (this.options.prefilter) {
			this.mySlide.set(this.options.prefilterDistance);
			
			this.listform.getElements('input[name^=radius_search_active]').filter(function (f) {
				return f.get('value') === '1';
			}).getLast().checked = true;
			
			this.listform.getElements('input[value=mylocation]').checked = true;
			if (!this.list) {
				// In a viz
				this.listform.getParent('form').submit();
			} else {
				this.getList().submit('filter');
			}
		}
	},

	geoCenter: function (p) {
		if (typeOf(p) === 'null') {
			alert(Joomla.JText._('PLG_VIEW_RADIUS_NO_GEOLOCATION_AVAILABLE'));
		} else {
			this.listform.getElement('input[name=radius_search_lat]').value = p.coords.latitude.toFixed(2);
			this.listform.getElement('input[name=radius_search_lon]').value = p.coords.longitude.toFixed(2);
		}
	},

	geoCenterErr: function (p) {
		fconsole('geo location error=' + p.message);
	},

	toggleActive: function (e) {
		
	},

	toggleFields: function (e) {
		// var c = this.listform;
		var c = e.target.getParent('.radius_search_options');
		
		switch (e.target.get('value')) {
		case 'latlon':
			c.getElement('.radius_search_place_container').hide();
			c.getElement('.radius_search_coords_container').show();
			c.getElement('.radius_search_geocode').setStyles({'position': 'absolute', 'left': '-100000px'});
			
			break;
		case 'mylocation':
			c.getElement('.radius_search_place_container').hide();
			c.getElement('.radius_search_coords_container').hide();
			c.getElement('.radius_search_geocode').setStyles({'position': 'absolute', 'left': '-100000px'});
			this.setGeoCenter(this.geocenterpoint);
			break;
		case 'place':
			c.getElement('.radius_search_place_container').show();
			c.getElement('.radius_search_coords_container').hide();
			c.getElement('.radius_search_geocode').setStyles({'position': 'absolute', 'left': '-100000px'});
			break;
		case 'geocode':
			c.getElement('.radius_search_place_container').hide();
			c.getElement('.radius_search_coords_container').hide();
			c.getElement('.radius_search_geocode').setStyles({'position': 'relative', 'left': 0});
			break;
		}
	},

	clearFilter: function () {
		this.listform.getElements('input[name^=radius_search_active]').filter(function (f) {
			return f.get('value') === '0';
		}).getLast().checked = true;
		return this.injectIntoListForm();
	}

});