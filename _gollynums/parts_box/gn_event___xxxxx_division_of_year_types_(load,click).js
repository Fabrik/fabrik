/* start_division_of_year_types */
var form     = Fabrik.getBlock('form_23');

var table    = 'gn_event___';
var tabStart = 'start_';
var tabEnd   = 'end_';
var tab      = tabStart;
var baseName = table + tab + 'division_of_year';

var types    = baseName + '_types';
var choices  = baseName + '_choices';
var savers   = baseName + '_saver_';

var type     = form.elements.get(types).getValue();
var saver    = (savers + type).toLowerCase();

var saved = form.elements.get(saver).getValue();
form.elements.get(choices).update(saved);
form.elements.get(choices).setLabel(type);