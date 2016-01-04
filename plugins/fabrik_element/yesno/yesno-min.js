/*! Fabrik */
FbYesno=new Class({Extends:FbRadio,initialize:function(a,b){this.setPlugin("fabrikyesno"),this.parent(a,b)},getChangeEvent:function(){return this.options.changeEvent}});