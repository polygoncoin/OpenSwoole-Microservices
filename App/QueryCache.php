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
	private $http = null;

	/**
	 * Query Cache Connection Object
	 *
	 * @var null|QueryCacheServer
	 */
	private $customerQueryCacheServer = null;

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
	 * Connect query Cache
	 *
	 * @return void
	 */
	public function connectCustomerQueryCache(): void
	{
        if ($this->customerQueryCacheServer !== null) {
            return;
        }

		$customerQueryCacheServerCred = DbCommonFunction::customerQueryCacheServerCred(customerData: $this->http->req->s['customerData']);
		$this->customerQueryCacheServer = new QueryCacheServer(
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
	public function queryCachePrepend($customerId, $queryCacheKey): mixed
	{
        $this->connectCustomerQueryCache();

		if (
			strlen($customerId) === 0
			|| strlen($queryCacheKey) === 0
		) {
			return false;
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
	public function queryCacheGet($customerId, $queryCacheKey): mixed
	{
        $this->connectCustomerQueryCache();
        
		if (strlen($queryCacheKey) === 0) {
			return false;
		}

		$queryCacheKey = $this->queryCachePrepend(
			customerId: $customerId,
			queryCacheKey: $queryCacheKey
		);

		$json = null;
		if ($this->customerQueryCacheServer->queryCacheExist(queryCacheKey: $queryCacheKey)) {
			$json = $this->customerQueryCacheServer->queryCacheGet(queryCacheKey: $queryCacheKey);
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
	public function queryCacheIncrement($customerId, $queryCacheKey): mixed
	{
        $this->connectCustomerQueryCache();
        
		if (strlen($queryCacheKey) === 0) {
			return false;
		}

		$queryCacheKey = 'i:' . $queryCacheKey;
		$queryCacheKey = $this->queryCachePrepend(
			customerId: $customerId,
			queryCacheKey: $queryCacheKey
		);

		return $this->customerQueryCacheServer->queryCacheIncrement(queryCacheKey: $queryCacheKey);
	}

	/**
	 * Set Query Cache key
	 *
	 * @param int    $customerId      Customer Id
	 * @param string $queryCacheKey   Query Cache key
	 * @param string $queryCacheValue Query Cache value
	 *
	 * @return mixed
	 */
	public function queryCacheSet($customerId, $queryCacheKey, &$queryCacheValue): mixed
	{
        $this->connectCustomerQueryCache();
        
		if (strlen($queryCacheKey) === 0) {
			return false;
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

		$this->customerQueryCacheServer->queryCacheDelete(queryCacheKey: $delQueryCacheKey);
		return $this->customerQueryCacheServer->queryCacheSet(queryCacheKey: $queryCacheKey, queryCacheValue: $queryCacheValue);
	}

	/**
	 * Delete Query Cache key
	 *
	 * @param int    $customerId    Customer Id
	 * @param string $queryCacheKey Query Cache key
	 *
	 * @return mixed
	 */
	public function queryCacheDelete($customerId, $queryCacheKey): mixed
	{
        $this->connectCustomerQueryCache();
        
		if (strlen($queryCacheKey) === 0) {
			return false;
		}

		$queryCacheKey = $this->queryCachePrepend(
			customerId: $customerId,
			queryCacheKey: $queryCacheKey
		);

		return $this->customerQueryCacheServer->queryCacheDelete(queryCacheKey: $queryCacheKey);
	}
}
