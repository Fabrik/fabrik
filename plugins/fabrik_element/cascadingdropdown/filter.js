var CascadeFilter = new Class({
	initialize: function (observerid, opts) {
		this.options = opts;
		this.observer = document.id(observerid);
		if (this.observer) {
			new Element('img', {'id': this.options.filterid + '_loading', 'src': Fabrik.liveSite + 'media/com_fabrik/images/ajax-loader.gif', 'alt': 'loading...', 'styles': {'opacity': '0'}}).inject(this.observer, 'before');
			var v = this.observer.get('value');
			this.myAjax = new Request({url: '', method: 'post',
				'data': {
					'option': 'com_fabrik',
					'format': 'raw',
					'task': 'plugin.pluginAjax',
					'plugin': 'cascadingdropdown',
					'method': 'ajax_getOptions',
					'element_id': this.options.elid,
					'v': v, 
					'formid': this.options.formid, 
					'fabrik_cascade_ajax_update': 1,
					'filterview': 'table'
				},
				onComplete: this.ajaxComplete.bindWithEvent(this)
			});

			this.observer.addEvent('change', function () {
				this.periodcount = 0;
				document.id(this.options.filterid + '_loading').setStyle('opacity', '1');
				var v = this.observer.get('value');
				this.myAjax.options.data.v = v;
				// $$$ hugh - added this so we fake out submitted form data for use as placeholders in query filter
				$filterData = eval(this.options.filterobj).getFilterData();
				Object.append(this.myAjax.options.data, $filterData);
				this.myAjax.send();
			}.bind(this));
			
			v = this.observer.get('value');
			this.periodical = this.update.periodical(500, this);
			this.periodcount = 0;
		} else {
			fconsole('observer not found ', observerid);
		}
	},
	
	update: function () {
		if (this.observer) {
			this.myAjax.options.data.v = this.observer.get('value');
			// $$$ hugh - added this so we fake out submitted form data for use as placeholders in query filter
			$filterData = eval(this.options.filterobj).getFilterData();
			Object.append(this.myAjax.options.data, $filterData);
			this.myAjax.send();
		}
	},
	
	ajaxComplete: function (json) {
		json = JSON.decode(json);
		this.periodcount ++;
		if (this.periodcount > 5) {
			this.endAjax();
			return;
		}
		if (typeOf(document.id(this.options.filterid)) === 'null') {
			fconsole('filterid not found: ', this.options.filterid);
			this.endAjax();
			return;				
		}

		document.id(this.options.filterid).empty();
		json.each(function (item) {
			new Element('option', {'value': item.value}).appendText(item.text).inject(document.id(this.options.filterid));
			document.id(this.options.filterid).value = this.options.def;
		}.bind(this));
		if (json.length > 0) {
			if ((json.length === 1 && json[0].value === '') === false) {
				this.endAjax();
			}
		} else {
			this.endAjax();
		}
	},
	
	endAjax: function ()
	{
		document.id(this.options.filterid + '_loading').setStyle('opacity', '0');
		clearInterval(this.periodical);
	}
});