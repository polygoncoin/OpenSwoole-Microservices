<?php

/**
 * Web
 * php version 8.3
 *
 * @category  Web
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App;

/**
 * Web class
 * php version 8.3
 *
 * @category  Web
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class Web
{
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
    public static function getCurlConfig(
        $homeURL,
        $method,
        $route,
        $queryString,
        $header = [],
        $payload = '',
        $file = null
    ): array {
        $curlConfig[\CURLOPT_URL] = "{$homeURL}?route={$route}{$queryString}";
        $curlConfig[\CURLOPT_HTTPHEADER] = $header;
        $curlConfig[\CURLOPT_HEADER] = 1;

        switch ($method) {
            case 'GET':
                break;
            case 'POST':
                $curlConfig[\CURLOPT_POST] = true;
                if ($file === null) {
                    $curlConfig[\CURLOPT_POSTFIELDS] = $payload;
                }
                break;
            case 'PUT':
            case 'PATCH':
            case 'DELETE':
                $curlConfig[\CURLOPT_CUSTOMREQUEST] = $method;
                if ($file === null) {
                    $curlConfig[\CURLOPT_POSTFIELDS] = $payload;
                }
                break;
        }
        $curlConfig[\CURLOPT_RETURNTRANSFER] = true;

        $cookieFileName = '/' . md5($homeURL) . '-cookies.txt';
        $cookieFile = Constants::$WEB_COOKIES_DIR . $cookieFileName;
        $curlConfig[\CURLOPT_COOKIEJAR] = $cookieFile; // Store cookies
        $curlConfig[\CURLOPT_COOKIEFILE] = $cookieFile; // Read cookies

        return $curlConfig;
    }

    /**
     * Trigger cURL
     *
     * @param string $homeURL Site URL
     * @param string $method  HTTP method
     * @param string $route   Route
     * @param array  $header  Header
     * @param string $payload Payload
     * @param string $file    File path
     *
     * @return mixed
     */
    public static function trigger(
        $homeURL,
        $method,
        $route,
        $header = [],
        $payload = '',
        $file = null
    ): mixed {
        $queryString = '';

        $curl = curl_init();
        $curlConfig = self::getCurlConfig(
            homeURL: $homeURL,
            method: $method,
            route: $route,
            queryString: $queryString,
            header: $header,
            payload: $payload,
            file: $file
        );
        if ($file !== null) {
            switch ($method) {
                case 'POST':
                    // // Create a CURLFile object
                    // if (function_exists('curl_file_create')) {
                    //     $cFile = curl_file_create($file, mime_content_type($file), basename($file));
                    // } else {
                    //     // Fallback for very old PHP versions (deprecated)
                    //     $cFile = '@' . realpath($file);
                    // }
                    // $postData = array(
                    //     'description' => 'A file upload test', // Other form fields go here
                    //     'file' => $cFile // This name must match what your server expects
                    // );
                    // $curlConfig[\CURLOPT_POSTFIELDS] = $postData;
                    $curlFile = new \CURLFile($file, 'text/plain', 'uploaded_file.txt');
                    $curlConfig[\CURLOPT_POSTFIELDS] = [
                        'file' => $curlFile
                    ];
                    break;
                case 'PUT':
                case 'PATCH':
                case 'DELETE':
                    $fp = fopen($file, 'rb');
                    $curlConfig[\CURLOPT_INFILE] = $fp;
                    $curlConfig[\CURLOPT_INFILESIZE] = filesize($file);
                    break;
            }
        }
        curl_setopt_array(handle: $curl, options: $curlConfig);

        $curlResponse = curl_exec(handle: $curl);

        $responseHttpCode = curl_getinfo(
            handle: $curl,
            option: \CURLINFO_HTTP_CODE
        );

        $responseContentType = curl_getinfo(
            handle: $curl,
            option: \CURLINFO_CONTENT_TYPE
        );

        $headerSize = curl_getinfo(handle: $curl, option: \CURLINFO_HEADER_SIZE);

        $responseHeaders = self::httpParseHeaders(
            rawHeaders: substr(
                string: $curlResponse,
                offset: 0,
                length: $headerSize
            )
        );
        $responseBody = substr(string: $curlResponse, offset: $headerSize);

        $queryString = empty($queryString) ? '' : '&' . $queryString;
        $return['request'] = [
            'URI' => htmlspecialchars(string: "{$homeURL}?route={$route}{$queryString}"),
            'httpMethod' => $method,
            'requestHeaders' => $curlConfig[\CURLOPT_HTTPHEADER],
            'requestPayload' => nl2br(htmlspecialchars(string: $payload)),
        ];

        if ($curlResponse === false) {
            $errorCode = curl_errno(handle: $curl);
            $errorMessage = curl_error(handle: $curl);

            $errorConstants = [];

            $list   = get_defined_constants(true);
            $list   = preg_grep('/^CURLE_/', array_flip($list['curl']));

            foreach ($list as $const) {
                if (constant($const) === $errorCode) {
                    $errorConstants[] = $const;
                }
            }

            $return['response'] = [
                'errorCode' => $errorCode,
                'errorMessage' => $errorMessage,
                'errorConstants' => $errorConstants
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

            $return['response'] = [
                'responseHttpCode' => $responseHttpCode,
                'responseHeaders' => $responseHeaders,
                'responseContentType' => $responseContentType,
                'responseBody' => $response
            ];
        }
        curl_close(handle: $curl);


        return $return;
    }

    /**
     * Generates raw headers into array
     *
     * @param string $rawHeaders Raw headers from cURL response
     *
     * @return array
     * @throws \Exception
     */
    private static function httpParseHeaders($rawHeaders): array
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
    public static function genXmlPayload(&$params, &$payload, $rowsFlag = false): void
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
                self::genXmlPayload(params: $value, payload: $payload, rowsFlag: $rows);
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
