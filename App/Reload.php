<?php
namespace Microservices\App;

use Microservices\App\Constants;
use Microservices\App\CacheKey;
use Microservices\App\Common;
use Microservices\App\Env;
use Microservices\App\AppTrait;

/**
 * Updates cache
 *
 * This class is Reloads the Cache values of respective keys
 *
 * @category   Reload
 * @package    Microservices
 * @author     Ramesh Narayan Jangid
 * @copyright  Ramesh Narayan Jangid
 * @version    Release: @1.0.0@
 * @since      Class available since Release 1.0.0
 */
class Reload
{
    use AppTrait;

    /**
     * Microservices Collection of Common Objects
     * 
     * @var Microservices\App\Common
     */
    private $c = null;

    /**
     * Constructor
     * 
     * @param Microservices\App\Common $common
     */
    public function __construct(Common &$common)
    {
        $this->c = &$common;
    }

    /**
     * Initialize
     *
     * @return boolean
     */
    public function init()
    {
        return true;
    }

    /**
     * Process
     *
     * @return boolean
     */
    public function process()
    {
        $this->processDomainAndUser();
        $this->processGroup();

        return true;
    }

    /**
     * Adds user details to cache.
     *
     * @return void
     */
    private function processDomainAndUser()
    {
        $this->c->httpRequest->setDb(
            getenv('globalType'),
            getenv('globalHostname'),
            getenv('globalPort'),
            getenv('globalUsername'),
            getenv('globalPassword'),
            getenv('globalDatabase')
        );

        $this->c->httpRequest->db->execDbQuery("
            SELECT
                *
            FROM
                `{$this->execPhpFunc(getenv('clients'))}` C
            ", []);
        $crows = $this->c->httpRequest->db->fetchAll();
        $this->c->httpRequest->db->closeCursor();
        for ($ci = 0, $ci_count = count($crows); $ci < $ci_count; $ci++) {
            $c_key = CacheKey::Client($crows[$ci]['api_domain']);
            $this->c->httpRequest->cache->setCache($c_key, json_encode($crows[$ci]));
            $this->c->httpRequest->setDb(
                getenv($crows[$ci]['master_db_server_type']),
                getenv($crows[$ci]['master_db_hostname']),
                getenv($crows[$ci]['master_db_port']),
                getenv($crows[$ci]['master_db_username']),
                getenv($crows[$ci]['master_db_password']),
                getenv($crows[$ci]['master_db_database'])
            );
            $this->c->httpRequest->db->execDbQuery("
                SELECT
                    *
                FROM
                    `{$this->execPhpFunc(getenv('client_users'))}` U
                ", []);
            $urows = $this->c->httpRequest->db->fetchAll();
            $this->c->httpRequest->db->closeCursor();
            for ($ui = 0, $ui_count = count($urows); $ui < $ui_count; $ui++) {
                $cu_key = CacheKey::ClientUser($crows[$ci]['client_id'], $urows[$ui]['username']);
                $this->c->httpRequest->cache->setCache($cu_key, json_encode($urows[$ui]));
            }
        }
    }

    /**
     * Adds group details to cache.
     *
     * @return void
     */
    private function processGroup()
    {
        $this->c->httpRequest->setDb(
            getenv('globalType'),
            getenv('globalHostname'),
            getenv('globalPort'),
            getenv('globalUsername'),
            getenv('globalPassword'),
            getenv('globalDatabase')
        );

        $this->c->httpRequest->db->execDbQuery("
            SELECT
                *
            FROM
                `{$this->execPhpFunc(getenv('groups'))}` G
            ", []);

        while($row = $this->c->httpRequest->db->fetch(\PDO::FETCH_ASSOC)) {
            $g_key = CacheKey::Group($row['group_id']);
            $this->c->httpRequest->cache->setCache($g_key, json_encode($row));
            if (!empty($row['allowed_ips'])) {
                $cidrs = $this->c->httpRequest->cidrsIpNumber($row['allowed_ips']);
                if (count($cidrs)>0) {
                    $cidr_key = CacheKey::CIDR($row['group_id']);
                    $this->c->httpRequest->cache->setCache($cidr_key, json_encode($cidrs));
                }
            }
        }
        $this->c->httpRequest->db->closeCursor();
    }

    /**
     * Remove token from cache.
     *
     * @param string $token Token to be delete from cache.
     * @return void
     */
    private function processToken($token)
    {
        $this->c->httpRequest->cache->deleteCache("t:$token");
    }

    /**
     * Destructor
     */
    public function __destruct()
    {
    }
}
