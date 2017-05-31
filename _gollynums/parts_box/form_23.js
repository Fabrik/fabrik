function adjustPerDivisionOfYear(tab) {
window.alert('adjustPerDivisionOfYear ' + tab);
/**
* Ensure that Fabrik's loaded
*/

requirejs(['fab/fabrik'], function () {

  // The block you want to use
  var blockRef = 'form_23';

  // Should we use an exact match for the blockRef?
  var exact = true;

  var form = Fabrik.getBlock(blockRef, exact, function (block) {

    // This callback function is run once the block has been loaded.
    // The variable 'block' refers to Fabirk.blocks object that controls the form.
    var v = block.elements.get('gn_event___year').get('value');

  });
});
}