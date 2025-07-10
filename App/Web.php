<?php
/**
 * Web via cURL
 * php version 8.3
 *
 * @category  Web
 * @package   OpenSwoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/OpenSwoole-Microservices
 * @since     Class available since Release 1.0.0
 */
namespace Microservices\App;

use Microservices\App\Common;
use Microservices\App\HttpStatus;

/**
 * Web via cURL
 * php version 8.3
 *
 * @category  Web
 * @package   OpenSwoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/OpenSwoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class Web
{
    /**
     * Common Object
     *
     * @var null|Common
     */
    private $_c = null;

    /**
     * Constructor
     *
     * @param Common $common Common object
     */
    public function __construct(Common &$common)
    {
        $this->_c = &$common;
    }

    /**
     * Return cURL Config
     *
     * @param string $homeURL     Site URL
     * @param string $method      HTTP method
     * @param string $route       Route
     * @param string $queryString Query String
     * @param array  $header      Header
     * @param string $payload     Payload
     *
     * @return array
     */
    private function _getCurlConfig(
        $homeURL,
        $method,
        $route,
        $queryString,
        $header = [],
        $payload = ''
    ): array {
        $curlConfig[CURLOPT_URL] = "{$homeURL}?r={$route}&{$queryString}";
        $curlConfig[CURLOPT_HTTPHEADER] = $header;
        $curlConfig[CURLOPT_HEADER] = 1;

        $payload = http_build_query(
            data: [
                "Payload" => $payload
            ]
        );

        $contentType = 'Content-Type: text/plain; charset=utf-8';

        switch ($method) {
        case 'GET':
            break;
        case 'POST':
            $curlConfig[CURLOPT_HTTPHEADER][] = $contentType;
            $curlConfig[CURLOPT_POST] = true;
            $curlConfig[CURLOPT_POSTFIELDS] = $payload;
            break;
        case 'PUT':
            $curlConfig[CURLOPT_HTTPHEADER][] = $contentType;
            $curlConfig[CURLOPT_CUSTOMREQUEST] = 'PUT';
            $curlConfig[CURLOPT_POSTFIELDS] = $payload;
            break;
        case 'PATCH':
            $curlConfig[CURLOPT_HTTPHEADER][] = $contentType;
            $curlConfig[CURLOPT_CUSTOMREQUEST] = 'PATCH';
            $curlConfig[CURLOPT_POSTFIELDS] = $payload;
            break;
        case 'DELETE':
            $curlConfig[CURLOPT_HTTPHEADER][] = $contentType;
            $curlConfig[CURLOPT_CUSTOMREQUEST] = 'DELETE';
            $curlConfig[CURLOPT_POSTFIELDS] = $payload;
            break;
        }
        $curlConfig[CURLOPT_RETURNTRANSFER] = true;

        return $curlConfig;
    }

    /**
     * Trigger cURL
     *
     * @param string $homeURL     Site URL
     * @param string $method      HTTP method
     * @param string $route       Route
     * @param string $queryString Query String
     * @param array  $header      Header
     * @param string $payload     Payload
     *
     * @return mixed
     */
    private function _trigger(
        $homeURL,
        $method,
        $route,
        $queryString,
        $header = [],
        $payload = ''
    ): mixed {
        $curl = curl_init();
        $curlConfig = $this->_getCurlConfig(
            homeURL: $homeURL,
            method: $method,
            route: $route,
            queryString: $queryString,
            header: $header,
            payload: $payload
        );
        curl_setopt_array(handle: $curl, options: $curlConfig);
        $curlResponse = curl_exec(handle: $curl);

        $responseHttpCode = curl_getinfo(
            handle: $curl,
            option: CURLINFO_HTTP_CODE
        );
        $responseContentType = curl_getinfo(
            handle: $curl,
            option: CURLINFO_CONTENT_TYPE
        );

        $headerSize = curl_getinfo(handle: $curl, option: CURLINFO_HEADER_SIZE);
        $responseHeaders = $this->_httpParseHeaders(
            rawHeaders: substr(
                string: $curlResponse,
                offset: 0,
                length: $headerSize
            )
        );
        $responseBody = substr(string: $curlResponse, offset: $headerSize);

        $error = curl_error(handle: $curl);
        curl_close(handle: $curl);

        $error = curl_error(handle: $curl);
        curl_close(handle: $curl);

        if ($error) {
            $response = 'cURL Error #:' . $error;
        } else {
            $response = $responseBody;
        }

        // return [
        //     'route' => "{$homeURL}?r={$route}&{$queryString}",
        //     'httpMethod' => $method,
        //     'requestHeaders' => $curlConfig[CURLOPT_HTTPHEADER],
        //     'requestPayload' => $payload,
        //     'responseHttpCode' => $responseHttpCode,
        //     'responseHeaders' => $responseHeaders,
        //     'responseContentType' => $responseContentType,
        //     'responseBody' => $response
        // ];

        return $response;
    }

    /**
     * Trigger Config
     *
     * @param array $triggerConfig Config
     *
     * @return mixed
     */
    public function triggerConfig($triggerConfig): mixed
    {
        if (!isset($this->_c->req->sess['token'])) {
            throw new \Exception(
                message: 'Missing token',
                code: HttpStatus::$InternalServerError
            );
        }

        $assoc = (!isset($triggerConfig[0])) ? true : false;
        if (!$assoc && isset($triggerConfig[0])
            && count(value: $triggerConfig) === 1
        ) {
            $triggerConfig = $triggerConfig[0];
            $assoc = true;
        }

        $homeURL = 'http://127.0.0.1:9501';

        $header = [];
        $header[] = 'x-api-version: v1.0.0';
        $header[] = 'Cache-Control: no-cache';
        $header[] = 'Authorization: Bearer ' . $this->_c->req->sess['token'];

        $response = [];

        // For use in function configuration
        $sess = &$this->_c->req->sess;

        if ($assoc) {
            $method = $triggerConfig['__METHOD__'];
            [$routeElementsArr, $errors] = $this->_getTriggerPayload(
                payloadConfig: $triggerConfig['__ROUTE__']
            );

            if (empty($errors)) {
                $route = '/' . implode(separator: '/', array: $routeElementsArr);
            } else {
                $response = $errors;
            }

            if (empty($response) && isset($triggerConfig['__QUERY-STRING__'])) {
                [$queryStringArr, $errors] = $this->_getTriggerPayload(
                    payloadConfig: $triggerConfig['__QUERY-STRING__']
                );

                if (empty($errors)) {
                    $queryString = http_build_query(data: $queryStringArr);
                } else {
                    $response = $errors;
                }
            }

            if (empty($response)) {
                if (isset($triggerConfig['__PAYLOAD__'])) {
                    [$payloadArr, $errors] = $this->_getTriggerPayload(
                        payloadConfig: $triggerConfig['__PAYLOAD__']
                    );
                } else {
                    $payloadArr = $errors = [];
                }

                $response = (empty($errors)) ?
                    $response = $this->_trigger(
                        homeURL: $homeURL,
                        method: $method,
                        route: $route,
                        queryString: $queryString,
                        header: $header,
                        payload: json_encode(value: $payloadArr)
                    ) : $errors;
            }
        } else {
            foreach ($triggerConfig as &$config) {
                $method = $config['__METHOD__'];
                [$routeElementsArr, $errors] = $this->_getTriggerPayload(
                    payloadConfig: $config['__ROUTE__']
                );

                if (empty($errors)) {
                    $route = '/' . implode(separator: '/', array: $routeElementsArr);
                } else {
                    $response[] = $errors;
                    continue;
                }

                if (isset($config['__QUERY-STRING__'])) {
                    [$queryStringArr, $errors] = $this->_getTriggerPayload(
                        payloadConfig: $config['__QUERY-STRING__']
                    );

                    if (empty($errors)) {
                        $queryString = http_build_query(data: $queryStringArr);
                    } else {
                        $response[] = $errors;
                        continue;
                    }
                }

                if (isset($config['__PAYLOAD__'])) {
                    [$payloadArr, $errors] = $this->_getTriggerPayload(
                        payloadConfig: $config['__PAYLOAD__']
                    );
                } else {
                    $payloadArr = $errors = [];
                }

                $response[] = (empty($errors)) ?
                    $this->_trigger(
                        homeURL: $homeURL,
                        method: $method,
                        route: $route,
                        queryString: $queryString,
                        header: $header,
                        payload: json_encode(value: $payloadArr)
                    ) : $errors;
            }
        }

        return $response;
    }

    /**
     * Generates Params for statement to execute
     *
     * @param array $payloadConfig API Payload configuration
     *
     * @return array
     * @throws \Exception
     */
    private function _getTriggerPayload(&$payloadConfig): array
    {
        $sqlParams = [];
        $errors = [];

        // Collect param values as per config respectively
        foreach ($payloadConfig as &$config) {
            $var = $config['column'] ?? null;

            $fetchFrom = $config['fetchFrom'];
            $fKey = $config['fetchFromValue'];
            if ($fetchFrom === 'function') {
                $function = $fKey;
                $value = $function($this->_c->req->sess);
                if (is_null(value: $var)) {
                    $sqlParams[] = $value;
                } else {
                    $sqlParams[$var] = $value;
                }
                continue;
            } elseif (in_array(
                needle: $fetchFrom,
                haystack: ['sqlResults', 'sqlParams', 'sqlPayload']
            )
            ) {
                $fetchFromKeys = explode(separator: ':', string: $fKey);
                $value = $this->_c->req->sess[$fetchFrom];
                foreach ($fetchFromKeys as $key) {
                    if (!isset($value[$key])) {
                        throw new \Exception(
                            message: 'Invalid hierarchy:  Missing hierarchy data',
                            code: HttpStatus::$InternalServerError
                        );
                    }
                    $value = $value[$key];
                }
                if (is_null(value: $var)) {
                    $sqlParams[] = $value;
                } else {
                    $sqlParams[$var] = $value;
                }
                continue;
            } elseif ($fetchFrom === 'custom') {
                $value = $fKey;
                if (is_null(value: $var)) {
                    $sqlParams[] = $value;
                } else {
                    $sqlParams[$var] = $value;
                }
                continue;
            } elseif (isset($this->_c->req->sess[$fetchFrom][$fKey])) {
                $value = $this->_c->req->sess[$fetchFrom][$fKey];
                if (is_null(value: $var)) {
                    $sqlParams[] = $value;
                } else {
                    $sqlParams[$var] = $value;
                }
                continue;
            } else {
                $errors[] = "Invalid configuration of '{$fetchFrom}' for '{$fKey}'";
                continue;
            }
        }

        return [$sqlParams, $errors];
    }

    /**
     * Generates raw headers into array
     *
     * @param string $rawHeaders Raw headers from cURL response
     *
     * @return array
     * @throws \Exception
     */
    private function _httpParseHeaders($rawHeaders): array
    {
        $headers = array();
        $key = '';

        foreach (explode(separator: "\n", string: $rawHeaders) as $i => $h) {
            $h = explode(separator: ':', string: $h, limit: 2);

            if (isset($h[1])) {
                if (!isset($headers[$h[0]])) {
                    $headers[$h[0]] = trim(string: $h[1]);
                } elseif (is_array(value: $headers[$h[0]])) {
                    $headers[$h[0]] = array_merge(
                        $headers[$h[0]],
                        array(trim(string: $h[1]))
                    );
                } else {
                    $headers[$h[0]] = array_merge(
                        array($headers[$h[0]]),
                        array(trim(string: $h[1]))
                    );
                }

                $key = $h[0];
            } else {
                if (substr(string: $h[0], offset: 0, length: 1) == "\t") {
                    $headers[$key] .= "\r\n\t".trim(string: $h[0]);
                } elseif (!$key) {
                    $headers[0] = trim(string: $h[0]);
                }
            }
        }

        return $headers;
    }
}
