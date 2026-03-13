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

use Microservices\App\Http;
use Microservices\App\DbCommonFunction;
use Microservices\App\Env;
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
	 * Http Object
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
		$errors = [];
		foreach ($validationConfig as &$v) {
			$args = [];
			foreach ($v['fnArgs'] as $attr => [$mode, $key]) {
				if ($mode === 'custom') {
					$args[$attr] = $key;
				} else {
					$args[$attr] = $this->http->req->s[$mode][$key];
				}
			}
			$fn = $v['fn'];
			if (!$this->$fn($args)) {
				$errors[] = $v['errorMessage'];
				$isValidData = false;
			}
		}

		return [$isValidData, $errors];
	}

	/**
	 * Customer Id Exist
	 *
	 * @param array $args Arguments
	 *
	 * @return int 0/1
	 */
	public function cIdExist(&$args): int
	{
		extract(array: $args);
		return $this->getPrimaryCount(
			table: Env::$customerTable,
			primary: 'id',
			id: $id
		);
	}

	/**
	 * Gets primary key count
	 *
	 * @param string $table   Table Name
	 * @param string $primary Primary Key
	 * @param int    $id      Primary ID
	 *
	 * @return int 0/1
	 */
	private function getPrimaryCount(&$table, $primary, &$id): int
	{
		$dbServerDB = DbCommonFunction::$masterDb[$this->http->req->cId]->dbServerDB;
		$sql = "
			SELECT count(1) as `count`
			FROM `{$dbServerDB}`.`{$table}`
			WHERE `{$primary}` = ?
		";
		$params = [$id];
		DbCommonFunction::$masterDb[$this->http->req->cId]->execDbQuery(sql: $sql, params: $params);
		return (int)(DbCommonFunction::$masterDb[$this->http->req->cId]->fetch())['count'];
	}

	/**
	 * Checks primary key exist
	 *
	 * @param array $args Arguments
	 *
	 * @return bool
	 */
	private function primaryKeyExist(&$args): bool
	{
		extract(array: $args);
		$sql = "SELECT count(1) as `count` FROM `{$table}` WHERE `{$primary}` = ?";
		$params = [$id];
		DbCommonFunction::$masterDb[$this->http->req->cId]->execDbQuery(sql: $sql, params: $params);
		$row = DbCommonFunction::$masterDb[$this->http->req->cId]->fetch();
		DbCommonFunction::$masterDb[$this->http->req->cId]->closeCursor();
		return ($row['count'] === 0) ? false : true;
	}
}
