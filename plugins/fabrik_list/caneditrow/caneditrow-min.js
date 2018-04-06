/*! Fabrik */

define(["jquery","fab/list-plugin","fab/fabrik"],function(n,i,t){return new Class({Extends:i,initialize:function(n){this.parent(n),t.addEvent("onCanEditRow",function(n,i){this.onCanEditRow(n,i)}.bind(this))},onCanEditRow:function(n,i){i=i[0],n.result=this.options.acl[i]}})});