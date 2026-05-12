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

use Microservices\App\DbCommonFunction;
use Microservices\App\Env;
use Microservices\App\Http;
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
		if (DbCommonFunction::$masterDb[$this->http->req->cID]->dbServerDB === Env::$gDbServerDB) {
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
			isset(($this->http->req->s['requiredFieldArr']))
			&& count(value: $this->http->req->s['requiredFieldArr']) > 0
		) {
			if (
				([$isValidData, $errorArr] = $this->validateRequired())
				&& !$isValidData
			) {
				return [$isValidData, $errorArr];
			}
		}

		return $this->v->validate(validationConfig: $validationConfig);
	}

	/**
	 * Validate required payload
	 *
	 * @return array
	 */
	private function validateRequired(): array
	{
		$isValidData = true;
		$errorArr = [];
		// Required fields payload validation
		if (!empty($this->http->req->s['requiredFieldArr']['payload'])) {
			foreach ($this->http->req->s['requiredFieldArr']['payload'] as $fetchFromDetail) {
				if (!in_array($fetchFromDetail, $this->http->req->s['payload'])) {
					$errorArr[] = 'Missing required payload: ' . $fetchFromDetail;
					$isValidData = false;
				}
			}
		}

		return [$isValidData, $errorArr];
	}
}
