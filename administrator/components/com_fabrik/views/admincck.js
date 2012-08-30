var adminCCK = new Class({

	Implements : [ Options ],

	options : {},

	initialize : function (options) {

		this.setOptions(options);

		this.form = Fabrik.blocks['form_' + this.options.formid];

		new Element('fieldset').adopt([ new Element('legend').set('text', 'CCK'), new Element('table', {
			'class': 'paramlist admintable'
		}).adopt(new Element('tbody').adopt([ new Element('tr').adopt([ new Element('td', {
			'class': 'paramlist_key'
		}).adopt(new Element('label', {
			'for': 'template'
		}).set('html', 'template: ')), new Element('td', {
			'class': 'paramlist_value'
		}).set('html', this.options.tmplList) ]), new Element('tr').adopt([ new Element('td', {
			'class': 'paramlist_key'
		}).set('text', 'view: '), new Element('td', {
			'class': 'paramlist_value'
		}).set('html', this.options.viewList) ]) ])) ]).inject(document.id('form_' + this.options.formid), 'before');

		Fabrik.addBlock('cck', this);
		this.form.options.ajax = true;
		// get the form to emulate being in a module
		document.getElement('input[name=packageid]').value = '-1';
	},

	insertTag : function (json) {
		var tmpl = document.id('fabrik_cck_template').get('value');
		var view = document.getElements('input[name=fabrik_cck_view]').filter(function (v) {
			return v.checked;
		});
		view = view.length === 0 ? 'form' : view[0].get('value');
		var tag = "{fabrik view=" + view + " id=" + this.options.formid + " rowid=" + json.rowid + " layout=" + tmpl + "}";
		window.parent.jInsertEditorText(tag, this.options.ename);
		window.parent.document.getElementById('sbox-window').close();
	}
});
