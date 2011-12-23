<?php
require_once('config.php');
require_once(APP_PATH.'lib/service.php');

/**
 * Auth services
 *
 */
$ga = new gapi(GOOGLE_USERNAME, GOOGLE_PASSWORD);
// TODO: Catch analytics auth exception
$pd = new Pingdom(PINGDOM_USERNAME, PINGDOM_PASSWORD, PINGDOM_KEY);
// TODO: Catch pingdom auth exception

/**
 * Loop charts
 *
 */
foreach($charts as $slug => $chart) {

    /**
     * Read data from cache if exists, have not expired and is enabled
     *
     */
    if(file_exists(APP_PATH.'cache/'.$slug) && APP_CACHE) {
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
            array('pageviews'/*, 'visits', 'pageLoadTime'*/), // metrics
            array('-date', '-hour'), // sort_metric
            null, // filter
            null, // start_date
            null, // end_date
            1, // start_index
            60 // max_results
        );
        if($analytics = $ga->getResults()) {
            foreach ($analytics as $data) {
                $pageviewsData = $data->getPageviews();
                $dimesionsData = $data->getDimesions();
                $date = strtotime($dimesionsData['date'].' '.$dimesionsData['hour'].':00:00');
                $charts[$slug]['data'][$date]['pageviews'] = $pageviewsData;
            }
        }
    }

    /**
     * Get pingdom data
     *
     */    
    if($chart['pingdom']) {
        $pingdomRequest = array(
            'from'          => time()-(60*60*48), // Last 48 hours
            'to'            => time(),
            'resolution'    => 'hour',
            'includeuptime' => 'true',
            'order'         => 'desc'
        );
        if($performance = $pd->summaryPerformance($chart['pingdom'], $pingdomRequest)) {
            foreach($performance->summary->hours as $data) {
                $charts[$slug]['data'][$data->starttime]['avgresponse'] = $data->avgresponse;
            }
        }
    }

    /**
     * Prepare data for graph
     *
     */
    $hours = array();
    $pageviews = array();
    $avgresponse = array();
    foreach($charts[$slug]['data'] as $hour => $data) {
        if((isset($data['pageviews']) && $data['pageviews']) ||
            (isset($data['avgresponse']) && $data['avgresponse']) ||
            !empty($hours)) {

            if(count($hours) >= 24) {
                break;
            }
            $hours[]        = date('H', $hour) == '00' ? date('M j') : date('H', $hour);
            $pageviews[]    = isset($data['pageviews']) ? $data['pageviews'] : 0;
            $avgresponse[]  = isset($data['avgresponse']) ? $data['avgresponse'] : 0;

        }
    }
    $charts[$slug]['hours'] = array_reverse($hours);
    $charts[$slug]['pageviews'] = array_reverse($pageviews);
    $charts[$slug]['avgresponse'] = array_reverse($avgresponse);

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
        <meta http-equiv="refresh" content="600" />
    </head>
    <body>
        <?php foreach($charts as $slug => $chart) : ?>
            <div id="<?php echo $slug; ?>" style="height: 200px; margin: 3em auto"></div>
        <?php endforeach; ?>

        <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.6.1/jquery.min.js"></script>
        <script src="js/highcharts.js"></script>
        <script src="js/scripts.js"></script>
        <script>
            $(document).ready(function() {
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
                            data: <?php echo json_encode($chart['avgresponse']); ?>
                        }, {
                            name: 'Page views', // Analytics
                            type: 'line',
                            color: '#0077cc',
                            data: <?php echo json_encode($chart['pageviews']); ?>
                        }]
                    }));
                <?php endforeach; ?>
            });
        </script>
    </body>
</html>