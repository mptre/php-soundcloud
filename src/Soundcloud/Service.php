<?php

namespace Soundcloud;

/**
 * SoundCloud API wrapper with support for authentication using OAuth 2
 *
 * @package   Soundcloud
 * @author    Anton Lindqvist <anton@qvister.se>
 * @copyright 2010 Anton Lindqvist <anton@qvister.se>
 * @license   http://www.opensource.org/licenses/mit-license.php MIT
 * @link      http://github.com/mptre/php-soundcloud
 */
class Service
{
    /**
     * Custom cURL option
     *
     * @var integer
     *
     * @access public
     */
    const CURLOPT_OAUTH_TOKEN = 173;

    /**
     * Access token returned by the service provider after a successful authentication
     *
     * @var string
     *
     * @access private
     */
    private $_accessToken;

    /**
     * Version of the API to use
     *
     * @var integer
     *
     * @access private
     * @static
     */
    private static $_apiVersion = 1;

    /**
     * Supported audio MIME types
     *
     * @var array
     *
     * @access private
     * @static
     */
    private static $_audioMimeTypes = array(
        'aac' => 'video/mp4',
        'aiff' => 'audio/x-aiff',
        'flac' => 'audio/flac',
        'mp3' => 'audio/mpeg',
        'ogg' => 'audio/ogg',
        'wav' => 'audio/x-wav'
    );

    /**
     * OAuth client id
     *
     * @var string
     *
     * @access private
     */
    private $_clientId;

    /**
     * OAuth client secret
     *
     * @var string
     *
     * @access private
     */
    private $_clientSecret;

    /**
     * Default cURL options
     *
     * @var array
     *
     * @access private
     * @static
     */
    private static $_curlDefaultOptions = array(
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
    private $_curlOptions;

    /**
     * Development mode
     *
     * @var boolean
     *
     * @access private
     */
    private $_development;

    /**
     * Available API domains
     *
     * @var array
     *
     * @access private
     * @static
     */
    private static $_domains = array(
        'development' => 'sandbox-soundcloud.com',
        'production' => 'soundcloud.com'
    );

    /**
     * HTTP response body from the last request
     *
     * @var string
     *
     * @access private
     */
    private $_lastHttpResponseBody;

    /**
     * HTTP response code from the last request
     *
     * @var integer
     *
     * @access private
     */
    private $_lastHttpResponseCode;

    /**
     * HTTP response headers from last request
     *
     * @var array
     *
     * @access private
     */
    private $_lastHttpResponseHeaders;

    /**
     * OAuth paths
     *
     * @var array
     *
     * @access private
     * @static
     */
    private static $_paths = array(
        'authorize' => 'connect',
        'access_token' => 'oauth2/token',
    );

    /**
     * OAuth redirect URI
     *
     * @var string
     *
     * @access private
     */
    private $_redirectUri;

    /**
     * API response format MIME type
     *
     * @var string
     *
     * @access private
     */
    private $_requestFormat;

    /**
     * Available response formats
     *
     * @var array
     *
     * @access private
     * @static
     */
    private static $_responseFormats = array(
        '*' => '*/*',
        'json' => 'application/json',
        'xml' => 'application/xml'
    );

    /**
     * HTTP user agent
     *
     * @var string
     *
     * @access private
     * @static
     */
    private static $_userAgent = 'PHP-SoundCloud';

    /**
     * Class constructor
     *
     * @param string  $clientId     OAuth client id
     * @param string  $clientSecret OAuth client secret
     * @param string  $redirectUri  OAuth redirect URI
     * @param boolean $development  Sandbox mode
     *
     * @return void
     * @throws Exception\MissingClientIdException
     *
     * @access public
     */
    public function __construct($clientId, $clientSecret, $redirectUri = null, $development = false)
    {
        if (empty($clientId)) {
            throw new Exception\MissingClientIdException();
        }

        $this->_clientId = $clientId;
        $this->_clientSecret = $clientSecret;
        $this->_redirectUri = $redirectUri;
        $this->_development = $development;
        $this->_responseFormat = self::$_responseFormats['json'];
        $this->_curlOptions = self::$_curlDefaultOptions;
        $this->_curlOptions[CURLOPT_USERAGENT] .= $this->_getUserAgent();
    }

    /**
     * Get authorization URL
     *
     * @param array $params Optional query string parameters
     *
     * @return string
     *
     * @access public
     * @see Service::_buildUrl()
     */
    public function getAuthorizeUrl($params = array())
    {
        $defaultParams = array(
            'client_id' => $this->_clientId,
            'redirect_uri' => $this->_redirectUri,
            'response_type' => 'code'
        );
        $params = array_merge($defaultParams, $params);

        return $this->_buildUrl(self::$_paths['authorize'], $params, false);
    }

    /**
     * Get access token URL
     *
     * @param array $params Optional query string parameters
     *
     * @return string
     *
     * @access public
     * @see Service::_buildUrl()
     */
    public function getAccessTokenUrl($params = array())
    {
        return $this->_buildUrl(self::$_paths['access_token'], $params, false);
    }

    /**
     * Retrieve access token through credentials flow
     *
     * @param string $username Username
     * @param string $password Password
     *
     * @return mixed
     *
     * @access public
     */
    public function credentialsFlow($username, $password)
    {
        $postData = array(
            'client_id' => $this->_clientId,
            'client_secret' => $this->_clientSecret,
            'username' => $username,
            'password' => $password,
            'grant_type' => 'password'
        );

        $options = array(CURLOPT_POST => true, CURLOPT_POSTFIELDS => $postData);
        $response = json_decode(
                $this->_request($this->getAccessTokenUrl(), $options), true
        );

        if (array_key_exists('access_token', $response)) {
            $this->_accessToken = $response['access_token'];

            return $response;
        } else {
            return false;
        }
    }

    /**
     * Retrieve access token
     *
     * @param string $code        Optional OAuth code returned from the service provider
     * @param array  $postData    Optional post data
     * @param array  $curlOptions Optional cURL options
     *
     * @return mixed
     *
     * @access public
     * @see Service::_getAccessToken()
     */
    public function accessToken($code = null, $postData = array(), $curlOptions = array())
    {
        $defaultPostData = array(
            'code' => $code,
            'client_id' => $this->_clientId,
            'client_secret' => $this->_clientSecret,
            'redirect_uri' => $this->_redirectUri,
            'grant_type' => 'authorization_code'
        );
        $postData = array_filter(array_merge($defaultPostData, $postData));

        return $this->_getAccessToken($postData, $curlOptions);
    }

    /**
     * Refresh access token
     *
     * @param string $refreshToken The token to refresh
     * @param array  $postData     Optional post data
     * @param array  $curlOptions  Optional cURL options
     *
     * @return mixed
     * @see Service::_getAccessToken()
     *
     * @access public
     */
    public function accessTokenRefresh($refreshToken, $postData = array(), $curlOptions = array())
    {
        $defaultPostData = array(
            'refresh_token' => $refreshToken,
            'client_id' => $this->_clientId,
            'client_secret' => $this->_clientSecret,
            'redirect_uri' => $this->_redirectUri,
            'grant_type' => 'refresh_token'
        );
        $postData = array_merge($defaultPostData, $postData);

        return $this->_getAccessToken($postData, $curlOptions);
    }

    /**
     * Get access token
     *
     * @return mixed
     *
     * @access public
     */
    public function getAccessToken()
    {
        return $this->_accessToken;
    }

    /**
     * Get API version
     *
     * @return integer
     *
     * @access public
     */
    public function getApiVersion()
    {
        return self::$_apiVersion;
    }

    /**
     * Get the corresponding MIME type for a given file extension
     *
     * @param string $extension Given extension
     *
     * @return string
     * @throws Exception\UnsupportedAudioFormatException
     *
     * @access public
     */
    public function getAudioMimeType($extension)
    {
        if (array_key_exists($extension, self::$_audioMimeTypes)) {
            return self::$_audioMimeTypes[$extension];
        } else {
            throw new Exception\UnsupportedAudioFormatException();
        }
    }

    /**
     * Get cURL options
     *
     * @param string $key Optional options key
     *
     * @return mixed
     *
     * @access public
     */
    public function getCurlOptions($key = null)
    {
        if ($key) {
            return (array_key_exists($key, $this->_curlOptions)) ? $this->_curlOptions[$key] : false;
        } else {
            return $this->_curlOptions;
        }
    }

    /**
     * Get development mode
     *
     * @return boolean
     *
     * @access public
     */
    public function getDevelopment()
    {
        return $this->_development;
    }

    /**
     * Get HTTP response header
     *
     * @param string $header Name of the header
     *
     * @return mixed
     *
     * @access public
     */
    public function getHttpHeader($header)
    {
        if (is_array($this->_lastHttpResponseHeaders) && array_key_exists($header, $this->_lastHttpResponseHeaders)
        ) {
            return $this->_lastHttpResponseHeaders[$header];
        } else {
            return false;
        }
    }

    /**
     * Get redirect URI
     *
     * @return string
     *
     * @access public
     */
    public function getRedirectUri()
    {
        return $this->_redirectUri;
    }

    /**
     * Get response format
     *
     * @return string
     *
     * @access public
     */
    public function getResponseFormat()
    {
        return $this->_responseFormat;
    }

    /**
     * Set access token
     *
     * @param string $accessToken Access token
     *
     * @return object
     *
     * @access public
     */
    public function setAccessToken($accessToken)
    {
        $this->_accessToken = $accessToken;

        return $this;
    }

    /**
     * Set cURL options
     *
     * The method accepts arguments in two ways.
     *
     * You could pass two arguments when adding a single option.
     * <code>
     * $soundcloud->setCurlOptions(CURLOPT_SSL_VERIFYHOST, 0);
     * </code>
     *
     * You could also pass an associative array when adding multiple options.
     * <code>
     * $soundcloud->setCurlOptions(array(
     *     CURLOPT_SSL_VERIFYHOST => 0,
     *    CURLOPT_SSL_VERIFYPEER => 0
     * ));
     * </code>
     *
     * @return object
     *
     * @access public
     */
    public function setCurlOptions()
    {
        $args = func_get_args();
        $options = (is_array($args[0])) ? $args[0] : array($args[0] => $args[1]);

        foreach ($options as $key => $val) {
            $this->_curlOptions[$key] = $val;
        }

        return $this;
    }

    /**
     * Set redirect URI
     *
     * @param string $redirectUri Redirect URI
     *
     * @return object
     *
     * @access public
     */
    public function setRedirectUri($redirectUri)
    {
        $this->_redirectUri = $redirectUri;

        return $this;
    }

    /**
     * Set response format
     *
     * @param string $format Response format, could either be XML or JSON
     *
     * @return object
     * @throws Exception\UnsupportedResponseFormatException
     *
     * @access public
     */
    public function setResponseFormat($format)
    {
        if (array_key_exists($format, self::$_responseFormats)) {
            $this->_responseFormat = self::$_responseFormats[$format];
        } else {
            throw new Exception\UnsupportedResponseFormatException();
        }

        return $this;
    }

    /**
     * Set development mode
     *
     * @param boolean $development Development mode
     *
     * @return object
     *
     * @access public
     */
    public function setDevelopment($development)
    {
        $this->_development = $development;

        return $this;
    }

    /**
     * Send a GET HTTP request
     *
     * @param string $path        Request path
     * @param array  $params      Optional query string parameters
     * @param array  $curlOptions Optional cURL options
     *
     * @return mixed
     *
     * @access public
     * @see Service::_request()
     */
    public function get($path, $params = array(), $curlOptions = array())
    {
        $url = $this->_buildUrl($path, $params);

        return $this->_request($url, $curlOptions);
    }

    /**
     * Send a POST HTTP request
     *
     * @param string $path        Request path
     * @param array  $postData    Optional post data
     * @param array  $curlOptions Optional cURL options
     *
     * @return mixed
     *
     * @access public
     * @see Service::_request()
     */
    public function post($path, $postData = array(), $curlOptions = array())
    {
        $url = $this->_buildUrl($path);
        $options = array(CURLOPT_POST => true, CURLOPT_POSTFIELDS => $postData);
        $options += $curlOptions;

        return $this->_request($url, $options);
    }

    /**
     * Send a PUT HTTP request
     *
     * @param string $path        Request path
     * @param array  $postData    Optional post data
     * @param array  $curlOptions Optional cURL options
     *
     * @return mixed
     *
     * @access public
     * @see Service::_request()
     */
    public function put($path, $postData, $curlOptions = array())
    {
        $url = $this->_buildUrl($path);
        $options = array(
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_POSTFIELDS => $postData
        );
        $options += $curlOptions;

        return $this->_request($url, $options);
    }

    /**
     * Send a DELETE HTTP request
     *
     * @param string $path        Request path
     * @param array  $params      Optional query string parameters
     * @param array  $curlOptions Optional cURL options
     *
     * @return mixed
     *
     * @access public
     * @see Service::_request()
     */
    public function delete($path, $params = array(), $curlOptions = array())
    {
        $url = $this->_buildUrl($path, $params);
        $options = array(CURLOPT_CUSTOMREQUEST => 'DELETE');
        $options += $curlOptions;

        return $this->_request($url, $options);
    }

    /**
     * Download track
     *
     * @param integer $trackId     Track id to download
     * @param array   $params      Optional query string parameters
     * @param array   $curlOptions Optional cURL options
     *
     * @return mixed
     *
     * @access public
     * @see Service::_request()
     */
    public function download($trackId, $params = array(), $curlOptions = array())
    {
        $lastResponseFormat = array_pop(explode('/', $this->getResponseFormat()));
        $defaultParams = array('oauth_token' => $this->getAccessToken());
        $defaultCurlOptions = array(
            CURLOPT_FOLLOWLOCATION => true,
            self::CURLOPT_OAUTH_TOKEN => false
        );
        $url = $this->_buildUrl(
                'tracks/' . $trackId . '/download', array_merge($defaultParams, $params)
        );
        $options = $defaultCurlOptions + $curlOptions;

        $this->setResponseFormat('*');

        $response = $this->_request($url, $options);

        // rollback to the previously defined response format.
        $this->setResponseFormat($lastResponseFormat);

        return $response;
    }

    /**
     * Update a existing playlist
     *
     * @param integer $playlistId       The playlist id
     * @param array   $trackIds         Tracks to add to the playlist
     * @param array   $optionalPostData Optional playlist fields to update
     *
     * @return mixed
     *
     * @access public
     * @see Service::_request()
     */
    public function updatePlaylist($playlistId, $trackIds, $optionalPostData = null)
    {
        $url = $this->_buildUrl('playlists/' . $playlistId);
        $postData = array();

        foreach ($trackIds as $trackId) {
            array_push($postData, 'playlist[tracks][][id]=' . $trackId);
        }

        if (is_array($optionalPostData)) {
            foreach ($optionalPostData as $key => $val) {
                array_push($postData, 'playlist[' . $key . ']=' . $val);
            }
        }

        $postData = implode('&', $postData);
        $curlOptions = array(
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_HTTPHEADER => array('Content-Length' => strlen($postData)),
            CURLOPT_POSTFIELDS => $postData
        );

        return $this->_request($url, $curlOptions);
    }

    /**
     * Construct default HTTP request headers
     *
     * @param boolean $includeAccessToken Include access token
     *
     * @return array $headers
     *
     * @access protected
     */
    protected function _buildDefaultHeaders($includeAccessToken = true)
    {
        $headers = array();

        if ($this->_responseFormat) {
            array_push($headers, 'Accept: ' . $this->_responseFormat);
        }

        if ($includeAccessToken && $this->_accessToken) {
            array_push($headers, 'Authorization: OAuth ' . $this->_accessToken);
        }

        return $headers;
    }

    /**
     * Construct a URL
     *
     * @param string  $path           Relative or absolute URI
     * @param array   $params         Optional query string parameters
     * @param boolean $includeVersion Include API version
     *
     * @return string $url
     *
     * @access protected
     */
    protected function _buildUrl($path, $params = array(), $includeVersion = true)
    {
        if (!$this->_accessToken) {
            $params['consumer_key'] = $this->_clientId;
        }

        if (preg_match('/^https?\:\/\//', $path)) {
            $url = $path;
        } else {
            $url = 'https://';
            $url .= (!preg_match('/connect/', $path)) ? 'api.' : '';
            $url .= ($this->_development) ? self::$_domains['development'] : self::$_domains['production'];
            $url .= '/';
            $url .= ($includeVersion) ? 'v' . self::$_apiVersion . '/' : '';
            $url .= $path;
        }

        $url .= (count($params)) ? '?' . http_build_query($params) : '';

        return $url;
    }

    /**
     * Retrieve access token
     *
     * @param array $postData    Post data
     * @param array $curlOptions Optional cURL options
     *
     * @return mixed
     *
     * @access protected
     */
    protected function _getAccessToken($postData, $curlOptions = array())
    {
        $options = array(CURLOPT_POST => true, CURLOPT_POSTFIELDS => $postData);
        $options += $curlOptions;
        $response = json_decode(
                $this->_request($this->getAccessTokenUrl(), $options), true
        );

        if (array_key_exists('access_token', $response)) {
            $this->_accessToken = $response['access_token'];

            return $response;
        } else {
            return false;
        }
    }

    /**
     * Get HTTP user agent
     *
     * @return string
     *
     * @access protected
     */
    protected function _getUserAgent()
    {
        return self::$_userAgent . '/' . new Version();
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
        return (bool) preg_match('/^20[0-9]{1}$/', $code);
    }

    /**
     * Performs the actual HTTP request using cURL
     *
     * @param string $url         Absolute URL to request
     * @param array  $curlOptions Optional cURL options
     *
     * @return mixed
     * @throws Exception\InvalidHttpResponseCodeException
     *
     * @access protected
     */
    protected function _request($url, $curlOptions = array())
    {
        $ch = curl_init($url);
        $options = $this->_curlOptions;
        $options += $curlOptions;

        if (array_key_exists(self::CURLOPT_OAUTH_TOKEN, $options)) {
            $includeAccessToken = $options[self::CURLOPT_OAUTH_TOKEN];
            unset($options[self::CURLOPT_OAUTH_TOKEN]);
        } else {
            $includeAccessToken = true;
        }

        if (array_key_exists(CURLOPT_HTTPHEADER, $options)) {
            $options[CURLOPT_HTTPHEADER] = array_merge(
                    $this->_buildDefaultHeaders(), $curlOptions[CURLOPT_HTTPHEADER]
            );
        } else {
            $options[CURLOPT_HTTPHEADER] = $this->_buildDefaultHeaders(
                    $includeAccessToken
            );
        }

        curl_setopt_array($ch, $options);

        $data = $this->_curlExecFollow($ch);
        $info = curl_getinfo($ch);

        curl_close($ch);

        if (array_key_exists(CURLOPT_HEADER, $options) && $options[CURLOPT_HEADER]) {
            $this->_lastHttpResponseHeaders = $this->_parseHttpHeaders(
                    substr($data, 0, $info['header_size'])
            );
            $this->_lastHttpResponseBody = substr($data, $info['header_size']);
        } else {
            $this->_lastHttpResponseHeaders = array();
            $this->_lastHttpResponseBody = $data;
        }

        $this->_lastHttpResponseCode = $info['http_code'];

        if ($this->_validResponseCode($this->_lastHttpResponseCode)) {
            return $this->_lastHttpResponseBody;
        } else {
            throw new Exception\InvalidHttpResponseCodeException(
            null, 0, $this->_lastHttpResponseBody, $this->_lastHttpResponseCode
            );
        }
    }

    /**
     * Allows the following of links on shared hosting environments where
     * open_basedir and/or safe_mode are used.
     *
     * @param resource $ch
     * @param int $maxredirect
     *
     * @return boolean
     *
     * @access private
     */
    private function _curlExecFollow($ch, &$maxredirect = null)
    {
        $mr = $maxredirect === null ? 5 : intval($maxredirect);
        if (ini_get('open_basedir') == '' && ini_get('safe_mode' == 'Off')) {
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $mr > 0);
            curl_setopt($ch, CURLOPT_MAXREDIRS, $mr);
        } else {
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
            if ($mr > 0) {
                $newurl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);

                $rch = curl_copy_handle($ch);
                curl_setopt($rch, CURLOPT_HEADER, true);
                curl_setopt($rch, CURLOPT_NOBODY, true);
                curl_setopt($rch, CURLOPT_FORBID_REUSE, false);
                curl_setopt($rch, CURLOPT_RETURNTRANSFER, true);
                do {
                    curl_setopt($rch, CURLOPT_URL, $newurl);
                    $header = curl_exec($rch);
                    if (curl_errno($rch)) {
                        $code = 0;
                    } else {
                        $code = curl_getinfo($rch, CURLINFO_HTTP_CODE);
                        if ($code == 301 || $code == 302) {
                            preg_match('/Location:(.*?)\n/', $header, $matches);
                            $newurl = trim(array_pop($matches));
                        } else {
                            $code = 0;
                        }
                    }
                } while ($code && --$mr);
                curl_close($rch);
                if (!$mr) {
                    if ($maxredirect === null) {
                        trigger_error('Too many redirects. When following redirects, libcurl hit the maximum amount.', E_USER_WARNING);
                    } else {
                        $maxredirect = 0;
                    }
                    return false;
                }
                curl_setopt($ch, CURLOPT_URL, $newurl);
            }
        }
        return curl_exec($ch);
    }

}
