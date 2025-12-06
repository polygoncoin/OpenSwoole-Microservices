<?php

/**
 * Load CacheServerKeys_Required
 * php version 8.3
 *
 * @category  Reload
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App;

use Microservices\App\AppTrait;
use Microservices\App\CacheKey;
use Microservices\App\DbFunctions;

/**
 * Load CacheServerKeys_Required
 * php version 8.3
 *
 * @category  Reload
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class Reload
{
    use AppTrait;

    /**
     * Initialize
     *
     * @return bool
     */
    public function init(): bool
    {
        return true;
    }

    /**
     * Process
     *
     * @return bool
     */
    public function process(): bool
    {
        DbFunctions::connectGlobalCache();

        $this->processDomainAndUser();
        $this->processGroup();

        return true;
    }

    /**
     * Adds user details to cache
     *
     * @return void
     */
    private function processDomainAndUser(): void
    {
        DbFunctions::connectGlobalDb();

        DbFunctions::$gDbServer->execDbQuery(
            sql: "
                SELECT
                    *
                FROM
                    `{$this->execPhpFunc(param: getenv(name: 'clients'))}` C
                ",
            params: []
        );
        $cRows = DbFunctions::$gDbServer->fetchAll();
        DbFunctions::$gDbServer->closeCursor();
        foreach ($cRows as $cRow) {
            if ($cRow['allowed_cidrs'] !== null) {
                $cCidrs = $this->cidrsIpNumber(cidrs: $cRow['allowed_cidrs']);
                if (count(value: $cCidrs) > 0) {
                    $cCidrKey = CacheKey::cCidr(cID: $cRow['id']);
                    DbFunctions::$gCacheServer->setCache(
                        key: $cCidrKey,
                        value: json_encode(value: $cCidrs)
                    );
                }
            }
            if (!empty($cRow['open_api_domain'])) {
                $c_key = CacheKey::clientOpenToWeb(
                    hostname: $cRow['open_api_domain']
                );
                DbFunctions::$gCacheServer->setCache(
                    key: $c_key,
                    value: json_encode(value: $cRow)
                );
            }
            $c_key = CacheKey::client(hostname: $cRow['api_domain']);
            DbFunctions::$gCacheServer->setCache(
                key: $c_key,
                value: json_encode(value: $cRow)
            );
            $db = DbFunctions::connectDb(
                dbServerType: getenv(name: $cRow['master_db_server_type']),
                dbHostname: getenv(name: $cRow['master_db_hostname']),
                dbPort: getenv(name: $cRow['master_db_port']),
                dbUsername: getenv(name: $cRow['master_db_username']),
                dbPassword: getenv(name: $cRow['master_db_password']),
                dbDatabase: getenv(name: $cRow['master_db_database'])
            );

            $db->execDbQuery(
                sql: "
                    SELECT
                        *
                    FROM
                        `{$this->execPhpFunc(param: getenv(name: 'clientUsers'))}` U
                    ",
                params: []
            );
            $uRows = $db->fetchAll();
            $db->closeCursor();
            foreach ($uRows as $uRow) {
                if ($uRow['allowed_cidrs'] !== null) {
                    $uCidrs = $this->cidrsIpNumber(cidrs: $uRow['allowed_cidrs']);
                    if (count(value: $uCidrs) > 0) {
                        $uCidrKey = CacheKey::uCidr(uID: $uRow['id']);
                        DbFunctions::$gCacheServer->setCache(
                            key: $uCidrKey,
                            value: json_encode(value: $uCidrs)
                        );
                    }
                }
                $cu_key = CacheKey::clientUser(
                    cID: $cRow['id'],
                    username: $uRow['username']
                );
                DbFunctions::$gCacheServer->setCache(
                    key: $cu_key,
                    value: json_encode(value: $uRow)
                );
            }
        }
    }

    /**
     * Adds group details to cache
     *
     * @return void
     */
    private function processGroup(): void
    {
        DbFunctions::connectGlobalCache();

        DbFunctions::$gDbServer->execDbQuery(
            sql: "
                SELECT
                    *
                FROM
                    `{$this->execPhpFunc(param: getenv(name: 'groups'))}` G
                ",
            params: []
        );

        while ($gRow = DbFunctions::$gDbServer->fetch(\PDO::FETCH_ASSOC)) {
            $g_key = CacheKey::group(gID: $gRow['id']);
            DbFunctions::$gCacheServer->setCache(key: $g_key, value: json_encode(value: $gRow));
            if ($gRow['allowed_cidrs'] !== null) {
                $cidrs = $this->cidrsIpNumber(cidrs: $gRow['allowed_cidrs']);
                if (count(value: $cidrs) > 0) {
                    $cidrKey = CacheKey::gCidr(gID: $gRow['id']);
                    DbFunctions::$gCacheServer->setCache(
                        key: $cidrKey,
                        value: json_encode(value: $cidrs)
                    );
                }
            }
        }
        DbFunctions::$gDbServer->closeCursor();
    }

    /**
     * Remove token from cache
     *
     * @param string $token Token to be delete from cache
     *
     * @return void
     */
    private function processToken($token): void
    {
        DbFunctions::$gCacheServer->deleteCache(key: CacheKey::token(token: $token));
    }

    /**
     * Returns Start IP and End IP for a given CIDR
     *
     * @param string $cidrs IP address range in CIDR notation for check
     *
     * @return array
     */
    private function cidrsIpNumber($cidrs): array
    {
        $response = [];

        foreach (
            explode(
                separator: ', ',
                string: str_replace(
                    search: ' ',
                    replace: '',
                    subject: $cidrs
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
}
