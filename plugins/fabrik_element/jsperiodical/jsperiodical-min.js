/*! Fabrik */
FbJSPeriodical=new Class({Extends:FbElement,options:{code:"",period:1e3},initialize:function(element,options){this.setPlugin("fabrikPeriodical"),this.parent(element,options);var periodical;this.fx=function(){eval(this.options.code)}.bind(this),this.fx(),periodical=this.fx.periodical(this.options.period,this)}});