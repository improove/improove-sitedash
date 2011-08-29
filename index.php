<?php
require_once('config.php');
require_once(APP_PATH.'lib/service.php');

/**
 * Get Google Analytics data
 *
 */
$ga = new gapi(GOOGLE_USERNAME, GOOGLE_PASSWORD);
/*
if($sites = $ga->requestAccountData()) {
    echo '<ul>';
    foreach($sites as $site) {
        echo '<li>', $site, '</li>';
    }
    echo '</ul>';
    print_r($sites);
}
*/
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
$rows = array();
foreach($analytics as $data) {
    $pageviews = $data->getPageviews();
    $visits = $data->getVisits();
    if($pageviews || $visits || !empty($rows)) {
        if(count($rows) >= 24) {
            break;
        }
        $dimesions = $data->getDimesions();
        $hour = $dimesions['hour'];

        $responsetime = $responsetimes[$hour];

        $rows[] = array($hour, $pageviews, $responsetime);
    }
}
$rows = array_reverse($rows);
?>
<html>
    <head>
        <script type="text/javascript" src="https://www.google.com/jsapi"></script>
        <script type="text/javascript">
            // Set dynamic values
            var siteData = [
                <?php foreach($rows as $row) : ?>
                    ['<?php echo $row[0]; ?>', <?php echo $row[1]; ?>, <?php echo $row[2]; ?>/<?php echo $data_division; ?>],
                <?php endforeach; ?>
            ];
            var siteTitle = '<?php echo $graph_title; ?>';


            // Load the Visualization API and the piechart package.
            google.load('visualization', '1.0', {'packages':['corechart']});

            // Set a callback to run when the Google Visualization API is loaded.
            google.setOnLoadCallback(drawChart);

            // Callback that creates and populates a data table, 
            // instantiates the pie chart, passes in the data and
            // draws it.
            function drawChart() {
                // Create the data table.
                var data = new google.visualization.DataTable();
                data.addColumn('string', 'Views & visits');
                data.addColumn('number', 'Pageviews');
                data.addColumn('number', 'Performance');
                data.addRows(siteData);

                // Set chart options
                var options = {
                    'title': siteTitle,
                    'width': 1150,
                    'height': 600,
                };

                // Instantiate and draw our chart, passing in some options.
                var chart = new google.visualization.LineChart(document.getElementById('chart'));
                chart.draw(data, options);
            }
        </script>
    </head>
    <body style="height:100%;margin:0;padding:0;">
        <div id="chart" style="margin:0 auto;width:1150px;position:absolute;top:50%;margin-top:-300px;margin-left:-575px;left:50%;"></div>
        <!-- table style="margin:0 auto;">
            <thead>
                <tr>
                    <th>Hour</th>
                    <th>Pageviews</th>
                    <th>Response time</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($rows as $row) : ?>
                    <tr>
                        <th><?php echo $row[0]; ?></th>
                        <td><?php echo $row[1]; ?></td>
                        <td><?php echo $row[2]; ?></td>
                    <tr>
                <?php endforeach; ?>
            </tbody>
        </table -->
    </body>
</html>