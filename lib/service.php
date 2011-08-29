<?php
/**
 * Allakartor API wrapper
 *
 * @category  Services
 * @package   Allakartor
 * @author    Erik Pettersson <mail@ptz0n.se>
 * @copyright 2011 Erik Pettersson <mail@ptz0n.se>,
 *            2010 Anton Lindqvist <anton@qvister.se>
 * @license   http://www.opensource.org/licenses/mit-license.php MIT
 */
abstract class Service
{

    /**
     * Default cURL options
     *
     * @var array
     *
     * @access private
     * @static
     */
    protected static $_curlDefaultOptions = array(
        CURLOPT_HEADER => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERAGENT => ''
    );

    /**
     * cURL options
     *
     * @var array
     *
     * @access private
     */
    protected $_curlOptions;

    /**
     * HTTP response body from the last request
     *
     * @var string
     *
     * @access private
     */
    protected $_lastHttpResponseBody;

    /**
     * HTTP response code from the last request
     *
     * @var integer
     *
     * @access private
     */
    protected $_lastHttpResponseCode;

    /**
     * HTTP response headers from last request
     *
     * @var array
     *
     * @access private
     */
    private $_lastHttpResponseHeaders;

    /**
     * HTTP user agent
     *
     * @var string
     *
     * @access private
     * @static
     */
    private static $_userAgent = 'PHP-Service';

    /**
     * Class constructor
     *
     * @param string  $clientKey     OAuth client key
     *
     * @return void
     *
     * @access public
     */
    function __construct()
    {
        $this->_curlOptions = self::$_curlDefaultOptions;
        $this->_curlOptions[CURLOPT_USERAGENT] .= self::$_userAgent;
    }

    /**
     * Parse HTTP headers
     *
     * @param string $headers HTTP headers
     *
     * @return array $parsedHeaders
     *
     * @access protected
     */
    protected function _parseHttpHeaders($headers)
    {
        $headers = explode("\n", trim($headers));
        $parsedHeaders = array();

        foreach ($headers as $header) {
            if (!preg_match('/\:\s/', $header)) {
                continue;
            }

            list($key, $val) = explode(': ', $header, 2);
            $key = str_replace('-', '_', strtolower($key));
            $val = trim($val);

            $parsedHeaders[$key] = $val;
        }

        return $parsedHeaders;
    }

    /**
     * Performs the actual HTTP request using cURL
     *
     * @param string $url         Absolute URL to request
     *
     * @return mixed
     *
     * @access protected
     */
    protected function _request($url, $decode = false)
    {
        $ch = curl_init($url);

        curl_setopt_array($ch, $this->_curlOptions);

        $data = curl_exec($ch);
        $info = curl_getinfo($ch);

        curl_close($ch);

        $this->_lastHttpResponseHeaders = $this->_parseHttpHeaders(
            substr($data, 0, $info['header_size'])
        );
        $this->_lastHttpResponseBody = substr($data, $info['header_size']);
        $this->_lastHttpResponseCode = $info['http_code'];

        if($this->_validResponseCode($this->_lastHttpResponseCode)) {
            if($decode) {
                return json_decode($this->_lastHttpResponseBody);
            }
            else {
                return $this->_lastHttpResponseBody;
            }
        }
        else {
            return false;
        }
    }

    /**
     * Validate HTTP response code
     *
     * @param integer $code HTTP code
     *
     * @return boolean
     *
     * @access protected
     */
    protected function _validResponseCode($code)
    {
        return (bool)preg_match('/^20[0-9]{1}$/', $code);
    }
}

/**
 * Include the child classes
 *
 */
require_once(APP_PATH.'lib/service.analytics.php');
require_once(APP_PATH.'lib/service.pingdom.php');