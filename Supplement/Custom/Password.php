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
use Microservices\App\Constant;
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
	private $httpObject = null;

	/**
	 * Constructor
	 * 
	 * @param Http $httpObject
	 */
	public function __construct(
		Http &$httpObject
	) {
		$this->httpObject = &$httpObject;
	}

	/**
	 * Initialize
	 * 
	 * @return bool
	 */
	public function init(): bool
	{
		$this->httpObject->httpRequestObject->loadPayload();

		return Constant::$TRUE;
	}

	/**
	 * Process
	 * 
	 * @return mixed
	 */
	public function process(): mixed
	{
		$payloadType = $this->httpObject->httpRequestObject->dataDecodeObject->dataType(
			keyString: Constant::$NULL
		);

		switch ($payloadType) {
			case 'Array':
				$payload = $this->httpObject->httpRequestObject->dataDecodeObject->getObject('0');
				break;
			case 'Object':
				$payload = $this->httpObject->httpRequestObject->dataDecodeObject->getObject();
				break;
		}

		$oldPassword = $payload['old_password'];
		$oldPasswordHash = $this->httpObject->httpRequestObject->activeRequestData['userData']['password_hash'];

		if (
			password_verify(
				password: $oldPassword,
				hash: $oldPasswordHash
			)
		) {
			$userName = $this->httpObject->httpRequestObject->activeRequestData['userData']['username'];
			$newPassword = $payload['new_password'];
			$newPasswordHash = password_hash(
				password: $newPassword,
				algo: PASSWORD_DEFAULT
			);

			$sql = "
				UPDATE `{$this->httpObject->httpRequestObject->activeRequestData['customerData']['customer_user_table']}`
				SET password_hash = :password_hash
				WHERE username = :username AND is_deleted = :is_deleted
			";
			$paramArray = [
				':password_hash' => $newPasswordHash,
				':username' => $userName,
				':is_deleted' => Constant::$NO,
			];

			$this->httpObject->httpRequestObject->customerDbObject->execQuery(
				sql: $sql,
				paramArray: $paramArray
			);
			$this->httpObject->httpRequestObject->customerDbObject->closeCursor();

			$customerId = $this->httpObject->httpRequestObject->customerId;
			$cacheKey = CacheServerKey::customerUsername(
				customerId: $customerId,
				username: $userName
			);
			Reload::processUser(
				httpRequestIp: $this->httpObject->httpReqData['server']['httpRequestIp'],
				customerData: $this->httpObject->httpRequestObject->activeRequestData['customerData'],
				customerUserId: $this->httpObject->httpRequestObject->customerUserId
			);
			$this->httpObject->httpRequestObject->customerCacheObject->cacheDelete(
				cacheKey: CacheServerKey::token(
					token: $this->httpObject->httpRequestObject->activeRequestData['authId']
				)
			);

			$this->httpObject->httpResponseObject->dataEncodeObject->addKeyData(
				objectKey: 'Results',
				data: 'Password changed successfully. Please login'
			);
		}

		return Constant::$TRUE;
	}
}
