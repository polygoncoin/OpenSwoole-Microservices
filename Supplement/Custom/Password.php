<?php
/**
 * CustomAPI
 * php version 8.3
 *
 * @category  CustomAPI_Interface
 * @package   OpenSwoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/OpenSwoole-Microservices
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
 * @package   OpenSwoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/OpenSwoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class Password implements CustomInterface
{
    use CustomTrait;

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
    public function __construct(Common &$common)
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
        $this->_c->req->loadPayload();
        return true;
    }

    /**
     * Process
     *
     * @return bool
     */
    public function process(): bool
    {
        if ($this->_c->req->sess['payloadType'] === 'Object') {
            $payload = $this->_c->req->dataDecode->get();
        } else {
            $payload = $this->_c->req->dataDecode->get('0');
        }
        $this->_c->req->sess['payload'] = $payload;

        $oldPassword = $this->_c->req->sess['payload']['old_password'];
        $oldPasswordHash = $this->_c->req->sess['userDetails']['password_hash'];

        if (password_verify(password: $oldPassword, hash: $oldPasswordHash)) {
            $userName = $this->_c->req->sess['userDetails']['username'];
            $newPassword = $this->_c->req->sess['payload']['new_password'];
            $newPasswordHash = password_hash(
                password: $newPassword,
                algo: PASSWORD_DEFAULT
            );

            $table = getenv(name: 'client_users');
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

            $this->_c->req->db->execDbQuery(sql: $sql, params: $sqlParams);
            $this->_c->req->db->closeCursor();

            $clientId = $this->_c->req->sess['clientDetails']['client_id'];
            $cu_key = CacheKey::clientUser(
                clientId: $clientId,
                username: $userName
            );
            if ($this->_c->req->cache->cacheExists(key: $cu_key)) {
                $userDetails = json_decode(
                    json: $this->_c->req->cache->getCache(
                        key: $cu_key
                    ),
                    associative: true
                );
                $userDetails['password_hash'] = $newPasswordHash;
                $this->_c->req->cache->setCache(
                    key: $cu_key,
                    value: json_encode(value: $userDetails)
                );
                $this->_c->req->cache->deleteCache(
                    key: CacheKey::token(token: $this->_c->req->sess['token'])
                );
            }

            $this->_c->res->dataEncode->addKeyData(
                key: 'Results',
                data: 'Password changed successfully'
            );
        }

        return true;
    }
}
