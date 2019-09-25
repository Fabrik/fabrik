/**
 * Created by rob on 21/03/2016.
 */
define(['jquery'], function (jQuery) {
    /**
     * observe keyboard short cuts
     */

    var FbListKeys = new Class({
        Binds: [],

        initialize: function (list) {
            window.addEvent('keyup', function (e) {
                if (e.alt) {
                    switch (e.key) {
                        case Joomla.JText._('COM_FABRIK_LIST_SHORTCUTS_ADD'):
                            var a = list.form.getElement('.addRecord');
                            if (list.options.ajax) {
                                a.fireEvent('click');
                            }
                            if (a.getElement('a')) {
                                list.options.ajax ? a.getElement('a').fireEvent('click') : document.location = a.getElement('a').get('href');
                            } else {
                                if (!list.options.ajax) {
                                    document.location = a.get('href');
                                }
                            }
                            break;

                        case Joomla.JText._('COM_FABRIK_LIST_SHORTCUTS_EDIT'):
                            fconsole('edit');
                            break;
                        case Joomla.JText._('COM_FABRIK_LIST_SHORTCUTS_DELETE'):
                            fconsole('delete');
                            break;
                        case Joomla.JText._('COM_FABRIK_LIST_SHORTCUTS_FILTER'):
                            fconsole('filter');
                            break;
                    }
                }
            }.bind(this));
        }
    });

    return FbListKeys;
});