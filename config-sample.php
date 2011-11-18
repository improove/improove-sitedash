<?php
/**
 * App Settings
 *
 */
define('APP_PATH', realpath($_SERVER['DOCUMENT_ROOT']).'/');

/**
 * Services access details
 *
 */
define('GOOGLE_USERNAME', '');
define('GOOGLE_PASSWORD', '');

define('PINGDOM_USERNAME', '');
define('PINGDOM_PASSWORD', '');
define('PINGDOM_KEY', '');

/**
 * Debug and Error Settings
 *
 */
error_reporting(E_ALL); // 0|E_ALL
ini_set('display_errors', '1'); // 0|1

/**
 * Graph and data settings
 *
 */
$graph_title        = ''; // ex. acnestudios.com
$analytics_report   = ; // ex. 33645348, must be accessible from given Google account
$pingdom_check      = ; // ex. 240735 must be accessible from given Pingdom account

if(!$graph_title || !$analytics_report || !$pingdom_check) {
    die('Configure the application correctly.');
}