/*! Fabrik */
var FbElement=new Class({Implements:[Events,Options],options:{element:null,defaultVal:"",value:"",label:"",editable:!1,isJoin:!1,joinId:0},initialize:function(a,b){if(this.plugin="",b.element=a,this.strElement=a,this.loadEvents=[],this.events=$H({}),this.setOptions(b),document.id(this.options.element+"_chzn")){var c=this.getChangeEvent();jQuery("#"+this.options.element).on("change",{changeEvent:c},function(a){document.id(this.id).fireEvent(a.data.changeEvent,new Event.Mock(document.id(this.id),a.data.changeEvent))})}return this.setElement()},destroy:function(){},setElement:function(){return document.id(this.options.element)?(this.element=document.id(this.options.element),this.setorigId(),!0):!1},get:function(a){return"value"===a?this.getValue():void 0},getFormElementsKey:function(a){return this.baseElementId=a,a},attachedToForm:function(){this.setElement(),Fabrik.bootstrapped?(this.alertImage=new Element("i."+this.form.options.images.alert),this.successImage=new Element("i.icon-checkmark",{styles:{color:"green"}})):(this.alertImage=new Asset.image(this.form.options.images.alert),this.alertImage.setStyle("cursor","pointer"),this.successImage=new Asset.image(this.form.options.images.action_check)),this.loadingImage=jQuery(this.form.options.images.ajax_loader).data("isicon")?new Element("span").set("html",this.form.options.images.ajax_loader):new Asset.image(this.form.options.images.ajax_loader)},fireEvents:function(a){this.hasSubElements()?this._getSubElements().each(function(b){Array.from(a).each(function(a){b.fireEvent(a)}.bind(this))}.bind(this)):Array.from(a).each(function(a){this.element&&this.element.fireEvent(a)}.bind(this))},getElement:function(){return"null"===typeOf(this.element)&&(this.element=document.id(this.options.element)),this.element},_getSubElements:function(){var a=this.getElement();return"null"===typeOf(a)?!1:(this.subElements=a.getElements(".fabrikinput"),this.subElements)},hasSubElements:function(){return this._getSubElements(),("array"===typeOf(this.subElements)||"elements"===typeOf(this.subElements))&&this.subElements.length>0?!0:!1},unclonableProperties:function(){return["form"]},cloneUpdateIds:function(a){this.element=document.id(a),this.options.element=a},runLoadEvent:function(js,delay){delay=delay?delay:0,"function"===typeOf(js)?js.delay(delay):0===delay?eval(js):function(){console.log("delayed calling runLoadEvent for "+delay),eval(js)}.bind(this).delay(delay)},removeCustomEvents:function(){},renewEvents:function(){this.events.each(function(a,b){this.element.removeEvents(b),a.each(function(a){this.addNewEventAux(b,a)}.bind(this))}.bind(this))},addNewEventAux:function(action,js){this.element.addEvent(action,function(e){"function"===typeOf(js)?js.delay(0,this,this):eval(js)}.bind(this))},addNewEvent:function(a,b){"load"===a?(this.loadEvents.push(b),this.runLoadEvent(b)):(this.element||(this.element=document.id(this.strElement)),this.element&&(Object.keys(this.events).contains(a)||(this.events[a]=[]),this.events[a].push(b),this.addNewEventAux(a,b)))},addEvent:function(a,b){this.addNewEvent(a,b)},validate:function(){},addNewOption:function(a,b){var c,d=document.id(this.options.element+"_additions").value,e={val:a,label:b};c=""!==d?JSON.decode(d):[],c.push(e);for(var f="[",g=0;g<c.length;g++)f+=JSON.encode(c[g])+",";f=f.substring(0,f.length-1)+"]",document.id(this.options.element+"_additions").value=f},getLabel:function(){return this.options.label},setLabel:function(a){this.options.label=a;var b=this.getLabelElement();b&&(b[0].textContent=a)},update:function(a){this.getElement()&&(this.options.editable?this.element.value=a:this.element.innerHTML=a)},updateByLabel:function(a){this.update(a)},set:function(a){this.update(a)},getValue:function(){return this.element?this.options.editable?this.element.value:this.options.value:!1},reset:function(){this.resetEvents(),this.options.editable===!0&&this.update(this.options.defaultVal)},resetEvents:function(){this.loadEvents.each(function(a){this.runLoadEvent(a,100)}.bind(this))},clear:function(){this.update("")},onsubmit:function(a){a&&a(!0)},afterAjaxValidation:function(){},cloned:function(){if(this.renewEvents(),this.element.hasClass("chzn-done")){this.element.removeClass("chzn-done"),this.element.addClass("chzn-select"),this.element.getParent().getElement(".chzn-container").destroy(),jQuery("#"+this.element.id).chosen();var a=this.getChangeEvent();jQuery("#"+this.options.element).on("change",{changeEvent:a},function(a){document.id(this.id).fireEvent(a.data.changeEvent,new Event.Mock(a.data.changeEvent,document.id(this.id)))})}},decloned:function(){},getContainer:function(){return"null"===typeOf(this.element)?!1:this.element.getParent(".fabrikElementContainer")},getErrorElement:function(){return this.getContainer().getElements(".fabrikErrorMessage")},getLabelElement:function(){return this.getContainer().getElements(".fabrikLabel")},getValidationFx:function(){return this.validationFX||(this.validationFX=new Fx.Morph(this.getErrorElement()[0],{duration:500,wait:!0})),this.validationFX},tips:function(){return Fabrik.tips.elements.filter(function(a){return a===this.getContainer()||a.getParent()===this.getContainer()?!0:void 0}.bind(this))},addTipMsg:function(a,b){b=b?b:"error";var c,d=this.tips();if(0!==d.length&&(d=jQuery(d[0]),void 0===d.attr(b))){d.data("popover").show(),d.attr(b,a),c=d.data("popover").tip().find(".popover-content");var e=new Element("div");e.set("html",c.html());var f=new Element("li."+b);f.set("html",a),new Element("i."+this.form.options.images.alert).inject(f,"top"),e.getElement("ul").adopt(f),d.attr("data-content",unescape(e.get("html"))),d.data("popover").setContent(),d.data("popover").options.content=e.get("html"),d.data("popover").hide()}},removeTipMsg:function(){var b=b?b:"error",c=this.tips();if(c=jQuery(c[0]),void 0!==c.attr(b)){c.data("popover").show(),a=c.data("popover").tip().find(".popover-content");var d=new Element("div");d.set("html",a.html());var e=d.getElement("li.error");e&&e.destroy(),c.attr("data-content",d.get("html")),c.data("popover").setContent(),c.data("popover").options.content=d.get("html"),c.data("popover").hide(),c.removeAttr(b)}},setErrorMessage:function(a,b){var c,d=["fabrikValidating","fabrikError","fabrikSuccess"],e=this.getContainer();if(e===!1)return void console.log("Notice: couldn not set error msg for "+a+" no container class found");d.each(function(a){b===a?e.addClass(a):e.removeClass(a)});var f=this.getErrorElement();switch(f.each(function(a){a.empty()}),b){case"fabrikError":if(Fabrik.loader.stop(this.element),Fabrik.bootstrapped?this.addTipMsg(a):(c=new Element("a",{href:"#",title:a,events:{click:function(a){a.stop()}}}).adopt(this.alertImage),Fabrik.tips.attach(c)),f[0].adopt(c),e.removeClass("success").removeClass("info").addClass("error"),f.length>1)for(i=1;i<f.length;i++)f[i].set("html",a);break;case"fabrikSuccess":if(e.addClass("success").removeClass("info").removeClass("error"),Fabrik.bootstrapped)Fabrik.loader.stop(this.element),this.removeTipMsg();else{f[0].adopt(this.successImage);var g=function(){f[0].addClass("fabrikHide"),e.removeClass("success")};g.delay(700)}break;case"fabrikValidating":e.removeClass("success").addClass("info").removeClass("error"),Fabrik.loader.start(this.element,a)}this.getErrorElement().removeClass("fabrikHide");var h=this.form;("fabrikError"===b||"fabrikSuccess"===b)&&h.updateMainError();var j=this.getValidationFx();switch(b){case"fabrikValidating":case"fabrikError":j.start({opacity:1});break;case"fabrikSuccess":j.start({opacity:1}).chain(function(){e.hasClass("fabrikSuccess")&&(e.removeClass("fabrikSuccess"),this.start.delay(700,this,{opacity:0,onComplete:function(){e.addClass("success").removeClass("error"),h.updateMainError(),d.each(function(a){e.removeClass(a)})}}))})}},setorigId:function(){if(this.options.inRepeatGroup){var a=this.options.element;this.origId=a.substring(0,a.length-1-this.options.repeatCounter.toString().length)}},decreaseName:function(a){var b=this.getElement();return"null"===typeOf(b)?!1:(this.hasSubElements()?this._getSubElements().each(function(b){b.name=this._decreaseName(b.name,a),b.id=this._decreaseId(b.id,a)}.bind(this)):"null"!==typeOf(this.element.name)&&(this.element.name=this._decreaseName(this.element.name,a)),"null"!==typeOf(this.element.id)&&(this.element.id=this._decreaseId(this.element.id,a)),this.element.id)},_decreaseId:function(a,b,c){var d=!1;c=c?c:!1,c!==!1&&a.contains(c)&&(a=a.replace(c,""),d=!0);var e=Array.from(a.split("_")),f=e.getLast();if("null"===typeOf(f.toInt()))return e.join("_");f>=1&&f>b&&f--,e.splice(e.length-1,1,f);var g=e.join("_");return d&&(g+=c),this.options.element=g,g},_decreaseName:function(a,b,c){suffixFound=!1,c=c?c:!1,c!==!1&&a.contains(c)&&(a=a.replace(c,""),suffixFound=!0);var d=a.split("["),e=d[1].replace("]","").toInt();e>=1&&e>b&&e--,e+="]",d[1]=e;var f=d.join("[");return suffixFound&&(f+=c),f},getRepeatNum:function(){return this.options.inRepeatGroup===!1?!1:this.element.id.split("_").getLast()},getBlurEvent:function(){return"select"===this.element.get("tag")?"change":"blur"},getChangeEvent:function(){return"change"},select:function(){},focus:function(){},hide:function(){var a=this.getContainer();a&&a.hide()},show:function(){var a=this.getContainer();a&&a.show()},toggle:function(){var a=this.getContainer();a&&a.toggle()},getCloneName:function(){return this.options.element},doTab:function(){(function(){this.redraw(),Fabrik.bootstrapped||this.options.tab_dt.removeEvent("click",function(a){this.doTab(a)}.bind(this))}).bind(this).delay(500)},watchTab:function(){var a,b,c=Fabrik.bootstrapped?".tab-pane":".current",d=this.element.getParent(c);d&&(Fabrik.bootstrapped?(a=document.getElement("a[href$=#"+d.id+"]"),b=a.getParent("ul.nav"),b.addEvent("click:relay(a)",function(a){this.doTab(a)}.bind(this))):(b=d.getPrevious(".tabs"),b&&(this.options.tab_dd=this.element.getParent(".fabrikGroup"),"none"===this.options.tab_dd.style.getPropertyValue("display")&&(this.options.tab_dt=b.getElementById("group"+this.groupid+"_tab"),this.options.tab_dt&&this.options.tab_dt.addEvent("click",function(a){this.doTab(a)}.bind(this))))))},updateUsingRaw:function(){return!1}}),FbFileElement=new Class({Extends:FbElement,ajaxFolder:function(){if(this.folderlist=[],"null"!==typeOf(this.element)){var a=this.element.getParent(".fabrikElement");this.breadcrumbs=a.getElement(".breadcrumbs"),this.folderdiv=a.getElement(".folderselect"),this.slider=new Fx.Slide(this.folderdiv,{duration:500}),this.slider.hide(),this.hiddenField=a.getElement(".folderpath"),a.getElement(".toggle").addEvent("click",function(a){a.stop(),this.slider.toggle()}.bind(this)),this.watchAjaxFolderLinks()}},watchAjaxFolderLinks:function(){this.folderdiv.getElements("a").addEvent("click",function(a){this.browseFolders(a)}.bind(this)),this.breadcrumbs.getElements("a").addEvent("click",function(a){this.useBreadcrumbs(a)}.bind(this))},browseFolders:function(a){a.stop(),this.folderlist.push(a.target.get("text"));var b=this.options.dir+this.folderlist.join(this.options.ds);this.addCrumb(a.target.get("text")),this.doAjaxBrowse(b)},useBreadcrumbs:function(a){a.stop();var b=a.target.className;this.folderlist=[];var c=(this.breadcrumbs.getElements("a").every(function(c){return c.className===b?!1:(this.folderlist.push(a.target.innerHTML),!0)},this),[this.breadcrumbs.getElements("a").shift().clone(),this.breadcrumbs.getElements("span").shift().clone()]);this.breadcrumbs.empty(),this.breadcrumbs.adopt(c),this.folderlist.each(function(a){this.addCrumb(a)},this);var d=this.options.dir+this.folderlist.join(this.options.ds);this.doAjaxBrowse(d)},doAjaxBrowse:function(a){var b={dir:a,option:"com_fabrik",format:"raw",task:"plugin.pluginAjax",plugin:"fileupload",method:"ajax_getFolders",element_id:this.options.id};new Request({url:"",data:b,onComplete:function(a){a=JSON.decode(a),this.folderdiv.empty(),a.each(function(a){new Element("li",{"class":"fileupload_folder"}).adopt(new Element("a",{href:"#"}).set("text",a)).inject(this.folderdiv)}.bind(this)),0===a.length?this.slider.hide():this.slider.slideIn(),this.watchAjaxFolderLinks(),this.hiddenField.value="/"+this.folderlist.join("/")+"/",this.fireEvent("onBrowse")}.bind(this)}).send()},addCrumb:function(a){this.breadcrumbs.adopt(new Element("a",{href:"#","class":"crumb"+this.folderlist.length}).set("text",a),new Element("span").set("text"," / "))}});