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

namespace Microservices\App;

use Microservices\App\Constant;
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
 * @package   Openswoole-Microservices
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
	private $validatorObject = null;

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
		if ($this->httpObject->httpRequestObject->customerDbObject->dbServerDatabase === Env::$gDbServerDatabase) {
			$this->validatorObject = new GlobalValidator(
				httpObject: $this->httpObject
			);
		} else {
			$this->validatorObject = new CustomerValidator(
				httpObject: $this->httpObject
			);
		}
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
		if (
			isset(($this->httpObject->httpRequestObject->activeRequestData['requiredFieldArray']))
			&& count(
				value: $this->httpObject->httpRequestObject->activeRequestData['requiredFieldArray']
			) > 0
		) {
			if (
				(
					[
						$isValidData,
						$errorArray
					] = $this->validateRequired()
				)
				&& !$isValidData
			) {
				return [
					$isValidData,
					$errorArray
				];
			}
		}

		return $this->validatorObject->validate(
			validationConfig: $validationConfig
		);
	}

	/**
	 * Validate required payload
	 * 
	 * @return array
	 */
	private function validateRequired(): array
	{
		$isValidData = Constant::$TRUE;
		$errorArray = [];
		// Required fields payload validation
		if (!empty($this->httpObject->httpRequestObject->activeRequestData['requiredFieldArray']['payload'])) {
			foreach ($this->httpObject->httpRequestObject->activeRequestData['requiredFieldArray']['payload'] as $activeRequestDataKeySubKey) {
				if (
					!in_array(
						needle: $activeRequestDataKeySubKey,
						haystack: $this->httpObject->httpRequestObject->activeRequestData['payload'],
						strict: Constant::$TRUE
					)
				) {
					$errorArray[] = 'Missing required payload: ' . $activeRequestDataKeySubKey;
					$isValidData = Constant::$FALSE;
				}
			}
		}

		return [
			$isValidData,
			$errorArray
		];
	}
}
