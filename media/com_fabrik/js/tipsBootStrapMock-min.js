var FloatingTips=new Class({Implements:[Options,Events],options:{fxProperties:{transition:Fx.Transitions.linear,duration:500},position:"top",showOn:"mouseenter",hideOn:"mouseleave",content:"title",distance:50,tipfx:"Fx.Transitions.linear",heading:"",duration:500,fadein:false,notice:false,html:true,showFn:function(a){a.stop();return true},hideFn:function(a){a.stop();return true},placement:function(c,b){Fabrik.fireEvent("bootstrap.tips.place",[c,b]);var d=Fabrik.eventResults.length===0?false:Fabrik.eventResults[0];if(d===false){var a=JSON.decode(b.get("opts","{}").opts);return a&&a.position?a.position:"top"}else{return d}}},initialize:function(elements,options){this.setOptions(options);this.options.fxProperties={transition:eval(this.options.tipfx),duration:this.options.duration};window.addEvent("tips.hideall",function(e,element){if(typeOf(element)==="null"){this.hideAll()}else{this.hideOthers(element)}}.bind(this));if(elements){this.attach(elements)}},attach:function(a){this.selector=a;this.elements=$$(a);this.elements.each(function(d){var b=JSON.decode(d.get("opts","{}").opts);b=b?b:{};if(b.position){b.defaultPos=b.position;delete (b.position)}var e=Object.merge(Object.clone(this.options),b);if(e.content==="title"){e.content=d.get("title");d.erase("title")}else{if(typeOf(e.content)==="function"){var f=e.content(d);e.content=typeOf(f)==="null"?"":f.innerHTML}}e.placement=this.options.placement;e.title=e.heading;if(d.hasClass("tip-small")){e.title=e.content;jQuery(d).tooltip(e)}else{if(!e.notice){e.title+='<button class="close" data-popover="'+d.id+'">&times;</button>'}jQuery(d).popoverex(e)}}.bind(this))},addStartEvent:function(a,b){},addEndEvent:function(a,b){},getTipContent:function(a,b){},show:function(a,b){},hide:function(a,b){},hideOthers:function(a){this.elements.each(function(b){b=jQuery(b);if(typeOf(b)!=="null"&&b!==a&&typeOf(b.data("popover"))!=="null"){b.data("popover").hide()}}.bind(this))},hideAll:function(){this.elements.each(function(a){a=jQuery(a);if(typeOf(a)!=="null"&&typeOf(a.data("popover"))!=="null"){a.data("popover").hide()}}.bind(this))}});(function(b){var a=function(d,c){this.triggers=c.trigger.split(" ");this.isManualShow=false;this.hasFocus=false;this.manualShowTimer=null;this.$element=jQuery(d);this.placement="";this.position={top:-1,left:-1};this.inside=null;c.trigger="manual";c.showOn=c.hideOn=null;this.init("popover",d,c);jQuery(d).mouseenter(this.mouseenter).mouseleave(this.mouseleave).click(this.click);jQuery(d).parents(".control-group").on("focus","input[type!=hidden]",this.focus).on("focus","select",this.focus).on("blur","input[type!=hidden]",this.blur).on("blur","select",this.blur)};a.prototype=b.extend({},b.fn.popover.Constructor.prototype,{constructor:a,tip:function(){if(!this.$tip){this.$tip=b(this.options.template);if(this.options.modifier){this.$tip.addClass(this.options.modifier)}}return this.$tip},show:function(){if(this.hasContent()&&this.enabled){var h=this.tip();var e=typeof this.options.placement==="function"?this.options.placement.call(this,h[0],this.$element[0]):this.options.placement;var c=/in/.test(e);e=c?e.split(" ")[1]:e;var d=e.split("-")[0];if(!h.hasClass("in")){this.setContent()}h.attr("for",this.$element.attr("for"));if(this.options.animation){h.addClass("fade")}if(!h.hasClass("in")||this.inside!==c){h.detach().appendTo(c?this.$element:document.body)}if(!h.hasClass("in")||this.placement!==e){h.removeClass("top bottom left right").addClass(d).css({top:0,left:0,display:"block"})}var j=this.getPosition(c);var f=h[0].offsetWidth;var i=h[0].offsetHeight;var g;switch(e){case"bottom":g={top:j.top+j.height,left:j.left+j.width/2-f/2};break;case"bottom-left":g={top:j.top+j.height,left:j.left};break;case"bottom-right":g={top:j.top+j.height,left:j.left+j.width-f};break;case"top":g={top:j.top-i,left:j.left+j.width/2-f/2};break;case"top-left":g={top:j.top-i,left:j.left};break;case"top-right":g={top:j.top-i,left:j.left+j.width-f};break;case"left":g={top:j.top+j.height/2-i/2,left:j.left-f};break;case"right":g={top:j.top+j.height/2-i/2,left:j.left+j.width};break}if(!h.hasClass("in")||this.placement!==e||this.position.top!==g.top||this.position.left!==g.left){h.css(g).addClass("in")}this.placement=e;this.position=g;this.inside=c;return this}},manualShow:function(c){c=typeOf(c)!=="null"?c:5000;this.isManualShow=true;this.show();this.manualShowTimer=window.setTimeout(function(){this.manualShowTimeout()}.bind(this),c);return this},manualShowTimeout:function(){this.manualShowTimer=null;if(!this.isManualShow){return}this.hide()},manualShowCancel:function(){if(!this.isManualShow){return}if(this.manualShowTimer!==null){window.clearTimeout(this.manualShowTimer);this.manualShowTimer=null}this.hide();return this},manualHide:function(){this.manualShowCancel();this.isManualShow=false;return this},mouseenter:function(){if(typeOf(jQuery(this))==="null"||typeOf(jQuery(this).data("popover"))==="null"){return}var c=jQuery(this).data("popover");if(c.triggers.indexOf("hover")<0){return}c.show()},mouseleave:function(){if(typeOf(jQuery(this))==="null"||typeOf(jQuery(this).data("popover"))==="null"){return}var c=jQuery(this).data("popover");if(c.triggers.indexOf("hover")<0||(c.isManualShow&&(c.manualShowTimer!==null||c.hasFocus))){return}c.hide()},focus:function(){var c=jQuery(this).parents(".control-group").find(Fabrik.tips.selector);if(typeOf(c)==="null"||typeOf(c.data("popover"))==="null"){return}var d=c.data("popover");d.hasFocus=true;if(d.triggers.indexOf("focus")<0&&!d.isManualShow){return}d.show()},blur:function(){var c=jQuery(this).parents(".control-group").find(Fabrik.tips.selector);if(typeOf(c)==="null"||typeOf(c.data("popover"))==="null"){return}var d=c.data("popover");d.hasFocus=false;if(d.triggers.indexOf("focus")<0&&!d.isManualShow){return}d.hide()},click:function(c){if(typeOf(jQuery(this))==="null"||typeOf(jQuery(this).data("popover"))==="null"){return}var d=jQuery(this).data("popover");if(d.triggers.indexOf("click")<0){return}d.toggle()}});b.fn.popoverex=function(c){return this.each(function(){var f=b(this),e=f.data("popover"),d=typeof c==="object"&&c;if(!e){f.data("popover",(e=new a(this,d)))}if(typeof c==="string"){e[c]()}})}})(window.jQuery);