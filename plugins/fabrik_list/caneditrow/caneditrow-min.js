/*! Fabrik */

define(["jquery","fab/list-plugin","fab/fabrik"],function(a,b,c){return new Class({Extends:b,initialize:function(a){this.parent(a),c.addEvent("onCanEditRow",function(a,b){this.onCanEditRow(a,b)}.bind(this))},onCanEditRow:function(a,b){b=b[0],a.result=this.options.acl[b]}})});