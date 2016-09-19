/**
 * Thumbs Element
 *
 * @copyright: Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

define(['jquery', 'fab/element'], function (jQuery, FbElement) {
    window.FbThumbs = new Class({
        Extends   : FbElement,
        initialize: function (element, options, thumb) {
            this.field = document.id(element);
            this.parent(element, options);
            this.thumb = thumb;
            this.spinner = new Spinner(this.getContainer());

            if (Fabrik.bootstrapped) {
                this.setupj3();
            } else {
                this.thumbup = document.id('thumbup');
                this.thumbdown = document.id('thumbdown');
                if (this.options.canUse) {
                    this.imagepath = Fabrik.liveSite + 'plugins/fabrik_element/thumbs/images/';

                    this.thumbup.addEvent('mouseover', function (e) {
                        this.thumbup.setStyle('cursor', 'pointer');
                        this.thumbup.src = this.imagepath + 'thumb_up_in.gif';
                    }.bind(this));

                    this.thumbdown.addEvent('mouseover', function (e) {
                        this.thumbdown.setStyle('cursor', 'pointer');
                        this.thumbdown.src = this.imagepath + 'thumb_down_in.gif';
                    }.bind(this));

                    this.thumbup.addEvent('mouseout', function (e) {
                        this.thumbup.setStyle('cursor', '');
                        if (this.options.myThumb === 'up') {
                            this.thumbup.src = this.imagepath + 'thumb_up_in.gif';
                        } else {
                            this.thumbup.src = this.imagepath + 'thumb_up_out.gif';
                        }
                    }.bind(this));
                    this.thumbdown.addEvent('mouseout', function (e) {
                        this.thumbdown.setStyle('cursor', '');
                        if (this.options.myThumb === 'down') {
                            this.thumbdown.src = this.imagepath + 'thumb_down_in.gif';
                        } else {
                            this.thumbdown.src = this.imagepath + 'thumb_down_out.gif';
                        }
                    }.bind(this));

                    this.thumbup.addEvent('click', function (e) {
                        this.doAjax('up');
                    }.bind(this));
                    this.thumbdown.addEvent('click', function (e) {
                        this.doAjax('down');
                    }.bind(this));
                }
                else {
                    this.thumbup.addEvent('click', function (e) {
                        e.stop();
                        this.doNoAccess();
                    }.bind(this));
                    this.thumbdown.addEvent('click', function (e) {
                        e.stop();
                        this.doNoAccess();
                    }.bind(this));
                }
            }
        },

        setupj3: function () {
            var c = this.getContainer();
            var up = c.getElement('button.thumb-up');
            var down = c.getElement('button.thumb-down');

            up.addEvent('click', function (e) {
                e.stop();
                if (this.options.canUse) {
                    var add = up.hasClass('btn-success') ? false : true;
                    this.doAjax('up', add);
                    if (!add) {
                        up.removeClass('btn-success');
                    } else {
                        up.addClass('btn-success');
                        if (typeOf(down) !== 'null') {
                            down.removeClass('btn-danger');
                        }
                    }
                }
                else {
                    this.doNoAccess();
                }
            }.bind(this));

            if (typeOf(down) !== 'null') {
                down.addEvent('click', function (e) {
                    e.stop();
                    if (this.options.canUse) {
                        var add = down.hasClass('btn-danger') ? false : true;
                        this.doAjax('down', add);
                        if (!add) {
                            down.removeClass('btn-danger');
                        } else {
                            down.addClass('btn-danger');
                            up.removeClass('btn-success');
                        }
                    }
                    else {
                        this.doNoAccess();
                    }
                }.bind(this));
            }
        },

        doAjax: function (th, add) {
            add = add ? true : false;
            if (this.options.editable === false) {
                this.spinner.show();
                var data = {
                    'option'     : 'com_fabrik',
                    'format'     : 'raw',
                    'task'       : 'plugin.pluginAjax',
                    'plugin'     : 'thumbs',
                    'method'     : 'ajax_rate',
                    'g'          : 'element',
                    'element_id' : this.options.elid,
                    'row_id'     : this.options.row_id,
                    'elementname': this.options.elid,
                    'userid'     : this.options.userid,
                    'thumb'      : th,
                    'listid'     : this.options.listid,
                    'formid'     : this.options.formid,
                    'add'        : add
                };

                new Request({
                    url       : '',
                    'data'    : data,
                    onComplete: function (r) {
                        r = JSON.decode(r);
                        this.spinner.hide();
                        if (r.error) {
                            console.log(r.error);
                        } else {
                            if (r !== '') {
                                if (Fabrik.bootstrapped) {
                                    var c = this.getContainer();
                                    c.getElement('button.thumb-up .thumb-count').set('text', r[0]);
                                    if (typeOf(c.getElement('button.thumb-down')) !== 'null') {
                                        c.getElement('button.thumb-down .thumb-count').set('text', r[1]);
                                    }
                                } else {
                                    var count_thumbup = document.id('count_thumbup');
                                    var count_thumbdown = document.id('count_thumbdown');
                                    var thumbup = document.id('thumbup');
                                    var thumbdown = document.id('thumbdown');
                                    count_thumbup.set('html', r[0]);
                                    count_thumbdown.set('html', r[1]);
                                    // Well since the element can't be rendered in
                                    // form view I guess this isn't really needed
                                    this.getContainer().getElement('.' + this.field.id).value =
                                        r[0].toFloat() - r[1].toFloat();
                                    if (r[0] === '1') {
                                        thumbup.src = this.imagepath + 'thumb_up_in.gif';
                                        thumbdown.src = this.imagepath + 'thumb_down_out.gif';
                                    } else {
                                        thumbup.src = this.imagepath + 'thumb_up_out.gif';
                                        thumbdown.src = this.imagepath + 'thumb_down_in.gif';
                                    }
                                }
                            }
                        }
                    }.bind(this)
                }).send();
            }
        },

        doNoAccess: function () {
            if (this.options.noAccessMsg !== '') {
                window.alert(this.options.noAccessMsg);
            }
        }

    });

    return window.FbThumbs;
});