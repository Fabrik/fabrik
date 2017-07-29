function division_of_year_types(from, tab, trigger) {

/*
var tabbedGroup = 'start';
var trigger = 'click';
*/

   var table    = 'gn_event___';
   var baseName = table + tab + '_division_of_year';

   var types    = baseName + '_types';
   var choices  = baseName + '_choices';
   var savers   = baseName + '_saver_';

   var type     = from.form.elements.get(types).getValue();

   var saver    = (savers + type).toLowerCase();
   var saved    = from.form.elements.get(saver).getValue();
   from.form.elements.get(choices).update(saved);
   from.form.elements.get(choices).setLabel(type);   
}