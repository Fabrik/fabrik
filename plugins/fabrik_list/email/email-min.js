/*! Fabrik */
define(["jquery","fab/list-plugin","fab/fabrik"],function(a,b,c){var d=new Class({Extends:b,initialize:function(a){this.parent(a)},watchSubmit:function(){var b=a("#emailtable"),d=this;b.submit(function(e){"undefined"!=typeof WFEditor&&WFEditor.getContent("message");var f="index.php";""!==d.options.additionalQS&&(f+="?"+d.options.additionalQS),c.loader.start(b),a.ajax({type:"POST",url:f,data:new FormData(this),encode:!0,processData:!1,contentType:!1}).done(function(a){b.html(a),c.loader.stop(b)}),e.preventDefault()})},watchAttachments:function(){a(document.body).on("click",".addattachment",function(b){b.preventDefault();var c=a(this).closest(".attachment");c.clone().insertAfter(c)}),a(document.body).on("click",".delattachment",function(b){b.preventDefault(),a(".addattachment").length>1&&a(this).closest(".attachment").remove()})},watchAddEmail:function(){a("#email_add").on("click",function(b){b.preventDefault(),a("#email_to_selectfrom option:selected").each(function(b,c){a(c).appendTo(a("#list_email_to"))})}),a("#email_remove").on("click",function(b){b.preventDefault(),a("#list_email_to option:selected").each(function(b,c){a(c).appendTo(a("#email_to_selectfrom"))})})},buttonAction:function(){var a="index.php?option=com_fabrik&controller=list.email&task=popupwin&tmpl=component&ajax=1&id="+this.listid+"&renderOrder="+this.options.renderOrder,b=this;this.listform.getElements("input[name^=ids]").each(function(b){b.get("value")!==!1&&b.checked!==!1&&(a+="&ids[]="+b.get("value"))}),a+=this.listform.getElement("input[name=checkAll]").checked?"&checkAll=1":"&checkAll=0",a+="&format=partial",""!==this.options.additionalQS&&(a+="&"+this.options.additionalQS);var d="email-list-plugin";this.windowopts={id:d,title:"Email",loadMethod:"xhr",contentURL:a,width:520,height:470,evalScripts:!0,minimizable:!1,collapsible:!0,onContentLoaded:function(){b.watchSubmit(),b.watchAttachments(),b.watchAddEmail(),this.fitToContent(!1)}},c.getWindow(this.windowopts)}});return d});