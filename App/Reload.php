<?php
namespace Microservices\App;

use Microservices\App\Constants;
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
        $this->c = $common;
    }

    /**
     * Initialize
     *
     * @return boolean
     */
    public function init()
    {
        Env::$dbType = getenv('defaultDbType');
        Env::$dbHostname = getenv('defaultDbHostname');
        Env::$dbPort = getenv('defaultDbPort');
        Env::$dbUsername = getenv('defaultDbUsername');
        Env::$dbPassword = getenv('defaultDbPassword');
        Env::$dbDatabase = getenv('defaultDbDatabase');

        $this->c->httpRequest->setDb();

        return $this->c->httpResponse->isSuccess();
    }

    /**
     * Process
     *
     * @return boolean
     */
    public function process($refresh = 'all', $idsString = null)
    {
        $ids = [];

        if (!is_null($idsString)) {
            foreach (explode(',', trim($idsString)) as $value) {
                if (ctype_digit($value = trim($value))) {
                    $ids[] = (int)$value;
                } else {
                    $this->c->httpResponse->return4xx(404, 'Only integer values supported for ids.');
                    return;
                }
            }
        }

        if ($refresh === 'all') {
            $this->processUser();
            $this->processGroup();
            $this->processGroupIps();
        } else {
            switch ($refresh) {
                case 'user':
                    $this->processUser($ids);
                    break;
                case 'group':
                    $this->processGroup($ids);
                    break;
                case 'groupIp':
                    $this->processGroupIps($ids);
                    break;
                case 'token':
                    $this->processToken($idsString);
                    break;
            }
        }

        return $this->c->httpResponse->isSuccess();
    }

    /**
     * Adds user details to cache.
     *
     * @param array $ids Optional - provide ids are specific reload.
     * @return void
     */
    private function processUser($ids = [])
    {
        $whereClause = count($ids) ? 'WHERE U.user_id IN (' . implode(', ',array_map(function ($id) { return '?';}, $ids)) . ');' : ';';

        $this->c->httpRequest->db->execDbQuery("
            SELECT
                U.user_id,
                U.username,
                U.password_hash,
                G.client_id,
                G.name as group_name,
                U.group_id
            FROM
                `{$this->execPhpFunc(getenv('users'))}` U
            LEFT JOIN
                `{$this->execPhpFunc(getenv('groups'))}` G ON U.group_id = G.group_id
            {$whereClause}", $ids);

        while($row = $this->c->httpRequest->db->fetch(\PDO::FETCH_ASSOC)) {
            $this->c->httpRequest->cache->setCache("user:{$row['username']}", json_encode($row));
        }

        $this->c->httpRequest->db->closeCursor();
    }

    /**
     * Adds group details to cache.
     *
     * @param array $ids Optional - privide ids are specific reload.
     * @return void
     */
    private function processGroup($ids = [])
    {
        $whereClause = count($ids) ? 'WHERE G.group_id IN (' . implode(', ',array_map(function ($id) { return '?';}, $ids)) . ');' : ';';

       $this->c->httpRequest->db->execDbQuery("
            SELECT
                G.group_id,
                G.name,
                G.client_id,
                C.write_db_server_type,
                C.write_db_hostname,
                C.write_db_port,
                C.write_db_username,
                C.write_db_password,
                C.write_db_database,
                C.read_db_server_type,
                C.read_db_hostname,
                C.read_db_port,
                C.read_db_username,
                C.read_db_password,
                C.read_db_database
            FROM
                `{$this->execPhpFunc(getenv('groups'))}` G
            LEFT JOIN
                `{$this->execPhpFunc(getenv('connections'))}` C on G.connection_id = C.connection_id
            {$whereClause}", $ids);

        while($row = $this->c->httpRequest->db->fetch(\PDO::FETCH_ASSOC)) {
            $this->c->httpRequest->cache->setCache("group:{$row['group_id']}", json_encode($row));
        }

       $this->c->httpRequest->db->closeCursor();
    }

    /**
     * Adds restricted ips for group members to cache.
     *
     * @param array $ids Optional - privide ids are specific reload.
     * @return void
     */
    private function processGroupIps($ids = [])
    {
        $whereClause = count($ids) ? 'WHERE group_id IN (' . implode(', ',array_map(function ($id) { return '?';}, $ids)) . ');' : ';';

       $this->c->httpRequest->db->execDbQuery(
            "SELECT group_id, allowed_ips FROM `{$this->execPhpFunc(getenv('groups'))}` {$whereClause}",
            $ids
        );

        $cidrArray = [];
        while($row = $this->c->httpRequest->db->fetch(\PDO::FETCH_ASSOC)) {
            if (!empty($row['allowed_ips'])) {
                $cidrs = $this->c->httpRequest->cidrsIpNumber($row['allowed_ips']);
                if (count($cidrs)>0) {
                    $this->c->httpRequest->cache->setCache("cidr:{$row['group_id']}", json_encode($cidrs));
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
        $this->c->httpRequest->cache->deleteCache($token);
    }

    /**
     * Destructor
     */
    public function __destruct()
    {
    }
}
