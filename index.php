<?php
require_once('config.php');
require_once(APP_PATH.'lib/service.php');

/**
 * Get Google Analytics data
 *
 */
$ga = new gapi(GOOGLE_USERNAME, GOOGLE_PASSWORD);
/*if($sites = $ga->requestAccountData()) {
    print_r($sites);
}*/

$ga->requestReportData(
    $analytics_report, // report_id
    array('date','hour'), // dimensions
    array('pageviews', 'visits', 'pageLoadTime'), // metrics
    array('-date', '-hour'), // sort_metric
    null, // filter
    null, // start_date
    null, // end_date
    1, // start_index
    48 // max_results
);
$analytics = $ga->getResults();

/**
 * Get pingdom data
 *
 */
$pingdom = new Pingdom(PINGDOM_USERNAME, PINGDOM_PASSWORD, PINGDOM_KEY);
$performance = $pingdom->summaryPerformance($pingdom_check, array(
    'from' => time()-(60*60*48),
    'to' => time(),
    'resolution' => 'hour',
    'includeuptime' => 'true',
    'order' => 'desc'
));
if($performance) {
    $responsetimes = array();
    foreach($performance->summary->hours as $data) {
        $hour = date('H', $data->starttime);
        $responsetimes[$hour] = $data->avgresponse;
    }
}

/**
 * Prepare data for graph
 *
 */
$hours = array();
$views = array();
$times = array();
foreach($analytics as $data) {
    $pageviews = $data->getPageviews();
    $visits = $data->getVisits();
    if($pageviews || $visits || !empty($hours)) {
        if(count($hours) >= 24) {
            break;
        }
        $dimesions = $data->getDimesions();
        $hour = $dimesions['hour'];

        $responsetime = $responsetimes[$hour];

        $hours[] = $hour;
        $views[] = $pageviews;
        $times[] = $responsetime;
        
        //$rows[] = array($hour, $pageviews, $responsetime);
    }
}
$hours = array_reverse($hours);
$views = array_reverse($views);
$times = array_reverse($times);

?><!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8" />
        <title>Sitedash</title>
    </head>
    <body>
        <div id="chart" style="width: 900px; height: 200px; margin: 0 auto"></div>

        <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.6.1/jquery.min.js"></script>
        <script type="text/javascript" src="js/highcharts.js"></script>
        <script type="text/javascript">
            var chart;
            $(document).ready(function() {

                chart = new Highcharts.Chart({
                    chart: {
                        renderTo: 'chart',
                        zoomType: 'xy'
                    },
                    title: {
                        text: false
                    },
                    exporting: {
                        enabled: false // Hide buttons
                    },
                    tooltip: {
                        formatter: function() {
                            var unit = {
                                'Response time': 'ms',
                                'Visitors': ''
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
                            text: 'Visitors (Analytics)',
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
                    }],
                    xAxis: [{
                        categories: <?php echo json_encode($hours); ?>
                    }],
                    series: [{
                        name: 'Response time', // Pingdom
                        color: '#00cc00',
                        type: 'area',
                        yAxis: 1,
                        data: <?php echo json_encode($times); ?>
                    }, {
                        name: 'Visitors', // Analytics
                        type: 'line',
                        color: '#0077cc',
                        data: <?php echo json_encode($views); ?>
                    }]
                });

            });
        </script>
    </body>
</html>