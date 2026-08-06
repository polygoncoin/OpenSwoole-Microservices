<?php

/**
 * NoSql Database
 * php version 8.3
 * 
 * @category  NoSql
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App\Server\Container\NoSql;

use Microservices\App\CommonFunction;
use Microservices\App\Constant;
use Microservices\App\HttpStatus;
use Microservices\App\Server\Container\NoSql\NoSqlInterface;

/**
 * Memcached
 * php version 8.3
 * 
 * @category  Memcached
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class Memcached implements NoSqlInterface
{
	/**
	 * Cache Server Hostname
	 * 
	 * @var null|string
	 */
	private $cacheServerHostname = null;

	/**
	 * Cache Server Port
	 * 
	 * @var null|int
	 */
	private $cacheServerPort = null;

	/**
	 * Cache Server Object
	 * 
	 * @var null|\Memcached
	 */
	private $cacheServerObject = null;

	/**
	 * Constructor
	 * 
	 * @param string      $cacheServerHostname Cache Server Hostname
	 * @param int         $cacheServerPort     Cache Server Port
	 * @param string      $cacheServerUsername Cache Server Username
	 * @param string      $cacheServerPassword Cache Server Password
	 * @param null|string $cacheServerDatabase Cache Server Database
	 * @param null|string $cacheServerTable    Cache Server Table
	 */
	public function __construct(
		$cacheServerHostname,
		$cacheServerPort,
		$cacheServerUsername,
		$cacheServerPassword,
		$cacheServerDatabase,
		$cacheServerTable
	) {
		$this->cacheServerHostname = $cacheServerHostname;
		$this->cacheServerPort = $cacheServerPort;
	}

	/**
	 * Cache Server Object
	 * 
	 * @return void
	 * @throws \Exception
	 */
	public function connect(): void
	{
		if ($this->cacheServerObject !== Constant::$NULL) {
			return;
		}

		if (
			!extension_loaded(
				extension: 'memcached'
			)
		) {
			throw new \Exception(
				message: 'Unable to find Memcached extension',
				code: HttpStatus::$InternalServerError
			);
		}

		try {
			$this->cacheServerObject = new \Memcached();
			$this->cacheServerObject->addServer(
				$this->cacheServerHostname,
				$this->cacheServerPort
			);
		} catch (\Exception $e) {
			throw new \Exception(
				message: $e->getMessage(),
				code: HttpStatus::$InternalServerError
			);
		}
	}

	/**
	 * Cache key exist
	 * 
	 * @param string $key Key
	 * 
	 * @return mixed
	 */
	public function exist(
		$key
	): mixed {
		$this->connect();

		if (empty($key)) {
			return Constant::$FALSE;
		}

		return $this->get(
			$key
		) !== Constant::$FALSE;
	}

	/**
	 * Get cache key
	 * 
	 * @param string $key Key
	 * 
	 * @return mixed
	 */
	public function get(
		$key
	): mixed {
		$this->connect();

		if (empty($key)) {
			return Constant::$FALSE;
		}

		$return = CommonFunction::jsonDecode(
			value: $this->cacheServerObject->get(
				$key
			)
		);

		return $return;
	}

	/**
	 * Set cache key
	 * 
	 * @param string $key    Key
	 * @param mixed  $value  Cache value
	 * @param int    $expire Seconds to expire. Default 0 - doesn't expire
	 * 
	 * @return mixed
	 */
	public function set(
		$key,
		$value,
		$expire = null
	): mixed {
		$this->connect();

		if (empty($key)) {
			return Constant::$FALSE;
		}

		$value = json_encode(
			value: $value
		);

		if ($expire === Constant::$NULL) {
			return $this->cacheServerObject->set(
				$key,
				$value
			);
		} else {
			return $this->cacheServerObject->set(
				$key,
				$value,
				$expire
			);
		}
	}

	/**
	 * Increment cache key with offset
	 * 
	 * @param string $key    Key
	 * @param int    $offset Offset
	 * 
	 * @return mixed
	 */
	public function increment(
		$key,
		$offset = 1
	): mixed {
		$this->connect();

		if (empty($key)) {
			return Constant::$FALSE;
		}

		return $this->cacheServerObject->increment(
			$key,
			$offset
		);
	}

	/**
	 * Delete cache key
	 * 
	 * @param string $key Key
	 * 
	 * @return mixed
	 */
	public function delete(
		$key
	): mixed {
		$this->connect();

		if (empty($key)) {
			return Constant::$FALSE;
		}

		return $this->cacheServerObject->delete(
			$key
		);
	}
}
