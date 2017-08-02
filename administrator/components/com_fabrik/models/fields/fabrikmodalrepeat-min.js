/*! Fabrik */

"use strict";var FabrikModalRepeat=new Class({options:{j3:!0},initialize:function(a,b,c,d){this.names=b,this.field=c,this.content=!1,this.setup=!1,this.elid=a,this.win={},this.el={},this.field={},this.options=Object.append(this.options,d),this.ready()?this.setUp():this.timer=this.testReady.periodical(500,this)},ready:function(){return"null"!==typeOf(document.id(this.elid))},testReady:function(){this.ready()&&(this.timer&&clearInterval(this.timer),this.setUp())},setUp:function(){this.button=document.id(this.elid+"_button"),this.mask=new Mask(document.body,{style:{"background-color":"#000",opacity:.4,"z-index":9998}}),document.addEvent("click:relay(*[data-modal="+this.elid+"])",function(a,b){a.preventDefault();var c,d=b.getNext("input").id,e=b.getParent("li");this.field[d]=b.getNext("input"),e||(e=b.getParent("div.control-group")),this.origContainer=e,c=e.getElement("table"),"null"!==typeOf(c)&&(this.el[d]=c),this.openWindow(d)}.bind(this))},openWindow:function(a){var b=!1;this.win[a]||(b=!0,this.makeTarget(a)),this.el[a].inject(this.win[a],"top"),this.el[a].show(),this.win[a]&&!b||this.makeWin(a),this.win[a].show(),this.win[a].position(),this.resizeWin(!0,a),this.win[a].position(),this.mask.show()},makeTarget:function(a){this.win[a]=new Element("div",{"data-modal-content":a,styles:{padding:"5px","background-color":"#fff",display:"none","z-index":9999}}).inject(document.body)},makeWin:function(a){var b=new Element("button.btn.button.btn-primary").set("text","close");b.addEvent("click",function(b){b.stop(),this.store(a),this.el[a].hide(),this.el[a].inject(this.origContainer),this.close()}.bind(this));var c=new Element("div.controls.form-actions",{styles:{"text-align":"right","margin-bottom":0}}).adopt(b);this.win[a].adopt(c),this.win[a].position(),this.content=this.el[a],this.build(a),this.watchButtons(this.win[a],a)},resizeWin:function(a,b){Object.each(this.win,function(b,c){var d=this.el[c].getDimensions(!0),e=b.getDimensions(!0);if(b.setStyles({width:d.x+"px"}),"undefined"!=typeof Fabrik&&!Fabrik.bootstrapped){var f=a?e.y:d.y+30;b.setStyle("height",f+"px")}}.bind(this))},close:function(){Object.each(this.win,function(a,b){a.hide()}),this.mask.hide()},_getRadioValues:function(a){var b,c=[];return this.getTrs(a).each(function(a){var d=(b=a.getElement("input[type=radio]:checked"))?b.get("value"):"";c.push(d)}),c},_setRadioValues:function(a,b){var c;this.getTrs(b).each(function(b,d){(c=b.getElement("input[type=radio][value="+a[d]+"]"))&&(c.checked="checked")})},addRow:function(a,b){var c=this._getRadioValues(a),d=b.getParent("table").getElement("tbody"),e=this.tmpl.clone(!0,!0);e.inject(d),this.stripe(a),this.fixUniqueAttributes(b,e),this._setRadioValues(c,a),this.resetChosen(e),this.resizeWin(!1,a)},fixUniqueAttributes:function(a,b){var c=a.getParent("table").getElements("tr").length-1;b.getElements("*[name]").each(function(a){a.name+="-"+c}),b.getElements("*[id]").each(function(a){a.id+="-"+c}),b.getElements("label[for]").each(function(a){a.label+="-"+c})},watchButtons:function(a,b){var c;a.addEvent("click:relay(a.add)",function(d){(c=this.findTr(d))&&this.addRow(b,c),a.position(),d.stop()}.bind(this)),a.addEvent("click:relay(a.remove)",function(d){(c=this.findTr(d))&&c.dispose(),this.resizeWin(!1,b),a.position(),d.stop()}.bind(this))},resetChosen:function(a){this.options.j3&&jQuery&&"null"!==typeOf(jQuery("select").chosen)&&(a.getElements("select").removeClass("chzn-done").show(),a.getElements("select").each(function(a){a.id=a.id+"_"+(1e7*Math.random()).toInt()}),a.getElements(".chzn-container").destroy(),jQuery(a).find("select").chosen({disable_search_threshold:10,allow_single_deselect:!0,width:"265px"}))},getTrs:function(a){return this.win[a].getElement("tbody").getElements("tr")},stripe:function(a){for(var b=this.getTrs(a),c=0;c<b.length;c++)b[c].removeClass("row1").removeClass("row0"),b[c].addClass("row"+c%2)},build:function(a){this.win[a]||this.makeWin(a);var b=JSON.parse(this.field[a].get("value"));"null"===typeOf(b)&&(b={});for(var c=this.win[a].getElement("tbody").getElement("tr"),d=Object.keys(b),e=0===d.length||0===b[d[0]].length,f=e?1:b[d[0]].length,g=1;g<f;g++){var h=c.clone();this.fixUniqueAttributes(c,h),h.inject(c,"after"),this.resetChosen(h)}this.stripe(a);var i=this.getTrs(a);for(g=0;g<f;g++)d.each(function(a){i[g].getElements("*[name*="+a+"]").each(function(c){"radio"===c.get("type")?c.value===b[a][g]&&(c.checked=!0):(c.value=b[a][g],"select"===c.get("tag")&&"undefined"!=typeof jQuery&&jQuery(c).trigger("liszt:updated"))})});this.tmpl=c,e&&c.dispose()},findTr:function(a){var b=a.target.getParents().filter(function(a){return"tr"===a.get("tag")});return 0!==b.length&&b[0]},store:function(a){var b=this.content;b=this.el[a];for(var c={},d=0;d<this.names.length;d++){var e=this.names[d],f=b.getElements("*[name*="+e+"]");c[e]=[],f.each(function(a){"radio"===a.get("type")?!0===a.get("checked")&&c[e].push(a.get("value")):c[e].push(a.get("value"))}.bind(this))}return this.field[a].value=JSON.stringify(c),!0}});