/*! Fabrik */

var KeyNav=new Class({initialize:function(){window.addEvent("keypress",function(e){switch(e.code){case 37:case 38:case 39:case 40:Fabrik.fireEvent("fabrik.keynav",[e.code,e.shift]),e.stop()}})}}),FabrikKeyNav=new KeyNav;