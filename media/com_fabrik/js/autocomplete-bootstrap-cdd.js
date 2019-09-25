/**
 * Created by rob on 18/03/2016.
 */
define(['jquery', 'fab/autocomplete-bootstrap', 'fab/fabrik'],

    function (jQuery, AutoComplete, Fabrik) {
        var FabCddAutocomplete = new Class({

            Binds: [],

            Extends: AutoComplete,

            search: function (e) {
                var key, msg;
                var v = this.getInputElement().get('value');
                if (v === '') {
                    this.element.value = '';
                }
                if (v !== this.searchText && v !== '') {
                    var observer = document.id(this.options.observerid);
                    if (typeOf(observer) !== 'null') {
                        if (this.options.formRef) {
                            observer = Fabrik.getBlock(this.options.formRef)
                                .formElements[this.options.observerid];
                        }
                        key = observer.get('value') + '.' + v;
                    } else {
                        this.parent(e);
                        return;
                    }
                    this.positionMenu();
                    if (this.cache[key]) {
                        if (this.populateMenu(this.cache[key])) {
                            this.openMenu();
                        }
                    } else {
                        if (this.ajax) {
                            this.closeMenu();
                            this.ajax.cancel();
                        }
                        this.ajax = new Request({
                            url      : this.options.url,
                            data     : {
                                value                     : v,
                                fabrik_cascade_ajax_update: 1,
                                v                         : observer.get('value')
                            },
                            onRequest: function () {
                                Fabrik.loader.start(this.getInputElement());
                            }.bind(this),
                            onCancel : function () {
                                Fabrik.loader.stop(this.getInputElement());
                            }.bind(this),
                            onSuccess: function (e) {
                                Fabrik.loader.stop(this.getInputElement());
                                this.ajax = null;
                                this.completeAjax(e);
                            }.bind(this),
                            onFailure: function (xhr) {
                                Fabrik.loader.stop(this.getInputElement());
                                this.ajax = null;
                                fconsole('Fabrik autocomplete: Ajax failure: Code ' + xhr.status + ': ' + xhr.statusText);
                                var elModel = Fabrik.getBlock(this.options.formRef)
                                    .formElements.get(this.element.id);
                                msg = Joomla.JText._('COM_FABRIK_AUTOCOMPLETE_AJAX_ERROR');
                                elModel.setErrorMessage(msg, 'fabrikError', true);
                            }.bind(this)
                        }).send();
                    }
                }
                this.searchText = v;
            }
        });
        return FabCddAutocomplete;
    });