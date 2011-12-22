var defaults = {
    chart: {
        zoomType: 'xy'
    },
    exporting: {
        enabled: false // Hide buttons
    },
    tooltip: {
        formatter: function() {
            var unit = {
                'Response time': 'ms',
                'Page views': ''
            }[this.series.name];
            return ''+this.x +': '+ this.y +' '+ unit;
        }
    },
    plotOptions: {
        area: {
            shadow: false,
            marker: {
                radius: 3
            },
            fillOpacity: .5,
            lineWidth: 3,
            states: {
                hover: {
                    lineWidth: 3
                }
            }
        },
        line: {
            marker: {
                symbol: 'circle',
                lineWidth: 1,
                radius: 4
            },
            lineWidth: 4,
            states: {
                hover: {
                    lineWidth: 4
                }
            }
        }
    },
    legend: {
        enabled: false
    },
    yAxis: [{
        gridLineWidth: 0,
        title: {
            text: 'Page views (Analytics)',
            style: {
                color: '#0077cc'
            }
        },
        labels: {
            formatter: function() {
                return this.value;
            },
            style: {
                color: '#999'
            }
        }
    }, {
        gridLineWidth: 0,
        title: {
            text: 'Response time (Pingdom)',
            style: {
                color: '#00cc00'
            }
        },
        labels: {
            formatter: function() {
                return this.value+'ms';
            },
            style: {
                color: '#999'
            }
        },
        opposite: true
    }]
};