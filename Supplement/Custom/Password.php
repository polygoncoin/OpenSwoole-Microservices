<?php

/**
 * CustomAPI
 * php version 8.3
 *
 * @category  CustomAPI_Interface
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\Supplement\Custom;

use Microservices\App\CacheServerKey;
use Microservices\App\DbCommonFunction;
use Microservices\App\Http;
use Microservices\App\Reload;
use Microservices\Supplement\Custom\CustomInterface;
use Microservices\Supplement\Custom\CustomTrait;

/**
 * CustomAPI Password
 * php version 8.3
 *
 * @category  CustomAPI_Password
 * @package   Openswoole-Microservices
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
	 * HTTP object
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
		$oldPasswordHash = $this->http->req->s['userData']['password_hash'];

		if (password_verify(password: $oldPassword, hash: $oldPasswordHash)) {
			$userName = $this->http->req->s['userData']['username'];
			$newPassword = $this->http->req->s['payload']['new_password'];
			$newPasswordHash = password_hash(
				password: $newPassword,
				algo: PASSWORD_DEFAULT
			);

			$sql = "
				UPDATE `{$this->http->req->s['customerData']['userTable']}`
				SET password_hash = :password_hash
				WHERE username = :username AND is_deleted = :is_deleted
			";
			$paramArr = [
				':password_hash' => $newPasswordHash,
				':username' => $userName,
				':is_deleted' => 'No',
			];

			$this->http->req->clientDbObj->execDbQuery(sql: $sql, paramArr: $paramArr);
			$this->http->req->clientDbObj->closeCursor();

			$customerId = $this->http->req->customerId;
			$cacheKey = CacheServerKey::customerUsername(
				customerId: $customerId,
				username: $userName
			);
			Reload::processUser(
				customerData: $this->http->req->s['customerData'],
				userId: $this->http->req->userId
			);
			$this->http->req->clientCacheObj->cacheDelete(
				cacheKey: CacheServerKey::token(token: $this->http->req->s['token'])
			);

			$this->http->res->dataEncode->addKeyData(
				objectKey: 'Results',
				data: 'Password changed successfully. Please login'
			);
		}

		return [true];
	}
}
