(function($)
{
  if (typeof charts == 'undefined')
    return;

  var startedData = [];
  var startedArray = []
  var salesData = [];
  var salesArray = []

  charts.chart_live =
  {
    // chart data
    data: [],
    data2: [],
    totalPoints: 20,
    updateInterval: 1000,

    updateData: function() {
        $.ajax({
            url: $('#urlToUpdate').attr('data-url'),
                timeout: 10000,
                method: 'GET',
                success: function(data) {
                    if (data.status == 'ok') {
                        $('#StartedCartsValue').html(data.startedCarts);
                        startedArray.push(data.startedCarts);

                        if(startedArray.length >= 22)
                            startedArray.shift();

                        startedData = [];
                        for (var i = 0; i < startedArray.length; ++i)
                            startedData.push([i, startedArray[i]])

                        $('#EstimatedForecastValue').html(data.saleForeCast + ' &euro;');
                        salesArray.push(data.saleForeCast);

                        if(salesArray.length >= 22)
                            salesArray.shift();

                        salesData = [];
                        for (var i = 0; i < salesArray.length; ++i)
                            salesData.push([i, salesArray[i]])

                        $('#CheckoutsInProgressValue').html(data.checkoutsAmount + ' &euro;');
                        $('#VisitsValue').html(data.checkoutVisits);
                    }
                }
        });
    },

    // will hold the chart object
    plot: null,

    // chart options
    options:
    {
      series: {
            grow: { active: false },
            shadowSize: 0,
            lines: {
                show: true,
                fill: false,
                lineWidth: 2,
                steps: false
              }
          },
          grid: {
        show: true,
          aboveData: false,
          color: "#3f3f3f",
          labelMargin: 5,
          axisMargin: 0,
          borderWidth: 0,
          borderColor:null,
          minBorderMargin: 5 ,
          clickable: true,
          hoverable: true,
          autoHighlight: false,
          mouseActiveRadius: 20,
          backgroundColor : { }
      },
      colors: [],
          tooltip: true,
      tooltipOpts: {
        content: "Value is : %y.0",
        shifts: {
          x: -30,
          y: -50
        },
        defaultTheme: false
      },
          yaxis: { min: 0, max: 100 },
          xaxis: { show: true, min: 0, max: 20 }
    },

    placeholder: "#chart_live",

    // initialize
    init: function()
    {
        // apply styling
        charts.utility.applyStyle(this);

        this.updateData();

        this.plot = $.plot($(this.placeholder), [ startedData, salesData ], this.options);
        var datos = this.plot.getData();
        setTimeout(this.update, charts.chart_live.updateInterval);
    },

    // update
    update: function()
    {
        charts.chart_live.updateData();

        charts.chart_live.plot.setData([ startedData, salesData ]);
        charts.chart_live.plot.draw();
        setTimeout(charts.chart_live.update, charts.chart_live.updateInterval);
    }
  };

  $(window).on('load', function(){
    setTimeout(function(){
      charts.chart_live.init();
    }, 1000);
  });

})(jQuery);
