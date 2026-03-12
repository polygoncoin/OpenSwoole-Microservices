<?php

/**
 * NoSql Database
 * php version 8.3
 *
 * @category  NoSql
 * @package   Sahar.Guru
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/sahar.guru
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
 * @package   Sahar.Guru
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/sahar.guru
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
	private $cacheServerDB = null;

	/**
	 * Cache Server Object
	 *
	 * @var null|\Redis
	 */
	private $cacheServerObj = null;

	/**
	 * Constructor
	 *
	 * @param string $cacheServerHostname Hostname
	 * @param string $cacheServerPort     Port
	 * @param string $cacheServerUsername Username
	 * @param string $cacheServerPassword Password
	 * @param string $cacheServerDB       Database
	 * @param string $cacheServerTable    Table
	 */
	public function __construct(
		$cacheServerHostname,
		$cacheServerPort,
		$cacheServerUsername,
		$cacheServerPassword,
		$cacheServerDB,
		$cacheServerTable
	) {
		$this->cacheServerHostname = $cacheServerHostname;
		$this->cacheServerPort = $cacheServerPort;
		$this->cacheServerUsername = $cacheServerUsername;
		$this->cacheServerPassword = $cacheServerPassword;
		$this->cacheServerDB = $cacheServerDB;
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
			$connParams = [
				'host' => $this->cacheServerHostname,
				'port' => (int)$this->cacheServerPort,
				'connectTimeout' => 2.5
			];

			if (
				($this->cacheServerUsername !== '')
				&& ($this->cacheServerPassword !== '')
			) {
				$connParams['auth'] = [
					$this->cacheServerUsername,
					$this->cacheServerPassword
				];
			}
			$this->cacheServerObj = new \Redis($connParams);

			if (!empty($this->cacheServerDB)) {
				$this->cacheServerObj->select($this->cacheServerDB);
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
	 * Checks if cache key exist
	 *
	 * @param string $key Cache key
	 *
	 * @return mixed
	 */
	public function cacheExists($key): mixed
	{
		$this->connect();

		return $this->cacheServerObj->exists($key);
	}

	/**
	 * Get cache on basis of key
	 *
	 * @param string $key Cache key
	 *
	 * @return mixed
	 */
	public function getCache($key): mixed
	{
		$this->connect();

		return $this->cacheServerObj->get($key);
	}

	/**
	 * Set cache on basis of key
	 *
	 * @param string $key    Cache key
	 * @param string $value  Cache value
	 * @param int    $expire Seconds to expire. Default 0 - doesn't expire
	 *
	 * @return mixed
	 */
	public function setCache($key, $value, $expire = null): mixed
	{
		$this->connect();

		if ($expire === null) {
			return $this->cacheServerObj->set($key, $value);
		} else {
			return $this->cacheServerObj->set($key, $value, $expire);
		}
	}

	/**
	 * Increment Key value with offset
	 *
	 * @param string $key    Cache key
	 * @param int    $offset Offset
	 *
	 * @return int
	 */
	public function incrementCache($key, $offset = 1): int
	{
		$this->connect();

		return $this->cacheServerObj->incrBy($key, $offset);
	}

	/**
	 * Delete basis of key
	 *
	 * @param string $key Cache key
	 *
	 * @return mixed
	 */
	public function deleteCache($key): mixed
	{
		$this->connect();

		return $this->cacheServerObj->del($key);
	}
}
