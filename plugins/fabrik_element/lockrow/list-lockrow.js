/**
 * @package Joomla!
 * @subpackage JavaScript
 * @since 1.5
 */

define(['jquery'], function (jQuery) {
    var FbLockrowList = new Class({

        getOptions: function () {
            return {
                'livesite': '',
                'locked_img': '',
                'unlocked_img': '',
                'userid': ''
            };
        },

        initialize: function (id, options) {
            this.setOptions(this.getOptions(), options);
            this.id = id;

            // preload image
            this.spinner = new Asset.image(this.options.livesite + 'media/com_fabrik/images/ajax-loader.gif', {
                'alt': 'loading',
                'class': 'ajax-loader'
            });

            Fabrik.addEvent('fabrik.list.updaterows', function () {
                this.makeEvents();
            }.bind(this));

            this.makeEvents();
        },

        makeEvents: function () {
            this.col = $$('.' + this.id);
            this.col.each(function (tr) {
                var row = tr.findClassUp('fabrik_row');
                if (row !== false) {
                    var rowid = row.id.replace('list_' + this.options.listRef + '_row_', '');
                    var all_locked = tr.getElements('.fabrikElement_lockrow_locked');
                    var all_unlocked = tr.getElements('.fabrikElement_lockrow_unlocked');
                    all_locked.each(function (locked) {
                        if (this.options.can_unlocks[rowid]) {
                            jQuery(locked).find('i').on('mouseover', function (e) {
                                //locked.src = this.options.imagepath + "key.png";
                                e.target.removeClass(this.options.lockIcon).addClass(this.options.keyIcon);
                            }.bind(this));
                            jQuery(locked).find('i').on('mouseout', function (e) {
                                //locked.src = this.options.imagepath + "locked.png";
                                e.target.removeClass(this.options.keyIcon).addClass(this.options.lockIcon);
                            }.bind(this));
                            jQuery(locked).find('i').on('click', function (e) {
                                this.doAjaxUnlock(locked);
                            }.bind(this));
                        }
                    }.bind(this));

                    all_unlocked.each(function (unlocked) {
                        if (this.options.can_locks[rowid]) {
                            jQuery(unlocked).find('i').on('mouseover', function (e) {
                                //unlocked.src = this.options.imagepath + "key.png";
                                e.target.removeClass(this.options.lockIcon).addClass(this.options.keyIcon);
                            }.bind(this));
                            jQuery(unlocked).find('i').on('mouseout', function (e) {
                                e.target.removeClass(this.options.keyIcon).addClass(this.options.unlockIcon);
                            }.bind(this));
                            jQuery(unlocked).find('i').on('click', function (e) {
                                this.doAjaxLock(unlocked);
                            }.bind(this));
                        }
                    }.bind(this));
                }
            }.bind(this));
        },

        doAjaxUnlock: function (locked) {
            var row = locked.findClassUp('fabrik_row');
            var rowid = row.id.replace('list_' + this.options.listRef + '_row_', '');

            /*
            var data = {
                'row_id': rowid,
                'element_id': this.options.elid,
                'userid': this.options.userid
            };
            var url = this.options.livesite +
                'index.php?option=com_fabrik&format=raw&controller=plugin&task=pluginAjax&g=element' +
                '&plugin=fabriklockrow&method=ajax_unlock';
*/
            var data = {
                'option'     : 'com_fabrik',
                'format'     : 'raw',
                'task'       : 'plugin.pluginAjax',
                'plugin'     : 'lockrow',
                'g'          : 'element',
                'method'     : 'ajax_unlock',
                'formid'     : this.options.formid,
                'element_id' : this.options.elid,
                'row_id'     : rowid,
                'elementname': this.options.elid,
                'userid'     : this.options.userid
            };

            new Request({
                'url': '',
                'data': data,
                onComplete: function (r) {
                    r = JSON.parse(r);
                    if (r.status === 'unlocked') {
                        this.options.row_locks[rowid] = false;
                        jQuery(locked).find('i').removeClass(this.options.keyIcon).addClass(this.options.unlockIcon);
                        jQuery(locked).find('i').off('mouseover');
                        jQuery(locked).find('i').off('mouseout');
                        jQuery(locked).find('i').off('click');
                        //locked.src = this.options.imagepath + "unlocked.png";
                        if (this.options.can_locks[rowid]) {
                            jQuery(locked).find('i').on('mouseover', function (e) {
                                //unlocked.src = this.options.imagepath + "key.png";
                                e.target.removeClass(this.options.unlockIcon).addClass(this.options.keyIcon);
                            }.bind(this));
                            jQuery(locked).find('i').on('mouseout', function (e) {
                                e.target.removeClass(this.options.keyIcon).addClass(this.options.unlockIcon);
                            }.bind(this));
                            jQuery(locked).find('i').on('click', function (e) {
                                this.doAjaxLock(locked);
                            }.bind(this));
                        }
                    }
                }.bind(this)
        }).send();
        },

        doAjaxLock: function (unlocked) {
            var row = unlocked.findClassUp('fabrik_row');
            var rowid = row.id.replace('list_' + this.options.listRef + '_row_', '');

            /*
            var data = {
                'row_id': rowid,
                'element_id': this.options.elid,
                'userid': this.options.userid
            };
            var url = this.options.livesite +
                'index.php?option=com_fabrik&format=raw&controller=plugin&task=pluginAjax&g=element' +
                '&plugin=fabriklockrow&method=ajax_unlock';
*/
            var data = {
                'option'     : 'com_fabrik',
                'format'     : 'raw',
                'task'       : 'plugin.pluginAjax',
                'plugin'     : 'lockrow',
                'g'          : 'element',
                'method'     : 'ajax_lock',
                'formid'     : this.options.formid,
                'element_id' : this.options.elid,
                'row_id'     : rowid,
                'elementname': this.options.elid,
                'userid'     : this.options.userid
            };

            new Request({
                'url': '',
                'data': data,
                onComplete: function (r) {
                    r = JSON.parse(r);
                    if (r.status === 'locked') {
                        this.options.row_locks[rowid] = true;
                        jQuery(unlocked).find('i').removeClass(this.options.keyIcon).addClass(this.options.lockIcon);
                        jQuery(unlocked).find('i').off('mouseover');
                        jQuery(unlocked).find('i').off('mouseout');
                        jQuery(unlocked).find('i').off('click');
                        //locked.src = this.options.imagepath + "unlocked.png";
                        if (this.options.can_unlocks[rowid]) {
                            jQuery(unlocked).find('i').on('mouseover', function (e) {
                                //unlocked.src = this.options.imagepath + "key.png";
                                e.target.removeClass(this.options.lockIcon).addClass(this.options.keyIcon);
                            }.bind(this));
                            jQuery(unlocked).find('i').on('mouseout', function (e) {
                                e.target.removeClass(this.options.keyIcon).addClass(this.options.lockIcon);
                            }.bind(this));
                            jQuery(unlocked).find('i').on('click', function (e) {
                                this.doAjaxUnlock(unlocked);
                            }.bind(this));
                        }
                    }
                }.bind(this)
            }).send();
        }


    });


    FbLockrowList.implement(new Events);
    FbLockrowList.implement(new Options);

    return FbLockrowList;
});
