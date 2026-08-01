<?php

/**
 * Validator
 * php version 8.3
 * 
 * @category  Validator
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\Validation;

use Microservices\App\Constant;
use Microservices\App\Http;
use Microservices\Validation\ValidatorInterface;
use Microservices\Validation\ValidatorTrait;

/**
 * Validator Global
 * php version 8.3
 * 
 * @category  Validator_Global
 * @package   Openswoole-Microservices
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
	private $httpObject = null;

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
	 * Validate payload
	 * 
	 * @param array $validationConfig Validation configuration
	 * 
	 * @return array
	 */
	public function validate(
		&$validationConfig
	): array {
		$isValidData = true;
		$errorArray = [];
		foreach ($validationConfig as &$v) {
			$argArray = [];
			foreach ($v['functionArgs'] as $argName => [$activeRequestDataKey, $activeRequestDataKeySubKey]) {
				if ($activeRequestDataKey === 'custom') {
					$argArray[$argName] = $activeRequestDataKeySubKey;
				} else {
					$argArray[$argName] = $this->httpObject->httpRequestObject->activeRequestData[$activeRequestDataKey][$activeRequestDataKeySubKey];
				}
			}
			$function = $v['function'];
			if (!$this->$function($argArray)) {
				$errorArray[] = $v['errorMessage'];
				$isValidData = false;
			}
		}
		return [$isValidData, $errorArray];
	}

	/**
	 * Check primary key exist
	 * 
	 * @param array $argArray Arguments
	 * 
	 * @return int 0/1
	 */
	private function primaryKeyExist(
		&$argArray
	): int {
		extract(
			array: $argArray
		);
		$sql = "SELECT count(1) as `count` FROM `{$table}` WHERE `{$primary}` = ?";
		$paramArray = [$id];
		$this->httpObject->httpRequestObject->customerDbObject->execQuery(
			sql: $sql,
			paramArray: $paramArray
		);
		$record = $this->httpObject->httpRequestObject->customerDbObject->fetch();
		$this->httpObject->httpRequestObject->customerDbObject->closeCursor();
		return (int)((isset($record['count']) && $record['count'] === 0) ? Constant::$FALSE : Constant::$TRUE);
	}

	/**
	 * Check column value exist
	 * 
	 * @param array $argArray Arguments
	 * 
	 * @return bool
	 */
	private function checkColumnValueExist(
		&$argArray
	): bool {
		extract(
			array: $argArray
		);
		$sql = "
			SELECT count(1) as `count`
			FROM `{$table}`
			WHERE `{$column}` = ? AND`{$primary}` = ?
		";
		$paramArray = [
			$columnValue,
			$id
		];
		$this->httpObject->httpRequestObject->customerDbObject->execQuery(
			sql: $sql,
			paramArray: $paramArray
		);
		$record = $this->httpObject->httpRequestObject->customerDbObject->fetch();
		$this->httpObject->httpRequestObject->customerDbObject->closeCursor();
		return ($record['count'] === 0) ? Constant::$FALSE : Constant::$TRUE;
	}
}
