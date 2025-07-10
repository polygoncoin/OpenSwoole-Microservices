<?php
/**
 * Load CacheServerKeys_Required
 * php version 8.3
 *
 * @category  Reload
 * @package   OpenSwoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/OpenSwoole-Microservices
 * @since     Class available since Release 1.0.0
 */
namespace Microservices\App;

use Microservices\App\AppTrait;
use Microservices\App\CacheKey;
use Microservices\App\Common;
use Microservices\App\Servers\Cache\AbstractCache;
use Microservices\App\Servers\Database\AbstractDatabase;

/**
 * Load CacheServerKeys_Required
 * php version 8.3
 *
 * @category  Reload
 * @package   OpenSwoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/OpenSwoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class Reload
{
    use AppTrait;

    /**
     * Database Object
     *
     * @var null|AbstractDatabase
     */
    public $db = null;

    /**
     * Caching Object
     *
     * @var null|AbstractCache
     */
    public $cache = null;

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
    public function __construct(&$common)
    {
        $this->_c = &$common;
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
        $this->cache = $this->_c->req->connectCache(
            cacheType: getenv(name: 'cacheType'), 
            cacheHostname: getenv(name: 'cacheHostname'), 
            cachePort: getenv(name: 'cachePort'), 
            cacheUsername: getenv(name: 'cacheUsername'), 
            cachePassword: getenv(name: 'cachePassword'), 
            cacheDatabase: getenv(name: 'cacheDatabase')
        );

        $this->_processDomainAndUser();
        $this->_processGroup();

        return true;
    }

    /**
     * Adds user details to cache
     *
     * @return void
     */
    private function _processDomainAndUser(): void
    {
        $this->_c->req->db = $this->_c->req->connectDb(
            dbType: getenv(name: 'globalType'), 
            dbHostname: getenv(name: 'globalHostname'), 
            dbPort: getenv(name: 'globalPort'), 
            dbUsername: getenv(name: 'globalUsername'), 
            dbPassword: getenv(name: 'globalPassword'), 
            dbDatabase: getenv(name: 'globalDatabase')
        );
        $this->db = &$this->_c->req->db;

        $this->db->execDbQuery(
            sql: "
                SELECT
                    *
                FROM
                    `{$this->execPhpFunc(param: getenv(name: 'clients'))}` C
                ", 
            params: []
        );
        $crows = $this->db->fetchAll();
        $this->db->closeCursor();
        for ($ci = 0, $ci_count = count(value: $crows); $ci < $ci_count; $ci++) {
            if (!empty($crows[$ci]['open_api_domain'])) {
                $c_key = CacheKey::clientOpenToWeb(
                    hostname: $crows[$ci]['open_api_domain']
                );
                $this->cache->setCache(
                    key: $c_key, 
                    value: json_encode(value: $crows[$ci])
                );
            }
            $c_key = CacheKey::client(hostname: $crows[$ci]['api_domain']);
            $this->cache->setCache(
                key: $c_key, 
                value: json_encode(value: $crows[$ci])
            );
            $this->_c->req->db = $this->_c->req->connectDb(
                dbType: getenv(name: $crows[$ci]['master_db_server_type']), 
                dbHostname: getenv(name: $crows[$ci]['master_db_hostname']), 
                dbPort: getenv(name: $crows[$ci]['master_db_port']), 
                dbUsername: getenv(name: $crows[$ci]['master_db_username']), 
                dbPassword: getenv(name: $crows[$ci]['master_db_password']), 
                dbDatabase: getenv(name: $crows[$ci]['master_db_database'])
            );

            $this->db->execDbQuery(
                sql: "
                    SELECT
                        *
                    FROM
                        `{$this->execPhpFunc(param: getenv(name: 'client_users'))}` U
                    ", 
                params: []
            );
            $urows = $this->db->fetchAll();
            $this->db->closeCursor();
            for ($ui = 0, $ui_count = count(value: $urows); $ui < $ui_count; $ui++) {
                $cu_key = CacheKey::clientUser(
                    clientId: $crows[$ci]['client_id'], 
                    username: $urows[$ui]['username']
                );
                $this->cache->setCache(
                    key: $cu_key, 
                    value: json_encode(value: $urows[$ui])
                );
            }
        }
    }

    /**
     * Adds group details to cache
     *
     * @return void
     */
    private function _processGroup(): void
    {
        $this->_c->req->db = $this->_c->req->connectDb(
            dbType: getenv(name: 'globalType'), 
            dbHostname: getenv(name: 'globalHostname'), 
            dbPort: getenv(name: 'globalPort'), 
            dbUsername: getenv(name: 'globalUsername'), 
            dbPassword: getenv(name: 'globalPassword'), 
            dbDatabase: getenv(name: 'globalDatabase')
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

        while ($row = $this->db->fetch(\PDO::FETCH_ASSOC)) {
            $g_key = CacheKey::group(groupId: $row['group_id']);
            $this->cache->setCache(key: $g_key, value: json_encode(value: $row));
            if (!empty($row['allowed_ips'])) {
                $cidrs = $this->_cidrsIpNumber(cidrs: $row['allowed_ips']);
                if (count(value: $cidrs)>0) {
                    $cidrKey = CacheKey::cidr(groupId: $row['group_id']);
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
    private function _processToken($token): void
    {
        $this->cache->deleteCache(key: "t:$token");
    }


    /**
     * Returns Start IP and End IP for a given CIDR
     *
     * @param string $cidrs IP address range in CIDR notation for check
     *
     * @return array
     */
    private function _cidrsIpNumber($cidrs): array
    {
        $response = [];

        foreach (explode(
            separator: ', ', 
            string: str_replace(
                search: ' ', 
                replace: '', 
                subject: $cidrs
            )
        ) as $cidr) {
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
     * Destructor
     */
    public function __destruct()
    {
    }
}
