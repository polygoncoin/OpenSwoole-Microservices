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
 * Customer Validator
 * php version 8.3
 * 
 * @category  Validator_Customer
 * @package   Openswoole-Microservices
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
	 * Get primary key count
	 * 
	 * @param string $table   Table Name
	 * @param string $primary Primary Key
	 * @param int    $id      Primary id
	 * 
	 * @return int 0/1
	 */
	private function getPrimaryCount(
		&$table,
		$primary,
		&$id
	): int {
		$dbServerDatabase = $this->httpObject->httpRequestObject->customerDbObject->dbServerDatabase;
		$sql = "
			SELECT count(1) as `count`
			FROM `{$dbServerDatabase}`.`{$table}`
			WHERE `{$primary}` = ?
		";
		$paramArray = [$id];
		$this->httpObject->httpRequestObject->customerDbObject->execQuery(
			sql: $sql,
			paramArray: $paramArray
		);
		return (int)($this->httpObject->httpRequestObject->customerDbObject->fetch())['count'];
	}

	/**
	 * Check primary key exist
	 * 
	 * @param array $argArray Arguments
	 * 
	 * @return bool
	 */
	private function primaryKeyExist(
		&$argArray
	): bool {
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
		return (isset($record['count']) && $record['count'] === 0) ? Constant::$FALSE : Constant::$TRUE;
	}
}
