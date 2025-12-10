<?php

/**
 * ThirdPartyAPI
 * php version 8.3
 *
 * @category  ThirdPartyAPI_Interface
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\Supplement\ThirdParty;

/**
 * ThirdPartyAPI Trait
 * php version 8.3
 *
 * @category  ThirdPartyAPI_Trait
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
trait ThirdPartyTrait
{
    /**
     * Return cURL Config
     *
     * @param array  $header        Header
     * @param string $contentType   Content-Type
     * @param string $method        HTTP method
     * @param string $url           URL
     * @param array  $urlParams     Query String Params array
     * @param array  $payloadParams Payload array
     *
     * @return array
     */
    private function getCurlConfig(
        $header,
        $contentType,
        $method,
        $url,
        $urlParams,
        $payloadParams
    ): array {
        $queryString = empty($urlParams) ? '' :
            '?' . http_build_query(data: $urlParams);
        $curlConfig[CURLOPT_URL] = "{$url}{$queryString}";
        $curlConfig[CURLOPT_HTTPHEADER] = $header;
        $curlConfig[CURLOPT_HTTPHEADER][] = 'Cache-Control: no-cache';
        $curlConfig[CURLOPT_HEADER] = 1;

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
     * @param array  $header        Header
     * @param string $contentType   Content-Type
     * @param string $method        HTTP method
     * @param string $url           URL
     * @param array  $urlParams     Query String Params array
     * @param array  $payloadParams Payload array
     *
     * @return array
     */
    private function trigger(
        $header,
        $contentType,
        $method,
        $url,
        $urlParams,
        $payloadParams
    ): array {

        // $contentType = 'Content-Type: text/plain; charset=utf-8';

        $curl = curl_init();
        $curlConfig = $this->getCurlConfig(
            header: $header,
            contentType: $contentType,
            method: $method,
            url: $url,
            urlParams: $urlParams,
            payloadParams: $payloadParams
        );
        curl_setopt_array(handle: $curl, options: $curlConfig);
        $curlResponse = curl_exec(handle: $curl);

        $return['request'] = [
            'url' => $curlConfig[CURLOPT_URL],
            'method' => $method,
            'headers' => $curlConfig[CURLOPT_HTTPHEADER],
            'payload' => $payloadParams
        ];

        if ($curlResponse === false) {
            $errorCode = curl_errno($ch);
            $errorMessage = curl_error($ch);

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
            $responseHttpCode = curl_getinfo(
                handle: $curl,
                option: CURLINFO_HTTP_CODE
            );
            $responseContentType = curl_getinfo(
                handle: $curl,
                option: CURLINFO_CONTENT_TYPE
            );

            $headerSize = curl_getinfo(handle: $curl, option: CURLINFO_HEADER_SIZE);
            $responseHeaders = $this->httpParseHeaders(
                rawHeaders: substr(
                    string: $curlResponse,
                    offset: 0,
                    length: $headerSize
                )
            );
            $responseBody = substr(string: $curlResponse, offset: $headerSize);
            $return['response'] = [
                'httpCode' => $responseHttpCode,
                'headers' => $responseHeaders,
                'contentType' => $responseContentType,
                'body' => $responseBody
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
    private function httpParseHeaders($rawHeaders): array
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
