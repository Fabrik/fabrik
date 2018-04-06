/*! Fabrik */

define(["jquery","fab/list-plugin"],function(i,n){return new Class({Extends:n,initialize:function(i){this.parent(i)},buttonAction:function(){this.list.submit("list.doPlugin")}})});