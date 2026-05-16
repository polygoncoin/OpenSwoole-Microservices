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

use Microservices\App\HttpStatus;
use Microservices\App\Server\Container\NoSql\NoSqlInterface;

/**
 * Redis
 * php version 8.3
 *
 * @category  Redis
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class Redis implements NoSqlInterface
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
	 * Cache Server Username
	 *
	 * @var null|string
	 */
	private $cacheServerUsername = null;

	/**
	 * Cache Server Password
	 *
	 * @var null|string
	 */
	private $cacheServerPassword = null;

	/**
	 * Cache Server DB
	 *
	 * @var null|string
	 */
	private $cacheServerDatabase = null;

	/**
	 * Cache Server Object
	 *
	 * @var null|\Redis
	 */
	private $cacheServerObj = null;

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
		$this->cacheServerUsername = $cacheServerUsername;
		$this->cacheServerPassword = $cacheServerPassword;
		$this->cacheServerDatabase = $cacheServerDatabase;
	}

	/**
	 * Cache Server Object
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function connect(): void
	{
		if ($this->cacheServerObj !== null) {
			return;
		}

		try {
			// https://github.com/phpredis/phpredis?tab=readme-ov-file#class-redis
			$connParamArr = [
				'host' => $this->cacheServerHostname,
				'port' => (int)$this->cacheServerPort,
				'connectTimeout' => 2.5
			];

			if (
				($this->cacheServerUsername !== '')
				&& ($this->cacheServerPassword !== '')
			) {
				$connParamArr['auth'] = [
					$this->cacheServerUsername,
					$this->cacheServerPassword
				];
			}
			$this->cacheServerObj = new \Redis($connParamArr);

			if (!empty($this->cacheServerDatabase)) {
				$this->cacheServerObj->select(
					$this->cacheServerDatabase
				);
			}

			if (!$this->cacheServerObj->ping()) {
				throw new \Exception(
					message: 'Unable to ping cache',
					code: HttpStatus::$InternalServerError
				);
			}
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
	public function exist($key): mixed
	{
		$this->connect();

		return $this->cacheServerObj->exists($key);
	}

	/**
	 * Get cache key
	 *
	 * @param string $key Key
	 *
	 * @return mixed
	 */
	public function get($key): mixed
	{
		$this->connect();

		return $this->cacheServerObj->get($key);
	}

	/**
	 * Set cache key
	 *
	 * @param string $key    Key
	 * @param string $value  Cache value
	 * @param int    $expire Seconds to expire. Default 0 - doesn't expire
	 *
	 * @return mixed
	 */
	public function set($key, $value, $expire = null): mixed
	{
		$this->connect();

		if ($expire === null) {
			return $this->cacheServerObj->set($key, $value);
		} else {
			return $this->cacheServerObj->set($key, $value, $expire);
		}
	}

	/**
	 * Increment cache key with offset
	 *
	 * @param string $key    Key
	 * @param int    $offset Offset
	 *
	 * @return int
	 */
	public function increment($key, $offset = 1): int
	{
		$this->connect();

		return $this->cacheServerObj->incrBy($key, $offset);
	}

	/**
	 * Delete cache key
	 *
	 * @param string $key Key
	 *
	 * @return mixed
	 */
	public function delete($key): mixed
	{
		$this->connect();

		return $this->cacheServerObj->del($key);
	}
}
