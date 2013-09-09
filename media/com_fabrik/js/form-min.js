var FbForm=new Class({Implements:[Options,Events],options:{rowid:"",admin:false,ajax:false,primaryKey:null,error:"",submitOnEnter:false,updatedMsg:"Form saved",pages:[],start_page:0,ajaxValidation:false,customJsAction:"",plugins:[],inlineMessage:true,images:{alert:"",action_check:"",ajax_loader:""}},initialize:function(b,a){this.id=b;if(typeOf(a.rowid)==="null"){a.rowid=""}this.result=true;this.setOptions(a);this.plugins=this.options.plugins;this.options.pages=$H(this.options.pages);this.subGroups=$H({});this.currentPage=this.options.start_page;this.formElements=$H({});this.elements=this.formElements;this.duplicatedGroups=$H({});this.grpValidation={};this.fx={};this.fx.elements=[];this.fx.validations={};this.events={};this.setupAll()},setupAll:function(){if(!this.getForm()){return}this.watchSubmit();if(this.options.ajax||this.options.submitOnEnter===false){this.stopEnterSubmitting()}this.watchClearSession();this.watchGoBackButton();this.watchAddOptions();if(this.isTabbed()){this.setupTabs()}else{this.setSubmitApplyStatus();this.setupPages()}this.winScroller=new Fx.Scroll(window);this.setStartHiddenGroups();this.setRepeatGroupMarkers();this.setMozBoxWidths();this.watchGroupButtons();this.duplicateGroupsToMin()},watchSubmit:function(){var d=this._getButton("submit");if(!d){return}if(this.options.ajax){var b=this._getButton("apply");var e=this._getButton("Copy");([b,d,e]).each(function(f){if(typeOf(f)!=="null"){f.addEvent("click",function(g){this.doSubmit(g,f)}.bind(this))}}.bind(this))}else{this.form.addEvent("submit",function(f){this.doSubmit(f)}.bind(this))}var a=this._getButton("delete");if(a){a.addEvent("click",function(g){if(confirm(Joomla.JText._("COM_FABRIK_CONFIRM_DELETE_1"))){var f=Fabrik.fireEvent("fabrik.form.delete",[this,this.options.rowid]).eventResults;if(typeOf(f)==="null"||f.length===0||!f.contains(false)){this.form.getElement("input[name=task]").value=this.options.admin?"form.delete":"delete"}else{g.stop();return false}}else{return false}}.bind(this))}},stopEnterSubmitting:function(){var a=this.form.getElements("input.fabrikinput[type!=hidden]");a.each(function(d,b){d.addEvent("keypress",function(f){if(f.key==="enter"){f.stop();if(a[b+1]){a[b+1].focus()}if(b===a.length-1){this._getButton("submit").focus()}}}.bind(this))}.bind(this))},watchClearSession:function(){if(this.form&&this.form.getElement(".clearSession")){this.form.getElement(".clearSession").addEvent("click",function(a){a.stop();this.form.getElement("input[name=task]").value="removeSession";this.clearForm();this.form.submit()}.bind(this))}},watchGoBackButton:function(){if(this.options.ajax){var a=this.getForm().getElement("input[name=Goback]");if(typeOf(a)==="null"){return}a.addEvent("click",function(b){b.stop();if(Fabrik.Windows[this.options.fabrik_window_id]){Fabrik.Windows[this.options.fabrik_window_id].close()}else{window.history.back()}}.bind(this))}},watchAddOptions:function(){this.fx.addOptions=[];this.getForm().getElements(".addoption").each(function(f){var b=f.getParent(".fabrikElementContainer").getElement(".toggle-addoption");var e=new Fx.Slide(f,{duration:500});e.hide();b.addEvent("click",function(a){a.stop();e.toggle()})})},isTabbed:function(){this.tabbed=false;if(this.form){if(this.form.getElement("ul.nav-tabs")){this.tabbed=true}}return this.tabbed},setupTabs:function(){this.form.getElement(".nav-tabs").getElements("a").each(function(b){jQuery(b).on("show",function(a){this.tabValidate(a,a.target,a.relatedTarget)}.bind(this))}.bind(this))},setupPages:function(){var b,a;if(this.options.pages.getKeys().length>1){this.options.pages.each(function(e,d){b=new Element("div",{"class":"page",id:"page_"+d});a=document.id("group"+e[0]);if(typeOf(a)!=="null"){b.inject(a,"before");e.each(function(f){b.adopt(document.id("group"+f))})}});if(typeOf(document.getElement(".fabrikPagePrevious"))!=="null"){this.form.getElement(".fabrikPagePrevious").disabled="disabled";this.form.getElement(".fabrikPagePrevious").addEvent("click",function(d){this.doPageNav(d,-1)}.bind(this))}if(typeOf(document.getElement(".fabrikPageNext"))!=="null"){this.form.getElement(".fabrikPageNext").addEvent("click",function(d){this.doPageNav(d,1)}.bind(this))}this.setPageButtons();this.hideOtherPages()}},setSubmitApplyStatus:function(){if(this.options.rowid===""){this.disableSubmitApply()}},setStartHiddenGroups:function(){$H(this.options.hiddenGroup).each(function(b,a){if(b===true&&typeOf(document.id("group"+a))!=="null"){var d=document.id("group"+a).getElement(".fabrikSubGroup");this.subGroups.set(a,d.cloneWithIds());this.hideLastGroup(a,d)}}.bind(this))},setRepeatGroupMarkers:function(){this.repeatGroupMarkers=$H({});this.form.getElements(".fabrikGroup").each(function(a){var d=a.id.replace("group","");var b=a.getElements(".fabrikSubGroup").length;if(b===1){if(a.getElement(".fabrikSubGroupElements").getStyle("display")==="none"){b=0}}this.repeatGroupMarkers.set(d,b)}.bind(this))},setMozBoxWidths:function(){if(Browser.firefox&&this.form){this.getForm().getElements(".fabrikElementContainer > .displayBox").each(function(d){var g=d.getParent().getComputedSize();var a=d.getParent().getSize().x-(g.computedLeft+g.computedRight);var f=d.getParent().getSize().x===0?400:a;d.setStyle("width",f+"px");var h=d.getElement(".fabrikElement");if(typeOf(h)!=="null"){a=0;d.getChildren().each(function(b){if(b!==h){a+=b.getSize().x}});h.setStyle("width",f-a-10+"px")}})}},watchGroupButtons:function(){this.form.addEvent("click:relay(.deleteGroup)",function(b,a){b.preventDefault();this.deleteGroup(b)}.bind(this));this.form.addEvent("click:relay(.addGroup)",function(b,a){b.preventDefault();this.duplicateGroup(b)}.bind(this));this.form.addEvent("click:relay(.fabrikSubGroup)",function(d,b){var a=b.getElement(".fabrikGroupRepeater");if(a){b.addEvent("mouseenter",function(f){a.fade(1)});b.addEvent("mouseleave",function(f){a.fade(0.2)})}}.bind(this))},watchValidation:function(d,b){var a=document.id(d);if(typeOf(a)==="null"){fconsole("form.js:watchValidation: Could not find element "+d);return}if(a.className==="fabrikSubElementContainer"){a.getElements(".fabrikinput").each(function(e){e.addEvent(b,function(f){this.doElementEvent.delay(500,this,[f,true])}.bind(this))}.bind(this))}else{a.addEvent(b,function(f){this.doElementEvent.delay(500,this,[f,false])}.bind(this))}},addElements:function(b){var e=[],d=0;b=$H(b);b.each(function(g,a){g.each(function(j){if(typeOf(j)==="array"){var h=new window[j[0]](j[1],j[2]);e.push(this.addElement(h,j[1],a))}else{if(typeOf(j)!=="null"){e.push(this.addElement(j,j.options.element,a))}}}.bind(this))}.bind(this));for(d=0;d<e.length;d++){if(typeOf(e[d])!=="null"){try{e[d].attachedToForm()}catch(f){fconsole("Fabrik formm.js::addElements Error attaching "+e[d].options.element+" to form:"+f)}}}Fabrik.fireEvent("fabrik.form.elements.added",[this])},addElement:function(b,a,d){a=b.getFormElementsKey(a);a=a.replace("[]","");var e=a.substring(a.length-3,a.length)==="_ro";b.form=this;b.groupid=d;this.grpValidation[d]=this.grpValidation[d]||b.options.validations;this.formElements.set(a,b);Fabrik.fireEvent("fabrik.form.element.added",[this,a,b]);if(e){a=a.substr(0,a.length-3);this.formElements.set(a,b)}return b},doElementFX:function(b,a,d){var g,h,j;var l=this.formElements.get(b.replace("fabrik_trigger_element_",""));var e=true;if(l){e=l.options.inRepeatGroup}if(d&&e){if(d.options.inRepeatGroup){var m=b.split("_");m[m.length-1]=d.options.repeatCounter;b=m.join("_")}}b=b.replace("fabrik_trigger_","");if(b.slice(0,6)==="group_"){b=b.slice(6,b.length);if(b.slice(0,6)==="group_"){b=b.slice(6,b.length)}g=b;h=true}else{h=false;b=b.slice(8,b.length);g="element"+b}var f=this.fx.elements[g];if(!f){f=this.addElementFX("element_"+b,a);if(!f){return}}if(h||f.css.element.hasClass("fabrikElementContainer")){j=f.css.element}else{j=f.css.element.getParent(".fabrikElementContainer")}if(j.get("tag")==="td"){j=j.getChildren()[0]}switch(a){case"show":j.fade("show").removeClass("fabrikHide");if(h){document.id(b).getElements(".fabrikinput").setStyle("opacity","1")}break;case"hide":j.fade("hide").addClass("fabrikHide");break;case"fadein":j.removeClass("fabrikHide");if(f.css.lastMethod!=="fadein"){f.css.element.show();f.css.start({opacity:[0,1]})}break;case"fadeout":if(f.css.lastMethod!=="fadeout"){f.css.start({opacity:[1,0]}).chain(function(){f.css.element.hide();j.addClass("fabrikHide")})}break;case"slide in":f.slide.slideIn();break;case"slide out":f.slide.slideOut();j.removeClass("fabrikHide");break;case"slide toggle":f.slide.toggle();break;case"clear":this.formElements.get(b).clear();break}f.lastMethod=a;Fabrik.fireEvent("fabrik.form.doelementfx",[this])},addElementFX:function(h,g){var f,b,e;h=h.replace("fabrik_trigger_","");if(h.slice(0,6)==="group_"){h=h.slice(6,h.length);b=h;f=document.id(h)}else{h=h.slice(8,h.length);b="element"+h;if(!document.id(h)){return false}f=document.id(h).getParent(".fabrikElementContainer")}if(f){var a=(f).get("tag");if(a==="li"||a==="td"){e=new Element("div",{style:"width:100%"}).adopt(f.getChildren());f.empty();e.inject(f)}else{e=f}var d={duration:800,transition:Fx.Transitions.Sine.easeInOut};this.fx.elements[b]={};this.fx.elements[b].css=new Fx.Morph(e,d);if(typeOf(e)!=="null"&&(g==="slide in"||g==="slide out"||g==="slide toggle")){this.fx.elements[b].slide=new Fx.Slide(e,d)}else{this.fx.elements[b].slide=null}return this.fx.elements[b]}return false},destroyElements:function(){this.formElements.each(function(a){a.destroy()})},getFormData:function(d){d=typeOf(d)!=="null"?d:true;if(d){this.formElements.each(function(g,f){g.onsubmit()})}this.getForm();var b=this.form.toQueryString();var a={};b=b.split("&");var e=$H({});b.each(function(g){g=g.split("=");var f=g[0];f=decodeURI(f);if(f.substring(f.length-2)==="[]"){f=f.substring(0,f.length-2);if(!e.has(f)){e.set(f,0)}else{e.set(f,e.get(f)+1)}f=f+"["+e.get(f)+"]"}a[f]=g[1]});this.formElements.each(function(g,f){if(g.plugin==="fabrikfileupload"){a[f]=g.get("value")}if(typeOf(a[f])==="null"){var h=false;$H(a).each(function(k,j){j=unescape(j);j=j.replace(/\[(.*)\]/,"");if(j===f){h=true}}.bind(this));if(!h){a[f]=""}}}.bind(this));return a},getFormElementData:function(){var a={};this.formElements.each(function(d,b){if(d.element){a[b]=d.getValue();a[b+"_raw"]=a[b]}}.bind(this));return a},enableSubmitApply:function(){var b=this._getButton("submit");if(b){this.enableElement(b)}var a=this._getButton("apply");if(a){this.enableElement(a)}},disableSubmitApply:function(){var b=this._getButton("submit");if(b){this.disableElement(b)}var a=this._getButton("apply");if(a){this.disableElement(a)}},doSubmit:function(d,a){Fabrik.fireEvent("fabrik.form.submit.start",[this,d,a]);this.elementsBeforeSubmit(d);if(this.result===false){this.result=true;d.stop();this.updateMainError();return}if(this.options.pages.getKeys().length>1){this.form.adopt(new Element("input",{name:"currentPage",value:this.currentPage.toInt(),type:"hidden"}))}if(this.options.ajax){if(this.form){var b=$H(this.getFormData());b=this.prepareRepeatsForAjax(b);b.fabrik_ajax="1";b.format="raw";if(a.name==="Copy"){b.Copy=1;d.stop()}b.fabrik_ajax="1";b.format="raw";new Request.JSON({url:this.form.action,data:b,onRequest:function(){Fabrik.loader.start(this.getBlock(),Joomla.JText._("COM_FABRIK_SAVING"))}.bind(this),onCancel:function(){Fabrik.loader.stop(this.getBlock())}.bind(this),onComplete:function(){Fabrik.loader.stop(this.getBlock())}.bind(this),onError:function(f,e){fconsole("Fabrik form::doSubmit Ajax JSON error: "+e+": "+f);this.showMainError("Ajax error on partial save: "+e)}.bind(this),onFailure:function(e){fconsole("Fabrik form::doSubmit Ajax failure: Code "+e.status+": "+e.statusText);this.showMainError("Ajax failure on partial save.")}.bind(this),onSuccess:function(o,g){if(typeOf(o)==="null"){fconsole("Fabrik form::doSubmit Ajax response empty.");this.showMainError("Ajax response empty on partial save.");return}var j=false;if(o.errors!==undefined){$H(o.errors).each(function(r,p){if(this.formElements.has(p)&&r.flatten().length>0){j=true;if(this.formElements[p].options.inRepeatGroup){for(d=0;d<r.length;d++){if(r[d].flatten().length>0){var q=p.replace(/(_\d+)$/,"_"+d);this.showElementError(r[d].flatten().join("<br />"),q)}}}else{this.showElementError(r.flatten().join("<br />"),p)}}}.bind(this))}this.updateMainError();if(j===false){var l=false;if(this.options.rowid===""&&a.name!=="apply"){l=true}var k=(typeOf(o.msg)!=="null"&&o.msg!==undefined&&o.msg!=="")?o.msg:Joomla.JText._("COM_FABRIK_FORM_SAVED");if(o.baseRedirect!==true){l=o.reset_form;if(o.url!==undefined){if(o.redirect_how==="popup"){var f=o.width?o.width:400;var n=o.height?o.height:400;var h=o.x_offset?o.x_offset:0;var e=o.y_offset?o.y_offset:0;var m=o.title?o.title:"";Fabrik.getWindow({id:"redirect",type:"redirect",contentURL:o.url,caller:this.getBlock(),height:n,width:f,offset_x:h,offset_y:e,title:m})}else{if(o.redirect_how==="samepage"){window.open(o.url,"_self")}else{if(o.redirect_how==="newpage"){window.open(o.url,"_blank")}}}}else{alert(k)}}else{l=o.reset_form!==undefined?o.reset_form:l;alert(k)}Fabrik.fireEvent("fabrik.form.submitted",[this,o]);if(a.name!=="apply"){if(l){this.clearForm()}if(Fabrik.Windows[this.options.fabrik_window_id]){Fabrik.Windows[this.options.fabrik_window_id].close()}}}else{Fabrik.fireEvent("fabrik.form.submit.failed",[this,o]);Fabrik.loader.stop(this.getBlock(),Joomla.JText._("COM_FABRIK_VALIDATION_ERROR"))}}.bind(this)}).send()}}Fabrik.fireEvent("fabrik.form.submit.end",[this]);if(this.result===false){this.result=true;d.stop();this.updateMainError()}else{if(this.options.ajax){Fabrik.fireEvent("fabrik.form.ajax.submit.end",[this])}}},elementsBeforeSubmit:function(a){this.formElements.each(function(d,b){if(!d.onsubmit()){a.stop()}})},clearForm:function(){this.getForm();if(!this.form){return}this.formElements.each(function(b,a){if(a===this.options.primaryKey){this.form.getElement("input[name=rowid]").value=""}b.update("")}.bind(this));this.form.getElements(".fabrikError").empty();this.form.getElements(".fabrikError").addClass("fabrikHide")},reset:function(){Fabrik.fireEvent("fabrik.form.reset",[this]);if(this.result===false){this.result=true;return}this.addedGroups.each(function(a){var d=document.id(a).findClassUp("fabrikGroup");var b=d.id.replace("group","");document.id("fabrik_repeat_group_"+b+"_counter").value=document.id("fabrik_repeat_group_"+b+"_counter").get("value").toInt()-1;a.remove()});this.addedGroups=[];this.formElements.each(function(b,a){b.reset()}.bind(this))},gidsValidation:function(a){var b=false;a.each(function(d){b=b||this.grpValidation[d]}.bind(this));return b},validateByAjax:function(d){this.hideMainError();if(typeOf(document.getElement(".tool-tip"))!=="null"){document.getElement(".tool-tip").setStyle("top",0)}var b=$H(this.getFormData());b=this.prepareRepeatsForAjax(b);b.fabrik_ajax="1";b.format="raw";b.task="form.ajax_validate";var a="index.php?option=com_fabrik&format=raw&task=form.ajax_validate&form_id="+this.id;if(this.ajax){this.ajax.cancel()}this.Ajax=new Request({url:a,data:b,onRequest:function(){Fabrik.loader.start(this.getBlock(),Joomla.JText._("COM_FABRIK_VALIDATING"))}.bind(this),onCancel:function(){Fabrik.loader.stop(this.getBlock());this.ajax=null}.bind(this),onComplete:function(){Fabrik.loader.stop(this.getBlock());this.ajax=null}.bind(this),onFailure:function(e){fconsole("Fabrik form::doSubmit Ajax failure: Code "+e.status+": "+e.statusText);this.showMainError("Validation ajax call failed");this.formElements.each(function(g,f){g.afterAjaxValidation()})}.bind(this),onSuccess:function(f){if(typeOf(f)==="null"){fconsole("Fabrik form::doSubmit Ajax response empty.");this.showMainError("Validation ajax response empty");return}this.formElements.each(function(j,h){j.afterAjaxValidation()});var e=this.form.getPosition();if(this.options.admin){document.id(window).scrollTo(e.x,e.y-70)}else{document.id(window).scrollTo(e.x,e.y-10)}f=JSON.decode(f);var g=this.showGroupError(f,b);if(g){this.disableSubmitApply();if(!this.tabbed){if(d===-1){this.changePage(d)}}}else{this.saveGroupsToDb();if(this.tabbed){this.tabShow(d)}else{this.changePage(d)}}this.ajax=null}.bind(this)}).send()},showGroupError:function(e,f){var a;if(this.tabbed){var g=this.form.getElement(".tab-pane.active").id;a=this.options.pages.get(g.replace("group-tab","").toInt())}else{a=Array.from(this.options.pages.get(this.currentPage.toInt()))}var b=false;$H(f).each(function(h,d){d=d.replace(/\[(.*)\]/,"").replace(/%5B(.*)%5D/,"");if(this.formElements.has(d)){var j=this.formElements.get(d);if(a.contains(j.groupid.toInt())){if(e.errors[d]){if(typeOf(e.errors[d])!=="null"){var l=e.errors[d].flatten().join("<br />");if(l!==""){b=this.showElementError(l,d)||b}else{this.showElementError("",d)}}else{this.showElementError("",d)}}if(e.modified[d]){if(j){j.update(e.modified[d])}}}}}.bind(this));this.updateMainError();return b},saveGroupsToDb:function(){if(this.options.multipage_save===0){return}Fabrik.fireEvent("fabrik.form.groups.save.start",[this]);if(this.result===false){this.result=true;return}var b=$H(this.getFormData());b=this.prepareRepeatsForAjax(b);b.fabrik_ajax="1";b.format="raw";b.task="form.savepage";var a="index.php?option=com_fabrik&format=raw&page="+this.currentPage;new Request({url:a,data:b,onRequest:function(){Fabrik.loader.start(this.getBlock(),"COM_FABRIK_SAVING")}.bind(this),onCancel:function(){Fabrik.loader.stop(this.getBlock())}.bind(this),onComplete:function(){Fabrik.loader.stop(this.getBlock())}.bind(this),onFailure:function(d){fconsole("Fabrik form::saveGroupsToDb Ajax failure: Code "+d.status+": "+d.statusText);this.showMainError("Partial save ajax call failed");this.formElements.each(function(f,e){f.afterAjaxValidation()})}.bind(this),onSuccess:function(d){fconsole("Fabrik form::saveGroupsToDb Ajax response: "+d);this.formElements.each(function(f,e){f.afterAjaxValidation()});Fabrik.fireEvent("fabrik.form.groups.save.completed",[this]);if(this.result===false){this.result=true;return}if(this.options.ajax){Fabrik.fireEvent("fabrik.form.groups.save.end",[this,d])}}.bind(this)}).send()},doElementEvent:function(a,b){this.options.ajaxValidation===true?this.doElementValidation(a,b):this.doElementClearError(a,b)},doElementValidation:function(d,f,g){if(this.options.ajaxValidation===false){return}var a=id=this._getValidationElId(d,f);if(typeOf(document.id(id))==="null"){fconsole("Fabrik form.js::doElementValidation: Cannot find the field: "+id);return}if(d.target.hasClass("autocomplete-trigger")){id=id.replace("-auto-complete","")}if(document.id(id).getProperty("readonly")===true||document.id(id).getProperty("readonly")==="readonly"){}var b=this.formElements.get(id);if(!b){g=typeOf(g)==="null"?"_time":g;id=id.replace(g,"");b=this.formElements.get(id);if(!b){fconsole("Fabrik form.js::doElementValidation: Cannot find the formElement: "+id);return}}b.doValidation(d,f,id,a)},doElementClearError:function(a,b){var d=this._getValidationElId(a,b);this.showElementError("",d,true);this.updateMainError()},_getValidationElId:function(a,b){var d;if(typeOf(a)==="event"||typeOf(a)==="object"||typeOf(a)==="domevent"){if(b===true){d=document.id(a.target).getParent(".fabrikSubElementContainer").id}else{d=a.target.id}}else{d=a}return d},prepareRepeatsForAjax:function(a){this.getForm();if(!this.form){return}if(typeOf(a)==="hash"){a=a.getClean()}this.form.getElements("input[name^=fabrik_repeat_group]").each(function(b){if(b.id.test(/fabrik_repeat_group_\d+_counter/)){var d=b.name.match(/\[(.*)\]/)[1];a["fabrik_repeat_group["+d+"]"]=b.get("value")}});return a},showElementError:function(b,e,d){d=typeOf(d)!=="null"?d:false;var a=(b==="")?"fabrikSuccess":"fabrikError";this.formElements.get(e).setErrorMessage(b,a,d);return(a==="fabrikSuccess")?false:true},updateMainError:function(){var b=this.form.getElement(".fabrikMainError");var a=this.form.getElements(".fabrikError").filter(function(f,d){return !f.hasClass("fabrikMainError")});if(a.length>0){if(b.hasClass("fabrikHide")){this.showMainError(this.options.error)}}else{this.hideMainError()}},showMainError:function(b){var a=this.form.getElement(".fabrikMainError");a.getChildren("span").each(function(d){d.destroy()});a.grab(new Element("span").set("html",b));a.removeClass("fabrikHide");new Fx.Tween(a,{property:"opacity",duration:500}).start(0,1)},hideMainError:function(){var a=this.form.getElement(".fabrikMainError");if(a.hasClass("fabrikHide")){return}new Fx.Tween(a,{property:"opacity",duration:500,onComplete:function(){a.addClass("fabrikHide")}}).start(1,0)},getForm:function(){this.form=document.id(this.getBlock());return this.form},getBlock:function(){var a=this.options.editable===true?"form_"+this.id:"details_"+this.id;if(this.options.rowid!==""){a+="_"+this.options.rowid}return a},enableElement:function(a){a.disabled="";a.setStyle("opacity",1)},disableElement:function(a){a.disabled="disabled";a.setStyle("opacity",0.5)},dispatchEvent:function(b,a,e,f){if(typeOf(f)==="string"){f=Encoder.htmlDecode(f)}var d=this.formElements.get(a);if(!d){Object.each(this.formElements,function(g){if(a===g.baseElementId){d=g}})}if(d&&f!==""){d.addNewEvent(e,f)}},action:function(a,d){var b=this.formElements.get(d);Browser.exec("oEl."+a+"()")},triggerEvents:function(a){this.formElements.get(a).fireEvents(arguments[1])},_getButton:function(d){if(!this.getForm()){return}var a=this.form.getElement("input[type=button][name="+d+"]");if(!a){a=this.form.getElement("input[type=submit][name="+d+"]")}if(!a){a=this.form.getElement("button[type=button][name="+d+"]")}if(!a){a=this.form.getElement("button[type=submit][name="+d+"]")}return a},doPageNav:function(d,a){d.stop();if(this.options.editable){var b=Array.from(this.options.pages.get(this.currentPage.toInt()));if(this.gidsValidation(b)){this.validateByAjax(a)}else{this.changePage(a)}}else{this.changePage(a)}},changePage:function(a){Fabrik.fireEvent("fabrik.form.page.change.start",[this]);if(this.result===false){this.result=true;return}this.currentPage=this.currentPage.toInt();if(this.currentPage+a>=0&&this.currentPage+a<this.options.pages.getKeys().length){this.currentPage=this.currentPage+a;if(!this.pageGroupsVisible()){this.changePage(a)}}this.setPageButtons();document.id("page_"+this.currentPage).setStyle("display","");this.setMozBoxWidths();this.hideOtherPages();Fabrik.fireEvent("fabrik.form.page.change.end",[this]);if(this.result===false){this.result=true;return}},pageGroupsVisible:function(){var a=false;this.options.pages.get(this.currentPage).each(function(b){var d=document.id("group"+b);if(typeOf(d)!=="null"){if(d.getStyle("display")!=="none"){a=true}}});return a},hideOtherPages:function(){var a;this.options.pages.each(function(d,b){if(b.toInt()!==this.currentPage.toInt()){a=document.id("page_"+b);if(typeOf(a)!=="null"){a.hide()}}}.bind(this))},setPageButtons:function(){var b=this.form.getElement(".fabrikPagePrevious");var a=this.form.getElement(".fabrikPageNext");if(typeOf(a)!=="null"){if(this.currentPage===this.options.pages.getKeys().length-1){this.enableSubmitApply();this.disableElement(a)}else{this.enableElement(a)}}if(typeOf(b)!=="null"){if(this.currentPage===0){this.disableElement(b)}else{this.enableElement(b)}}},tabValidate:function(f,b,a){var d;if(this.options.editable){var g=this.form.getElement(".tab-pane.active").id;if(this.options.pages.length===1){d=[this.options.pages["0"][g.replace("group-tab","").toInt()]]}else{d=this.options.pages.get(g.replace("group-tab","").toInt())}if(this.gidsValidation(d)){f.preventDefault();this.validateByAjax(b)}}},tabShow:function(a){var e=this.form.getElement(".nav-tabs").getElement("li.active");var b=this.form.getElement(".tab-pane.active");var d=document.id(a.getProperty("href").substring(1));a=a.getParent("li");e.removeClass("active");b.removeClass("active");a.addClass("active");d.addClass("active")},duplicateGroupsToMin:function(){if(!this.form){return}if(this.options.rowid===""){Fabrik.fireEvent("fabrik.form.group.duplicate.min",[this]);Object.each(this.options.minRepeat,function(g,f){if(g===0){var b=this.form.getElement("#group"+f+" .deleteGroup");if(typeOf(b)!=="null"){var h=new Event.Mock(b,"click");this.deleteGroup(h)}}else{var a=this.form.getElement("#group"+f+" .addGroup");if(typeOf(a)!=="null"){var d=new Event.Mock(a,"click");for(var e=1;e<g;e++){this.duplicateGroup(d)}}}}.bind(this))}},deleteGroup:function(k){Fabrik.fireEvent("fabrik.form.group.delete.start",[this,k]);if(this.result===false){this.result=true;return}k.stop();var n=k.target.getParent(".fabrikGroup");var f=0;n.getElements(".deleteGroup").each(function(o,e){if(o.getElement("img")===k.target||o.getElement("i")===k.target||o===k.target){f=e}}.bind(this));var h=n.id.replace("group","");var j=document.id("fabrik_repeat_group_"+h+"_counter").get("value").toInt();if(j<=this.options.minRepeat[h]&&this.options.minRepeat[h]!==0){return}delete this.duplicatedGroups.i;if(document.id("fabrik_repeat_group_"+h+"_counter").value==="0"){return}var b=n.getElements(".fabrikSubGroup");var l=k.target.getParent(".fabrikSubGroup");this.subGroups.set(h,l.clone());if(b.length<=1){this.hideLastGroup(h,l);Fabrik.fireEvent("fabrik.form.group.delete.end",[this,k,h,f])}else{var a=l.getPrevious();new Fx.Tween(l,{property:"opacity",duration:300,onComplete:function(){if(b.length>1){l.dispose()}this.formElements.each(function(p,o){if(typeOf(p.element)!=="null"){if(typeOf(document.id(p.element.id))==="null"){p.decloned(h);delete this.formElements.k}}}.bind(this));b=n.getElements(".fabrikSubGroup");var e={};this.formElements.each(function(p,o){if(p.groupid===h){e[o]=p.decreaseName(f)}}.bind(this));$H(e).each(function(p,o){if(o!==p){this.formElements[p]=this.formElements[o];delete this.formElements[o]}}.bind(this));Fabrik.fireEvent("fabrik.form.group.delete.end",[this,k,h,f])}.bind(this)}).start(1,0);if(a){var m=document.id(window).getScroll().y;var g=a.getCoordinates();if(g.top<m){var d=g.top;this.winScroller.start(0,d)}}}document.id("fabrik_repeat_group_"+h+"_counter").value=document.id("fabrik_repeat_group_"+h+"_counter").get("value").toInt()-1;this.repeatGroupMarkers.set(h,this.repeatGroupMarkers.get(h)-1)},hideLastGroup:function(a,g){var e=g.getElement(".fabrikSubGroupElements");var d=new Element("div",{"class":"fabrikNotice alert"}).appendText(Joomla.JText._("COM_FABRIK_NO_REPEAT_GROUP_DATA"));if(typeOf(e)==="null"){e=g;var f=e.getElement(".addGroup");var b=e.getParent("table").getElements("thead th").getLast();if(typeOf(f)!=="null"){f.inject(b)}}e.setStyle("display","none");d.inject(e,"after")},getClone:function(b,a){if(!a){a=this.subGroups.get(b)}var d=null;if(this.duplicatedGroups.has(b)){if(!a){d=this.duplicatedGroups.get(b)}else{d=a.cloneNode(true)}}else{d=a.cloneNode(true);this.duplicatedGroups.set(b,d)}return d},repeatGetChecked:function(b){var a=[];b.getElements(".fabrikinput").each(function(d){if(d.type==="radio"&&d.getProperty("checked")){a.push(d)}});return a},duplicateGroup:function(k){var n,a;Fabrik.fireEvent("fabrik.form.group.duplicate.start",[this,k]);if(this.result===false){this.result=true;return}if(k){k.stop()}var r=k.target.getParent(".fabrikSubGroup");var p=k.target.getParent(".fabrikGroup");var q=p.id.replace("group","").toInt();var h=document.id("fabrik_repeat_group_"+q+"_counter").get("value").toInt();if(this.options.maxRepeat[q]!==0&&h>=this.options.maxRepeat[q]){return}document.id("fabrik_repeat_group_"+q+"_counter").value=h+1;if(this.isFirstRepeatSubGroup(p)){this.showFirstSubGroup(p);return}var m=this.getClone(q,r);var b=this.repeatGetChecked(p);if(p.getElement("table.repeatGroupTable")){p.getElement("table.repeatGroupTable").appendChild(m)}else{p.appendChild(m)}b.each(function(e){e.setProperty("checked",true)});var g=[];this.subelementCounter=0;var f=false;var j=m.getElements(".fabrikinput");var s=null;var l=this.repeatGroupMarkers.get(q);this.formElements.each(function(t){var v=false;n=null;var o=-1;j.each(function(A){f=t.hasSubElements();a=A.getParent(".fabrikSubElementContainer");var z=(f&&a)?a.id:A.id;var C=t.getCloneName();if(z.contains(C)){s=A;v=true;if(f){o++;n=A.getParent(".fabrikSubElementContainer");if(document.id(z).getElement("input")){A.cloneEvents(document.id(z).getElement("input"))}}else{A.cloneEvents(t.element);var B=Array.from(t.element.id.split("_"));B.splice(B.length-1,1,l);A.id=B.join("_");var y=A.getParent(".fabrikElementContainer").getElement("label");if(y){y.setProperty("for",A.id)}}if(typeOf(A.name)!=="null"){A.name=A.name.replace("[0]","["+l+"]")}}}.bind(this));if(v){if(f&&typeOf(n)!=="null"){var u=Array.from(t.options.element.split("_"));u.splice(u.length-1,1,l);n.id=u.join("_")}var e=t.options.element;var x=t.unclonableProperties();var w=new CloneObject(t,true,x);w.container=null;w.options.repeatCounter=l;w.origId=e;if(f&&typeOf(n)!=="null"){w.element=document.id(n);w.cloneUpdateIds(n.id);w.options.element=n.id;w._getSubElements()}else{w.cloneUpdateIds(s.id)}g.push(w)}}.bind(this));this.duplicateResetElements(g,q);var d={};d[q]=g;this.addElements(d);this.duplicateScrollToClone(m);new Fx.Tween(m,{property:"opacity",duration:500}).set(0);m.fade(1);Fabrik.fireEvent("fabrik.form.group.duplicate.end",[this,k,i,l]);this.repeatGroupMarkers.set(i,this.repeatGroupMarkers.get(i)+1)},isFirstRepeatSubGroup:function(b){var a=b.getElements(".fabrikSubGroup");return a.length===1&&b.getElement(".fabrikNotice")},showFirstSubGroup:function(e){var a=e.getElements(".fabrikSubGroup");var b=a[0].getElement(".fabrikSubGroupElements");if(typeOf(b)==="null"){e.getElement(".fabrikNotice").dispose();b=a[0];var d=e.getElement(".addGroup");d.inject(b.getElement("td.fabrikGroupRepeater"));b.setStyle("display","")}else{a[0].getElement(".fabrikNotice").dispose();a[0].getElement(".fabrikSubGroupElements").show()}this.repeatGroupMarkers.set(i,this.repeatGroupMarkers.get(i)+1)},duplicateScrollToClone:function(f){var b=window.getHeight();var e=document.id(window).getScroll().y;var d=f.getCoordinates();if(d.bottom>(e+b)){var a=d.bottom-b;this.winScroller.start(0,a)}},duplicateResetElements:function(b,a){b.each(function(e){e.cloned(c);var d=new RegExp(this.options.group_pk_ids[a]);if(!this.options.group_copy_element_values[a]||(this.options.group_copy_element_values[a]&&e.element.name&&e.element.name.test(d))){e.reset()}else{e.resetEvents()}}.bind(this))},showErrors:function(a){var b=null;if(a.id===this.id){var e=new Hash(a.errors);if(e.getKeys().length>0){if(typeOf(this.form.getElement(".fabrikMainError"))!=="null"){this.form.getElement(".fabrikMainError").set("html",this.options.error);this.form.getElement(".fabrikMainError").removeClass("fabrikHide")}e.each(function(f,g){if(typeOf(document.id(g+"_error"))!=="null"){var h=document.id(g+"_error");for(var d=0;d<f.length;d++){for(var j=0;j<f[d].length;j++){b=new Element("div").appendText(f[d][j]).inject(h)}}}else{fconsole(g+"_error not found (form show errors)")}})}}},testPrevNext:function(){var a=this.options.editable===true?"form":"details";var e=this.form.getElement("input[name=rowid]");var d=typeOf(e)==="null"?"":e.value;var b={option:"com_fabrik",view:a,controller:"form",fabrik:this.id,rowid:d,format:"raw",task:"paginate",dir:1};[".previous-record",".next-record"].each(function(f,g){b.dir=g;if(this.form.getElement(f)){var h=new Request({url:"index.php",data:b,onComplete:function(j){Fabrik.loader.stop(this.getBlock());j=JSON.decode(j);this.update(j);this.form.getElement("input[name=rowid]").value=j.post.rowid}.bind(this)});this.form.getElement(f).addEvent("click",function(j){h.options.data.rowid=this.form.getElement("input[name=rowid]").value;j.stop();Fabrik.loader.start(this.getBlock(),Joomla.JText._("COM_FABRIK_LOADING"));h.send()}.bind(this))}}.bind(this))},update:function(e){Fabrik.fireEvent("fabrik.form.update",[this,e.data]);if(this.result===false){this.result=true;return}var a=arguments[1]||false;var b=e.data;this.getForm();if(this.form){var d=this.form.getElement("input[name=rowid]");if(d&&b.rowid){d.value=b.rowid}}this.formElements.each(function(g,f){if(typeOf(b[f])==="null"){if(f.substring(f.length-3,f.length)==="_ro"){f=f.substring(0,f.length-3)}}if(typeOf(b[f])==="null"){if(e.id===this.id&&!a){g.update("")}}else{g.update(b[f])}}.bind(this))},appendInfo:function(a){this.formElements.each(function(d,b){if(d.appendInfo){d.appendInfo(a,b)}}.bind(this))}});