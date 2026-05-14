<?php

/**
 * Validator
 * php version 8.3
 *
 * @category  Validator
 * @package   Openswoole_Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\Validation;

use Microservices\App\DbCommonFunction;
use Microservices\App\Env;
use Microservices\App\Http;
use Microservices\Validation\ValidatorInterface;
use Microservices\Validation\ValidatorTrait;

/**
 * Customer Validator
 * php version 8.3
 *
 * @category  Validator_Customer
 * @package   Openswoole_Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class CustomerValidator implements ValidatorInterface
{
	use ValidatorTrait;

	/**
	 * HTTP object
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
	}

	/**
	 * Validate payload
	 *
	 * @param array $validationConfig Validation configuration
	 *
	 * @return array
	 */
	public function validate(&$validationConfig): array
	{
		$isValidData = true;
		$errorArr = [];
		foreach ($validationConfig as &$v) {
			$argArr = [];
			foreach ($v['functionArgs'] as $argName => [$fetchFrom, $fetchFromData]) {
				if ($fetchFrom === 'custom') {
					$argArr[$argName] = $fetchFromData;
				} else {
					$argArr[$argName] = $this->http->req->s[$fetchFrom][$fetchFromData];
				}
			}
			$function = $v['function'];
			if (!$this->$function($argArr)) {
				$errorArr[] = $v['errorMessage'];
				$isValidData = false;
			}
		}

		return [$isValidData, $errorArr];
	}

	/**
	 * Get primary key count
	 *
	 * @param string $table   Table Name
	 * @param string $primary Primary Key
	 * @param int    $id      Primary id
	 *
	 * @return int 0/1
	 */
	private function getPrimaryCount(&$table, $primary, &$id): int
	{
		$dbServerDatabase = $this->http->req->clientDbObj->dbServerDatabase;
		$sql = "
			SELECT count(1) as `count`
			FROM `{$dbServerDatabase}`.`{$table}`
			WHERE `{$primary}` = ?
		";
		$paramArr = [$id];
		$this->http->req->clientDbObj->execDbQuery(sql: $sql, paramArr: $paramArr);
		return (int)($this->http->req->clientDbObj->fetch())['count'];
	}

	/**
	 * Check primary key exist
	 *
	 * @param array $argArr Arguments
	 *
	 * @return bool
	 */
	private function primaryKeyExist(&$argArr): bool
	{
		extract(array: $argArr);
		$sql = "SELECT count(1) as `count` FROM `{$table}` WHERE `{$primary}` = ?";
		$paramArr = [$id];
		$this->http->req->clientDbObj->execDbQuery(sql: $sql, paramArr: $paramArr);
		$row = $this->http->req->clientDbObj->fetch();
		$this->http->req->clientDbObj->closeCursor();
		return ($row['count'] === 0) ? false : true;
	}
}
