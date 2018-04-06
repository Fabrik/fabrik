/*! Fabrik */

require(["fab/fabrik","jquery"],function(n,d){n.buildChosen||(n.buildChosen=function(n,i){if(void 0!==d(n).chosen)return d(n).each(function(n,e){var o,a=d(e).data("chosen-options");o=a?d.extend({},i,a):i,d(e).chosen(o),d(e).addClass("chzn-done")}),!0},n.buildAjaxChosen=function(n,e,o){if(void 0!==d(n).ajaxChosen)return d(n).addClass("chzn-done"),d(n).ajaxChosen(e,o)})});