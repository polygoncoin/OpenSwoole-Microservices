<?php
namespace Microservices\Custom;

use Microservices\App\Constants;
use Microservices\App\CacheKey;
use Microservices\App\Common;
use Microservices\App\Env;
use Microservices\Custom\CustomInterface;
use Microservices\Custom\CustomTrait;

/**
 * Class to initialize DB Read operation
 *
 * This class process the GET api request
 *
 * @category   Category
 * @package    Microservices
 * @author     Ramesh Narayan Jangid
 * @copyright  Ramesh Narayan Jangid
 * @version    Release: @1.0.0@
 * @since      Class available since Release 1.0.0
 */
class Password implements CustomInterface
{
    use CustomTrait;

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
        $this->c->httpRequest->setConnection($fetchFrom = 'Master');
    }

    /**
     * Initialize
     *
     * @return boolean
     */
    public function init()
    {
        $this->c->httpRequest->loadPayload();
        return true;
    }

    /**
     * Process
     *
     * @return boolean
     */
    public function process()
    {
        if ($this->c->httpRequest->conditions['httprequestPayloadType'] === 'Object') {
            $payload = $this->c->httpRequest->jsonDecode->get();
        } else {
            $payload = $this->c->httpRequest->jsonDecode->get('0');
        }
        $this->c->httpRequest->conditions['payload'] = $payload;

        $oldPassword = $this->c->httpRequest->conditions['payload']['old_password'];
        $oldPasswordHash = $this->c->httpRequest->conditions['readOnlySession']['password_hash'];

        if (password_verify($oldPassword, $oldPasswordHash)) {
            $userName = $this->c->httpRequest->conditions['readOnlySession']['username'];
            $newPassword = $this->c->httpRequest->conditions['payload']['new_password'];
            $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);

            $table = getenv('client_users');
            $sql = "Update `{$table}` SET password_hash = :password_hash WHERE username = :username AND is_deleted = :is_deleted";
            $sqlParams = [
                ':password_hash' => $newPasswordHash,
                ':username' => $userName,
                ':is_deleted' => 'No',
            ];

            $this->c->httpRequest->db->execDbQuery($sql, $sqlParams);
            $this->c->httpRequest->db->closeCursor();

            $clientId = $this->c->httpRequest->clientInfo['client_id'];
            $cu_key = CacheKey::ClientUser($clientId,$userName);
            if ($this->c->httpRequest->cache->cacheExists($cu_key)) {
                $userDetails = json_decode($this->c->httpRequest->cache->getCache($cu_key), true);
                $userDetails['password_hash'] = $newPasswordHash;
                $this->c->httpRequest->cache->setCache($cu_key, json_encode($userDetails));
                $this->c->httpRequest->cache->deleteCache(CacheKey::Token($this->c->httpRequest->conditions['token']));
            }

            $this->c->httpResponse->jsonEncode->addKeyValue('Results', 'Password changed successfully');
        }

        return true;
    }
}
