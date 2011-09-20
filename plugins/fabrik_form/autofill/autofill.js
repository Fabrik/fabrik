/**
 * @author Robert
 */
var Autofill =  new Class({
	
	Implements: [Events, Options],
	
	options: {
		'observe': '',
		'trigger': '',
		cnn: 0,
		table: 0,
		map: '',
		editOrig: false
	},
	
	initialize: function (options) {
		this.setOptions(options);
		window.addEvent('fabrik.form.elements.added', function (form) {
			this.setUp(form);	
		}.bind(this));
	},
	
	setUp: function (form)
	{
		try {
			this.form = form;//eval('form_' + this.options.formid);
		} catch (err) {
			//form_x not found (detailed view perhaps)
			return;
		}
		var evnt = this.lookUp.bind(this);
		this.element = this.form.formElements.get(this.options.observe);
		//if its a joined element
		if (!this.element) {
			var regex = new RegExp(this.options.observe);
			var k = Object.keys(this.form.formElements);
			var ii = k.each(function (i) {
				if (i.test(regex)) {
					this.element = this.form.formElements.get(i);
				}
			}.bind(this));
		}
		if (this.options.trigger === '') {
			if (typeOf($(this.options.observe)) !== 'null') {
				var elEvnt = $(this.options.observe).get('tag') === 'select' ? 'change' : 'blur';
				this.form.dispatchEvent('', this.options.observe, elEvnt, evnt);
			} else {
				fconsole('autofill - couldnt find element to observe');
			}
		} else {
			this.form.dispatchEvent('', this.options.trigger, 'click', evnt);
		}
	},
	
	// perform ajax lookup when the observer element is blurred
	
	lookUp: function () {
		if (!confirm(Joomla.JText._('PLG_FORM_AUTOFILL_DO_UPDATE'))) {
			return;
		}
		Fabrik.loader.start('form_' + this.options.formid, Joomla.JText._('PLG_FORM_AUTOFILL_SEARCHING'));
		
		var v = this.element.getValue();
		var formid = this.options.formid;
		var observe = this.options.observe;
		var url = Fabrik.liveSite + 'index.php?option=com_fabrik&format=raw&view=plugin&task=pluginAjax';
		
		var myAjax = new Request({url: url, method: 'post', 
		'data': {
			'plugin': 'autofill',
			'method': 'ajax_getAutoFill',
			'g': 'form',
			'v': v, 
			'formid': formid,
			'observe': observe,
			'cnn': this.options.cnn,
			'table': this.options.table,
			'map': this.options.map
		},
		onComplete: function (json) {
			Fabrik.loader.stop('form_' + this.options.formid);
			this.updateForm(json);
		}.bind(this)}).send();
	},
	
	//update the form from the ajax request returned data
	updateForm: function (json) {
		json = $H(JSON.decode(json));
		if (json.length === 0) {
			alert(Joomla.JText._('PLG_FORM_AUTOFILL_NORECORDS_FOUND'));
		}
		json.each(function (val, key) {
			var k2 = key.substr(key.length-4, 4);
			if (k2 === '_raw') {
				key = key.replace('_raw', '');
				var el = this.form.formElements.get(key);
				if (typeOf(el) !== 'null') {
					el.update(val);
				}
			}
		}.bind(this));
		if (this.options.editOrig === true) {
			this.form.getForm().getElement('input[name=rowid]').value = json.__pk_val;
		}
	}
	
});