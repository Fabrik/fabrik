/*! Fabrik */

var InlineEdit=new Class({Implements:[Options,Events],options:{onComplete:function(){},onLoad:function(){},onKeyup:function(){},inputClass:"input",stripHtml:!0},initialize:function(t,e){this.setOptions(e),this.element=t,this.originalText=t.get("html").replace(/<br>/gi,"\n"),this.input=new Element("textarea",{class:this.options.inputClass,styles:this.element.getStyles("width","height","padding-top","padding-right","padding-bottom","padding-left","margin-top","margin-right","margin-bottom","margin-left","font-family","font-size","font-weight","line-height","border-top","border-right","border-bottom","border-left","background-color","color"),events:{keyup:this.keyup.bind(this),blur:this.complete.bind(this)},value:this.originalText}),this.input.setStyle("margin-left",this.input.getStyle("margin-left").toInt()-1),this.originalWidth=this.element.getStyle("width"),this.element.setStyles({visibility:"hidden",position:"absolute",width:this.element.offsetWidth}),this.input.inject(this.element,"after"),this.input.focus(),this.fireEvent("onLoad",[this.element,this.input])},keyup:function(t){t&&(this.fireEvent("onKeyup",[this.element,this.input,t]),this.element.set("html","enter"===t.key?this.getContent()+"&nbsp;":this.getContent()),"enter"===t.key&&this.input.addEvent("keydown",this.newLine.bind(this)),this.input.setStyle("height",this.element.offsetHeight),"esc"===t.key&&(this.element.set("text",this.originalText),this.end()))},getContent:function(){var t=this.input.value;return this.options.stripHtml&&(t=t.replace(/(<([^>]+)>)/gi,"")),t.replace(/\n/gi,"<br>")},newLine:function(){this.element.innerHTML=this.element.innerHTML.replace("&nbsp;",""),this.input.removeEvents("keydown")},complete:function(){this.element.set("html",this.getContent()),this.fireEvent("onComplete",this.element),this.end()},end:function(){this.input.destroy(),this.element.setStyles({visibility:"visible",position:"relative",width:this.originalWidth})}});Element.implement({inlineEdit:function(t){return new InlineEdit(this,t)}});