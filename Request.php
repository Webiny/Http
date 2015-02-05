<?php
/**
 * Webiny Framework (http://www.webiny.com/framework)
 *
 * @copyright Copyright Webiny LTD
 */

namespace Webiny\Component\Http;

use Webiny\Component\Http\Request\Env;
use Webiny\Component\Http\Request\File;
use Webiny\Component\Http\Request\Files;
use Webiny\Component\Http\Request\Headers;
use Webiny\Component\Http\Request\Payload;
use Webiny\Component\Http\Request\Query;
use Webiny\Component\Http\Request\Post;
use Webiny\Component\Http\Request\RequestException;
use Webiny\Component\Http\Request\Server;
use Webiny\Component\StdLib\SingletonTrait;
use Webiny\Component\StdLib\StdLibTrait;
use Webiny\Component\StdLib\StdObject\UrlObject\UrlObject;


/**
 * Request class holds the information about current request.
 *
 * @package         Webiny\
 */
class Request
{
    use SingletonTrait, StdLibTrait;

    const HEADER_CLIENT_IP = 'X_FORWARDED_FOR';
    const HEADER_CLIENT_HOST = 'X_FORWARDED_HOST';
    const HEADER_CLIENT_PROTO = 'X_FORWARDED_PROTO';
    const HEADER_CLIENT_PORT = 'X_FORWARDED_PORT';

    /**
     * @var array Array of IPs from trusted proxies.
     */
    private $_trustedProxies = [];

    /**
     * @var string
     */
    private $_currentUrl = '';

    /**
     * @var Query
     */
    private $_query;

    /**
     * @var Post
     */
    private $_post;

    /**
     * @var Payload
     */
    private $_payload;

    /**
     * @var Files
     */
    private $_files;

    /**
     * @var Server
     */
    private $_server;

    /**
     * @var Env
     */
    private $_env;

    /**
     * @var Headers
     */
    private $_headers;

    /**
     * This function prepare the Request and all of its sub-classes.
     * This class is called automatically by SingletonTrait.
     */
    protected function _init()
    {
        $this->_query = new Query();
        $this->_post = new Post();
        $this->_payload = new Payload();
        $this->_server = new Server();
        $this->_files = new Files();
        $this->_env = new Env();
        $this->_headers = new Headers();

        if (Http::getConfig()->TrustedProxies) {
            $this->_trustedProxies = Http::getConfig()->TrustedProxies->toArray();
        }
    }

    /**
     * Get a value from $_GET param for the given $key.
     * If key doesn't not exist, $value will be returned and assigned under that key.
     *
     * @param string $key   Key for which you wish to get the value.
     * @param mixed  $value Default value that will be returned if $key doesn't exist.
     *
     * @return mixed Value of the given $key.
     */
    public function query($key = null, $value = null)
    {
        return $this->isNull($key) ? $this->_query->getAll() : $this->_query->get($key, $value);
    }

    /**
     * Returns an array object with all GET parameters.
     *
     * @return Query
     */
    public function getQuery()
    {
        return $this->_query;
    }

    /**
     * Get a value from $_POST param for the given $key.
     * If key doesn't not exist, $value will be returned and assigned under that key.
     *
     * @param string $key   Key for which you wish to get the value.
     * @param mixed  $value Default value that will be returned if $key doesn't exist.
     *
     * @return mixed Value of the given $key.
     */
    public function post($key = null, $value = null)
    {
        return $this->isNull($key) ? $this->_post->getAll() : $this->_post->get($key, $value);
    }

    /**
     * Returns an array object with all POST parameters.
     *
     * @return Post
     */
    public function getPost()
    {
        return $this->_post;
    }

    /**
     * Get a value from HTTP Headers
     * If key doesn't not exist, $value will be returned and assigned under that key.
     *
     * @param string $key   Key for which you wish to get the value.
     * @param mixed  $value Default value that will be returned if $key doesn't exist.
     *
     * @return mixed Value of the given $key.
     */
    public function header($key = null, $value = null)
    {
        return $this->isNull($key) ? $this->_headers->getAll() : $this->_headers->get($key, $value);
    }

    /**
     * Get a value from request payload param for the given $key.
     * If key doesn't not exist, $value will be returned and assigned under that key.
     *
     * @param string $key   Key for which you wish to get the value.
     * @param mixed  $value Default value that will be returned if $key doesn't exist.
     *
     * @return mixed Value of the given $key.
     */
    public function payload($key = null, $value = null)
    {
        return $this->isNull($key) ? $this->_payload->getAll() : $this->_payload->get($key, $value);
    }

    /**
     * Returns an array object with all post parameters.
     *
     * @return Payload
     */
    public function getPayload()
    {
        return $this->_payload;
    }

    /**
     * Get a value from $_ENV param for the given $key.
     * If key doesn't not exist, $value will be returned and assigned under that key.
     *
     * @param string $key   Key for which you wish to get the value.
     * @param mixed  $value Default value that will be returned if $key doesn't exist.
     *
     * @return mixed Value of the given $key.
     */
    public function env($key = null, $value = null)
    {
        return $this->isNull($key) ? $this->_env->getAll() : $this->_env->get($key, $value);
    }

    /**
     * Access to the $_SERVER parameter over a object wrapper.
     *
     * @return Server
     */
    public function server()
    {
        return $this->_server;
    }

    /**
     * Get the File object for the given $name.
     * If you have a multi-dimensional upload field name, than you should pass the optional $arrayOffset param to get the
     * right File object.
     *
     * @param string   $name        Name of the upload field.
     * @param null|int $arrayOffset Optional array offset for multi-dimensional upload fields.
     *
     * @throws \Exception|Request\Files\FilesException
     * @return Files\File
     */
    public function files($name, $arrayOffset = null)
    {
        try {
            return $this->_files->get($name, $arrayOffset);
        } catch (Files\FilesException $e) {
            throw $e;
        }

    }

    /**
     * Array of IPs from trusted proxies.
     * @return array
     */
    public function getTrustedProxies()
    {
        return $this->_trustedProxies;
    }

    /**
     * Get a list of trusted headers.
     *
     * @return array List of trusted headers.
     */
    public function getTrustedHeaders()
    {
        $trustedHeaders = Http::getConfig()->TrustedHeaders;

        return [
            'client_ip'    => $trustedHeaders->get('client_ip', self::HEADER_CLIENT_IP),
            'client_host'  => $trustedHeaders->get('client_host', self::HEADER_CLIENT_HOST),
            'client_proto' => $trustedHeaders->get('client_proto', self::HEADER_CLIENT_PROTO),
            'client_port'  => $trustedHeaders->get('client_port', self::HEADER_CLIENT_PORT),
        ];
    }

    /**
     * Get current url with schema, host, port, request uri and query string.
     * You can get the result in a form of a string or as a url standard object.
     *
     * @param bool $asUrlObject In which format you want to get the result, url standard object or a string.
     *
     * @return string|\Webiny\Component\StdLib\StdObject\UrlObject\UrlObject Current url.
     */
    public function getCurrentUrl($asUrlObject = false)
    {
        if ($this->_currentUrl == '') {
            // schema
            $pageURL = 'http';
            if ($this->isRequestSecured()) {
                $pageURL = 'https';
            }
            $pageURL .= "://";

            // port, server name and request uri
            $host = $this->getHostName();

            $port = $this->getConnectionPort();
            if ($port && $port != '80' && $port != '443') {
                $pageURL .= $host . ":" . $port . $this->server()->requestUri();
            } else {
                $pageURL .= $host . $this->server()->requestUri();
            }

            // query
            $query = $this->server()->queryString();
            if ($query) {
                $pageURL .= '?' . $query;
            }

            $this->_currentUrl = $pageURL;
        }

        if ($asUrlObject) {
            return $this->url($this->_currentUrl);
        } else {
            return $this->_currentUrl;
        }
    }

    /**
     * This method sets the internal value of currentUrl to $url.
     * This method will not actually do a redirect, it is used mostly for mocking the internal value.
     *
     * @param string $url Current url.
     */
    public function setCurrentUrl($url)
    {
        $this->_currentUrl = $url;
    }

    /**
     * Get client ip address.
     * This function check and validates headers from trusted proxies.
     *
     * @throws RequestException
     * @return string Client IP address.
     */
    public function getClientIp()
    {
        $remoteAddress = $this->server()->remoteAddress();
        $fwdClientIp = $this->server()->get($this->getTrustedHeaders()['client_ip']);
        if ($fwdClientIp && $remoteAddress && in_array($remoteAddress, $this->getTrustedProxies())) {
            // Use the forwarded IP address, typically set when the
            // client is using a proxy server.
            // Format: "X-Forwarded-For: client1, proxy1, proxy2"
            $clientIps = explode(',', $fwdClientIp);
            $clientIp = array_shift($clientIps);
        } elseif ($this->server()->httpClientIp() && $remoteAddress && in_array($remoteAddress,
                                                                                $this->getTrustedProxies()
            )
        ) {
            // Use the forwarded IP address, typically set when the
            // client is using a proxy server.
            $clientIps = explode(',', $this->server()->httpClientIp());
            $clientIp = array_shift($clientIps);
        } elseif ($this->server()->remoteAddress()) {
            // The remote IP address
            $clientIp = $this->server()->remoteAddress();
        } else {
            throw new RequestException('Unable to get client IP address.');
        }

        return $clientIp;
    }

    /**
     * Check if connection is secured.
     * This function check the forwarded headers from trusted proxies.
     *
     * @return bool True if connection is secured (https), otherwise false is returned.
     */
    public function isRequestSecured()
    {
        $remoteAddress = $this->server()->remoteAddress();

        $protocol = $this->server()->serverProtocol();
        $fwdProto = $this->server()->get($this->getTrustedHeaders()['client_proto']);
        if ($fwdProto && $fwdProto != '' && in_array($remoteAddress, $this->getTrustedProxies())) {
            $protocol = $fwdProto;
        }
        $protocol = strtolower($protocol);

        $isSecured = in_array($protocol, [
                'https',
                'on',
                '1'
            ]
        );

        if (!$isSecured) {
            if (in_array(strtolower($this->server()->https()), [
                    'https',
                    'on',
                    '1'
                ]
            )) {
                $isSecured = true;
            }
        }

        return $isSecured;
    }

    /**
     * Return the connection port number.
     * This function check the forwarded headers from trusted proxies.
     *
     * @return int Port number.
     */
    public function getConnectionPort()
    {
        $port = 80;
        $host = $this->server()->httpHost();
        if(empty($host)){
            return $port;
        }

        $host = $this->str($host);

        if($host->contains(':')){
            $port = $host->explode(':')->last();
        }

        return $port;
    }

    /**
     * Returns the host name.
     * This function check the forwarded headers from trusted proxies.
     *
     * @return string Host name
     */
    public function getHostName()
    {
        $remoteAddress = $this->server()->remoteAddress();

        $host = $this->server()->serverName();
        $fwdHost = $this->server()->get($this->getTrustedHeaders()['client_host']);
        if ($fwdHost && $fwdHost != '' && in_array($remoteAddress, $this->getTrustedProxies())) {
            $host = $fwdHost;
        }

        return strtolower($host);
    }

    /**
     * Checks if current request method is POST.
     *
     * @return bool True if it's POST.
     */
    public function isPost()
    {
        return $this->str($this->server()->requestMethod())->equals('POST');
    }

    /**
     * Checks if current request method is GET.
     *
     * @return bool True if it's GET.
     */
    public function isGet()
    {
        return $this->str($this->server()->requestMethod())->equals('GET');
    }

    /**
     * Checks if current request method is DELETE.
     *
     * @return bool True if it's DELETE.
     */
    public function isDelete()
    {
        return $this->str($this->server()->requestMethod())->equals('DELETE');
    }

    /**
     * Checks if current request method is PUT.
     *
     * @return bool True if it's PUT.
     */
    public function isPut()
    {
        return $this->str($this->server()->requestMethod())->equals('PUT');
    }

    /**
     * Checks if current request method is PATCH.
     *
     * @return bool True if it's PATCH.
     */
    public function isPatch()
    {
        return $this->str($this->server()->requestMethod())->equals('PATCH');
    }

    /**
     * Get request method from HTTP headers
     * @return string
     */
    public function getRequestMethod()
    {
        return $this->server()->requestMethod();
    }
}