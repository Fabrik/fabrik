var FbElement=new Class({Implements:[Events,Options],options:{element:null,defaultVal:"",value:"",label:"",editable:false,isJoin:false,joinId:0},initialize:function(b,a){this.plugin="";a.element=b;this.strElement=b;this.loadEvents=[];this.events=$H({});this.setOptions(a);this.successTimer=null;return this.setElement()},destroy:function(){},setElement:function(){if(document.id(this.options.element)){this.element=document.id(this.options.element);this.setOrigId();return true}return false},get:function(a){if(a==="value"){return this.getValue()}},getFormElementsKey:function(a){this.baseElementId=a;return a},attachedToForm:function(){this.attachedToFormParent()},attachedToFormParent:function(){this.setElement();if(Fabrik.bootstrapped){this.alertImage=new Element("i."+this.form.options.images.alert);this.successImage=new Element("i.icon-checkmark",{styles:{color:"green"}})}else{this.alertImage=new Asset.image(this.form.options.images.alert);this.alertImage.setStyle("cursor","pointer");this.successImage=new Asset.image(this.form.options.images.action_check)}if(this.form.options.images.ajax_loader.contains("<i")){this.loadingImage=new Element("span").set("html",this.form.options.images.ajax_loader)}else{this.loadingImage=new Asset.image(this.form.options.images.ajax_loader)}},fireEvents:function(a){if(this.hasSubElements()){this._getSubElements().each(function(b){Array.from(a).each(function(c){b.fireEvent(c)}.bind(this))}.bind(this))}else{Array.from(a).each(function(b){if(this.element){this.element.fireEvent(b)}}.bind(this))}},getElement:function(){if(typeOf(this.element)==="null"){this.element=document.id(this.options.element)}return this.element},_getSubElements:function(){var a=this.getElement();if(typeOf(a)==="null"){return false}this.subElements=a.getElements(".fabrikinput");return this.subElements},hasSubElements:function(){this._getSubElements();if(typeOf(this.subElements)==="array"||typeOf(this.subElements)==="elements"){return this.subElements.length>0?true:false}return false},unclonableProperties:function(){return["form"]},cloneUpdateIds:function(a){this.element=document.id(a);this.options.element=a},runLoadEvent:function(js,delay){delay=delay?delay:0;if(typeOf(js)==="function"){js.delay(delay)}else{if(delay===0){eval(js)}else{(function(){eval(js)}.bind(this)).delay(delay)}}},removeCustomEvents:function(){},renewEvents:function(){this.events.each(function(a,b){this.element.removeEvents(b);a.each(function(c){this.addNewEventAux(b,c)}.bind(this))}.bind(this))},addNewEventAux:function(action,js){this.element.addEvent(action,function(){typeOf(js)==="function"?js.delay(0,this,this):eval(js)}.bind(this))},addNewEvent:function(a,b){if(a==="load"){this.loadEvents.push(b);this.runLoadEvent(b)}else{if(!this.element){this.element=document.id(this.strElement)}if(this.element){if(!Object.keys(this.events).contains(a)){this.events[a]=[]}this.events[a].push(b);this.addNewEventAux(a,b)}}},addEvent:function(a,b){this.addNewEvent(a,b)},validate:function(){},addNewOption:function(h,c){var b;var g=document.id(this.options.element+"_additions").value;var e={val:h,label:c};if(g!==""){b=JSON.decode(g)}else{b=[]}b.push(e);var f="[";for(var d=0;d<b.length;d++){f+=JSON.encode(b[d])+","}f=f.substring(0,f.length-1)+"]";document.id(this.options.element+"_additions").value=f},getLabel:function(){return this.options.label},update:function(a){if(this.getElement()){if(this.options.editable){this.element.value=a}else{this.element.innerHTML=a}}},set:function(a){this.update(a)},getValue:function(){if(this.element){if(this.options.editable){return this.element.value}else{return this.options.value}}return false},reset:function(){this.resetEvents();if(this.options.editable===true){this.update(this.options.defaultVal)}},resetEvents:function(){this.loadEvents.each(function(a){this.runLoadEvent(a,100)}.bind(this))},clear:function(){this.update("")},onsubmit:function(){return true},afterAjaxValidation:function(){},cloned:function(a){this.renewEvents()},decloned:function(a){},getContainer:function(){return typeOf(this.element)==="null"?false:this.element.getParent(".fabrikElementContainer")},getErrorElements:function(){return this.getContainer().getElements(".fabrikErrorMessage")},getValidationFx:function(){if(!this.validationFX){this.validationFX=new Fx.Morph(this.getErrorElements()[0],{duration:500,wait:true})}return this.validationFX},tips:function(){var a=this.getContainer();return Fabrik.tips.elements.filter(function(b){if(!b.hasClass("fabrikElementContainer")){b=b.getParent(".fabrikElementContainer")}if(b===a){return true}}.bind(this))},addTipMsg:function(f,b){var e;var d=this.tips();if(d.length===0){return}d=jQuery(d[0]);if(typeOf(d.data("popover-saved"))==="null"){d.data("popover-saved",d.data("popover").options.content)}var a=new Element("li");a.set("html","&nbsp;"+f);b=b===null?"fabrikError":b;switch(b){case"fabrikError":e="text-error.error";a.grab(this.alertImage.clone(),"top");break;case"fabrikSuccess":e="text-success.success";a.grab(this.successImage.clone(),"top");break}var c=new Element("ul.validation-notices."+e).setProperty("style","list-style:none").adopt(a);d.data("popover").options.content=c.outerHTML;d.data("popover").manualShow()},removeTipMsg:function(){var a=this.tips();if(a.length===0){return}a=jQuery(a[0]);a.data("popover").manualHide();if(typeOf(a.data("popover-saved"))!=="null"){a.data("popover").options.content=a.data("popover-saved");a.data("popover-saved",null)}},setErrorMessage:function(d,c,f){f=typeOf(f)!=="null"?f:false;var a=this.getContainer();if(a===false){fconsole("element.js: Could not display error msg for "+d+" no container class found");return}if(this.successTimer!==null){window.clearTimeout(this.successTimer);this.successTimer=null}var e=this.getErrorElements();e.each(function(g){g.empty();g.removeClass("fabrikHide").removeClass("text-error").removeClass("text-success")});a.removeClass("error").removeClass("success").removeClass("fabrikError").removeClass("fabrikSuccess");switch(c){case"fabrikError":a.addClass("error").addClass("fabrikError");if(Fabrik.bootstrapped&&f){this.addTipMsg(d,c)}else{e.each(function(g){g.addClass("text-error");g.set("html","&nbsp;"+d);g.grab(this.alertImage.clone(),"top")}.bind(this))}break;case"fabrikSuccess":a.addClass("success").addClass("fabrikSuccess");if(Fabrik.bootstrapped&&f){if(d!==""){this.addTipMsg(d,c)}else{this.removeTipMsg()}}else{if(!f){e.each(function(g){g.addClass("text-success");g.set("html","&nbsp;"+d);g.grab(this.successImage.clone(),"top")}.bind(this))}}var b=function(){var g=this.getContainer();if(g.hasClass("fabrikSuccess")){e.each(function(h){h.empty();h.removeClass("text-success").addClass("fabrikHide")});g.removeClass("success").removeClass("fabrikSuccess");this.removeTipMsg()}this.successTimer=null}.bind(this);this.successTimer=window.setTimeout(b,5000);break}},setOrigId:function(){if(this.options.inRepeatGroup){var a=this.options.element;this.origid=a.substring(0,a.length-1-this.options.repeatCounter.toString().length)}},decreaseName:function(b){var a=this.getElement();if(typeOf(a)==="null"){return false}if(this.hasSubElements()){this._getSubElements().each(function(c){c.name=this._decreaseName(c.name,b);c.id=this._decreaseId(c.id,b)}.bind(this))}else{if(typeOf(this.element.name)!=="null"){this.element.name=this._decreaseName(this.element.name,b)}}if(typeOf(this.element.id)!=="null"){this.element.id=this._decreaseId(this.element.id,b)}return this.element.id},_decreaseId:function(g,f,e){var a=false;e=e?e:false;if(e!==false){if(g.contains(e)){g=g.replace(e,"");a=true}}var d=Array.from(g.split("_"));var b=d.getLast();if(typeOf(b.toInt())==="null"){return d.join("_")}if(b>=1&&b>f){b--}d.splice(d.length-1,1,b);var c=d.join("_");if(a){c+=e}this.options.element=c;return c},_decreaseName:function(g,f,e){var b=false;e=e?e:false;if(e!==false){if(g.contains(e)){g=g.replace(e,"");b=true}}var a=g.split("[");var c=a[1].replace("]","").toInt();if(c>=1&&c>f){c--}c=c+"]";a[1]=c;var d=a.join("[");if(b){d+=e}return d},getRepeatNum:function(){if(this.options.inRepeatGroup===false){return false}return this.element.id.split("_").getLast()},getBlurEvent:function(){return this.element.get("tag")==="select"?"change":"blur"},select:function(){},focus:function(){},hide:function(){var a=this.getContainer();if(a){a.hide()}},show:function(){var a=this.getContainer();if(a){a.show()}},toggle:function(){var a=this.getContainer();if(a){a.toggle()}},getCloneName:function(){return this.options.element},doValidation:function(g,i,j,c){this.spinId=c;var h=$H(this.form.getFormData());h.set("task","form.ajax_validate");h.set("fabrik_ajax","1");h.set("format","raw");h=this.form.prepareRepeatsForAjax(h);var a=j;var f=this.form.formElements.get(j);if(f.origid){a=f.origid+"_0"}f.options.repeatCounter=f.options.repeatCounter?f.options.repeatCounter:0;var b="index.php?option=com_fabrik&form_id="+this.form.id;Fabrik.fireEvent("fabrik.form.element.validaton.start",[this.form,f,g]);Fabrik.fireEvent("fabrik.form.element.validation.start",[this.form,f,g]);if(this.form.result===false){this.form.result=true;return}if(this.ajax){this.ajax.cancel()}this.ajax=new Request({url:b,data:h,onRequest:function(){Fabrik.loader.start(this.spinId,Joomla.JText._("COM_FABRIK_VALIDATING"))}.bind(this),onCancel:function(){Fabrik.loader.stop(this.spinId);this.ajax=null}.bind(this),onComplete:function(){Fabrik.loader.stop(this.spinId);this.ajax=null}.bind(this),onFailure:function(d){fconsole("Fabrik element::doValidation Ajax failure: Code "+d.status+": "+d.statusText);this.setErrorMessage("Validation ajax call failed","fabrikError");this.form.formElements.each(function(k,e){k.afterAjaxValidation()})}.bind(this),onSuccess:function(e){if(typeOf(e)==="null"){fconsole("Fabrik element::doValidation Ajax response empty.");this.setErrorMessage("Validation ajax call response empty","fabrikError");return}this.form.formElements.each(function(m,l){m.afterAjaxValidation()});e=JSON.decode(e);Fabrik.fireEvent("fabrik.form.elemnet.validation.complete",[this.form,e,j,a]);Fabrik.fireEvent("fabrik.form.element.validation.complete",[this.form,e,j,a]);if(this.form.result===false){this.form.result=true;return}var d=this.form.formElements.get(j);if((typeOf(e.modified[a])!=="null")){d.update(e.modified[a])}if(typeOf(e.errors[a])!=="null"){var k=e.errors[a].flatten().join("<br />");if(k!==""){this.setErrorMessage(k,"fabrikError",true)}else{this.setErrorMessage("","fabrikSuccess",true)}}else{this.setErrorMessage("","fabrikSuccess",true)}}.bind(this)}).send()}});var FbFileElement=new Class({Extends:FbElement,ajaxFolder:function(){this.folderlist=[];if(typeOf(this.element)==="null"){return}var a=this.element.getParent(".fabrikElement");this.breadcrumbs=a.getElement(".breadcrumbs");this.folderdiv=a.getElement(".folderselect");this.slider=new Fx.Slide(this.folderdiv,{duration:500});this.slider.hide();this.hiddenField=a.getElement(".folderpath");a.getElement(".toggle").addEvent("click",function(b){b.stop();this.slider.toggle()}.bind(this));this.watchAjaxFolderLinks()},watchAjaxFolderLinks:function(){this.folderdiv.getElements("a").addEvent("click",function(a){this.browseFolders(a)}.bind(this));this.breadcrumbs.getElements("a").addEvent("click",function(a){this.useBreadcrumbs(a)}.bind(this))},browseFolders:function(b){b.stop();this.folderlist.push(b.target.get("text"));var a=this.options.dir+this.folderlist.join(this.options.ds);this.addCrumb(b.target.get("text"));this.doAjaxBrowse(a)},useBreadcrumbs:function(b){b.stop();var f=b.target.className;this.folderlist=[];this.breadcrumbs.getElements("a").every(function(c){if(c.className===f){return false}this.folderlist.push(b.target.innerHTML);return true},this);var d=[this.breadcrumbs.getElements("a").shift().clone(),this.breadcrumbs.getElements("span").shift().clone()];this.breadcrumbs.empty();this.breadcrumbs.adopt(d);this.folderlist.each(function(c){this.addCrumb(c)},this);var a=this.options.dir+this.folderlist.join(this.options.ds);this.doAjaxBrowse(a)},doAjaxBrowse:function(a){var b={dir:a,option:"com_fabrik",format:"raw",task:"plugin.pluginAjax",plugin:"fileupload",method:"ajax_getFolders",element_id:this.options.id};new Request({url:"",data:b,onSuccess:function(c){c=JSON.decode(c);this.folderdiv.empty();c.each(function(d){new Element("li",{"class":"fileupload_folder"}).adopt(new Element("a",{href:"#"}).set("text",d)).inject(this.folderdiv)}.bind(this));if(c.length===0){this.slider.hide()}else{this.slider.slideIn()}this.watchAjaxFolderLinks();this.hiddenField.value="/"+this.folderlist.join("/")+"/";this.fireEvent("onBrowse")}.bind(this)}).send()},addCrumb:function(a){this.breadcrumbs.adopt(new Element("a",{href:"#","class":"crumb"+this.folderlist.length}).set("text",a),new Element("span").set("text"," / "))}});