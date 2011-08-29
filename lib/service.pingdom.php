<?php
/**
 * Pingdom API wrapper
 *
 * @category  Services
 * @package   Pingdom
 * @author    Erik Pettersson <mail@ptz0n.se>
 * @copyright 2011 Erik Pettersson <mail@ptz0n.se>,
 *            2010 Anton Lindqvist <anton@qvister.se>
 * @license   http://www.opensource.org/licenses/mit-license.php MIT
 */
class Pingdom extends Service
{

    /**
     * Client username
     *
     * @var string
     *
     * @access private
     */
    private $_clientUsername;

    /**
     * Client password
     *
     * @var string
     *
     * @access private
     */
    private $_clientPassword;

    /**
     * Client key
     *
     * @var string
     *
     * @access private
     */
    private $_clientKey;

    /**
     * Version of the API to use
     *
     * @var integer
     *
     * @access private
     * @static
     */
    private static $_apiVersion = '2.0';

    /**
     * API domain
     *
     * @var string
     *
     * @access private
     * @static
     */
    private static $_domain = 'api.pingdom.com';

    /**
     * HTTP user agent
     *
     * @var string
     *
     * @access private
     * @static
     */
    private static $_userAgent = 'PHP-Pingdom';

    /**
     * Class constructor
     *
     * @param string  $clientKey     OAuth client key
     *
     * @return void
     *
     * @access public
     */
    function __construct($clientUsername, $clientPassword, $clientKey)
    {
        if(empty($clientUsername) || empty($clientPassword) || empty($clientKey)) {
            throw new Exception('All requests must include username, password and a API key.');
        }

        $this->_clientUsername  = $clientUsername;
        $this->_clientPassword  = $clientPassword;
        $this->_clientKey       = $clientKey;

        parent::__construct();

        $this->_curlOptions[CURLOPT_USERPWD] = $this->_clientUsername.':'.$this->_clientPassword;
        $this->_curlOptions[CURLOPT_HTTPHEADER] = array('App-Key: '.$this->_clientKey);
    }

    /**
     * Construct a URL
     *
     * @param string  $path           Method path
     * @param array   $params         Query string parameters
     *
     * @return string $url
     *
     * @access protected
     */
    protected function _buildUrl($path, $params = array())
    {
        $url = 'https://';
        $url .= self::$_domain;
        $url .= '/api/';
        $url .= '' . self::$_apiVersion . '/';
        $url .= $path;
        $url .= (count($params)) ? '?' . http_build_query($params) : '';

        return $url;
    }
    
    /*function __call($method, $args)
    {
        if(preg_match('/^([a-z]+)([A-Z]{1}[a-zA-Z]+)/', $method, $matches)) {
            var_dump($method);
            var_dump($matches);
        }
        // 1. if create, update or delete method
        // 2. elseif valid index method (ex. check)
        // 3. else thorw exception
        //actions                           - actions($params)
        //analysis/{checkid}                - analysis($check, $analysisid, $params)
        //analysis/{checkid}/{analysisid}   - 
        //checks
        //checks/{checkid}
        //contacts/{contactid}
        //credits
        //probes
        //reports.email
        //reports.email/{reportid}
        //reports.public
        //reports.public/{checkid}
        //reports.shared/{reportid}
        //servertime
        //settings
        //summary.average/{checkid}
        //summary.hoursofday/{checkid}
        //summary.outage/{checkid}
        //summary.performance/{checkid}
        //summary.probes/{checkid}
        //single
        //traceroute
    }*/
    
    function checks($params = array())
    {
        $url = $this->_buildUrl('checks');
        return $this->_request($url, true);
    }
    
    function check($id, $params = array())
    {
        $url = $this->_buildUrl('checks/'.$id);
        return $this->_request($url, true);
    }
    
    function results($id, $params = array())
    {
        $url = $this->_buildUrl('results/'.$id, $params);
        return $this->_request($url, true);
    }

    function summaryHoursOfDay($id, $params = array())
    {
        $url = $this->_buildUrl('summary.hoursofday/'.$id, $params);
        return $this->_request($url, true);
    }

    function summaryPerformance($id, $params = array())
    {
        $url = $this->_buildUrl('summary.performance/'.$id, $params);
        return $this->_request($url, true);
    }
}