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
        $this->c->req->loadPayload();
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
        if ($this->c->req->s['payloadType'] === 'Object') {
            $payload = $this->c->req->dataDecode->get();
        } else {
            $payload = $this->c->req->dataDecode->get('0');
        }
        $this->c->req->s['payload'] = $payload;

        $oldPassword = $this->c->req->s['payload']['old_password'];
        $oldPasswordHash = $this->c->req->s['uDetails']['password_hash'];

        if (password_verify(password: $oldPassword, hash: $oldPasswordHash)) {
            $userName = $this->c->req->s['uDetails']['username'];
            $newPassword = $this->c->req->s['payload']['new_password'];
            $newPasswordHash = password_hash(
                password: $newPassword,
                algo: PASSWORD_DEFAULT
            );

            $table = getenv(name: 'clientUsers');
            $sql = "
                UPDATE `{$table}`
                SET password_hash = :password_hash
                WHERE username = :username AND is_deleted = :is_deleted
            ";
            $sqlParams = [
                ':password_hash' => $newPasswordHash,
                ':username' => $userName,
                ':is_deleted' => 'No',
            ];

            $this->c->req->db->execDbQuery(sql: $sql, params: $sqlParams);
            $this->c->req->db->closeCursor();

            $cID = $this->c->req->s['cDetails']['id'];
            $cu_key = CacheKey::clientUser(
                cID: $cID,
                username: $userName
            );
            if ($this->c->req->cache->cacheExists(key: $cu_key)) {
                $uDetails = json_decode(
                    json: $this->c->req->cache->getCache(
                        key: $cu_key
                    ),
                    associative: true
                );
                $uDetails['password_hash'] = $newPasswordHash;
                $this->c->req->cache->setCache(
                    key: $cu_key,
                    value: json_encode(value: $uDetails)
                );
                $this->c->req->cache->deleteCache(
                    key: CacheKey::token(token: $this->c->req->s['token'])
                );
            }

            $this->c->res->dataEncode->addKeyData(
                key: 'Results',
                data: 'Password changed successfully'
            );
        }

        return [true];
    }
}
