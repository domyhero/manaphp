<?php
namespace ManaPHP\Http {

    use ManaPHP\Component;
    use ManaPHP\Http\Client\Exception;
    use ManaPHP\Utility\Text;

    /**
     * Class Client
     *
     * @package ManaPHP\Http
     */
    class Client extends Component implements ClientInterface
    {
        /**
         * @var array
         */
        protected $_headers = [];

        /**
         * @var array
         */
        protected $_options = [];

        /**
         * @var string
         */
        protected $_responseBody = false;

        /**
         * @var array
         */
        protected $_curlResponseHeader = [];

        /**
         * Client constructor.
         *
         * @param array $options
         *    - `timeout`: How long should we wait for a response?
         *    (integer, seconds, default: 10)
         *    - `max_redirects`: How many times should we redirect 3xx before error?
         *    (integer, default: 10)
         *    (string, default: '')
         *    - `proxy`: Proxy details to use for proxy by-passing and authentication
         *    (string, default: '')
         *    - `ssl_certificates`: Should we verify SSL certificates? Allows passing in a custom
         *    certificate file as a string. (Using true uses the system-wide root
         *    certificate store instead, but this may have different behaviour
         *    across transports.)
         *    (string, default: 'xxx/ca.pem')
         *    - `verify_host`: Should we verify the common name in the SSL certificate?
         *    (boolean: default, true)
         *
         * @param array $headers
         *
         * - `User-Agent`: User Agent to send to the server
         *   (string, default: php-requests/$version)
         *
         * @throws \ManaPHP\Http\Client\Exception
         */
        public function __construct($options = [], $headers = [])
        {
            parent::__construct();

            if (!function_exists('curl_init')) {
                throw new Exception('curl extension is not loaded: http://php.net/curl');
            }

            $this->_options = array_merge([
                'timeout' => 10,
                'max_redirects' => 10,
                'proxy' => '',
                'ssl_certificates' => __DIR__ . '/Client/ca.pem',
                'verify_host' => true,
            ], $options);

            $this->_headers = array_merge(['User-Agent' => 'ManaPHP/httpClient'], $headers);
        }

        public function setProxy($proxy)
        {
            $this->_options['proxy'] = $proxy;

            return $this;
        }

        protected function request($type, $url, $data, $headers, $options)
        {
            $this->_responseBody = false;

            $url = $this->_buildUrl($url);
            if (preg_match('/^http(s)?:\/\//i', $url) !== 1) {
                throw new Exception('Only HTTP requests are handled: ' . $url);
            }

            $headers = array_merge($this->_headers, $headers);
            $options = array_merge($this->_options, $options);

            $this->fireEvent('httpClient:beforeRequest', ['type' => $type, 'url' => &$url, 'headers' => &$headers, 'data' => &$data, 'options' => &$options]);
            $httpCode = $this->_request($type, $url, $data, $headers, $options);
            $this->fireEvent('httpClient:afterResponse', [
                'type' => $type,
                'url' => $url,
                'headers' => $headers,
                'data' => $data,
                'options' => $options,
                'httpCode' => &$httpCode,
                'responseBody' => &$this->_responseBody
            ]);
            return $httpCode;
        }

        public function _request($type, $url, $data, $headers, $options)
        {
            $this->_curlResponseHeader = [];

            $curl = curl_init();

            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_AUTOREFERER, true);

            if ($options['max_redirects'] > 0) {
                curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($curl, CURLOPT_MAXREDIRS, $options['max_redirects']);
            } else {
                curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
            }

            if (isset($headers['Cookie'])) {
                curl_setopt($curl, CURLOPT_COOKIE, $headers['Cookie']);
            }

            if (is_array($data)) {
                $hasFiles = false;
                foreach ($data as $k => $v) {
                    if (is_string($v) && $v[0] === '@') {
                        $hasFiles = true;
                        if (class_exists('CURLFile')) {
                            $file = substr($v, 1);

                            $parts = explode(';', $file);

                            if (count($parts) === 1) {
                                /** @noinspection AlterInForeachInspection */
                                $data[$k] = new \CURLFile($file);
                            } else {
                                $file = $parts[0];
                                $types = explode('=', $parts[1]);
                                if ($types[0] !== 'type' || count($types) !== 2) {
                                    throw new Exception('invalid file name: ' . $v);
                                } else {
                                    /** @noinspection AlterInForeachInspection */
                                    $data[$k] = new \CURLFile($file, $types[1]);
                                }
                            }
                        }
                    } elseif (is_object($v)) {
                        $hasFiles = true;
                    }
                }

                if (!$hasFiles) {
                    $data = http_build_query($data);
                }
            }

            switch ($type) {
                case 'GET':
                    break;
                case 'POST':
                    curl_setopt($curl, CURLOPT_POST, 1);
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                    break;
                case 'PATCH':
                    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PATCH');
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                    break;
                case 'PUT':
                    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                    break;
                case 'DELETE':
                    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
                    break;
            }

            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_TIMEOUT, $options['timeout']);
            curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $options['timeout']);
            curl_setopt($curl, CURLOPT_REFERER, isset($headers['Referer']) ? $headers['Referer'] : $url);
            curl_setopt($curl, CURLOPT_USERAGENT, $headers['User-Agent']);

            unset($headers['Referer'], $headers['User-Agent'], $headers['Cookie']);

            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

            if ($options['proxy']) {
                curl_setopt($curl, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
                curl_setopt($curl, CURLOPT_PROXY, $options['proxy']);
            }

            if ($options['ssl_certificates']) {
                curl_setopt($curl, CURLOPT_CAINFO, $options['ssl_certificates']);
            } else {
                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            }

            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, $options['verify_host'] ? 2 : 0);

            $this->_responseBody = curl_exec($curl);

            /** @noinspection NotOptimalIfConditionsInspection */
            if (curl_errno($curl) === 23 || curl_errno($curl) === 61) {
                curl_setopt($curl, CURLOPT_ENCODING, 'none');
                $this->_responseBody = curl_exec($curl);
            }

            if (curl_errno($curl)) {
                throw new Exception('cURL error ' . curl_errno($curl) . ':' . curl_error($curl));
            }

            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

            $this->_curlResponseHeader = curl_getinfo($curl);

            curl_close($curl);

            return $httpCode;
        }

        protected function _buildUrl($url)
        {
            if (is_string($url)) {
                return $url;
            }

            list($url, $data) = $url;
            return $url . (Text::contains($url, '?') ? '&' : '?') . http_build_query($data);
        }

        public function get($url, $headers = [], $options = [])
        {
            return $this->request('GET', $url, null, $headers, $options);
        }

        public function post($url, $data = [], $headers = [], $options = [])
        {
            return $this->request('POST', $url, $data, $headers, $options);
        }

        public function delete($url, $headers = [], $options = [])
        {
            return $this->request('DELETE', $url, null, $headers, $options);
        }

        public function put($url, $data = [], $headers = [], $options = [])
        {
            return $this->request('PUT', $url, $data, $headers, $options);
        }

        public function patch($url, $data = [], $headers = [], $options = [])
        {
            return $this->request('PATCH', $url, $data, $headers, $options);
        }

        public function getResponseBody()
        {
            return $this->_responseBody;
        }
    }
}