var FbListRadiusSearch = new Class({
	Extends : FbListPlugin,
	initialize : function (element, options) {
		this.parent(options);
		head.ready(function () {

			this.element = document.id(element).getElement('.radus_search');
			if (typeOf(this.options.value) === 'null') {
				this.options.value = 0;
			}
			this.fx = new Fx.Slide(this.element.getElement('.radius_search_options'));
			this.element.getElements('input[name^=radius_search_active]').addEvent('click', this.toggleActive.bindWithEvent(this));
			var a = this.element.getElements('input[name^=radius_search_active]').filter(function (f) {
				return f.checked === true;
			});
			if (a[0].get('value') === '0') {
				this.fx.slideOut();
			}
			this.element.getElements('input[name^=radius_search_type]').addEvent('click', this.toggleFields.bindWithEvent(this));
			this.options.value = this.options.value.toInt();
			if (typeOf(this.element) === 'null') {
				return;
			}
			var output = this.element.getElement('.radius_search_distance');
			var output2 = this.element.getElement('.slider_output');
			this.mySlide = new Slider(this.element.getElement('.fabrikslider-line'), this.element.getElement('.knob'), {
				onChange : function (pos) {
					output.value = pos;
					output2.set('text', pos + this.options.unit);
				}.bind(this),
				steps : this.options.steps
			}).set(0);

			this.mySlide.set(this.options.value);
			output.value = this.options.value;
			output2.set('text', this.options.value);

			if (geo_position_js.init()) {
				geo_position_js.getCurrentPosition(this.setGeoCenter.bind(this), this.geoCenterErr.bind(this), {
					enableHighAccuracy : true
				});
			}

		}.bind(this));
	},

	setGeoCenter : function (p) {
		this.geocenterpoint = p;
		this.geoCenter(p);
	},

	geoCenter : function (p) {
		if (typeOf(p) === 'null') {
			alert(Joomla.JText._('PLG_VIEW_RADIUS_NO_GEOLOCATION_AVAILABLE'));
		} else {
			this.element.getElement('input[name=radius_search_lat]').value = p.coords.latitude.toFixed(2);
			this.element.getElement('input[name=radius_search_lon]').value = p.coords.longitude.toFixed(2);
		}
	},

	geoCenterErr : function (p) {
		fconsole('geo location error=' + p.message);
	},

	toggleActive : function (e) {
		switch (e.target.get('value')) {
		case '1':
			this.fx.slideIn();
			break;
		case '0':
			this.fx.slideOut();
			break;
		}
	},

	toggleFields : function (e) {
		switch (e.target.get('value')) {
		case 'latlon':
			this.element.getElement('.radius_search_place_container').setStyle('display', 'none');
			this.element.getElement('.radius_search_coords_container').setStyle('display', '');
			break;
		case 'mylocation':
			this.element.getElement('.radius_search_place_container').setStyle('display', 'none');
			this.element.getElement('.radius_search_coords_container').setStyle('display', 'none');
			this.setGeoCenter(this.geocenterpoint);
			break;
		case 'place':
			this.element.getElement('.radius_search_place_container').setStyle('display', '');
			this.element.getElement('.radius_search_coords_container').setStyle('display', 'none');
			break;
		}
	},

	clearFilter : function () {
		this.element.getElements('input[name^=radius_search_active]').filter(function (f) {
			return f.get('value') === '0';
		}).getLast().checked = true;
	}

});