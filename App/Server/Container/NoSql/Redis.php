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
		if ($this->cacheServerObject !== Constant::$NULL) {
			return;
		}

		try {
			// https://github.com/phpredis/phpredis?tab=readme-ov-file#class-redis
			$connParamArray = [
				'host' => $this->cacheServerHostname,
				'port' => (int)$this->cacheServerPort,
				'connectTimeout' => 2.5
			];

			if (
				($this->cacheServerUsername !== '')
				&& ($this->cacheServerPassword !== '')
			) {
				$connParamArray['auth'] = [
					$this->cacheServerUsername,
					$this->cacheServerPassword
				];
			}
			$this->cacheServerObject = new \Redis(
				$connParamArray
			);

			if (!empty($this->cacheServerDatabase)) {
				$this->cacheServerObject->select(
					$this->cacheServerDatabase
				);
			}

			if (!$this->cacheServerObject->ping()) {
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
	public function exist(
		$key
	): mixed {
		$this->connect();

		if (empty($key)) {
			return false;
		}

		return $this->cacheServerObject->exists(
			$key
		);
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
			return false;
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
			return false;
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
			return false;
		}

		return $this->cacheServerObject->incrBy(
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
			return false;
		}

		return $this->cacheServerObject->del(
			$key
		);
	}
}
