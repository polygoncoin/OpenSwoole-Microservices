<?php

/**
 * Start
 * php version 8.3
 *
 * @category  Start
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices;

use Microservices\App\Common;
use Microservices\App\Logs;
use Microservices\App\DataRepresentation\DataEncode;
use Microservices\App\HttpStatus;
use Microservices\Services;

class Start
{
    /**
     * Process http request data
     *
     * @param array $http       HTTP request details
     * @param bool  $streamData false - represent child request
     *
     * @return array
     */
    public static function http($http, $streamData = false)
    {
        $headers = [];
        $version = 'v1.0.0';

        echo PHP_EOL . $http['server']['method'] . ':' . $http['get']['r'];

        // Check version
        if (
            !isset($http['server']['api_version'])
            || $http['server']['api_version'] !== $version
        ) {
            if ($streamData) {
                // Set response headers
                $headers['Content-Type'] = 'application/json; charset=utf-8';
                $headers['Cache-Control'] = 'no-store, no-cache, must-revalidate, max-age=0';
                $headers['Pragma'] = 'no-cache';
            }

            $data = '{"Status": 400, "Message": "Bad Request"}';
            $status = HttpStatus::$BadRequest;

            return [$headers, $data, $status];
        }

        try {
            $services = new Services(http: $http);

            if ($streamData && $http['server']['method'] == 'OPTIONS') {
                // Setting CORS
                $headers = $services->getHeaders();

                $data = '{}';
                
                $status = HttpStatus::$Ok;

                return [$headers, $data, $status];
            }

            if ($services->init()) {
                // Setting CORS
                if ($streamData) {
                    $headers = $services->getHeaders();
                }

                $services->process();

                $data = $services->returnResults();
                $status = Common::$res->httpStatus;

                return [$headers, $data, $status];
            }
        } catch (\Exception $e) {
            if (!in_array(needle: $e->getCode(), haystack: [400, 429])) {
                list($usec, $sec) = explode(separator: ' ', string: microtime());
                $dateTime = date(
                    format: 'Y-m-d H:i:s',
                    timestamp: $sec
                ) . substr(string: $usec, offset: 1);

                // Log request details
                $logDetails = [
                    'LogType' => 'ERROR',
                    'DateTime' => $dateTime,
                    'HttpDetails' => [
                        'HttpCode' => $e->getCode(),
                        'HttpMessage' => $e->getMessage()
                    ],
                    'Details' => [
                        '$_GET' => $_GET,
                        'php:input' => @file_get_contents(filename: 'php://input'),
                        'session' => Common::$req->s
                    ]
                ];
                $logsObj = new Logs();
                $logsObj->log(logDetails: $logDetails);
            }

            if ($e->getCode() == 429) {
                $headers = [];
                $headers['Retry-After'] = $e->getMessage();
                $arr = [
                    'Status' => $e->getCode(),
                    'Message' => 'Too Many Requests',
                    'RetryAfter' => $e->getMessage()
                ];
            } else {
                $arr = [
                    'Status' => $e->getCode(),
                    'Message' => $e->getMessage()
                ];
            }

            $dataEncode = new DataEncode(http: $http);
            $dataEncode->init();
            $dataEncode->startObject();
            $dataEncode->addKeyData(key: 'Error', data: $arr);

            $data = $dataEncode->getData();
            $status = $e->getCode();

            return [$headers, $data, $status];        
        }
    }
}
