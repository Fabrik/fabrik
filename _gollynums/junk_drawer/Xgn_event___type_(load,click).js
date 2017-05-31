var form = Fabrik.getBlock('form_23');
var table = 'gn_event___';
var buttons = table + 'type';

var chosen = form.elements.get(buttons).getValue();

var choice1 = 'Single Date';
var choice2 = 'Start/End';
var choice3 = 'Start/Duration';
var choice4 = 'Duration/End';
var choice5 = 'Duration';

var group_a = '#group118_tab';
var group_b = '#group122_tab';
var group_c = '#group121_tab';

switch(chosen) {
   case choice1 :
      jQuery(group_a).show();
      jQuery(group_b).hide();
      jQuery(group_c).hide();
      jQuery(group_a).trigger('click');
      break;

    case choice2 :
      jQuery(group_a).show();
      jQuery(group_b).hide();
      jQuery(group_c).show();
      jQuery(group_a).trigger('click');
      break;

    case choice3 :
      jQuery(group_a).show();
      jQuery(group_b).show();
      jQuery(group_c).hide();
      jQuery(group_a).trigger('click');
      break;

    case choice4 :
      jQuery(group_a).hide();
      jQuery(group_b).show();
      jQuery(group_c).show();
      jQuery(group_b).trigger('click');
      break;

    case choice5 :
      jQuery(group_a).hide();
      jQuery(group_b).show();
      jQuery(group_c).hide();
      jQuery(group_b).trigger('click');
      break;
}