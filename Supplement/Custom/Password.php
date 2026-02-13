<?php

/**
 * CustomAPI
 * php version 8.3
 *
 * @category  CustomAPI_Interface
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\Supplement\Custom;

use Microservices\App\CacheKey;
use Microservices\App\Common;
use Microservices\App\DbFunctions;
use Microservices\Supplement\Custom\CustomInterface;
use Microservices\Supplement\Custom\CustomTrait;

/**
 * CustomAPI Password
 * php version 8.3
 *
 * @category  CustomAPI_Password
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class Password implements CustomInterface
{
    use CustomTrait;

    /**
     * Api common Object
     *
     * @var null|Common
     */
    private $api = null;

    /**
     * Constructor
     *
     * @param Common $api
     */
    public function __construct(Common &$api)
    {
        $this->api = &$api;
    }

    /**
     * Initialize
     *
     * @return bool
     */
    public function init(): bool
    {
        $this->api->req->loadPayload();
        return true;
    }

    /**
     * Process
     *
     * @param array $payload Payload
     *
     * @return array
     */
    public function process(array $payload = []): array
    {
        if ($this->api->req->s['payloadType'] === 'Object') {
            $payload = $this->api->req->dataDecode->get();
        } else {
            $payload = $this->api->req->dataDecode->get('0');
        }
        $this->api->req->s['payload'] = $payload;

        $oldPassword = $this->api->req->s['payload']['old_password'];
        $oldPasswordHash = $this->api->req->s['uDetails']['password_hash'];

        if (password_verify(password: $oldPassword, hash: $oldPasswordHash)) {
            $userName = $this->api->req->s['uDetails']['username'];
            $newPassword = $this->api->req->s['payload']['new_password'];
            $newPasswordHash = password_hash(
                password: $newPassword,
                algo: PASSWORD_DEFAULT
            );

            $usersTable = $this->api->req->usersTable;
            $sql = "
                UPDATE `{$usersTable}`
                SET password_hash = :password_hash
                WHERE username = :username AND is_deleted = :is_deleted
            ";
            $sqlParams = [
                ':password_hash' => $newPasswordHash,
                ':username' => $userName,
                ':is_deleted' => 'No',
            ];

            DbFunctions::$masterDb[$this->api->req->cId]->execDbQuery(sql: $sql, params: $sqlParams);
            DbFunctions::$masterDb[$this->api->req->cId]->closeCursor();

            $cID = $this->api->req->s['cDetails']['id'];
            $cu_key = CacheKey::clientUser(
                cID: $cID,
                username: $userName
            );
            if (DbFunctions::$gCacheServer->cacheExists(key: $cu_key)) {
                $uDetails = json_decode(
                    json: DbFunctions::$gCacheServer->getCache(
                        key: $cu_key
                    ),
                    associative: true
                );
                $uDetails['password_hash'] = $newPasswordHash;
                DbFunctions::$gCacheServer->setCache(
                    key: $cu_key,
                    value: json_encode(value: $uDetails)
                );
                DbFunctions::$gCacheServer->deleteCache(
                    key: CacheKey::token(token: $this->api->req->s['token'])
                );
            }

            $this->api->res->dataEncode->addKeyData(
                key: 'Results',
                data: 'Password changed successfully'
            );
        }

        return [true];
    }
}
