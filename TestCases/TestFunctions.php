<?php

/**
 * TestCases
 * php version 8.3
 *
 * @category  TestCases
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\TestCases;

if (!defined(constant_name: 'GET')) {
    define(constant_name: 'GET', value: __DIR__ . DIRECTORY_SEPARATOR . 'GET');
}

if (!defined(constant_name: 'POST')) {
    define(constant_name: 'POST', value: __DIR__ . DIRECTORY_SEPARATOR . 'POST');
}

if (!defined(constant_name: 'PUT')) {
    define(constant_name: 'PUT', value: __DIR__ . DIRECTORY_SEPARATOR . 'PUT');
}

if (!defined(constant_name: 'PATCH')) {
    define(constant_name: 'PATCH', value: __DIR__ . DIRECTORY_SEPARATOR . 'PATCH');
}

if (!defined(constant_name: 'DELETE')) {
    define(constant_name: 'DELETE', value: __DIR__ . DIRECTORY_SEPARATOR . 'DELETE');
}

if (!function_exists(function: 'httpParseHeaders')) {
    /**
     * Generates raw headers into array
     *
     * @param string $rawHeaders Raw headers from cURL response
     *
     * @return array
     * @throws \Exception
     */
    function httpParseHeaders($rawHeaders): array
    {
        $headers = [];
        $key = '';

        foreach (explode(separator: "\n", string: $rawHeaders) as $i => $h) {
            $h = explode(separator: ':', string: $h, limit: 2);

            if (isset($h[1])) {
                if (!isset($headers[$h[0]])) {
                    $headers[$h[0]] = trim(string: $h[1]);
                } elseif (is_array(value: $headers[$h[0]])) {
                    $headers[$h[0]] = array_merge(
                        $headers[$h[0]],
                        [trim(string: $h[1])]
                    );
                } else {
                    $headers[$h[0]] = array_merge(
                        [$headers[$h[0]]],
                        [trim(string: $h[1])]
                    );
                }

                $key = $h[0];
            } else {
                if (substr(string: $h[0], offset: 0, length: 1) == "\t") {
                    $headers[$key] .= "\r\n\t" . trim(string: $h[0]);
                } elseif (!$key) {
                    $headers[0] = trim(string: $h[0]);
                }
            }
        }

        return $headers;
    }
}

if (!function_exists(function: 'getCurlConfig')) {
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
    function getCurlConfig(
        $homeURL,
        $method,
        $route,
        $queryString,
        $header = [],
        $payload = ''
    ): array {
        $queryString = empty($queryString) ? '' : '&' . $queryString;
        $curlConfig[CURLOPT_URL] = "{$homeURL}?r={$route}{$queryString}";
        $curlConfig[CURLOPT_HTTPHEADER] = $header;
        $curlConfig[CURLOPT_HTTPHEADER][] = 'x-api-version: v1.0.0';
        $curlConfig[CURLOPT_HTTPHEADER][] = 'Cache-Control: no-cache';
        $curlConfig[CURLOPT_HEADER] = 1;

        $payload = http_build_query(
            data: [
                "Payload" => $payload
            ]
        );

        $contentType = [
            // 'Content-Type: text/plain; charset=utf-8',
            'Content-Type: application/x-www-form-urlencoded; charset=utf-8',
        ];

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
}

if (!function_exists(function: 'trigger')) {
    /**
     * Trigger cURL
     *
     * @param string $homeURL Site URL
     * @param string $method  HTTP method
     * @param string $route   Route
     * @param array  $header  Header
     * @param string $payload Payload
     *
     * @return mixed
     */
    function trigger(
        $homeURL,
        $method,
        $route,
        $header = [],
        $payload = ''
    ): mixed {
        $curl = curl_init();
        $curlConfig = getCurlConfig(
            homeURL: $homeURL,
            method: $method,
            route: $route,
            queryString: $queryString = '',
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
        $responseHeaders = httpParseHeaders(
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
            echo PHP_EOL . '===>' . $responseBody . PHP_EOL;
            $response = [
                'cURL Error #:' . $error,
                $responseBody
            ];
        } else {
            if (
                strpos(
                    haystack: $responseContentType,
                    needle: 'application/json;'
                ) !== false
            ) {
                $responseBody = json_decode(json: $responseBody, associative: true);
            }
            $response = $responseBody;
        }

        $queryString = empty($queryString) ? '' : '&' . $queryString;

        return [
            'route' => htmlspecialchars(string: "{$homeURL}?r={$route}{$queryString}"),
            'httpMethod' => $method,
            'requestHeaders' => $curlConfig[CURLOPT_HTTPHEADER],
            'requestPayload' => htmlspecialchars(string: $payload),
            'responseHttpCode' => $responseHttpCode,
            'responseHeaders' => $responseHeaders,
            'responseContentType' => $responseContentType,
            'responseBody' => $response
        ];
    }
}

if (!function_exists(function: 'genXmlPayload')) {
    /**
     * Generates XML Payload
     *
     * @param array $params   Params
     * @param array $payload  Payload
     * @param bool  $rowsFlag Flag
     *
     * @return array
     * @throws \Exception
     */
    function genXmlPayload(&$params, &$payload, $rowsFlag = false): void
    {
        if (empty($params)) {
            return;
        }

        $rows = false;

        $isObject = (isset($params[0])) ? false : true;

        if (!$isObject && count(value: $params) === 1) {
            $params = $params[0];
            if (empty($params)) {
                return;
            }
            $isObject = true;
        }

        if (!$isObject) {
            $payload .= '<Rows>';
            $rows = true;
        }

        if ($rowsFlag) {
            $payload .= '<Row>';
        }
        foreach ($params as $key => &$value) {
            if ($isObject) {
                $payload .= "<{$key}>";
            }
            if (is_array(value: $value)) {
                genXmlPayload(params: $value, payload: $payload, rowsFlag: $rows);
            } else {
                $payload .= htmlspecialchars(string: $value);
            }
            if ($isObject) {
                $payload .= "</{$key}>";
            }
        }
        if ($rowsFlag) {
            $payload .= '</Row>';
        }
        if (!$isObject) {
            $payload .= '</Rows>';
        }
    }
}
