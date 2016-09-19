/**
 * Image Element
 *
 * @copyright: Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

define(['jquery', 'fab/fileelement'], function (jQuery, FbFileElement) {
    window.FbImage = new Class({
        Extends   : FbFileElement,
        initialize: function (element, options) {
            this.setPlugin('image');
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
                    this.ajaxFolder();
                    this.element = this.hiddenField;
                    this.selectedFolder = this.getFolderPath();
                }
            }
        },

        getMyElements: function () {
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

        hasSubElements: function () {
            return true;
        },

        getFolderPath: function () {
            return this.options.rootPath + this.folderlist.join('/');
        },

        doAjaxBrowse: function (dir) {
            this.parent(dir);
            this.changeFolder(dir);
        },

        changeFolder: function (dir) {
            var folder = this.imageDir;
            this.selectedFolder = this.getFolderPath();
            folder.empty();
            var myAjax = new Request({
                url   : '',
                method: 'post',
                'data': {
                    'option': 'com_fabrik',
                    'format': 'raw',
                    'task'  : 'plugin.pluginAjax',
                    'g'     : 'element',
                    'plugin': 'image',
                    'method': 'ajax_files',
                    'folder': dir
                },

                onComplete: function (r) {
                    var newImages = eval(r);
                    newImages.each(function (opt) {
                        folder.adopt(new Element('option', {
                            'value': opt.value
                        }).appendText(opt.text));
                    });
                    this.showImage();
                }.bind(this)
            }).send();
        },

        showImage: function (e) {
            if (this.imageDir) {
                if (this.imageDir.options.length === 0) {
                    this.image.src = '';
                    this.selectedImage = '';
                } else {
                    this.selectedImage = this.imageDir.get('value');
                    this.image.src = Fabrik.liveSite + this.selectedFolder + '/' + this.selectedImage;
                }
                //this.hiddenField.value =  this.get('value');//this.selectedImage;
                this.hiddenField.value = this.getValue();
            }
        },

        getValue: function () {
            return this.folderlist.join('/') + '/' + this.selectedImage;// this.hiddenField.value;
        },

        update: function (val) {
            if (!this.hiddenField) {
                var el = this.element.getParent('.fabrikElement');
                this.hiddenField = el.getElement('.folderpath');
            }
            if (this.hiddenField) {
                this.hiddenField.value = val;
            }
            if (val !== '') {
                this.image.src = Fabrik.liveSite + '/' + val;
                this.image.alt = val;
            }
            else {
                this.image.src = '';
                this.image.alt = '';
            }
        }

    });

    return window.FbImage;
});