/*! Fabrik */
var FbLike=new Class({Extends:FbElement,initialize:function(a,b){this.setPlugin("fblike"),this.parent(a,b),FB.Event.subscribe("edge.create",function(){this.like("+")}.bind(this)),FB.Event.subscribe("edge.remove",function(){this.like("-")}.bind(this))},like:function(a){var b={option:"com_fabrik",format:"raw",task:"plugin.pluginAjax",plugin:"fblike",method:"ajax_rate",g:"element",element_id:this.options.elid,row_id:this.options.row_id,elementname:this.options.elid,listid:this.options.listid,direction:a};new Request({url:"",data:b,onComplete:function(a){a=JSON.decode(a),a.error&&console.log(a.error)}.bind(this)}).send()}});