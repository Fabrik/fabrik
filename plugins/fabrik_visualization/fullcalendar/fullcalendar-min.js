/*! Fabrik */
define(["jquery","fab/fabrik","fullcalendar"],function(t,e,i){var n=new Class({Implements:[Options],options:{canAdd:!1,show_week:!1,show_day:!1,default_view:"dayView",time_format:"",first_week_day:1,minDuration:0,greyscaledweekend:!1,calOptions:{},url:{del:"index.php?option=com_fabrik&controller=visualization.fullcalendar&view=visualization&task=deleteEvent&format=raw"}},initialize:function(i,n){function o(t,e,i){c=t,p=i.name,d.calendar.on("mousemove",a)}function a(){c=p=null,d.calendar.off("mousemove",a)}var d=this,s="",l=[];this.el=t("#"+i),this.calendar=this.el.find('*[data-role="calendar"]'),this.setOptions(n),this.date=new Date,this.clickdate=null,this.ajax={},this.windowopts={id:"addeventwin",title:"",loadMethod:"xhr",minimizable:!1,evalScripts:!0},this.el.find(".addEventButton").on("click",function(t){t.preventDefault(),d.openAddEvent(t)}),e.addEvent("fabrik.form.submitted",function(t,i){d.calendar.fullCalendar("refetchEvents"),e.Windows.addeventwin.close()}),this.options.eventLists.each(function(t,e){l.push({events:function(i,n,o,a){new Request({url:this.options.url.add+"&listid="+t.value+"&eventListKey="+e,evalScripts:!0,onSuccess:function(t,e){"null"!==typeOf(e)&&this.processEvents(e,a)}.bind(this,a)}).send()}.bind(this),color:t.colour})}.bind(this)),this.options.show_week!==!1&&(s+="agendaWeek"),this.options.show_day!==!1&&(s.length>0&&(s+=","),s+="agendaDay"),s.length>0&&(s="month,"+s);var r="month";switch(this.options.default_view){case"monthView":break;case"weekView":this.options.show_week!==!1&&(r="agendaWeek");break;case"dayView":this.options.show_day!==!1&&(r="agendaDay")}var c=null,p=null;this.calendar.dblclick(function(t){c&&d.openAddEvent(t,p,c)}),this.calOptions={header:{left:"prev,next today",center:"title",right:s},fixedWeekCount:!1,timeFormat:this.options.time_format,defaultView:r,nextDayThreshold:"00:00:00",firstDay:this.options.first_week_day,eventSources:l,defaultTimedEventDuration:this.options.minDuration,minTime:this.options.open,maxTime:this.options.close,weekends:this.options.showweekends,eventClick:function(t,e,i){return d.clickEntry(t),!1},dayClick:o,viewRender:function(e,i){d.options.greyscaledweekend===!0&&(t("td.fc-sat").css("background","#f2f2f2"),t("td.fc-sun").css("background","#f2f2f2"))},eventRender:function(t,e){e.find(".fc-title").html(t.title)},loading:function(e){e||t(".fc-view-container").delegate(".popover button.jclose","click",function(){var e=t(this).data("popover");t("#"+e).popover("hide")})}},t.extend(!0,this.calOptions,JSON.parse(d.options.calOptions)),this.calendar.fullCalendar(this.calOptions),document.addEvent("click:relay(button[data-task=viewCalEvent], a[data-task=viewCalEvent])",function(e){e.preventDefault();var i=e.target.findClassUp("calEventButtons").id;i=i.replace(/_buttons/,"");var n=d.calendar.fullCalendar("clientEvents",i)[0];t("#"+i).popover("hide"),d.viewEntry(n)}),document.addEvent("click:relay(button[data-task=editCalEvent], a[data-task=editCalEvent])",function(e){e.preventDefault();var i=e.target.findClassUp("calEventButtons").id;i=i.replace(/_buttons/,"");var n=d.calendar.fullCalendar("clientEvents",i)[0];t("#"+i).popover("hide"),d.editEntry(n)}),document.addEvent("click:relay(button[data-task=deleteCalEvent], a[data-task=deleteCalEvent])",function(e){e.preventDefault();var i=e.target.findClassUp("calEventButtons").id;i=i.replace(/_buttons/,"");var n=d.calendar.fullCalendar("clientEvents",i)[0];t("#"+i).popover("hide"),d.deleteEntry(n)}),t(document).on("click",".popover .jclose",function(e,i){e.preventDefault();var n=t(e.target).attr("data-popover");t("#"+n).popover("hide")}),this.ajax.deleteEvent=new Request({url:this.options.url.del,data:{visualizationid:this.options.calendarId},onComplete:function(){d.calendar.fullCalendar("refetchEvents")}})},processEvents:function(i,n){i=$H(JSON.decode(i));var o,a,d,s,l,r,c,p,u,v,h,f,m,w,_=[];i.each(function(i){v=t(e.jLayouts["fabrik-visualization-fullcalendar-event-popup"])[0],h=i._listid+"_"+i.id,v.id="fabrikevent_"+h,f=t(e.jLayouts["fabrik-visualization-fullcalendar-viewevent"])[0],m=moment(i.startdate),w=moment(i.enddate),p=u="",(moment(w.format("YYYY-MM-DD"))>moment(m.format("YYYY-MM-DD"))||i.startShowTime===!1&&i.endShowTime===!1)&&(p=m.format("MMM DD")+" ",u=w.format("MMM DD")+" "),o=a="",i.startShowTime===!0&&i.endShowTime===!0&&(o=m.format("hh.mm A"),a=w.format("hh.mm A")),f.getElement("#viewstart").innerHTML=p+o,f.getElement("#viewend").innerHTML=u+a,d=t(e.jLayouts["fabrik-visualization-fullcalendar-viewbuttons"]),d[0].id="fabrikevent_buttons_"+h,l=d.find(".popupDelete"),i._canDelete===!1?l.remove():l.attr("title",Joomla.JText._("PLG_VISUALIZATION_FULLCALENDAR_DELETE")),r=d.find(".popupEdit"),i._canEdit===!1?r.remove():r.attr("title",Joomla.JText._("PLG_VISUALIZATION_FULLCALENDAR_EDIT")),c=d.find(".popupView"),i._canView===!1?c.remove():c.attr("title",Joomla.JText._("PLG_VISUALIZATION_FULLCALENDAR_VIEW")),t(v).attr("data-content",t(f).prop("outerHTML")+d.prop("outerHTML")),s=""===p?"auto":"200px",t(v).attr("data-title",'<div style="width:'+s+'; height: 35px;"><div style="float:right;"><button class="btn jclose" data-popover="'+v.id+'" data-toggle="tooltip" title="'+Joomla.JText._("PLG_VISUALIZATION_FULLCALENDAR_CLOSE")+'"><i class="icon-remove"></i></button></div><div style="text-align:center;">'+i.label+"</div></div>"),t(v).append(i.label),_.push({id:v.id,title:t(v).prop("outerHTML"),start:i.startdate,end:i.enddate,url:i.link,className:i.status,allDay:i.allday,listid:i._listid,rowid:i.__pk_val,formid:i._formid})}.bind(_)),n(_)},addEvForm:function(i){var n=this;"undefined"!=typeof t&&t(this.popOver).popover("hide"),this.windowopts.id="addeventwin";var o="index.php?option=com_fabrik&controller=visualization.fullcalendar&view=visualization&task=addEvForm&format=raw&listid="+i.listid+"&rowid="+i.rowid;if(o+="&visualizationid="+this.options.calendarId,i.nextView&&(o+="&nextview="+i.nextView),o+="&fabrik_window_id="+this.windowopts.id,null!==this.clickdate){var a=n.calendar.fullCalendar("option","defaultTimedEventDuration").split(":"),d=moment(this.clickdate).add({h:a[0],m:a[1],s:a[2]}).format("YYYY-MM-DD HH:mm:ss");o+="&start_date="+this.clickdate+"&end_date="+d}this.windowopts.type="window",this.windowopts.contentURL=o,this.windowopts.title=i.title,this.windowopts.modalId="fullcalendar_addeventwin";var s=this.options.filters;this.windowopts.onContentLoaded=function(){s.each(function(t){if(document.id(t.key))switch(document.id(t.key).get("tag")){case"select":document.id(t.key).selectedIndex=t.val;break;case"input":document.id(t.key).value=t.val}}),this.fitToContent(!1)},e.getWindow(this.windowopts)},viewEntry:function(t){this.clickdate=null;var e={};e.id=t.formid,e.rowid=t.rowid,e.listid=t.listid,e.nextView="details",e.title=Joomla.JText._("PLG_VISUALIZATION_FULLCALENDAR_VIEW_EVENT"),this.addEvForm(e)},editEntry:function(t){this.clickdate=null;var e={};e.id=t.formid,e.rowid=t.rowid,e.listid=t.listid,e.nextView="form",e.title=Joomla.JText._("PLG_VISUALIZATION_FULLCALENDAR_EDIT_EVENT"),this.addEvForm(e)},deleteEntry:function(t){window.confirm(Joomla.JText._("PLG_VISUALIZATION_FULLCALENDAR_CONF_DELETE"))&&(this.ajax.deleteEvent.options.data={id:t.rowid,listid:t.listid},this.ajax.deleteEvent.send())},clickEntry:function(e){if(this.options.showFullDetails===!1){var i="fabrikevent_"+e.listid+"_"+e.rowid;t("#"+i).popover("show")}else this.viewEntry(e)},openAddEvent:function(t,e,i){var n,o,a,d,s,l,r,c;if(this.options.canAdd!==!1&&("month"!==e||this.options.readonlyMonth!==!0)){switch(t.type){case"dblclick":c=i;break;case"click":c=moment();break;default:return void window.alert("Unknown event in OpenAddEvent: "+t.type)}"month"===e?a=d="00":(a=(a=c.hour())<10?"0"+a:a,d=(d=c.minute())<10?"0"+d:d),o=(o=c.date())<10?"0"+o:o,s=(s=c.month()+1)<10?"0"+s:s,l=c.year(),this.clickdate=l+"-"+s+"-"+o+" "+a+":"+d+":00",("dblclick"!==t.type||this.dateInLimits(this.clickdate))&&(this.options.eventLists.length>1?this.openChooseEventTypeForm(this.clickdate,n):(r={},r.rowid="",r.id="",r.listid=this.options.eventLists[0].value,r.title=Joomla.JText._("PLG_VISUALIZATION_FULLCALENDAR_ADD_EVENT"),this.addEvForm(r)))}},dateInLimits:function(t){var e=new moment(t);if(""!==this.options.dateLimits.min){var i=new moment(this.options.dateLimits.min);if(e.isBefore(i))return window.alert(Joomla.JText._("PLG_VISUALIZATION_FULLCALENDAR_DATE_ADD_TOO_EARLY")),!1}if(""!==this.options.dateLimits.max){var n=new moment(this.options.dateLimits.max);if(e.isAfter(n))return window.alert(Joomla.JText._("PLG_VISUALIZATION_FULLCALENDAR_DATE_ADD_TOO_LATE")),!1}return!0},openChooseEventTypeForm:function(t,i){var n="index.php?option=com_fabrik&tmpl=component&view=visualization&controller=visualization.fullcalendar&task=chooseAddEvent&format=partial&id="+this.options.calendarId+"&d="+t+"&rawd="+i;n+="&renderContext="+this.el.prop("id").replace(/visualization_/,""),this.windowopts.contentURL=n,this.windowopts.id="chooseeventwin",this.windowopts.modalId="fullcalendar_!chooseeventwin",e.getWindow(this.windowopts)}});return n});