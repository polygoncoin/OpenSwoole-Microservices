<?php
namespace Microservices\Custom;

use Microservices\App\Constants;
use Microservices\App\Common;
use Microservices\App\Env;

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
class Password
{
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
        $this->c->httpRequest->loadPayload();
        return $this->c->httpResponse->isSuccess();
    }

    /**
     * Process
     *
     * @return boolean
     */
    public function process()
    {
        if ($this->c->httpRequest->input['payloadType'] === 'Object') {
            $payload = $this->c->httpRequest->jsonDecode->get();
        } else {
            $payload = $this->c->httpRequest->jsonDecode->get('0');
        }
        $this->c->httpRequest->input['payload'] = $payload;

        $oldPassword = $this->c->httpRequest->input['payload']['old_password'];
        $oldPasswordHash = $this->c->httpRequest->input['readOnlySession']['password_hash'];

        if (password_verify($oldPassword, $oldPasswordHash)) {
            $userName = $this->c->httpRequest->input['readOnlySession']['username'];
            $newPassword = $this->c->httpRequest->input['payload']['new_password'];
            $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);

            $database = getenv('globalDatabase');
            $table = getenv('users');
            $sql = "Update `{$database}`.`{$table}` SET password_hash = :password_hash WHERE username = :username AND is_deleted = :is_deleted";
            $sqlParams = [
                ':password_hash' => $newPasswordHash,
                ':username' => $userName,
                ':is_deleted' => 'No',
            ];

            $this->c->httpRequest->db->execDbQuery($sql, $sqlParams);
            $this->c->httpRequest->db->closeCursor();

            if ($this->c->httpRequest->cache->cacheExists("user:{$userName}")) {
                $userDetails = json_decode($this->c->httpRequest->cache->getCache("user:{$userName}"), true);
                $userDetails['password_hash'] = $newPasswordHash;
                $this->c->httpRequest->cache->setCache("user:{$userName}", json_encode($userDetails));
                $this->c->httpRequest->cache->deleteCache($this->c->httpRequest->input['token']);
            }

            $this->c->httpResponse->jsonEncode->addKeyValue('Results', 'Password changed successfully');
        }

        return $this->c->httpResponse->isSuccess();
    }
}
