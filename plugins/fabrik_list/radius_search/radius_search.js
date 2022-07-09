/**
 * List Radius Search
 *
 * @copyright: Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */
define(['jquery', 'fab/list-plugin', 'fab/fabrik'], function (jQuery, FbListPlugin, Fabrik) {
	var doGeoCode = function (btn) {
		var uberC = btn.retrieve('uberC'),
			mapid = btn.retrieve('mapid'),
			address = btn.retrieve('fld').value,
			geocoder = new google.maps.Geocoder();

		if (!Fabrik.radiusSearchResults) {
			Fabrik.radiusSearchResults = {};
		}

		if (Fabrik.radiusSearchResults[address]) {
			parseGeoCodeResult(uberC, mapid, Fabrik.radiusSearchResults[address]);
		}
		geocoder.geocode({'address': address}, function (results, status) {
			if (status === google.maps.GeocoderStatus.OK) {
				parseGeoCodeResult(uberC, mapid, results[0].geometry.location);
				Fabrik.radiusSearchResults[address] = results[0].geometry.location;
			} else {
				window.alert(Joomla.JText._('PLG_LIST_RADIUS_SEARCH_GEOCODE_ERROR').replace('%s', status));
			}
		});
	};

	/**
	 * Parse a google geocode result.
	 * @param {domnode} uberC Radius search container div
	 * @param {string} mapid  Map id
	 * @param {object} loc
	 */
	var parseGeoCodeResult = function (uberC, mapid, loc) {
		uberC.getElement('input[name^=radius_search_geocode_lat]').value = loc.lat();
		uberC.getElement('input[name^=radius_search_geocode_lon]').value = loc.lng();
		Fabrik.radiusSearch[mapid].map.setCenter(loc);
		Fabrik.radiusSearch[mapid].marker.setPosition(loc);
	};


	window.geoCode = function () {
		// Tell fabrik that the google map script has loaded and the callback has run
		Fabrik.googleMap = true;

		window.addEvent('domready', function () {
			var latlng = new google.maps.LatLng(Fabrik.radiusSearch.geocode_default_lat,
				Fabrik.radiusSearch.geocode_default_long);
			var mapOptions = {
				zoom     : 4,
				mapTypeId: google.maps.MapTypeId.ROADMAP
			};
			Fabrik.radiusSearch = typeOf(Fabrik.radiusSearch) === 'null' ? {} : Fabrik.radiusSearch;
			var radiusSearchMaps = document.getElements('.radius_search_geocode_map');
			var radiusGeocompletes = document.getElements('.radius_search_geocomplete_field');

			radiusGeocompletes.each(function (geo) {
                var c = geo.getParent('.radius_search');
                var trigger = c.getElement('.radius_search_geocode_field');
                jQuery('#' + geo.id).geocomplete()
                    .bind(
                        'geocode:result',
                        function(event, result){
							var loc = result.geometry.location;
                            c.getElement('input[name^=radius_search_geocomplete_lat]').value = loc.lat();
                            c.getElement('input[name^=radius_search_geocomplete_lon]').value = loc.lng();
                        }
                    );
			});
			radiusSearchMaps.each(function (map) {
				var c = map.getParent('.radius_search_geocode');
				var btn = c.getElement('button');
				var trigger = btn ? btn : c.getElement('.radius_search_geocode_field');
				if (trigger.retrieve('events-added', 0).toInt() !== 1) {
					Fabrik.radiusSearch[map.id] = typeOf(Fabrik.radiusSearch[map.id]) === 'null' ?
					{} : Fabrik.radiusSearch[map.id];
					Fabrik.radiusSearch[map.id].map = new google.maps.Map(map, mapOptions);

					var uberC = c.getParent('.radius_search');

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
						var timer;
						fld.addEvent('keyup', function (e) {
							if (timer) {
								clearTimeout(timer);
							}
							if (e.key === 'enter') {
								doGeoCode(trigger);
							}
							timer = window.setTimeout(function () {
								doGeoCode(trigger);
							}, 1000);
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


	var FbListRadius_search = new Class({
		Extends: FbListPlugin,

		options: {
			geocode_default_lat : '0',
			geocode_default_long: '0',
			geocode_default_zoom: 4,
			prefilter           : true,
			prefilterDistance   : 1000,
			prefilterDone       : false,
			offset_y            : 0,
			usePopup            : true,
			key                 : false
		},

		geocoder: null,
		map     : null,


		initialize: function (options) {
			this.parent(options);
			Fabrik.radiusSearch = Fabrik.radiusSearch ? Fabrik.radiusSearch : {};

			var mapid = 'radius_search_geocode_map' + this.options.renderOrder;
			if (typeOf(Fabrik.radiusSearch[mapid]) === 'null') {
				Fabrik.radiusSearch[mapid] = {};

				Fabrik.radiusSearch[mapid].geocode_default_lat = this.options.geocode_default_lat;
				Fabrik.radiusSearch[mapid].geocode_default_long = this.options.geocode_default_long;
				Fabrik.radiusSearch[mapid].geocode_default_zoom = this.options.geocode_default_zoom;
				Fabrik.addEvent('google.radiusmap.loaded', function (mapid, zoom, lat, lon) {

					var latlng = new google.maps.LatLng(lat, lon);
					if (Fabrik.radiusSearch[mapid].loaded) {
						return;
					}
					Fabrik.radiusSearch[mapid].loaded = true;
					Fabrik.radiusSearch[mapid].map.setCenter(latlng);
					Fabrik.radiusSearch[mapid].map.setZoom(zoom);
					Fabrik.radiusSearch[mapid].marker = new google.maps.Marker({
						map      : Fabrik.radiusSearch[mapid].map,
						draggable: true,
						position : latlng
					});

					google.maps.event.addListener(Fabrik.radiusSearch[mapid].marker, 'dragend', function () {
						var loc = Fabrik.radiusSearch[mapid].marker.getPosition();
						var uberC = document.id(mapid).getParent('.radius_search');
						var geocodeLat = uberC.getElement('input[name^=radius_search_geocode_lat]');
						if (typeOf(geocodeLat) !== 'null') {
							geocodeLat.value = loc.lat();
							uberC.getElement('input[name^=radius_search_geocode_lon]').value = loc.lng();
						}
					});

					google.maps.event.addListener(Fabrik.radiusSearch[mapid].map, 'drag', function (event) {
						fconsole('dragged');
					});

				}.bind(this));

				Fabrik.loadGoogleMap(this.options.key, 'geoCode', this.options.language);

				if (typeOf(this.options.value) === 'null') {
					this.options.value = 0;
				}

                this.options.value = this.options.value.toInt();
				
				if (this.options.usePopup && typeOf(this.listform) !== 'null') {
					this.listform = this.listform.getElement('#radius_search' + this.options.renderOrder);
					if (typeOf(this.listform) === 'null') {
						fconsole('didnt find element #radius_search' + this.options.renderOrder);
						return;
					}

					var select = this.listform.getElements('select[name^=radius_search_type]');
					select.addEvent('change', function (e) {
						this.toggleFields(e);
					}.bind(this));

					this.listform.getElements('input.cancel').addEvent('click', function () {
						this.win.close();
					}.bind(this));

					this.active = false;
					this.listform.getElement('.fabrik_filter_submit').addEvent('mousedown', function (e) {
						this.active = true;
						this.listform.getElement('input[name^=radius_search_active]').value = 1;
					}.bind(this));

                    var output = this.listform.getElement('.radius_search_distance');
                    var output2 = this.listform.getElement('.slider_output');
                    this.mySlide = new Slider(this.listform.getElement('.fabrikslider-line'), this.listform.getElement('.knob'), {
                        onChange: function (pos) {
                            output.value = pos;
                            output2.set('text', pos + this.options.unit);
                        }.bind(this),
                        steps   : this.options.steps
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
                                    enableHighAccuracy: true
                                });
                        }
                    }
				}
			}

			// Ensure that if in a map viz clearing the list filter is run.
			Fabrik.addEvent('listfilter.clear', function (caller) {
				if (caller.contains(this.options.ref)) {
					this.clearFilter();
				}
			}.bind(this));

			if (this.options.usePopup) {
                this.makeWin(mapid);
            }
            else {
                Fabrik.addEvent('fabrik.list.submit.ajax.complete', function(list, json) {
                    if (this.listid === list.id) {
                        var $emptyMsgDiv = jQuery('#radius_search' + this.options.renderOrder).find('.fabrikRadiusSearchEmptyMsg');
                        if ($emptyMsgDiv) {
                            if (json.nav.total === 0) {
                                $emptyMsgDiv.parent().removeClass('fabrikHide');
                            }
                            else {
                                $emptyMsgDiv.parent().addClass('fabrikHide');
                            }
                        }
                    }
                }.bind(this));
                this.listform.getElement('input[name^=radius_search_active]').value = 1;
			}
		},

		/**
		 * Moves the interface into a window and injects a search button to open it.
		 */
		makeWin: function (mapid) {
			var c = document.id(mapid).getParent('.radius_search');
			var b = new Element('button.btn.button').set('html', '<i class="icon-location"></i> ' + Joomla.JText._('PLG_LIST_RADIUS_SEARCH_BUTTON'));
			c.getParent().adopt(b);
			var offset_y = this.options.offset_y > 0 ? this.options.offset_y : null;
			var winOpts = {
				'id'             : 'win_' + mapid,
				'title'          : Joomla.JText._('PLG_LIST_RADIUS_SEARCH'),
				'loadMethod'     : 'html',
				'content'        : c,
				'width'          : 500,
				'height'         : 540,
				'offset_y'       : offset_y,
				'visible'        : false,
				'destroy'        : false,
				'onClose'        : function (e, x) {
					var active;
					if (!this.active && window.confirm(Joomla.JText._('PLG_LIST_RADIUS_SEARCH_CLEAR_CONFIRM'))) {
						active = 0;
					} else {
						active = 1;
					}
					this.win.window.getElement('input[name^=radius_search_active]').value = active;
				}.bind(this)
			};
			var win = Fabrik.getWindow(winOpts);

			b.addEvent('click', function (e) {
				e.stop();

				// Show the map.
				c.setStyles({'position': 'relative', 'left': 0});
				var w = b.retrieve('win');
				w.center();
				w.open();
				w.fitToContent();

				var mapid = 'radius_search_geocode_map' + this.options.renderOrder;
				if (mapid in Fabrik.radiusSearch) {
					google.maps.event.trigger(Fabrik.radiusSearch[mapid].map, 'resize');
				}
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
			var c = win.contentEl.clone();
			c.hide();

            // clone() doesn't copy select (or textarea) state, so set selects by hand!
            var selects = jQuery(win.contentEl).find("select");
            jQuery(selects).each(function(i) {
                var select = this;
                jQuery(c).find("select").eq(i).val(jQuery(select).val());
            });

			jQuery(this.button).parent().append(c);
			return true;
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

				this.listform.getElement('input[name^=radius_search_active]').value = 1;
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
				window.alert(Joomla.JText._('PLG_VIEW_RADIUS_NO_GEOLOCATION_AVAILABLE'));
			} else {
				this.listform.getElement('input[name*=radius_search_lat]').value = p.coords.latitude.toFixed(2);
				this.listform.getElement('input[name*=radius_search_lon]').value = p.coords.longitude.toFixed(2);
			}
		},

		geoCenterErr: function (p) {
			fconsole('geo location error=' + p.message);
		},

		toggleActive: function (e) {

		},

		toggleFields: function (e) {
			var c = e.target.getParent('.radius_search');

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
			this.win.fitToContent(false);
		},

		clearFilter: function () {
			this.listform.getElement('input[name^=radius_search_active]').value = 0;
			//return this.injectIntoListForm();
			return true;
		}

	});

	return FbListRadius_search;
});