/**
 * Image Element
 *
 * @copyright: Copyright (C) 2005-2013, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var FbImage = new Class({
	Extends : FbFileElement,
	initialize : function (element, options) {
		this.plugin = 'image';
		this.folderlist = [];
		this.parent(element, options);
		this.options.rootPath = options.rootPath;
		if (options.editable) {
			this.getMyElements();
			this.imageFolderList = [];

			this.selectedImage = '';
			if (this.imageDir) {
				if (this.imageDir.options.length !== 0) {
					this.selectedImage = this.imageDir.get('value');
				}
				this.imageDir.addEvent('change', function (e) {
					this.showImage(e);
				}.bind(this));
			}
			if (this.options.canSelect === true) {
				this.addEvent('onBrowse', this.changeFolder);
				this.ajaxFolder();
				this.element = this.hiddenField;
				this.selectedFolder = this.getFolderPath();
			}
		}
	},

	getMyElements : function () {
		var element = this.options.element;
		var c = this.getContainer();
		if (!c) {
			return;
		}
		this.image = c.getElement('.imagedisplayor');
		this.folderDir = c.getElement('.folderselector');
		this.imageDir = c.getElement('.imageselector');
		// this.hiddenField is set in FbFileElement
	},

	cloned: function (c) {
		this.getMyElements();
		this.ajaxFolder();
		this.parent(c);
	},

	hasSubElements : function () {
		return true;
	},

	getFolderPath : function () {
		return this.options.rootPath + this.folderlist.join('/');
	},

	changeFolder : function (e) {
		var folder = this.imageDir;
		this.selectedFolder = this.getFolderPath();
		folder.empty();
		var myAjax = new Request({
			url : '',
			method : 'post',
			'data' : {
				'option': 'com_fabrik',
				'format': 'raw',
				'task': 'plugin.pluginAjax',
				'g': 'element',
				'plugin': 'image',
				'method': 'ajax_files',
				'folder': this.selectedFolder
			},

			onComplete : function (r) {
				var newImages = eval(r);
				newImages.each(function (opt) {
					folder.adopt(new Element('option', {
						'value' : opt.value
					}).appendText(opt.text));
				});
				this.showImage();
			}.bind(this)
		}).send();
	},

	showImage : function (e) {
		if (this.imageDir) {
			if (this.imageDir.options.length === 0) {
				this.image.src = '';
				this.selectedImage = '';
			} else {
				this.selectedImage = this.imageDir.get('value');
				this.image.src = Fabrik.liveSite + this.selectedFolder + '/' + this.selectedImage;
			}
			//this.hiddenField.value =  this.get('value');//this.selectedImage;
			this.hiddenField.value =  this.getValue();
		}
	},

	getValue : function () {
		return this.folderlist.join('/') + '/' + this.selectedImage;// this.hiddenField.value;
	}
});