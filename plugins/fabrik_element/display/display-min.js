/*! Fabrik */

define(["jquery","fab/element"],function(e,n){return window.FbDisplay=new Class({Extends:n,initialize:function(e,n){this.parent(e,n)},update:function(e){this.getElement()&&(this.element.innerHTML=e)}}),window.FbDisplay});