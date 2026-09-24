<?php

/**
 * Query Cache
 * php version 8.3
 *
 * @category  Server
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App\Server;

use Microservices\App\Constant;
use Microservices\App\HttpStatus;
use Microservices\App\Server\QueryCacheServer\QueryCacheServerInterface;

/**
 * Query Cache Server
 * php version 8.3
 *
 * @category  Query Cache Server
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class QueryCacheServer
{
	/**
	 * Query Cache Server Type
	 *
	 * @var null|string
	 */
	private $queryCacheServerMode = null;

	/**
	 * Query Cache Server Hostname
	 *
	 * @var null|string
	 */
	private $queryCacheServerHost = null;

	/**
	 * Query Cache Server Port
	 *
	 * @var null|int
	 */
	private $queryCacheServerPort = null;

	/**
	 * Query Cache Server Username
	 *
	 * @var null|string
	 */
	private $queryCacheServerUser = null;

	/**
	 * Query Cache Server Password
	 *
	 * @var null|string
	 */
	private $queryCacheServerPassword = null;

	/**
	 * Query Cache Server DB
	 *
	 * @var null|string
	 */
	private $queryCacheServerDb = null;

	/**
	 * Cache collection
	 *
	 * @var null|string
	 */
	public $queryCacheServerTable = null;

	/**
	 * Query Cache Server Object
	 *
	 * @var null|QueryCacheServerInterface
	 */
	private $queryCacheServerObject = null;

	/**
	 * Constructor
	 *
	 * @param string      $queryCacheServerMode     Query Cache Server Type
	 * @param string      $queryCacheServerHost Query Cache Server Hostname
	 * @param int         $queryCacheServerPort     Query Cache Server Port
	 * @param string      $queryCacheServerUser Query Cache Server Username
	 * @param string      $queryCacheServerPassword Query Cache Server Password
	 * @param null|string $queryCacheServerDb Query Cache Server Database
	 * @param null|string $queryCacheServerTable    Query Cache Server Table
	 */
	public function __construct(
        $queryCacheServerMode,
		$queryCacheServerHost,
		$queryCacheServerPort,
		$queryCacheServerUser,
		$queryCacheServerPassword,
		$queryCacheServerDb,
		$queryCacheServerTable
	) {
		$this->queryCacheServerMode = $queryCacheServerMode;
		$this->queryCacheServerHost = $queryCacheServerHost;
		$this->queryCacheServerPort = $queryCacheServerPort;
		$this->queryCacheServerUser = $queryCacheServerUser;
		$this->queryCacheServerPassword = $queryCacheServerPassword;
		$this->queryCacheServerDb = $queryCacheServerDb;
		$this->queryCacheServerTable = $queryCacheServerTable;
	}

	/**
	 * Connect Query Cache
	 *
	 * @return void
	 */
	public function connectQueryCache(): void
	{
		if ($this->queryCacheServerObject !== Constant::$NULL) {
			return;
		}

		if (
            !in_array(
                needle: $this->queryCacheServerMode,
				haystack: [
                    'Redis',
                    'Memcached',
                    'MongoDb'
                ],
				strict: Constant::$TRUE
            )
        ) {
			throw new \Exception(
				message: 'Invalid Query Cache mode: ' . $this->queryCacheServerMode,
				code: HttpStatus::$InternalServerError
			);
		}

		$queryCacheServerNS = 'Microservices\\App\\Server\\QueryCacheServer\\'
            . $this->queryCacheServerMode . 'QueryCache';

		$this->queryCacheServerObject = new $queryCacheServerNS(
			queryCacheServerHost: $this->queryCacheServerHost,
			queryCacheServerPort: $this->queryCacheServerPort,
			queryCacheServerUser: $this->queryCacheServerUser,
			queryCacheServerPassword: $this->queryCacheServerPassword,
			queryCacheServerDb: $this->queryCacheServerDb,
			queryCacheServerTable: $this->queryCacheServerTable
		);
	}

	/**
	 * Query Cache key exist
	 *
	 * @param string $queryCacheKey Query Cache key
	 *
	 * @return mixed
	 */
	public function queryCacheExist(
		$queryCacheKey
	): mixed {
		$this->connectQueryCache();

		if (empty($queryCacheKey)) {
			return Constant::$FALSE;
		}

		return $this->queryCacheServerObject->queryCacheExist(
			queryCacheKey: $queryCacheKey
		);
	}

	/**
	 * Get Query Cache key
	 *
	 * @param string $queryCacheKey Query Cache key
	 *
	 * @return mixed
	 */
	public function queryCacheGet(
		$queryCacheKey
	): mixed {
		$this->connectQueryCache();

		if (empty($queryCacheKey)) {
			return Constant::$FALSE;
		}

		return $this->queryCacheServerObject->queryCacheGet(
			queryCacheKey: $queryCacheKey
		);
	}

	/**
	 * Set cache key
	 *
	 * @param string $queryCacheKey   Query Cache key
	 * @param mixed  $queryCacheValue Query Cache value
	 *
	 * @return mixed
	 */
	public function queryCacheSet(
		$queryCacheKey,
		$queryCacheValue
	): mixed {
		$this->connectQueryCache();

		if (empty($queryCacheKey)) {
			return Constant::$FALSE;
		}

		return $this->queryCacheServerObject->queryCacheSet(
			queryCacheKey: $queryCacheKey,
			queryCacheValue:  $queryCacheValue
		);
	}

	/**
	 * Increment Query Cache key as per offset
	 *
	 * @param string $queryCacheKey    Query Cache key
	 * @param int    $queryCacheOffset Query Cache offset
	 *
	 * @return mixed
	 */
	public function queryCacheIncrement(
		$queryCacheKey,
		$queryCacheOffset = 1
	): mixed {
		$this->connectQueryCache();

		if (empty($queryCacheKey)) {
			return Constant::$FALSE;
		}

		return $this->queryCacheServerObject->queryCacheIncrement(
			queryCacheKey: $queryCacheKey,
			queryCacheOffset: $queryCacheOffset
		);
	}

	/**
	 * Delete Query Cache key
	 *
	 * @param string $queryCacheKey Query Cache key
	 *
	 * @return mixed
	 */
	public function queryCacheDelete(
		$queryCacheKey
	): mixed {
		$this->connectQueryCache();

		if (empty($queryCacheKey)) {
			return Constant::$FALSE;
		}

		return $this->queryCacheServerObject->queryCacheDelete(
			queryCacheKey: $queryCacheKey
		);
	}
}
