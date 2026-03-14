<?php

/**
 * CustomAPI
 * php version 8.3
 *
 * @category  CustomAPI_Interface
 * @package   Openswoole_Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\Supplement\Custom;

use Microservices\App\CacheServerKey;
use Microservices\App\Http;
use Microservices\App\DbCommonFunction;
use Microservices\Supplement\Custom\CustomInterface;
use Microservices\Supplement\Custom\CustomTrait;

/**
 * CustomAPI Password
 * php version 8.3
 *
 * @category  CustomAPI_Password
 * @package   Openswoole_Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class Password implements CustomInterface
{
	use CustomTrait;

	/**
	 * Http Object
	 *
	 * @var null|Http
	 */
	private $http = null;

	/**
	 * Constructor
	 *
	 * @param Http $http
	 */
	public function __construct(Http &$http)
	{
		$this->http = &$http;
	}

	/**
	 * Initialize
	 *
	 * @return bool
	 */
	public function init(): bool
	{
		$this->http->req->loadPayload();
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
		if ($this->http->req->s['payloadType'] === 'Object') {
			$payload = $this->http->req->dataDecode->get();
		} else {
			$payload = $this->http->req->dataDecode->get('0');
		}
		$this->http->req->s['payload'] = $payload;

		$oldPassword = $this->http->req->s['payload']['old_password'];
		$oldPasswordHash = $this->http->req->s['uDetails']['password_hash'];

		if (password_verify(password: $oldPassword, hash: $oldPasswordHash)) {
			$userName = $this->http->req->s['uDetails']['username'];
			$newPassword = $this->http->req->s['payload']['new_password'];
			$newPasswordHash = password_hash(
				password: $newPassword,
				algo: PASSWORD_DEFAULT
			);

			$usersTable = $this->http->req->usersTable;
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

			DbCommonFunction::$masterDb[$this->http->req->cId]->execDbQuery(sql: $sql, params: $sqlParams);
			DbCommonFunction::$masterDb[$this->http->req->cId]->closeCursor();

			$cID = $this->http->req->s['cDetails']['id'];
			$cu_key = CacheServerKey::customerUser(
				cID: $cID,
				username: $userName
			);
			if (DbCommonFunction::$gCacheServer->cacheExists(key: $cu_key)) {
				$uDetails = json_decode(
					json: DbCommonFunction::$gCacheServer->getCache(
						key: $cu_key
					),
					associative: true
				);
				$uDetails['password_hash'] = $newPasswordHash;
				DbCommonFunction::$gCacheServer->setCache(
					key: $cu_key,
					value: json_encode(value: $uDetails)
				);
				DbCommonFunction::$gCacheServer->deleteCache(
					key: CacheServerKey::token(token: $this->http->req->s['token'])
				);
			}

			$this->http->res->dataEncode->addKeyData(
				key: 'Results',
				data: 'Password changed successfully'
			);
		}

		return [true];
	}
}
