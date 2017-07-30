function division_of_year_types(from, tab) {

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


function division_of_year_choices(from, tab) {

var table    = 'gn_event___';
var baseName = table + tab + 'division_of_year';

var types    = baseName + '_types';
var choices  = baseName + '_choices';
var savers   = baseName + '_saver_';

var type     = from.form.elements.get(types).getValue();
var choice   = from.form.elements.get(choices).getValue();

var saver    = (savers + type).toLowerCase();
from.form.elements.get(saver).update(choice);
}