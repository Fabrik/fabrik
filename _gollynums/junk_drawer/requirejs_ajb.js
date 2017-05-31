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
.-------->function (block)
| BLOCK     {
.-------<<  var v = block.elements.get('tablename___elementname').get('value');
            }
        );
      }  
  );