/*! Fabrik */

define(["jquery","fab/element"],function(a,b){return window.FbDisplay=new Class({Extends:b,initialize:function(a,b){this.parent(a,b)},update:function(a){this.getElement()&&(this.element.innerHTML=a)}}),window.FbDisplay});