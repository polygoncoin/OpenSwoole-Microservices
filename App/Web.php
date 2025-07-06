<?php
namespace Microservices\App;

use Microservices\App\Common;
use Microservices\App\HttpStatus;

/**
 * Web class
 *
 * @category   Web class
 * @package    Microservices
 * @author     Ramesh Narayan Jangid
 * @copyright  Ramesh Narayan Jangid
 * @version    Release: @1.0.0@
 * @since      Class available since Release 1.0.0
 */
class Web
{
    /**
     * Microservices Collection of Common Objects
     *
     * @var null|Common
     */
    private $c = null;

    /**
     * Constructor
     *
     * @param Common $common
     */
    public function __construct(&$common)
    {
        $this->c = &$common;
    }

    private function getCurlConfig($homeURL, $method, $route, $queryString, $header = [], $payload = '')
    {
        $curlConfig[CURLOPT_URL] = "{$homeURL}?r={$route}&{$queryString}";
        $curlConfig[CURLOPT_HTTPHEADER] = $header;
        $curlConfig[CURLOPT_HEADER] = 1;

        $payload = http_build_query([
            "Payload" => $payload
        ]);

        switch ($method) {
            case 'GET':
                break;
            case 'POST':
                $curlConfig[CURLOPT_HTTPHEADER][] = 'Content-Type: text/plain; charset=utf-8';
                $curlConfig[CURLOPT_POST] = true;
                $curlConfig[CURLOPT_POSTFIELDS] = $payload;
                break;
            case 'PUT':
                $curlConfig[CURLOPT_HTTPHEADER][] = 'Content-Type: text/plain; charset=utf-8';
                $curlConfig[CURLOPT_CUSTOMREQUEST] = 'PUT';
                $curlConfig[CURLOPT_POSTFIELDS] = $payload;
                break;
            case 'PATCH':
                $curlConfig[CURLOPT_HTTPHEADER][] = 'Content-Type: text/plain; charset=utf-8';
                $curlConfig[CURLOPT_CUSTOMREQUEST] = 'PATCH';
                $curlConfig[CURLOPT_POSTFIELDS] = $payload;
                break;
            case 'DELETE':
                $curlConfig[CURLOPT_HTTPHEADER][] = 'Content-Type: text/plain; charset=utf-8';
                $curlConfig[CURLOPT_CUSTOMREQUEST] = 'DELETE';
                $curlConfig[CURLOPT_POSTFIELDS] = $payload;
                break;
        }
        $curlConfig[CURLOPT_RETURNTRANSFER] = true;

        return $curlConfig;
    }

    private function trigger($homeURL, $method, $route, $queryString, $header = [], $payload = '')
    {
        $curl = curl_init();
        $curlConfig = $this->getCurlConfig($homeURL, $method, $route, $queryString, $header, $payload);
        curl_setopt_array($curl, $curlConfig);
        $curlResponse = curl_exec($curl);

        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($curl, CURLINFO_CONTENT_TYPE);

        $headerSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $responseHeaders = $this->http_parse_headers(substr($curlResponse, 0, $headerSize));
        $responseBody = substr($curlResponse, $headerSize);

        $error = curl_error($curl);
        curl_close($curl);

        if ($error) {
            $response = ['cURL Error #:' . $error];
        } else {
            $response = $responseBody;
        }

        return [
            'httpCode' => $httpCode,
            'contentType' => $contentType,
            'headers' => $responseHeaders,
            'body' => $response
        ];
    }

    public function triggerConfig($triggerConfig)
    {
        if (!isset($this->c->httpRequest->session['token'])) {
            throw new \Exception('Missing token', HttpStatus::$InternalServerError);
        }

        $assoc = (!isset($triggerConfig[0])) ? true : false;

        $homeURL = 'http://127.0.0.1:9501';

        $header = [];
        $header[] = 'X-API-Version: v1.0.0';
        $header[] = 'Cache-Control: no-cache';
        $header[] = 'Authorization: Bearer ' . $this->c->httpRequest->session['token'];

        $response = [];
        $session = &$this->c->httpRequest->session;

        if ($assoc) {
            $method = $triggerConfig['__METHOD__'];
            list($routeElementsArr, $errors) = $this->getTriggerPayload($triggerConfig['__ROUTE__']);
            
            if (empty($errors)) {
                $route = '/' . implode('/',$routeElementsArr);
            } else {
                $response = $errors;
            }

            if (empty($response) && isset($triggerConfig['__QUERY-STRING__'])) {
                list($queryStringArr, $errors) = $this->getTriggerPayload($triggerConfig['__QUERY-STRING__']);

                if (empty($errors)) {
                    $queryString = http_build_query($queryStringArr);
                } else {
                    $response = $errors;
                }
            }

            if (empty($response)) {
                if (isset($triggerConfig['__PAYLOAD__'])) {
                    list($payloadArr, $errors) = $this->getTriggerPayload($triggerConfig['__PAYLOAD__']);
                } else {
                    $payloadArr = $errors = [];
                }
                
                if (empty($errors)) {
                    $response = $this->trigger($homeURL, $method, $route, $queryString, $header, $payload = json_encode($payloadArr));
                } else {
                    $response = $errors;
                }
            }
        } else {
            foreach ($triggerConfig as &$config) {
                $method = $config['__METHOD__'];
                list($routeElementsArr, $errors) = $this->getTriggerPayload($config['__ROUTE__']);

                if (empty($errors)) {
                    $route = '/' . implode('/',$routeElementsArr);
                } else {
                    $response[] = $errors;
                    continue;
                }

                if (isset($config['__QUERY-STRING__'])) {
                    list($queryStringArr, $errors) = $this->getTriggerPayload($config['__QUERY-STRING__']);

                    if (empty($errors)) {
                        $queryString = http_build_query($queryStringArr);
                    } else {
                        $response[] = $errors;
                        continue;
                    }
                }

                if (isset($config['__PAYLOAD__'])) {
                    list($payloadArr, $errors) = $this->getTriggerPayload($config['__PAYLOAD__']);
                } else {
                    $payloadArr = $errors = [];
                }
                
                if (empty($errors)) {
                    $response[] = $this->trigger($homeURL, $method, $route, $queryString, $header, $payload = json_encode($payloadArr));
                } else {
                    $response[] = $errors;
                }
            }
        }

        return $response;
    }

    /**
     * Generates Params for statement to execute
     *
     * @param array $payloadConfig API Payload configuration
     * @return array
     * @throws \Exception
     */
    private function getTriggerPayload(&$payloadConfig)
    {
        $sqlParams = [];
        $errors = [];

        // Collect param values as per config respectively
        foreach ($payloadConfig as &$config) {
            if (isset($config['column'])) {
                $var = $config['column'];
            } else {
                $var = null;
            }

            $dataPayloadType = $config['fetchFrom'];
            $dataPayloadTypeKey = $config['fetchFromValue'];
            if ($dataPayloadType === 'function') {
                $function = $dataPayloadTypeKey;
                $value = $function($this->c->httpRequest->session);
                if (is_null($var)) {
                    $sqlParams[] = $value;
                } else {
                    $sqlParams[$var] = $value;
                }
                continue;
            } else if (in_array($dataPayloadType, ['sqlResults', 'sqlParams', 'sqlPayload'])) {
                $dataPayloadTypeKeys = explode(':',$dataPayloadTypeKey);
                $value = $this->c->httpRequest->session[$dataPayloadType];
                foreach($dataPayloadTypeKeys as $key) {
                    if (!isset($value[$key])) {
                        throw new \Exception('Invalid hierarchy:  Missing hierarchy data', HttpStatus::$InternalServerError);
                    }
                    $value = $value[$key];
                }
                if (is_null($var)) {
                    $sqlParams[] = $value;
                } else {
                    $sqlParams[$var] = $value;
                }
                continue;
            } else if ($dataPayloadType === 'custom') {
                $value = $dataPayloadTypeKey;
                if (is_null($var)) {
                    $sqlParams[] = $value;
                } else {
                    $sqlParams[$var] = $value;
                }
                continue;
            } else if (isset($this->c->httpRequest->session[$dataPayloadType][$dataPayloadTypeKey])) {
                $value = $this->c->httpRequest->session[$dataPayloadType][$dataPayloadTypeKey];
                if (is_null($var)) {
                    $sqlParams[] = $value;
                } else {
                    $sqlParams[$var] = $value;
                }
                continue;
            } else {
                $errors[] = "Invalid configuration of '{$dataPayloadType}' for '{$dataPayloadTypeKey}'";
                continue;
            }
        }

        return [$sqlParams, $errors];
    }

    private function http_parse_headers($raw_headers) {
        $headers = array();
        $key = '';

        foreach(explode("\n", $raw_headers) as $i => $h) {
            $h = explode(':', $h, 2);

            if (isset($h[1])) {
                if (!isset($headers[$h[0]]))
                    $headers[$h[0]] = trim($h[1]);
                elseif (is_array($headers[$h[0]])) {
                    $headers[$h[0]] = array_merge($headers[$h[0]], array(trim($h[1])));
                }
                else {
                    $headers[$h[0]] = array_merge(array($headers[$h[0]]), array(trim($h[1])));
                }

                $key = $h[0];
            }
            else {
                if (substr($h[0], 0, 1) == "\t")
                    $headers[$key] .= "\r\n\t".trim($h[0]);
                elseif (!$key)
                    $headers[0] = trim($h[0]);
            }
        }

        return $headers;
    }
}
