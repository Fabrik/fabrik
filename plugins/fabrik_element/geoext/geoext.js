var FbGeoext = new Class({
    Extends: FbElement,
    options: {
        'drawtype': '',
        'bg_layers': '',
        'layers': '',
        'drawablemap': true,
        'default_loc': '',
        'proxyurl': '',
        'geom': '',
        'bounds': ''
    },
    
    initializeAtt: function(element) {
        //type de dessin (point/polygone)
        drawtype = this.options.drawtype;
        bg = this.options.bg_layers;
        layers = this.options.layers;
        geom = this.options.geom;
        proxyurl = this.options.proxyurl;

        saveStrategy = '';
        elementGeoext = element;
        
        //consultation ou saisie de la carte dans le formulaire.
        drawablemap = this.options.drawablemap;

        

        default_loc = this.options.default_loc;

   },
    //surchage du initialize de la super classe pour lancement du script.
    initialize: function(element, options) {
        //constructeur parent
        this.parent(element, options);
        //initialisation des attributs
        this.initializeAtt(element);
        //Construction de la carte GeoExt
        this.makeGeoExtMap();
    },
    /**
     * Cr�ation de la carte GeoExt pour affichage dans un formulaire de saisie.
     * */
    makeGeoExtMap: function() {
        Ext.onReady(function() {
        OpenLayers.Lang.setCode("fr");
            /**
             * Methode utilitaire pour parser l'URL avec r�cup�ration de la valeur de la chaine pass�e 
             * en param�tre
             * @param codeParam chaine � r�cup�rer
             * @returns valeur de la chaine ou "" sinon 
             * */

            function extractUrlParams(codeParam) {
                var sous_chaine = location.search.substring(1).split('&');
                var valeur = "";
                //parcours du tableau des cl�s/valeurs
                for (var i = 0; i < sous_chaine.length; i++) {
                    if (sous_chaine[i].split('=')[0] == codeParam)
                    {
                        valeur = sous_chaine[i].split('=')[1];
                        break;
                    }
                }
                return valeur;
            }
            
            /**
             * Transforme les lon et lat 900913 en 4326.
             * */
            function getMapLonLat(lng, lat) {
                return new OpenLayers.LonLat(lng, lat).transform(
                        new OpenLayers.Projection("EPSG:4326"), map.getProjectionObject());
            }

            var myElem = elementGeoext;
            
            var geocoder = new google.maps.Geocoder();
            maxBounds = new google.maps.LatLngBounds(new google.maps.LatLng(42.12, -2.0), new google.maps.LatLng(43.9, 3.8));

            var controlZoomBox = new OpenLayers.Control.ZoomBox({CLASS_NAME: 'zoomIn'});

            //affiche la palette de navigation � gauche de la fenetre de dessin. 
            var controlPan = new OpenLayers.Control.PanZoomBar();

            //palette de controle des couches.
            var ctrlLayerSwitcher = new OpenLayers.Control.LayerSwitcher();

            var controlAttribution = new OpenLayers.Control.Attribution();

            var options = {
                controls: [controlZoomBox, controlPan, ctrlLayerSwitcher, controlAttribution],
                projection: "EPSG:3857",
                maxExtent: new OpenLayers.Bounds(-20037508, -20037508, 20037508, 20037508),
                resolutions: [156543.033928, 78271.5169639999, 39135.7584820001, 19567.8792409999, 9783.93962049996, 4891.96981024998, 2445.98490512499, 1222.99245256249, 611.49622628138, 305.748113140558, 152.874056570411, 76.4370282850732, 38.2185141425366, 19.1092570712683, 9.55462853563415, 4.77731426794937, 2.38865713397468, 1.19432856685505, 0.597164283559817],
                units: 'm'
            };

            //création de la carte en varibale gloable (sans le var..) avec en param�tres les options.
            var map = new OpenLayers.Map(options);

            var controls = map.getControlsByClass('OpenLayers.Control.Navigation');
            var myStyle = OpenLayers.Util.extend({},OpenLayers.Feature.Vector.style['default']);
            myStyle.fillColor = "#ff0000";
            myStyle.strokeColor = "#ff0000";
            
            //Couche de saisie des dessin
            var vector_d = new OpenLayers.Layer.Vector("Objet", {style: myStyle});
            
            var overlays = JSON.parse(layers);
            if (overlays){
                addWmsLayersToMap(map, overlays.fb_ge_layers_url, overlays.fb_ge_layers_name, overlays.fb_ge_layers_title);
            }
            
            var bgLayers = JSON.parse(bg);
            addBGLayersToMap(map,bgLayers.fb_ge_bg_layers_code);
            
            map.addLayers([vector_d]);
            
            var bounds = new OpenLayers.Bounds();
            if ((geom) && (geom !== "")){
                vector_d.addFeatures(new OpenLayers.Format.WKT().read(geom));
                bounds = vector_d.getDataExtent();
            }else{
                    if (default_loc == "") {
                        default_loc = 'France';
                    }
                    //cf : https://developers.google.com/maps/documentation/javascript/geocoding
                    geocoder.geocode({'address': default_loc}, function(results, status) {
                        bounds.extend(getMapLonLat(results[0].geometry.bounds.getNorthEast().lng(),results[0].geometry.bounds.getNorthEast().lat()));
                        bounds.extend(getMapLonLat(results[0].geometry.bounds.getSouthWest().lng(),results[0].geometry.bounds.getSouthWest().lat()));
                        map.zoomToExtent(bounds);
                    });
            }   
            
            //Script Geoext
            Ext.QuickTips.init();
            
            //Delete
            var DeleteFeature = OpenLayers.Class(OpenLayers.Control, {
                initialize: function(layer, options) {
                    OpenLayers.Control.prototype.initialize.apply(this, [options]);
                    this.layer = layer;
                    this.handler = new OpenLayers.Handler.Feature(
                            this, layer, {click: this.clickFeature}
                    );
                },
                clickFeature: function(feature) {
                    // si le feature n'a pas de fid, le d�truire.
                    if (feature.fid == undefined) {
                        this.layer.destroyFeatures([feature]);
                    } else {
                        feature.state = OpenLayers.State.DELETE;
                        this.layer.events.triggerEvent("afterfeaturemodified",
                                {feature: feature});
                        feature.renderIntent = "select";
                        this.layer.drawFeature(feature);
                    }
                },
                setMap: function(map) {
                    this.handler.setMap(map);
                    OpenLayers.Control.prototype.setMap.apply(this, arguments);
                },
                CLASS_NAME: "OpenLayers.Control.DeleteFeature"
            });

            //initialisation des outils
            var save, toolbarItems = [], action, actions = {};

            //element de contr�le de la navigation.	
            action = new GeoExt.Action({
                text: Joomla.JText._('PLG_ELEMENT_GEOEXT_TOOL_NAVIGATE'),
                control: new OpenLayers.Control.Navigation({'zoomWheelEnabled': false}),
                map: map,
                // options des boutons
                toggleGroup: "draw",
                allowDepress: false,
                pressed: true,
                tooltip: Joomla.JText._('PLG_ELEMENT_GEOEXT_TOOL_NAVIGATE_DESC'),
                group: "draw",
                checked: true
            });
            actions["nav"] = action;
            toolbarItems.push(action);
            toolbarItems.push("-");
            //action.map


            //Nouvelle saisie (i.e pas une consultation)
            if (drawablemap)
            {
                //element de dessin polygone 
                if (drawtype === 'polygon') {
                    action = new GeoExt.Action({
                        text: Joomla.JText._('PLG_ELEMENT_GEOEXT_TOOL_DRAW'),
                        control: new OpenLayers.Control.DrawFeature(
                                vector_d, OpenLayers.Handler.Polygon, {handlerOptions:{style:myStyle}}
                                ),
                        map: map,
                        toggleGroup: "draw",
                        allowDepress: false,
                        tooltip: Joomla.JText._('PLG_ELEMENT_GEOEXT_TOOL_DRAW_POLY_DESC'),
                        group: "draw"
                    });
                    actions["draw_poly"] = action;
                    toolbarItems.push(action);
                    toolbarItems.push("-");
                }

                //element de dessin point et nouvelle saisie (i.e pas une consultation)
                if (drawtype === 'point') {
                    action = new GeoExt.Action({
                        text: Joomla.JText._('PLG_ELEMENT_GEOEXT_TOOL_DRAW'),
                        control: new OpenLayers.Control.DrawFeature(
                                vector_d, OpenLayers.Handler.Point
                                ),
                        map: map,
                        toggleGroup: "draw",
                        allowDepress: false,
                        tooltip: Joomla.JText._('PLG_ELEMENT_GEOEXT_TOOL_DRAW_POINT_DESC'),
                        group: "draw"
                    });
                    actions["draw_point"] = action;
                    toolbarItems.push(action);
                    toolbarItems.push("-");
                }
                
                //element de dessin point et nouvelle saisie (i.e pas une consultation)
                if (drawtype === 'line') {
                    action = new GeoExt.Action({
                        text: Joomla.JText._('PLG_ELEMENT_GEOEXT_TOOL_DRAW'),
                        control: new OpenLayers.Control.DrawFeature(
                                vector_d, OpenLayers.Handler.Path
                                ),
                        map: map,
                        toggleGroup: Joomla.JText._('PLG_ELEMENT_GEOEXT_TOOL_DRAW'),
                        allowDepress: false,
                        tooltip: Joomla.JText._('PLG_ELEMENT_GEOEXT_TOOL_DRAW_LINE_DESC'),
                        group: "draw"
                    });
                    actions["draw_line"] = action;
                    toolbarItems.push(action);
                    toolbarItems.push("-");
                }

                //bouton de modification.

                action = new GeoExt.Action({
                    text: Joomla.JText._('PLG_ELEMENT_GEOEXT_TOOL_EDIT'),
                    control: new OpenLayers.Control.ModifyFeature(
                            vector_d),
                    map: map,
                    toggleGroup: "draw",
                    allowDepress: false,
                    tooltip: Joomla.JText._('PLG_ELEMENT_GEOEXT_TOOL_EDIT_DESC'),
                    group: "draw"
                });
                actions["modify"] = action;
                toolbarItems.push(action);
                toolbarItems.push("-");
               
                //bouton de suppression du motif selectionn�.
                action = new GeoExt.Action({
                    text: Joomla.JText._('PLG_ELEMENT_GEOEXT_TOOL_DELETE'),
                    control: new DeleteFeature(vector_d, {title: "Effacer"}),
                    map: map,
                    // button options
                    toggleGroup: "draw",
                    allowDepress: false,
                    tooltip: Joomla.JText._('PLG_ELEMENT_GEOEXT_TOOL_DELETE_DESC'),
                    // check item options
                    group: "draw"
                            //iconCls: 'olControlDragFeature'
                });
                actions["effacer"] = action;
                toolbarItems.push(action);
                toolbarItems.push("-");
            }

            //Outils de mesure
            action = new GeoExt.Action({
                text: Joomla.JText._('PLG_ELEMENT_GEOEXT_TOOL_DISTANCE'),
                control: new OpenLayers.Control.Measure(OpenLayers.Handler.Path, {
                    eventListeners: {
                        measure: function(evt) {
                            //alert("Distance : " + evt.measure + evt.units);
                            popup = new GeoExt.Popup({
                                title: Joomla.JText._('PLG_ELEMENT_GEOEXT_TOOL_DISTANCE'),
                                map: map,
                                location: evt.object.map.center,
                                width: 200,
                                html: "<div>" + evt.measure.toFixed(2) + " " + evt.units + "</div>",
                                maximizable: false,
                                collapsible: false
                            });
                            popup.show();
                        }
                    }
                }),
                map: map,
                // options des boutons
                toggleGroup: "draw",
                allowDepress: false,
                pressed: false,
                tooltip: Joomla.JText._('PLG_ELEMENT_GEOEXT_TOOL_DISTANCE_DESC'),
                group: "draw",
                checked: false
            });
            actions["dist"] = action;
            toolbarItems.push(action);
            toolbarItems.push("-");


            action = new GeoExt.Action({
                text: Joomla.JText._('PLG_ELEMENT_GEOEXT_TOOL_SURFACE'),
                control: new OpenLayers.Control.Measure(OpenLayers.Handler.Polygon, {
                    eventListeners: {
                        measure: function(evt) {
                            //alert("Distance : " + evt.measure + evt.units);
                            var ha;
                            var units = evt.units;
                            var measure = evt.measure;
                            if (units == "m") {
                                ha = measure.toFixed(3) * 0.0001;
                            }
                            else if (units == "km") {
                                ha = measure.toFixed(3) * 100;
                            }
                            
                            ha = ha.toFixed(2);
                            
                            popup = new GeoExt.Popup({
                                title: Joomla.JText._('PLG_ELEMENT_GEOEXT_TOOL_SURFACE'),
                                map: map,
                                location: evt.object.map.center,
                                width: 200,
                                html: "<div>" + ha + " ha</div>",
                                maximizable: false,
                                collapsible: false
                            });
                            popup.show();
                        }
                    }
                }),
                map: map,
                // options des boutons
                toggleGroup: "draw",
                allowDepress: false,
                pressed: false,
                tooltip: Joomla.JText._('PLG_ELEMENT_GEOEXT_TOOL_SURFACE_DESC'),
                group: "draw",
                checked: false
            });
            actions["dist"] = action;
            toolbarItems.push(action);
            toolbarItems.push("-");

            //Ajout d'un listener sur la couche de dessin pour récupérer la géométrie saisie
            vector_d.events.on({
                featuresadded: onFeaturesUpdated,
                afterfeaturemodified: onFeaturesUpdated,
                featuresremoved: onFeaturesUpdated
            });
               
            /** Listener sur le dessin*/
            function onFeaturesUpdated(event) {     
                //ElementGeoExt dans la page html
                this.field = document.getElementsByName(myElem);
                geometry = new OpenLayers.Format.WKT().write(vector_d.features);
                this.field[0].value = geometry;
                /*extentField = document.getElementsByName(myElem + '_extent');
                extentField.value = vector_d.getDataExtent();*/
            }
            
            /**
            * Fonction d'ajout de couches wms à partir de l'administration du composant.
            * @param map OpenLayers.Map
            * @param layersUrl, tableau des services wms 
            * @param layersName, tableau des noms des couches wms
            * @param layersTitle; tableau de titres des couches wms
            * A noter que chaque tableau a pour sa position i le trio url,nom,titre
            * */
           function addWmsLayersToMap(map, layersUrl, layersName, layersTitle) 
           {

               if (layersUrl.lenght != 0)
               {
                   for (var i = 0; i < layersUrl.length; i++)
                   {
                       if (layersUrl[i] != '' && layersName[i] != '') {
                           var title;
                           if (layersTitle[i] != '') {
                               title = layersTitle[i];
                           }
                           else {
                               title = "layer_" + i;
                           }
                           var layer = new OpenLayers.Layer.WMS(title, layersUrl[i], {layers: layersName[i], transparent: true}, {singleTile: true, opacity: 1, visibility: false});

                           //Ajout de la nouvelle couche à la carte :
                           map.addLayers([layer]);
                       }
                   }
               }
           }
           
            /**
            * Fonction d'ajout de couches de fond de plan OpenLayers à partir de l'administration du composant.
            * @param map OpenLayers.Map
            * @param bg, tableau decode javascript d'ajout de fond de plan
            * */
           function addBGLayersToMap(map, bg) 
           {
                var layers_bg =[];
                
                for (var i = 0; i < bg.length; i++)
                {
                    layers_bg[i] = eval(bg[i]);          
                }
                map.addLayers(layers_bg);
           }
           

           if (drawablemap){
                var mapPanel = new GeoExt.MapPanel({
                    renderTo: document.id(elementGeoext).getElement('.mapPanel'),
                    height: 550,
                    maxHeight: 1000,
                    minWidth: 300,
                    maxWidth: 900,
                    map: map,
                    extent:bounds,
                    tbar: toolbarItems
                });
            }
            else {
                var mapPanel = new GeoExt.MapPanel({
                    renderTo: document.id(elementGeoext).getElement('.mapPanel'),
                    height: 550,
                    maxHeight: 1000,
                    minWidth: 300,
                    maxWidth: 900,
                    map: map,
                    extent:bounds
                });
                //afficheBtnGeoextMap = 'hidden';
            }
        });

    },
    
    saveMap: function() {
        //Sauvegarde de l'image associée au feature dans le répertoire x images/brulages de Joomla

    }
});
