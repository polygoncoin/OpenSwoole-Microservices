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
	private $queryCache = null;

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
	public function connectQueryCache(): void
	{
        if ($this->queryCache !== Constant::$NULL) {
            return;
        }

		$queryCacheServerCred = DbCommonFunction::getQueryCacheCred(
			customerId: $this->httpObject->httpReqData['active']['customerId']
		);
		$this->queryCache = new QueryCacheServer(
			queryCacheServerMode: $queryCacheServerCred['cacheServerType'],
			queryCacheServerHost: $queryCacheServerCred['cacheServerHost'],
			queryCacheServerPort: $queryCacheServerCred['cacheServerPort'],
			queryCacheServerUser: $queryCacheServerCred['cacheServerUser'],
			queryCacheServerPassword: $queryCacheServerCred['cacheServerPassword'],
			queryCacheServerDb: $queryCacheServerCred['cacheServerDb'],
			queryCacheServerTable: $queryCacheServerCred['cacheServerTable']
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
        $this->connectQueryCache();

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
        $this->connectQueryCache();

		if (empty($queryCacheKey)) {
			return Constant::$FALSE;
		}

		$queryCacheKey = $this->queryCachePrepend(
			customerId: $this->httpObject->httpReqData['active']['customerId'],
			queryCacheKey: $queryCacheKey
		);

		$json = Constant::$NULL;
		if (
			$this->queryCache->queryCacheExist(
				queryCacheKey: $queryCacheKey
			)
		) {
			$json = $this->queryCache->queryCacheGet(
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
        $this->connectQueryCache();

		if (empty($queryCacheKey)) {
			return Constant::$FALSE;
		}

		$queryCacheKey = 'i:' . $queryCacheKey;
		$queryCacheKey = $this->queryCachePrepend(
			customerId: $this->httpObject->httpReqData['active']['customerId'],
			queryCacheKey: $queryCacheKey
		);

		return $this->queryCache->queryCacheIncrement(
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
        $this->connectQueryCache();

		if (empty($queryCacheKey)) {
			return Constant::$FALSE;
		}

		$delQueryCacheKey = 'i:' . $queryCacheKey;

		$queryCacheKey = $this->queryCachePrepend(
			customerId: $this->httpObject->httpReqData['active']['customerId'],
			queryCacheKey: $queryCacheKey
		);

		$delQueryCacheKey = $this->queryCachePrepend(
			customerId: $this->httpObject->httpReqData['active']['customerId'],
			queryCacheKey: $delQueryCacheKey
		);

		$this->queryCache->queryCacheDelete(
			queryCacheKey: $delQueryCacheKey
		);
		return $this->queryCache->queryCacheSet(
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
        $this->connectQueryCache();

		if (empty($queryCacheKey)) {
			return Constant::$FALSE;
		}

		$queryCacheKey = $this->queryCachePrepend(
			customerId: $this->httpObject->httpReqData['active']['customerId'],
			queryCacheKey: $queryCacheKey
		);

		return $this->queryCache->queryCacheDelete(
			queryCacheKey: $queryCacheKey
		);
	}
}
