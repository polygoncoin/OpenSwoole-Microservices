<?php

/**
 * Functions File
 * php version 8.3
 *
 * @category  Functions
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App;

use Microservices\App\Common;

/**
 * Functions File
 * php version 8.3
 *
 * @category  Functions
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class Functions
{
    /**
     * Check Errors related to File Upload
     *
     * @param array $httpFiles $this->api->http['files']
     *
     * @return void
     * @throws \Exception
     */
    public static function validateFileUpload($httpFiles): void
    {
        if (count($httpFiles) > 1) {
            throw new \Exception(
                message: 'Supports only one file with each request',
                code: HttpStatus::$BadRequest
            );
        }

        foreach ($httpFiles as $file => $details) {
            if (isset($details['error'])) {
                switch ($details['error']) {
                    case \UPLOAD_ERR_INI_SIZE: // value 1
                        throw new \Exception(
                            message: 'Size of the uploaded file exceeds the maximum value specified',
                            code: HttpStatus::$InternalServerError
                        );
                        break;

                    case \UPLOAD_ERR_FORM_SIZE: // value 2
                        throw new \Exception(
                            message: 'Size of the uploaded file exceeds the maximum value specified in the HTML form in the MAX_FILE_SIZE element',
                            code: HttpStatus::$BadRequest
                        );
                        break;

                    case \UPLOAD_ERR_PARTIAL: // value 3
                        throw new \Exception(
                            message: 'The file was only partially uploaded',
                            code: HttpStatus::$InternalServerError
                        );
                        break;

                    case \UPLOAD_ERR_NO_FILE: // value 4
                        throw new \Exception(
                            message: 'No file was uploaded',
                            code: HttpStatus::$InternalServerError
                        );
                        break;

                    case \UPLOAD_ERR_NO_TMP_DIR: // value 6
                        throw new \Exception(
                            message: 'No temporary directory is specified',
                            code: HttpStatus::$InternalServerError
                        );
                        break;

                    case \UPLOAD_ERR_CANT_WRITE: // value 7
                        throw new \Exception(
                            message: 'Writing the file to disk failed',
                            code: HttpStatus::$InternalServerError
                        );
                        break;

                    case \UPLOAD_ERR_EXTENSION: // value 8
                        throw new \Exception(
                            message: 'An extension stopped the file upload process',
                            code: HttpStatus::$InternalServerError
                        );
                        break;
                }
            }
        }
    }

    /**
     * Returns Start IP and End IP for a given CIDR
     *
     * @param string $cidrString IP address range in CIDR notation for check
     *
     * @return array
     */
    public static function cidrsIpNumber($cidrString): array
    {
        $response = [];

        foreach (
            explode(
                separator: ', ',
                string: str_replace(
                    search: ' ',
                    replace: '',
                    subject: $cidrString
                )
            ) as $cidr
        ) {
            if (strpos(haystack: $cidr, needle: '/')) {
                [$cidrIp, $bits] = explode(
                    separator: '/',
                    string: str_replace(search: ' ', replace: '', subject: $cidr)
                );
                $binCidrIpStr = str_pad(
                    string: decbin(num: ip2long(ip: $cidrIp)),
                    length: 32,
                    pad_string: 0,
                    pad_type: STR_PAD_LEFT
                );
                $startIpNumber = bindec(
                    binary_string: str_pad(
                        string: substr(
                            string: $binCidrIpStr,
                            offset: 0,
                            length: $bits
                        ),
                        length: 32,
                        pad_string: 0,
                        pad_type: STR_PAD_RIGHT
                    )
                );
                $endIpNumber = $startIpNumber + pow(num: 2, exponent: $bits) - 1;
                $response[] = [
                    'start' => $startIpNumber,
                    'end' => $endIpNumber
                ];
            } else {
                if ($ipNumber = ip2long(ip: $cidr)) {
                    $response[] = [
                        'start' => $ipNumber,
                        'end' => $ipNumber
                    ];
                }
            }
        }

        return $response;
    }

    /**
     * Check Cache CIDR
     *
     * @param string       $IP              $this->api->req->IP
     * @param string|array $againstCacheKey Cache Key(s)
     *
     * @return null|bool
     * @throws \Exception
     */
    public static function checkCacheCidr($IP, $againstCacheKey): null|bool
    {
        $cidrChecked = false;

        if (!is_array($againstCacheKey)) {
            $againstCacheKeys = [$againstCacheKey];
        } else {
            $againstCacheKeys = $againstCacheKey;
        }

        foreach ($againstCacheKeys as $againstCacheKey) {
            if (!DbFunctions::$gCacheServer->cacheExists(key: $againstCacheKey)) {
                continue;
            }
            $cidrChecked = true;

            $cidrs = json_decode(
                json: DbFunctions::$gCacheServer->getCache(
                    key: $againstCacheKey
                ),
                associative: true
            );
            $isValidIp = self::belongsToCidrsRange(IP: $IP, cidrs: $cidrs);
            if (!$isValidIp) {
                throw new \Exception(
                    message: 'IP not supported',
                    code: HttpStatus::$BadRequest
                );
            }
        }

        return $cidrChecked;
    }

    /**
     * Check CIDR
     *
     * @param string $IP         $this->api->req->IP
     * @param string $cidrString CIDRs
     *
     * @return null|bool
     * @throws \Exception
     */
    public static function checkCidr($IP, $cidrString): null|bool
    {
        $cidrs = self::cidrsIpNumber(cidrString: $cidrString);
        $isValidIp = self::belongsToCidrsRange(IP: $IP, cidrs: $cidrs);
        if (!$isValidIp) {
            throw new \Exception(
                message: 'IP not supported',
                code: HttpStatus::$BadRequest
            );
        }

        return $isValidIp;
    }

    /**
     * Belongs to Cidrs range
     *
     * @param string $IP    $this->api->req->IP
     * @param array  $cidrs Cache Key(s)
     *
     * @return bool
     * @throws \Exception
     */
    public static function belongsToCidrsRange($IP, $cidrs): bool
    {
        $ipNumber = ip2long(ip: $IP);

        $isValidIp = false;
        foreach ($cidrs as $cidr) {
            if (
                $cidr['start'] === 0
                && $cidr['end'] === 0
            ) {
                $isValidIp = true;
                break;
            } elseif ($cidr['start'] <= $ipNumber && $ipNumber <= $cidr['end']) {
                $isValidIp = true;
                break;
            }
        }

        return $isValidIp;
    }
}
