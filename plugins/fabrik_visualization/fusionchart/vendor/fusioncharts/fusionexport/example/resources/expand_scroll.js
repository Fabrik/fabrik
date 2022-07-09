FusionCharts.items.myChartId.addEventListener('renderComplete', (evt, data) => {
  setTimeout(() => {
    evt.sender.resizeTo('3000', '800');
    FusionExport.emit('CAPTURE_EXIT');
  }, 3000);
});
