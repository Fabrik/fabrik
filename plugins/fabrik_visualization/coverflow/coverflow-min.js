/*! Fabrik */

var FbVisCoverflow=new Class({Implements:[Options],options:{},initialize:function(json,options){json=eval(json),this.setOptions(options),widget=Runway.createOrShowInstaller(document.getElementById("coverflow"),{onReady:function(){widget.setRecords(json)}})}});