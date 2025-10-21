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
use Microservices\App\Common;

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
     * Database object
     *
     * @var null|Object
     */
    public $db = null;

    /**
     * Caching object
     *
     * @var null|Object
     */
    public $cache = null;

    /**
     * Common object
     *
     * @var null|Common
     */
    private $c = null;

    /**
     * Constructor
     *
     * @param Common $common Common object
     */
    public function __construct(Common &$common)
    {
        $this->c = &$common;
    }

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
        $this->cache = $this->c->req->connectCache(
            cacheType: getenv(name: 'globalCacheType'),
            cacheHostname: getenv(name: 'globalCacheHostname'),
            cachePort: getenv(name: 'globalCachePort'),
            cacheUsername: getenv(name: 'globalCacheUsername'),
            cachePassword: getenv(name: 'globalCachePassword'),
            cacheDatabase: getenv(name: 'globalCacheDatabase'),
            cacheTable: getenv(name: 'globalCacheTable')
        );

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
        $this->c->req->db = $this->c->req->connectDb(
            dbType: getenv(name: 'globalDbType'),
            dbHostname: getenv(name: 'globalDbHostname'),
            dbPort: getenv(name: 'globalDbPort'),
            dbUsername: getenv(name: 'globalDbUsername'),
            dbPassword: getenv(name: 'globalDbPassword'),
            dbDatabase: getenv(name: 'globalDbDatabase')
        );
        $this->db = &$this->c->req->db;

        $this->db->execDbQuery(
            sql: "
                SELECT
                    *
                FROM
                    `{$this->execPhpFunc(param: getenv(name: 'clients'))}` C
                ",
            params: []
        );
        $cRows = $this->db->fetchAll();
        $this->db->closeCursor();
        for ($ci = 0, $ciCount = count(value: $cRows); $ci < $ciCount; $ci++) {
            if (!empty($cRows[$ci]['open_api_domain'])) {
                $c_key = CacheKey::clientOpenToWeb(
                    hostname: $cRows[$ci]['open_api_domain']
                );
                $this->cache->setCache(
                    key: $c_key,
                    value: json_encode(value: $cRows[$ci])
                );
            }
            $c_key = CacheKey::client(hostname: $cRows[$ci]['api_domain']);
            $this->cache->setCache(
                key: $c_key,
                value: json_encode(value: $cRows[$ci])
            );
            $this->c->req->db = $this->c->req->connectDb(
                dbType: getenv(name: $cRows[$ci]['master_db_server_type']),
                dbHostname: getenv(name: $cRows[$ci]['master_db_hostname']),
                dbPort: getenv(name: $cRows[$ci]['master_db_port']),
                dbUsername: getenv(name: $cRows[$ci]['master_db_username']),
                dbPassword: getenv(name: $cRows[$ci]['master_db_password']),
                dbDatabase: getenv(name: $cRows[$ci]['master_db_database'])
            );

            $this->db->execDbQuery(
                sql: "
                    SELECT
                        *
                    FROM
                        `{$this->execPhpFunc(param: getenv(name: 'clientUsers'))}` U
                    ",
                params: []
            );
            $uRows = $this->db->fetchAll();
            $this->db->closeCursor();
            for ($ui = 0, $uiCount = count(value: $uRows); $ui < $uiCount; $ui++) {
                $cu_key = CacheKey::clientUser(
                    cID: $cRows[$ci]['id'],
                    username: $uRows[$ui]['username']
                );
                $this->cache->setCache(
                    key: $cu_key,
                    value: json_encode(value: $uRows[$ui])
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
        $this->c->req->db = $this->c->req->connectDb(
            dbType: getenv(name: 'globalDbType'),
            dbHostname: getenv(name: 'globalDbHostname'),
            dbPort: getenv(name: 'globalDbPort'),
            dbUsername: getenv(name: 'globalDbUsername'),
            dbPassword: getenv(name: 'globalDbPassword'),
            dbDatabase: getenv(name: 'globalDbDatabase')
        );

        $this->db->execDbQuery(
            sql: "
                SELECT
                    *
                FROM
                    `{$this->execPhpFunc(param: getenv(name: 'groups'))}` G
                ",
            params: []
        );

        while ($gRows = $this->db->fetch(\PDO::FETCH_ASSOC)) {
            $g_key = CacheKey::group(gID: $gRows['id']);
            $this->cache->setCache(key: $g_key, value: json_encode(value: $gRows));
            if (!empty($gRows['allowed_ips'])) {
                $cidrs = $this->cidrsIpNumber(cidrs: $gRows['allowed_ips']);
                if (count(value: $cidrs) > 0) {
                    $cidrKey = CacheKey::cidr(gID: $gRows['id']);
                    $this->cache->setCache(
                        key: $cidrKey,
                        value: json_encode(value: $cidrs)
                    );
                }
            }
        }
        $this->db->closeCursor();
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
        $this->cache->deleteCache(key: CacheKey::token(token: $token));
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
