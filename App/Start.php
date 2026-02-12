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

namespace Microservices\App;

use Microservices\App\Cache;
use Microservices\App\Constants;
use Microservices\App\Logs;
use Microservices\App\DataRepresentation\DataEncode;
use Microservices\App\HttpStatus;
use Microservices\App\Microservices;

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
        if ($http['server']['method'] === Constants::$GET) {
            $cache = new Cache(http: $http);
            if ($cache->init(mode: 'Open')) {
                // File exists - Serve from Dropbox
                return $cache->process();
            }
            $cache = null;
        }

        $headers = [];

        try {
            $Microservices = new Microservices(http: $http);

            if ($streamData && $http['server']['method'] == 'OPTIONS') {
                // Setting CORS
                $headers = $Microservices->getHeaders();
                $data = '{}';
                $status = HttpStatus::$Ok;

                return [$headers, $data, $status];
            }

            if ($Microservices->init()) {
                // Setting CORS
                if ($streamData) {
                    $headers = $Microservices->getHeaders();
                }

                $return = $Microservices->process();
                if (is_array($return) && count($return) === 3) {
                    return $return;
                }

                $data = $Microservices->returnResults();
                $status = $Microservices->api->res->httpStatus;

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
                    'Details' => $Microservices->api->req->s
                ];
                $logsObj = new Logs();
                $logsObj->log(logDetails: $logDetails);
            }

            $headers = [];
            if ($e->getCode() == 429) {
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

            // $dataEncode = new DataEncode(http: $http);
            // $dataEncode->init();
            // $dataEncode->startObject();
            // $dataEncode->addKeyData(key: 'Error', data: $arr);

            // $data = $dataEncode->getData();
            $data = json_encode(['Error' => $arr]);
            $status = $e->getCode();

            return [$headers, $data, $status];
        }
    }
}
