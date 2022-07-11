module.exports = {
  cust: (chartConfig, index) => {
    const caption = chartConfig.dataSource.chart.caption;
    return `${index}__${caption}`;
  },
  art: ['S1', 'S2', 'S3', 'S4'],
};
