<?php

/**
 * Database Common Function
 * php version 8.3
 * 
 * @category  Database Common Function
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App;

use Microservices\App\Constant;
use Microservices\App\DbCommonFunction;
use Microservices\App\Server\QueryCacheServer;

/**
 * Database Common Function
 * php version 8.3
 * 
 * @category  Database Common Function
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class QueryCache
{
	/**
	 * HTTP object
	 * 
	 * @var null|Http
	 */
	private $httpObject = null;

	/**
	 * Query Cache Connection Object
	 * 
	 * @var null|QueryCacheServer
	 */
	private $queryCacheServerObject = null;

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
	 * Connect query Cache
	 * 
	 * @return void
	 */
	public function connectCustomerQueryCache(): void
	{
        if ($this->queryCacheServerObject !== Constant::$NULL) {
            return;
        }

		$customerQueryCacheServerCred = DbCommonFunction::customerQueryCacheServerCred(
			customerData: $this->httpObject->httpRequestObject->activeRequestData['customerData']
		);
		$this->queryCacheServerObject = new QueryCacheServer(
			queryCacheServerType: $customerQueryCacheServerCred['cacheServerType'],
			queryCacheServerHostname: $customerQueryCacheServerCred['cacheServerHostname'],
			queryCacheServerPort: $customerQueryCacheServerCred['cacheServerPort'],
			queryCacheServerUsername: $customerQueryCacheServerCred['cacheServerUsername'],
			queryCacheServerPassword: $customerQueryCacheServerCred['cacheServerPassword'],
			queryCacheServerDatabase: $customerQueryCacheServerCred['cacheServerDatabase'],
			queryCacheServerTable: $customerQueryCacheServerCred['cacheServerTable']
		);
	}

	/**
	 * Prepend Query Cache key
	 * 
	 * @param int    $customerId    Customer Id
	 * @param string $queryCacheKey Query Cache key
	 * 
	 * @return mixed
	 */
	public function queryCachePrepend(
		$customerId,
		$queryCacheKey
	): mixed {
        $this->connectCustomerQueryCache();

		if (
			strlen($customerId) === 0
			|| strlen($queryCacheKey) === 0
		) {
			return Constant::$FALSE;
		}

		return "qc:{$customerId}:{$queryCacheKey}";
	}

	/**
	 * Get Query Cache key
	 * 
	 * @param int    $customerId    Customer Id
	 * @param string $queryCacheKey Query Cache key
	 * 
	 * @return mixed
	 */
	public function queryCacheGet(
		$customerId,
		$queryCacheKey
	): mixed {
        $this->connectCustomerQueryCache();

		if (empty($queryCacheKey)) {
			return Constant::$FALSE;
		}

		$queryCacheKey = $this->queryCachePrepend(
			customerId: $customerId,
			queryCacheKey: $queryCacheKey
		);

		$json = Constant::$NULL;
		if (
			$this->queryCacheServerObject->queryCacheExist(
				queryCacheKey: $queryCacheKey
			)
		) {
			$json = $this->queryCacheServerObject->queryCacheGet(
				queryCacheKey: $queryCacheKey
			);
		}

		return $json;
	}

	/**
	 * Increment Query Cache key counter
	 * 
	 * @param int    $customerId    Customer Id
	 * @param string $queryCacheKey Query Cache key
	 * 
	 * @return mixed
	 */
	public function queryCacheIncrement(
		$customerId,
		$queryCacheKey
	): mixed {
        $this->connectCustomerQueryCache();

		if (empty($queryCacheKey)) {
			return Constant::$FALSE;
		}

		$queryCacheKey = 'i:' . $queryCacheKey;
		$queryCacheKey = $this->queryCachePrepend(
			customerId: $customerId,
			queryCacheKey: $queryCacheKey
		);

		return $this->queryCacheServerObject->queryCacheIncrement(
			queryCacheKey: $queryCacheKey
		);
	}

	/**
	 * Set Query Cache key
	 * 
	 * @param int    $customerId      Customer Id
	 * @param string $queryCacheKey   Query Cache key
	 * @param mixed  $queryCacheValue Query Cache value
	 * 
	 * @return mixed
	 */
	public function queryCacheSet(
		$customerId,
		$queryCacheKey,
		&$queryCacheValue
	): mixed {
        $this->connectCustomerQueryCache();

		if (empty($queryCacheKey)) {
			return Constant::$FALSE;
		}

		$delQueryCacheKey = 'i:' . $queryCacheKey;

		$queryCacheKey = $this->queryCachePrepend(
			customerId: $customerId,
			queryCacheKey: $queryCacheKey
		);

		$delQueryCacheKey = $this->queryCachePrepend(
			customerId: $customerId,
			queryCacheKey: $delQueryCacheKey
		);

		$this->queryCacheServerObject->queryCacheDelete(
			queryCacheKey: $delQueryCacheKey
		);
		return $this->queryCacheServerObject->queryCacheSet(
			queryCacheKey: $queryCacheKey,
			queryCacheValue: $queryCacheValue
		);
	}

	/**
	 * Delete Query Cache key
	 * 
	 * @param int    $customerId    Customer Id
	 * @param string $queryCacheKey Query Cache key
	 * 
	 * @return mixed
	 */
	public function queryCacheDelete(
		$customerId,
		$queryCacheKey
	): mixed {
        $this->connectCustomerQueryCache();

		if (empty($queryCacheKey)) {
			return Constant::$FALSE;
		}

		$queryCacheKey = $this->queryCachePrepend(
			customerId: $customerId,
			queryCacheKey: $queryCacheKey
		);

		return $this->queryCacheServerObject->queryCacheDelete(
			queryCacheKey: $queryCacheKey
		);
	}
}
