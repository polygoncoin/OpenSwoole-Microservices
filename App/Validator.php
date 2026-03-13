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

namespace Microservices\App;

use Microservices\App\Http;
use Microservices\App\DbCommonFunction;
use Microservices\App\Env;
use Microservices\Validation\CustomerValidator;
use Microservices\Validation\GlobalValidator;
use Microservices\Validation\ValidatorInterface;

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
class Validator
{
	/**
	 * Validator object
	 *
	 * @var null|ValidatorInterface
	 */
	private $v = null;

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
		if (DbCommonFunction::$masterDb[$this->http->req->cId]->dbServerDB === Env::$gDbServerDB) {
			$this->v = new GlobalValidator($this->http);
		} else {
			$this->v = new CustomerValidator($this->http);
		}
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
		if (
			isset(($this->http->req->s['necessary']))
			&& count(value: $this->http->req->s['necessary']) > 0
		) {
			if (
				([$isValidData, $errors] = $this->validateRequired())
				&& !$isValidData
			) {
				return [$isValidData, $errors];
			}
		}

		return $this->v->validate(validationConfig: $validationConfig);
	}

	/**
	 * Validate necessary payload
	 *
	 * @return array
	 */
	private function validateRequired(): array
	{
		$isValidData = true;
		$errors = [];
		// Required fields payload validation
		if (!empty($this->http->req->s['necessary']['payload'])) {
			foreach ($this->http->req->s['necessary']['payload'] as $column => &$arr) {
				if ($arr['necessary'] && !isset($this->http->req->s['payload'][$column])) {
					$errors[] = 'Missing necessary payload: ' . $column;
					$isValidData = false;
				}
			}
		}

		return [$isValidData, $errors];
	}
}
