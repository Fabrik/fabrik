/**
 * List PHP
 *
 * @copyright: Copyright (C) 2005-2013, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */
define(['jquery', 'fab/list-plugin'], function (jQuery, FbListPlugin) {
    var FbListPhp = new Class({
        Extends   : FbListPlugin,
        initialize: function (options) {
            this.parent(options);
        },

        buttonAction: function (event) {
            var additional_data = this.options.additional_data,
                hdata = $H({}),
                rowIndexes = [],
                ok;
            this.list.getForm().getElements('input[name^=ids]').each(function (c) {
                if (c.checked) {
                    ok = true;
                    var row_index = c.name.match(/ids\[(\d+)\]/)[1];
                    rowIndexes.push(row_index);

                    // Funky custom stuff from Hugh - leave as it might be used somewhere in the galaxy
                    if (additional_data) {
                        if (!hdata.has(row_index)) {
                            hdata.set(row_index, $H({}));
                        }
                        hdata[row_index].rowid = c.value;
                        additional_data.split(',').each(function (elname) {
                            var cell_data = c.getParent('.fabrik_row').getElements('td.fabrik_row___' + elname)[0].innerHTML;
                            hdata[row_index][elname] = cell_data;
                        });
                    }
                }
            });

            var chxs = this.list.getForm().getElements('input[name^=ids]').filter(function (i) {
                return i.checked;
            });

            var ids = chxs.map(function (chx) {
                return chx.get('value');
            });

            // Build rows object for ease of access to selected rows' data
            var rows = {};
            chxs.each(function (chx) {
                var id = chx.get('value');
                rows[id] = this.list.getRow(id);
            }.bind(this));

            if (additional_data) {
                this.list.getForm().getElement('input[name=fabrik_listplugin_options]').value = Json.encode(hdata);
            }

            if (this.options.js_code !== '') {
                var result = eval('(function() {' + this.options.js_code + '}())');

                if (result === false) {
                    return;
                }
            }

            this.list.submit('list.doPlugin');
        }
    });
    return FbListPhp;
});
