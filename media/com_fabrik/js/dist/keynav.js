/*! Fabrik */

var KeyNav=new Class({initialize:function(){window.addEvent("keypress",function(a){switch(a.code){case 37:case 38:case 39:case 40:Fabrik.fireEvent("fabrik.keynav",[a.code,a.shift]),a.stop()}})}}),FabrikKeyNav=new KeyNav;