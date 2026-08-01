<?php

/**
 * CustomAPI
 * php version 8.3
 * 
 * @category  CustomAPI
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\Supplement\Custom;

use Microservices\App\Constant;
use Microservices\App\DbCommonFunction;
use Microservices\App\Http;
use Microservices\Supplement\Custom\CustomInterface;
use Microservices\Supplement\Custom\CustomTrait;

/**
 * CustomAPI Category
 * php version 8.3
 * 
 * @category  CustomAPI_Category
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class Category implements CustomInterface
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
		$this->httpObject->httpRequestObject->customerDbObject = DbCommonFunction::connectCustomerDb(
			customerData: $this->httpObject->httpRequestObject->activeRequestData['customerData'],
			fetchDbMode: 'Slave'
		);
	}

	/**
	 * Initialize
	 * 
	 * @return bool
	 */
	public function init(): bool
	{
		return true;
	}

	/**
	 * Process
	 * 
	 * @return mixed
	 */
	public function process(): mixed
	{
		$sql = '
			SELECT * 
			FROM category
			WHERE is_deleted = :is_deleted AND parent_id = :parent_id
		';
		$paramArray = [
			':is_deleted' => Constant::$NO,
			':parent_id' => 0,
		];
		$this->httpObject->httpRequestObject->customerDbObject->execQuery(
			sql: $sql,
			paramArray: $paramArray
		);
		$rowArray = $this->httpObject->httpRequestObject->customerDbObject->fetchAll();
		$this->httpObject->httpRequestObject->customerDbObject->closeCursor();
		$this->httpObject->httpResponseObject->dataEncodeObject->addKeyData(
			objectKey: 'Results',
			data: $rowArray
		);

		return true;
	}
}
