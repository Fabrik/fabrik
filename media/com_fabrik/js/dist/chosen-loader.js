/*! Fabrik */

require(["fab/fabrik","jquery"],function(a,b){a.buildChosen||(a.buildChosen=function(a,c){if(void 0!==b(a).chosen)return b(a).each(function(a,d){var e,f=b(d).data("chosen-options");e=f?b.extend({},c,f):c,b(d).chosen(e),b(d).addClass("chzn-done")}),!0},a.buildAjaxChosen=function(a,c,d){if(void 0!==b(a).ajaxChosen)return b(a).addClass("chzn-done"),b(a).ajaxChosen(c,d)})});