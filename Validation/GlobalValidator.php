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
use Microservices\App\Http;
use Microservices\Validation\ValidatorInterface;
use Microservices\Validation\ValidatorTrait;

/**
 * Validator Global
 * php version 8.3
 *
 * @category  Validator_Global
 * @package   Openswoole_Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class GlobalValidator implements ValidatorInterface
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
	 * Check primary key exist
	 *
	 * @param array $argArr Arguments
	 *
	 * @return int 0/1
	 */
	private function primaryKeyExist(&$argArr): int
	{
		extract(array: $argArr);
		$sql = "SELECT count(1) as `count` FROM `{$table}` WHERE `{$primary}` = ?";
		$paramArr = [$id];
		$this->http->req->clientDbObj->execDbQuery(sql: $sql, paramArr: $paramArr);
		$row = $this->http->req->clientDbObj->fetch();
		$this->http->req->clientDbObj->closeCursor();
		return (int)(($row['count'] === 0) ? false : true);
	}

	/**
	 * Check column value exist
	 *
	 * @param array $argArr Arguments
	 *
	 * @return bool
	 */
	private function checkColumnValueExist(&$argArr): bool
	{
		extract(array: $argArr);
		$sql = "
			SELECT count(1) as `count`
			FROM `{$table}`
			WHERE `{$column}` = ? AND`{$primary}` = ?
		";
		$paramArr = [$columnValue, $id];
		$this->http->req->clientDbObj->execDbQuery(sql: $sql, paramArr: $paramArr);
		$row = $this->http->req->clientDbObj->fetch();
		$this->http->req->clientDbObj->closeCursor();
		return ($row['count'] === 0) ? false : true;
	}
}
