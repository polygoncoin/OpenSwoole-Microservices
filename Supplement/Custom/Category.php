<?php

/**
 * CustomAPI
 * php version 8.3
 *
 * @category  CustomAPI
 * @package   Openswoole_Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\Supplement\Custom;

use Microservices\App\Http;
use Microservices\App\DbCommonFunction;
use Microservices\Supplement\Custom\CustomInterface;
use Microservices\Supplement\Custom\CustomTrait;

/**
 * CustomAPI Category
 * php version 8.3
 *
 * @category  CustomAPI_Category
 * @package   Openswoole_Microservices
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
		DbCommonFunction::setDbConnection($this->http->req, fetchFrom: 'Slave');
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
	 * @param array $payload Payload
	 *
	 * @return array
	 */
	public function process(array $payload = []): array
	{
		$sql = '
			SELECT *
			FROM category
			WHERE is_deleted = :is_deleted AND parent_id = :parent_id
		';
		$sqlParams = [
			':is_deleted' => 'No',
			':parent_id' => 0,
		];
		DbCommonFunction::$slaveDb[$this->http->req->cId]->execDbQuery(sql: $sql, params: $sqlParams);
		$rows = DbCommonFunction::$slaveDb[$this->http->req->cId]->fetchAll();
		DbCommonFunction::$slaveDb[$this->http->req->cId]->closeCursor();
		$this->http->res->dataEncode->addKeyData(key: 'Results', data: $rows);
		return [true];
	}
}
