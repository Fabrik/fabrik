var FbGeoext=new Class({Extends:FbElement,options:{drawtype:"",bg_layers:"",layers:"",drawablemap:true,default_loc:"",proxyurl:"",geom:"",bounds:""},initializeAtt:function(a){drawtype=this.options.drawtype;bg=this.options.bg_layers;layers=this.options.layers;geom=this.options.geom;proxyurl=this.options.proxyurl;saveStrategy="";elementGeoext=a;drawablemap=this.options.drawablemap;default_loc=this.options.default_loc},initialize:function(b,a){this.parent(b,a);this.initializeAtt(b);this.makeGeoExtMap()},makeGeoExtMap:function(){Ext.onReady(function(){OpenLayers.Lang.setCode("fr");function extractUrlParams(codeParam){var sous_chaine=location.search.substring(1).split("&");var valeur="";for(var i=0;i<sous_chaine.length;i++){if(sous_chaine[i].split("=")[0]==codeParam){valeur=sous_chaine[i].split("=")[1];break}}return valeur}function getMapLonLat(lng,lat){return new OpenLayers.LonLat(lng,lat).transform(new OpenLayers.Projection("EPSG:4326"),map.getProjectionObject())}var myElem=elementGeoext;var geocoder=new google.maps.Geocoder();maxBounds=new google.maps.LatLngBounds(new google.maps.LatLng(42.12,-2),new google.maps.LatLng(43.9,3.8));var controlZoomBox=new OpenLayers.Control.ZoomBox({CLASS_NAME:"zoomIn"});var controlPan=new OpenLayers.Control.PanZoomBar();var ctrlLayerSwitcher=new OpenLayers.Control.LayerSwitcher();var controlAttribution=new OpenLayers.Control.Attribution();var options={controls:[controlZoomBox,controlPan,ctrlLayerSwitcher,controlAttribution],projection:"EPSG:3857",maxExtent:new OpenLayers.Bounds(-20037508,-20037508,20037508,20037508),resolutions:[156543.033928,78271.5169639999,39135.7584820001,19567.8792409999,9783.93962049996,4891.96981024998,2445.98490512499,1222.99245256249,611.49622628138,305.748113140558,152.874056570411,76.4370282850732,38.2185141425366,19.1092570712683,9.55462853563415,4.77731426794937,2.38865713397468,1.19432856685505,0.597164283559817],units:"m"};var map=new OpenLayers.Map(options);var controls=map.getControlsByClass("OpenLayers.Control.Navigation");var myStyle=OpenLayers.Util.extend({},OpenLayers.Feature.Vector.style["default"]);myStyle.fillColor="#ff0000";myStyle.strokeColor="#ff0000";var vector_d=new OpenLayers.Layer.Vector("Objet",{style:myStyle});var overlays=JSON.parse(layers);if(overlays){addWmsLayersToMap(map,overlays.fb_ge_layers_url,overlays.fb_ge_layers_name,overlays.fb_ge_layers_title)}var bgLayers=JSON.parse(bg);addBGLayersToMap(map,bgLayers.fb_ge_bg_layers_code);map.addLayers([vector_d]);var bounds=new OpenLayers.Bounds();if((geom)&&(geom!=="")){vector_d.addFeatures(new OpenLayers.Format.WKT().read(geom));bounds=vector_d.getDataExtent()}else{if(default_loc==""){default_loc="France"}geocoder.geocode({address:default_loc},function(results,status){bounds.extend(getMapLonLat(results[0].geometry.bounds.getNorthEast().lng(),results[0].geometry.bounds.getNorthEast().lat()));bounds.extend(getMapLonLat(results[0].geometry.bounds.getSouthWest().lng(),results[0].geometry.bounds.getSouthWest().lat()));map.zoomToExtent(bounds)})}Ext.QuickTips.init();var DeleteFeature=OpenLayers.Class(OpenLayers.Control,{initialize:function(layer,options){OpenLayers.Control.prototype.initialize.apply(this,[options]);this.layer=layer;this.handler=new OpenLayers.Handler.Feature(this,layer,{click:this.clickFeature})},clickFeature:function(feature){if(feature.fid==undefined){this.layer.destroyFeatures([feature])}else{feature.state=OpenLayers.State.DELETE;this.layer.events.triggerEvent("afterfeaturemodified",{feature:feature});feature.renderIntent="select";this.layer.drawFeature(feature)}},setMap:function(map){this.handler.setMap(map);OpenLayers.Control.prototype.setMap.apply(this,arguments)},CLASS_NAME:"OpenLayers.Control.DeleteFeature"});var save,toolbarItems=[],action,actions={};action=new GeoExt.Action({text:Joomla.JText._("PLG_ELEMENT_GEOEXT_TOOL_NAVIGATE"),control:new OpenLayers.Control.Navigation({zoomWheelEnabled:false}),map:map,toggleGroup:"draw",allowDepress:false,pressed:true,tooltip:Joomla.JText._("PLG_ELEMENT_GEOEXT_TOOL_NAVIGATE_DESC"),group:"draw",checked:true});actions.nav=action;toolbarItems.push(action);toolbarItems.push("-");if(drawablemap){if(drawtype==="polygon"){action=new GeoExt.Action({text:Joomla.JText._("PLG_ELEMENT_GEOEXT_TOOL_DRAW"),control:new OpenLayers.Control.DrawFeature(vector_d,OpenLayers.Handler.Polygon,{handlerOptions:{style:myStyle}}),map:map,toggleGroup:"draw",allowDepress:false,tooltip:Joomla.JText._("PLG_ELEMENT_GEOEXT_TOOL_DRAW_POLY_DESC"),group:"draw"});actions.draw_poly=action;toolbarItems.push(action);toolbarItems.push("-")}if(drawtype==="point"){action=new GeoExt.Action({text:Joomla.JText._("PLG_ELEMENT_GEOEXT_TOOL_DRAW"),control:new OpenLayers.Control.DrawFeature(vector_d,OpenLayers.Handler.Point),map:map,toggleGroup:"draw",allowDepress:false,tooltip:Joomla.JText._("PLG_ELEMENT_GEOEXT_TOOL_DRAW_POINT_DESC"),group:"draw"});actions.draw_point=action;toolbarItems.push(action);toolbarItems.push("-")}if(drawtype==="line"){action=new GeoExt.Action({text:Joomla.JText._("PLG_ELEMENT_GEOEXT_TOOL_DRAW"),control:new OpenLayers.Control.DrawFeature(vector_d,OpenLayers.Handler.Path),map:map,toggleGroup:Joomla.JText._("PLG_ELEMENT_GEOEXT_TOOL_DRAW"),allowDepress:false,tooltip:Joomla.JText._("PLG_ELEMENT_GEOEXT_TOOL_DRAW_LINE_DESC"),group:"draw"});actions.draw_line=action;toolbarItems.push(action);toolbarItems.push("-")}action=new GeoExt.Action({text:Joomla.JText._("PLG_ELEMENT_GEOEXT_TOOL_EDIT"),control:new OpenLayers.Control.ModifyFeature(vector_d),map:map,toggleGroup:"draw",allowDepress:false,tooltip:Joomla.JText._("PLG_ELEMENT_GEOEXT_TOOL_EDIT_DESC"),group:"draw"});actions.modify=action;toolbarItems.push(action);toolbarItems.push("-");action=new GeoExt.Action({text:Joomla.JText._("PLG_ELEMENT_GEOEXT_TOOL_DELETE"),control:new DeleteFeature(vector_d,{title:"Effacer"}),map:map,toggleGroup:"draw",allowDepress:false,tooltip:Joomla.JText._("PLG_ELEMENT_GEOEXT_TOOL_DELETE_DESC"),group:"draw"});actions.effacer=action;toolbarItems.push(action);toolbarItems.push("-")}action=new GeoExt.Action({text:Joomla.JText._("PLG_ELEMENT_GEOEXT_TOOL_DISTANCE"),control:new OpenLayers.Control.Measure(OpenLayers.Handler.Path,{eventListeners:{measure:function(evt){popup=new GeoExt.Popup({title:Joomla.JText._("PLG_ELEMENT_GEOEXT_TOOL_DISTANCE"),map:map,location:evt.object.map.center,width:200,html:"<div>"+evt.measure.toFixed(2)+" "+evt.units+"</div>",maximizable:false,collapsible:false});popup.show()}}}),map:map,toggleGroup:"draw",allowDepress:false,pressed:false,tooltip:Joomla.JText._("PLG_ELEMENT_GEOEXT_TOOL_DISTANCE_DESC"),group:"draw",checked:false});actions.dist=action;toolbarItems.push(action);toolbarItems.push("-");action=new GeoExt.Action({text:Joomla.JText._("PLG_ELEMENT_GEOEXT_TOOL_SURFACE"),control:new OpenLayers.Control.Measure(OpenLayers.Handler.Polygon,{eventListeners:{measure:function(evt){var ha;var units=evt.units;var measure=evt.measure;if(units=="m"){ha=measure.toFixed(3)*0.0001}else{if(units=="km"){ha=measure.toFixed(3)*100}}ha=ha.toFixed(2);popup=new GeoExt.Popup({title:Joomla.JText._("PLG_ELEMENT_GEOEXT_TOOL_SURFACE"),map:map,location:evt.object.map.center,width:200,html:"<div>"+ha+" ha</div>",maximizable:false,collapsible:false});popup.show()}}}),map:map,toggleGroup:"draw",allowDepress:false,pressed:false,tooltip:Joomla.JText._("PLG_ELEMENT_GEOEXT_TOOL_SURFACE_DESC"),group:"draw",checked:false});actions.dist=action;toolbarItems.push(action);toolbarItems.push("-");vector_d.events.on({featuresadded:onFeaturesUpdated,afterfeaturemodified:onFeaturesUpdated,featuresremoved:onFeaturesUpdated});function onFeaturesUpdated(event){this.field=document.getElementsByName(myElem);geometry=new OpenLayers.Format.WKT().write(vector_d.features);this.field[0].value=geometry}function addWmsLayersToMap(map,layersUrl,layersName,layersTitle){if(layersUrl.lenght!=0){for(var i=0;i<layersUrl.length;i++){if(layersUrl[i]!=""&&layersName[i]!=""){var title;if(layersTitle[i]!=""){title=layersTitle[i]}else{title="layer_"+i}var layer=new OpenLayers.Layer.WMS(title,layersUrl[i],{layers:layersName[i],transparent:true},{singleTile:true,opacity:1,visibility:false});map.addLayers([layer])}}}}function addBGLayersToMap(map,bg){var layers_bg=[];for(var i=0;i<bg.length;i++){layers_bg[i]=eval(bg[i])}map.addLayers(layers_bg)}if(drawablemap){var mapPanel=new GeoExt.MapPanel({renderTo:document.id(elementGeoext).getElement(".mapPanel"),height:550,maxHeight:1000,minWidth:300,maxWidth:900,map:map,extent:bounds,tbar:toolbarItems})}else{var mapPanel=new GeoExt.MapPanel({renderTo:document.id(elementGeoext).getElement(".mapPanel"),height:550,maxHeight:1000,minWidth:300,maxWidth:900,map:map,extent:bounds})}})},saveMap:function(){}});