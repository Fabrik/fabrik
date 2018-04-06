/*! Fabrik */

define(["jquery","element/radiobutton/radiobutton"],function(n,e){return window.FbYesno=new Class({Extends:e,initialize:function(n,e){this.setPlugin("fabrikyesno"),this.parent(n,e)},checkEventAction:function(n){return"change"===n&&(n="click"),n},getChangeEvent:function(){return this.options.changeEvent}}),window.FbYesno});