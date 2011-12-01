<?php
require_once('config.php');
require_once(APP_PATH.'lib/service.php');

/**
 * Auth services
 *
 */
$ga = new gapi(GOOGLE_USERNAME, GOOGLE_PASSWORD);
// TODO: Catch analytics auth exception
$pingdom = new Pingdom(PINGDOM_USERNAME, PINGDOM_PASSWORD, PINGDOM_KEY);
// TODO: Catch pingdom auth exception

/**
 * Loop charts
 *
 */
foreach($charts as $slug => $chart) {

    /**
     * Read data from cache if exists and have not expired
     *
     */
    if(file_exists(APP_PATH.'cache/'.$slug)) {
        $cache = json_decode(file_get_contents(APP_PATH.'cache/'.$slug), true);
        if(isset($cache['generated']) && $cache['generated'] > time()-1800) {
            $charts[$slug] = $cache;
            continue;
        }
    }

    /**
     * Get Analytics data
     *
     */
    if($chart['analytics']) {
        $ga->requestReportData(
            $chart['analytics'], // report_id
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
    }

    /**
     * Get pingdom data
     *
     */    
    if($chart['pingdom']) {
        $performance = $pingdom->summaryPerformance($chart['pingdom'], array(
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

            $hours[] = $hour;
            $views[] = $pageviews;
            $times[] = $responsetimes[$hour];
        }
    }
    $charts[$slug]['hours'] = array_reverse($hours);
    $charts[$slug]['views'] = array_reverse($views);
    $charts[$slug]['times'] = array_reverse($times);

    // Simple file cache
    $charts[$slug]['generated'] = time();
    @file_put_contents(APP_PATH.'cache/'.$slug, json_encode($charts[$slug]));
}

?><!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8" />
        <title>Sitedash</title>
        <meta name="viewport" content="initial-scale=1, maximum-scale=1" />
    </head>
    <body>
        <?php foreach($charts as $slug => $chart) : ?>
            <div id="<?php echo $slug; ?>" style="height: 200px; margin: 3em auto"></div>
        <?php endforeach; ?>

        <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.6.1/jquery.min.js"></script>
        <script src="js/highcharts.js"></script>
        <script>
            $(document).ready(function() {

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

                var chart;
                <?php foreach($charts as $slug => $chart) : ?>
                    chart = new Highcharts.Chart(jQuery.extend(defaults, {
                        chart: {
                            renderTo: '<?php echo $slug; ?>'
                        },
                        title: {
                            text: '<?php echo $chart['title']; ?>'
                        },
                        xAxis: [{
                            categories: <?php echo json_encode($chart['hours']); ?>
                        }],
                        series: [{
                            name: 'Response time', // Pingdom
                            color: '#00cc00',
                            type: 'area',
                            yAxis: 1,
                            data: <?php echo json_encode($chart['times']); ?>
                        }, {
                            name: 'Page views', // Analytics
                            type: 'line',
                            color: '#0077cc',
                            data: <?php echo json_encode($chart['views']); ?>
                        }]
                    }));
                <?php endforeach; ?>

            });
        </script>
    </body>
</html>