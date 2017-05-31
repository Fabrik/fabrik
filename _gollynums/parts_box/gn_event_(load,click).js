function gn_utils.js (fromForm,fromTab,fromElement,trigger) {
    requirejs(
        ['fab/fabrik'],
        function (Fabrik) {
            var blockRef = 'form_23';
            var exact = true;
            var form = Fabrik.getBlock
                blockRef,
                exact,
                function (block) {
                    var v = block.elements.get('tablename___elementname').get('value');
                    var switcher = (fromTab + fromElement+ '/' + trigger);
                    switcher.replace(/ /g,'').toLowerCase();
                    switch (switcher) {
                        case 'tab/element/trigger' :
                            /* xxx */;
                            break;
                        case 'tab/element/trigger' :
                            /* xxx */;
                            break;
                    }
                }
            );
        }  
    );
}




requirejs
  (
    ['fab/fabrik'],
    function (Fabrik)
      {
        var blockRef = 'form_23';
        var exact = true;
        var form = Fabrik.getBlock
          (
            blockRef,
            exact,
.-------->  function (block)
| BLOCK       {
.-------<<      var v = block.elements.get('tablename___elementname').get('value');
              }
          );
      }  
  );



