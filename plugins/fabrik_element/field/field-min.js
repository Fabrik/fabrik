/*! Fabrik */
function geolocateLoad(){document.body?window.fireEvent("google.geolocate.loaded"):console.log("no body")}var FbField=new Class({Extends:FbElement,initialize:function(a,b){if(this.plugin="fabrikfield",this.parent(a,b),this.options.use_input_mask){if(""!==this.options.input_mask_definitions){var c=JSON.parse(this.options.input_mask_definitions);$H(c).each(function(a,b){jQuery.mask.definitions[b]=a})}jQuery("#"+a).mask(this.options.input_mask)}this.options.geocomplete&&(this.gcMade=!1,this.loadFn=function(){this.gcMade===!1&&(jQuery("#"+this.element.id).geocomplete(),this.gcMade=!0)}.bind(this),window.addEvent("google.geolocate.loaded",this.loadFn),Fabrik.loadGoogleMap(!1,"geolocateLoad"))},select:function(){var a=this.getElement();a&&this.getElement().select()},focus:function(){var a=this.getElement();a&&this.getElement().focus()},cloned:function(a){if(this.options.use_input_mask){var b=this.getElement();if(b){if(""!==this.options.input_mask_definitions){var c=JSON.parse(this.options.input_mask_definitions);$H(c).each(function(a,b){jQuery.mask.definitions[b]=a})}jQuery("#"+b.id).mask(this.options.input_mask)}}if(this.options.geocomplete){var b=this.getElement();b&&jQuery("#"+b.id).geocomplete()}this.parent(a)}});