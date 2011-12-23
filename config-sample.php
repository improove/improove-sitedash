<?php
/**
 * App Settings
 *
 */
define('APP_PATH', realpath($_SERVER['DOCUMENT_ROOT']).'/');
define('APP_CACHE', true);

/**
 * Services access details
 *
 */
define('GOOGLE_USERNAME', '');
define('GOOGLE_PASSWORD', '');

define('PINGDOM_USERNAME', '');
define('PINGDOM_PASSWORD', '');
define('PINGDOM_KEY', ''); // Global key (not tied to username)

/**
 * Debug and Error Settings
 *
 */
error_reporting(E_ALL); // 0|E_ALL
ini_set('display_errors', '1'); // 0|1

/**
 * Charts and data settings
 *
 */
$charts = array(
    'my-site' => array(
        'title'     => 'My Site',
        'analytics' => 0, // Report ID ex. 33645348, must be accessible from given Google account
        'pingdom'   => 0 // Check ID ex. 240735 must be accessible from given Pingdom account
    )
);