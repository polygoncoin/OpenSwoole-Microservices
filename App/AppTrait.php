<?php

/**
 * Read / Write Trait
 * php version 8.3
 * 
 * @category  API
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App;

use Microservices\App\CacheServerKey;
use Microservices\App\CommonFunction;
use Microservices\App\Counter;
use Microservices\App\Constant;
use Microservices\App\DatabaseServerDataType;
use Microservices\App\Env;
use Microservices\App\HttpStatus;
use Microservices\App\Start;
use Microservices\App\Validator;

/**
 * Trait for API
 * php version 8.3
 * 
 * @category  API_Trait
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
trait AppTrait
{
	/**
	 * Validator class object
	 * 
	 * @var null|Validator
	 */
	public $validatorObject = null;

	/**
	 * Function to help execute PHP functions enclosed with double quotes
	 * 
	 * @param mixed $param Returned values by PHP inbuilt functions
	 * 
	 * @return mixed
	 */
	public function execPhpFunc(
		$param
	): mixed {
		return $param;
	}

	/**
	 * Get required payload
	 * 
	 * @param array $sqlConfig         Sql config
	 * @param bool  $maintainHierarchy Maintain Hierarchy
	 * @param bool  $isFirstCall       true to represent the first call in recursion
	 * 
	 * @return array
	 * @throws \Exception
	 */
	private function getRequired(
		&$sqlConfig,
		$maintainHierarchy,
		$isFirstCall
	): array {
		$requiredFieldArray = [];

		foreach (['__PAYLOAD__', '__SET__', '__WHERE__'] as $option) {
			if (isset($sqlConfig[$option])) {
				foreach ($sqlConfig[$option] as $sqlParamConfig) {
					$activeRequestDataKey = $sqlParamConfig['activeRequestDataKey'];
					if ($activeRequestDataKey === 'function') {
						continue;
					}
					$isRequired = isset($sqlParamConfig['isRequired'])
						? $sqlParamConfig['isRequired'] : Constant::$FALSE;

					if ($isRequired) {
						$activeRequestDataKeySubKey = $sqlParamConfig['activeRequestDataKeySubKey'];

						if (!isset($requiredFieldArray[$activeRequestDataKey])) {
							$requiredFieldArray[$activeRequestDataKey] = [];
						}
						if (
							!in_array(
								needle: $activeRequestDataKeySubKey,
								haystack: $requiredFieldArray[$activeRequestDataKey],
								strict: Constant::$TRUE
							)
						) {
							$requiredFieldArray[$activeRequestDataKey][] = $activeRequestDataKeySubKey;
						}
					}
				}
			}
		}

		// Check for hierarchy setting
		$foundHierarchy = false;
		if (isset($sqlConfig['__WHERE__'])) {
			foreach ($sqlConfig['__WHERE__'] as $sqlParamConfig) {
				$activeRequestDataKey = $sqlParamConfig['activeRequestDataKey'];
				$activeRequestDataKeySubKey = $sqlParamConfig['activeRequestDataKeySubKey'];

				if (
					$isFirstCall
					&& in_array(
						needle: $activeRequestDataKey,
						haystack: ['sqlResults', 'sqlParamArray', 'sqlPayload'],
						strict: Constant::$TRUE
					)
				) {
					throw new \Exception(
						message: "First query can not have {$activeRequestDataKey} config",
						code: HttpStatus::$InternalServerError
					);
				}
				if (
					in_array(
						needle: $activeRequestDataKey,
						haystack: ['sqlResults', 'sqlParamArray', 'sqlPayload'],
						strict: Constant::$TRUE
					)
				) {
					$foundHierarchy = true;
					break;
				}
			}
			// if (
			// 	!$isFirstCall
			// 	&& $maintainHierarchy
			// 	&& !$foundHierarchy
			// ) {
			//     throw new \Exception(
			//          message: 'Invalid config: missing ' . $activeRequestDataKey,
			//          code: HttpStatus::$InternalServerError
			//      );
			// }
		}

		// Check in subQuery
		if (
			isset($sqlConfig['__SUB-CONFIG__'])
			|| isset($sqlConfig['__SUB-CONFIG__'])
		) {
			if (
				isset($sqlConfig['__SUB-CONFIG__'])
				&& !$this->isObject(
					arr: $sqlConfig['__SUB-CONFIG__']
				)
			) {
				throw new \Exception(
					message: 'Sub-Query should be an associative array',
					code: HttpStatus::$InternalServerError
				);
			}
			if (
				isset($sqlConfig['__SUB-CONFIG__'])
				&& !$this->isObject(
					arr: $sqlConfig['__SUB-CONFIG__']
				)
			) {
				throw new \Exception(
					message: 'Sub-Payload should be an associative array',
					code: HttpStatus::$InternalServerError
				);
			}
			foreach (['__SUB-CONFIG__', '__SUB-CONFIG__'] as $option) {
				if (isset($sqlConfig[$option])) {
					foreach ($sqlConfig[$option] as $module => &$moduleSqlConfig) {
						$maintainHierarchy = ($maintainHierarchy) ?? $this->getMaintainHierarchy(
							sqlConfig: $moduleSqlConfig
						);
						$moduleRequiredFieldArray = $this->getRequired(
							sqlConfig: $moduleSqlConfig,
							maintainHierarchy: $maintainHierarchy,
							isFirstCall: Constant::$FALSE
						);
						if ($maintainHierarchy) {
							$requiredFieldArray[$module] = $moduleRequiredFieldArray;
						} else {
							foreach ($moduleRequiredFieldArray as $activeRequestDataKey => &$activeRequestDataKeySubKeyArray) {
								if (!isset($requiredFieldArray[$activeRequestDataKey])) {
									$requiredFieldArray[$activeRequestDataKey] = [];
								}
								foreach ($activeRequestDataKeySubKeyArray as $activeRequestDataKeySubKey) {
									if (
										!in_array(
											needle: $activeRequestDataKeySubKey,
											haystack: $requiredFieldArray[$activeRequestDataKey],
											strict: Constant::$TRUE
										)
									) {
										$requiredFieldArray[$activeRequestDataKey][] = $activeRequestDataKeySubKey;
									}
								}
							}
						}
					}
				}
			}
		}

		return $requiredFieldArray;
	}

	/**
	 * Validate payload
	 * 
	 * @param array $validationConfig Validation config from Config file
	 * 
	 * @return array
	 */
	public function validate(&$validationConfig): array
	{
		if ($this->validatorObject === Constant::$NULL) {
			$this->validatorObject = new Validator(
				httpObject: $this->httpObject
			);
		}

		return $this->validatorObject->validate(
			validationConfig: $validationConfig
		);
	}

	/**
	 * Generate Sql query and its param's in Named format
	 * 
	 * @param array      $sqlConfig       Sql config
	 * @param array|null $payloadKeyArray Payload key's
	 * 
	 * @return array
	 */
	private function getSqlAndParamNamedMode(
		&$sqlConfig,
		$payloadKeyArray = null
	): array {
		$insertId = null;
		$sql = '';
		/*!999999 comment goes here */
		if (isset($sqlConfig['__SQL-COMMENT__'])) {
			$sql .= '/' . '*!999999 ';
			$sql .= $sqlConfig['__SQL-COMMENT__'];
			$sql .= ' */';
		}
		switch (true) {
			case isset($sqlConfig['__SQL__']):
				$sql .= $sqlConfig['__SQL__'];
				break;
			case isset($sqlConfig['__DOWNLOAD__']):
				$sql .= $sqlConfig['__DOWNLOAD__'];
				break;
		}
		$paramArray = [];
		$paramKeyArray = [];
		$errorArray = [];
		$record = [];
		$__SET__ = [];

		// Check __SET__
		if (
			isset($sqlConfig['__SET__'])
			&& count(
				value: $sqlConfig['__SET__']
			) !== 0
		) {
			[$setParamArray, $errorArray] = $this->getSqlParam(
				sqlConfig: $sqlConfig['__SET__'],
				sqlConfigVariables: $sqlConfig['__VARIABLE__'] ?? []
			);
			if (empty($errorArray)) {
				if (!empty($setParamArray)) {
					// __SET__ not compulsory in query
					$found = strpos(
						haystack: $sql,
						needle: '__SET__'
					) !== Constant::$FALSE;

					if (
						$found
						&& Env::$enableGlobalCounter
						&& isset($sqlConfig['__PRIMARY-KEY__'])
						&& !isset($sqlConfig['__WHERE__'])
						&& isset($sqlConfig['__SQL__'])
						&& strpos(
								haystack: strtolower(trim($sqlConfig['__SQL__'])),
								needle: 'insert'
							) === 0
					) {
						$insertId = Counter::getGlobalCounter();
						$setParamArray[$sqlConfig['__PRIMARY-KEY__']] = $insertId;
					}

					foreach ($setParamArray as $paramKey => &$paramKeyValue) {
						$paramKey = str_replace(
							search: ['`', ' '],
							replace: '',
							subject: $paramKey
						);
						$paramKeyArray[] = $paramKey;
						if ($found) {
							$__SET__[] = "`{$paramKey}` = :{$paramKey}";
						}
						$paramArray[":{$paramKey}"] = $paramKeyValue;
						$record[$paramKey] = $paramKeyValue;
					}
				}
			}
		}

		// Check __WHERE__
		if (
			empty($errorArray)
			&& isset($sqlConfig['__WHERE__'])
			&& count(
				value: $sqlConfig['__WHERE__']
			) !== 0
		) {
			$wErrorArray = [];
			[$whereParamArray, $wErrorArray] = $this->getSqlParam(
				sqlConfig: $sqlConfig['__WHERE__'],
				sqlConfigVariables: $sqlConfig['__VARIABLE__'] ?? []
			);
			if (empty($wErrorArray)) {
				if (!empty($whereParamArray)) {
					// __WHERE__ not compulsory in query
					$whereFound = strpos(
						haystack: $sql,
						needle: '__WHERE__'
					) !== Constant::$FALSE;
					if ($whereFound) {
						$__WHERE__ = [];
						foreach ($whereParamArray as $whereParamKey => &$whereParamKeyValue) {
							$whereParam = $whereParamKey = str_replace(
								search: ['`', ' '],
								replace: '',
								subject: $whereParamKey
							);
							$index = 0;
							while (
								in_array(
									needle: $whereParam,
									haystack: $paramKeyArray,
									strict: Constant::$TRUE
								)
							) {
								$index++;
								$whereParam = "{$whereParamKey}{$index}";
							}
							$paramKeyArray[] = $whereParam;
							$__WHERE__[] = "`{$whereParamKey}` = :{$whereParam}";
							$paramArray[":{$whereParam}"] = $whereParamKeyValue;
							$record[$whereParam] = $whereParamKeyValue;
						}
						$sql = str_replace(
							search: '__WHERE__',
							replace: implode(
								separator: ' AND ', array: $__WHERE__
							),
							subject: $sql
						);
					}
				}
			} else {
				$errorArray = array_merge($errorArray, $wErrorArray);
			}
		}
		if (!empty($__SET__)) {
			$sql = str_replace(
				search: '__SET__',
				replace: implode(
					separator: ', ', array: $__SET__
				),
				subject: $sql
			);
		}

		if (!empty($record)) {
			$this->resetFetchData(
				activeRequestDataKey: 'sqlParamArray',
				payloadKeyArray: $payloadKeyArray,
				record: $record
			);
		}

		return [$insertId, $sql, $paramArray, $errorArray];
	}

	/**
	 * Generate Sql query and its param's in Unnamed format
	 * 
	 * @param array      $sqlConfig       Sql config
	 * @param array|null $payloadKeyArray Payload key's
	 * 
	 * @return array
	 */
	private function getSqlAndParamUnnamedMode(
		&$sqlConfig,
		$payloadKeyArray = null
	): array {
		$insertId = null;
		$sql = '';
		/*!999999 comment goes here */
		if (isset($sqlConfig['__SQL-COMMENT__'])) {
			$sql .= '/' . '*!999999 ';
			$sql .= $sqlConfig['__SQL-COMMENT__'];
			$sql .= ' */';
		}
		switch (true) {
			case isset($sqlConfig['__SQL__']):
				$sql .= $sqlConfig['__SQL__'];
				break;
			case isset($sqlConfig['__DOWNLOAD__']):
				$sql .= $sqlConfig['__DOWNLOAD__'];
				break;
		}
		$paramArray = [];
		$paramKeyArray = [];
		$errorArray = [];
		$record = [];
		$__SET__ = [];

		// Check __SET__
		if (
			isset($sqlConfig['__SET__'])
			&& count(
				value: $sqlConfig['__SET__']
			) !== 0
		) {
			[$setParamArray, $errorArray] = $this->getSqlParam(
				sqlConfig: $sqlConfig['__SET__'],
				sqlConfigVariables: $sqlConfig['__VARIABLE__'] ?? []
			);
			if (empty($errorArray)) {
				if (!empty($setParamArray)) {
					// __SET__ not compulsory in query
					$found = strpos(
						haystack: $sql,
						needle: '__SET__'
					) !== Constant::$FALSE;

					if (
						$found
						&& Env::$enableGlobalCounter
						&& isset($sqlConfig['__PRIMARY-KEY__'])
						&& !isset($sqlConfig['__WHERE__'])
						&& isset($sqlConfig['__SQL__'])
						&& strpos(
								haystack: strtolower(trim($sqlConfig['__SQL__'])),
								needle: 'insert'
							) === 0
					) {
						$insertId = Counter::getGlobalCounter();
						$setParamArray[$sqlConfig['__PRIMARY-KEY__']] = $insertId;
					}

					foreach ($setParamArray as $paramKey => &$paramKeyValue) {
						$paramKeyArray[] = $paramKey;
						if ($found) {
							$__SET__[] = "{$paramKey} = ?";
						}
						$paramArray[] = $paramKeyValue;
						$record[$paramKey] = $paramKeyValue;
					}
				}
			}
		}

		// Check __WHERE__
		if (
			empty($errorArray)
			&& isset($sqlConfig['__WHERE__'])
			&& count(
				value: $sqlConfig['__WHERE__']
			) !== 0
		) {
			$wErrorArray = [];
			[$whereParamArray, $wErrorArray] = $this->getSqlParam(
				sqlConfig: $sqlConfig['__WHERE__'],
				sqlConfigVariables: $sqlConfig['__VARIABLE__'] ?? []
			);
			if (empty($wErrorArray)) {
				if (!empty($whereParamArray)) {
					// __WHERE__ not compulsory in query
					$whereFound = strpos(
						haystack: $sql,
						needle: '__WHERE__'
					) !== Constant::$FALSE;
					if ($whereFound) {
						$__WHERE__ = [];
						foreach ($whereParamArray as $whereParamKey => &$whereParamKeyValue) {
							$whereParam = $whereParamKey;
							$index = 0;
							while (
								in_array(
									needle: $whereParam,
									haystack: $paramKeyArray,
									strict: Constant::$TRUE
								)
							) {
								$index++;
								$whereParam = "{$whereParamKey}{$index}";
							}
							$paramKeyArray[] = $whereParam;
							$__WHERE__[] = "{$whereParamKey} = ?";
							$paramArray[] = $whereParamKeyValue;
							$record[$whereParam] = $whereParamKeyValue;
						}
						$sql = str_replace(
							search: '__WHERE__',
							replace: implode(
								separator: ' AND ', array: $__WHERE__
							),
							subject: $sql
						);
					}
				}
			} else {
				$errorArray = array_merge($errorArray, $wErrorArray);
			}
		}
		if (!empty($__SET__)) {
			$sql = str_replace(
				search: '__SET__',
				replace: implode(
					separator: ', ', array: $__SET__
				),
				subject: $sql
			);
		}

		if (!empty($record)) {
			$this->resetFetchData(
				activeRequestDataKey: 'sqlParamArray',
				payloadKeyArray: $payloadKeyArray,
				record: $record
			);
		}

		return [$insertId, $sql, $paramArray, $errorArray];
	}

	/**
	 * Generates ParamArray for statement to execute
	 * 
	 * @param array $sqlConfig          Sql config
	 * @param array $sqlConfigVariables Payload Variables
	 * 
	 * @return array
	 * @throws \Exception
	 */
	private function getSqlParam(
		&$sqlConfig,
		$sqlConfigVariables
	): array {
		$paramArray = [];
		$errorArray = [];

		// Collect param values as per config respectively
		foreach ($sqlConfig as $sqlParamConfig) {
			$column = $sqlParamConfig['column'];
			$activeRequestDataKey = $sqlParamConfig['activeRequestDataKey'];
			$activeRequestDataKeySubKey = $sqlParamConfig['activeRequestDataKeySubKey'];
			if ($activeRequestDataKey === 'function') {
				$function = $activeRequestDataKeySubKey;
				$value = $function($this->httpObject->httpRequestObject->activeRequestData);
				$paramArray[$column] = $value;
				continue;
			} elseif (
				in_array(
					needle: $activeRequestDataKey,
					haystack: ['sqlParamArray', 'sqlPayload'],
					strict: Constant::$TRUE
				)
			) {
				if (!isset($this->httpObject->httpRequestObject->activeRequestData[$activeRequestDataKey])) {
					$errorArray[] = "Missing key '{$activeRequestDataKeySubKey}' in '{$activeRequestDataKey}'";
					continue;
				}
				$value = $this->httpObject->httpRequestObject->activeRequestData[$activeRequestDataKey];
				$break = false;
				foreach (
					explode(
						separator: ':',
						string: $activeRequestDataKeySubKey
					) as $_activeRequestDataKeySubKey
				) {
					if (isset($value[$_activeRequestDataKeySubKey])) {
						$value = &$value[$_activeRequestDataKeySubKey];
						continue;
					}
					$errorArray[] = "Missing '{$activeRequestDataKey}' for '{$_activeRequestDataKeySubKey}'";
					$break = true;
					break;
				}
				if (!$break) {
					$paramArray[$column] = $value;
				}
				continue;
			} elseif ($activeRequestDataKey === 'sqlResults') {
				if (!isset($this->httpObject->httpRequestObject->activeRequestData[$activeRequestDataKey])) {
					$errorArray[] = "Missing '{$activeRequestDataKey}'";
					continue;
				}
				$activeRequestDataKeySubKeyArray = explode(
					separator: ':',
					string: $activeRequestDataKeySubKey
				);
				$value = $this->httpObject->httpRequestObject->activeRequestData[$activeRequestDataKey];
				foreach ($activeRequestDataKeySubKeyArray as $_activeRequestDataKeySubKey) {
					if (isset($value[$_activeRequestDataKeySubKey])) {
						$value = &$value[$_activeRequestDataKeySubKey];
						continue;
					}
					$errorArray[] = "Missing '{$activeRequestDataKey}' for '{$_activeRequestDataKeySubKey}'";
					break;
				}
				$paramArray[$column] = $value;
				continue;
			} elseif ($activeRequestDataKey === 'custom') {
				$value = $activeRequestDataKeySubKey;
				$paramArray[$column] = $value;
				continue;
			} elseif ($activeRequestDataKey === 'variables') {
				if (isset($sqlConfigVariables[$activeRequestDataKeySubKey])) {
					$paramArray[$column] = $sqlConfigVariables[$activeRequestDataKeySubKey];
				} else {
					$errorArray[] = "Missing '{$activeRequestDataKey}' for '{$activeRequestDataKeySubKey}'";
				}
				continue;
			} elseif (isset($this->httpObject->httpRequestObject->activeRequestData[$activeRequestDataKey][$activeRequestDataKeySubKey])) {
				if (
					isset($this->httpObject->httpRequestObject->activeRequestData['requiredFieldArray'][$activeRequestDataKey])
					&& in_array(
						needle: $activeRequestDataKeySubKey,
						haystack: $this->httpObject->httpRequestObject->activeRequestData['requiredFieldArray'][$activeRequestDataKey],
						strict: Constant::$TRUE
					)
				) {
					if (isset($sqlParamConfig['dataType'])) {
						if (
							!DatabaseServerDataType::validateDataType(
								data: $this->httpObject->httpRequestObject->activeRequestData[$activeRequestDataKey][$activeRequestDataKeySubKey],
								dataType: $sqlParamConfig['dataType']
							)
						) {
							$errorArray[] = "Invalid required field data-type of '{$activeRequestDataKey}' for '{$activeRequestDataKeySubKey}'";
							continue;
						}
					}
				}
				$paramArray[$column] = $this->httpObject->httpRequestObject->activeRequestData[$activeRequestDataKey][$activeRequestDataKeySubKey];
				continue;
			} elseif (
				isset($this->httpObject->httpRequestObject->activeRequestData['requiredFieldArray'][$activeRequestDataKey])
				&& in_array(
					needle: $activeRequestDataKeySubKey,
					haystack: $this->httpObject->httpRequestObject->activeRequestData['requiredFieldArray'][$activeRequestDataKey],
					strict: Constant::$TRUE
				)
			) {
				$errorArray[] = "Missing required field '{$activeRequestDataKey}' for '{$activeRequestDataKeySubKey}'";
				continue;
			} else {
				$errorArray[] = "Invalid configuration of '{$activeRequestDataKey}' for '{$activeRequestDataKeySubKey}'";
				continue;
			}
		}

		return [$paramArray, $errorArray];
	}

	/**
	 * Function to find array is associative/simple array
	 * 
	 * @param array $arr Array to search for associative/simple array
	 * 
	 * @return bool
	 */
	private function isObject($arr): bool
	{
		$isObject = false;

		$index = 0;
		foreach ($arr as $key => &$value) {
			if ($key !== $index++) {
				$isObject = true;
				break;
			}
		}

		return $isObject;
	}

	/**
	 * Use results in where clause of sub queries recursively
	 * 
	 * @param array  $sqlConfig Sql config
	 * 
	 * @return bool
	 */
	private function getMaintainHierarchy(
		&$sqlConfig
	): bool {
		if (
			isset($sqlConfig['__HIERARCHY__'])
			&& $sqlConfig['__HIERARCHY__'] === Constant::$TRUE
		) {
			return true;
		}
		return false;
	}

	/**
	 * Return explain params recursively
	 * 
	 * @param array $sqlConfig         Sql config
	 * @param bool  $maintainHierarchy Maintain Hierarchy flag
	 * @param bool  $isFirstCall       Flag to check if this is first request
	 * 
	 * @return array
	 * @throws \Exception
	 */
	private function getExplainParam(
		&$sqlConfig,
		$maintainHierarchy,
		$isFirstCall
	): array {
		$explainParamArray = [];

		if (isset($sqlConfig['__COUNT-SQL__'])) {
			$sqlConfig['__CONFIG__'][] = [
				'column' => 'page',
				'activeRequestDataKey' => 'queryParamArray',
				'activeRequestDataKeySubKey' => 'page',
				'dataType' => DatabaseServerDataType::$INT,
				'isRequired' => Constant::$REQUIRED
			];
			$sqlConfig['__CONFIG__'][] = [
				'column' => 'perPage',
				'activeRequestDataKey' => 'queryParamArray',
				'activeRequestDataKeySubKey' => 'perPage',
				'dataType' => DatabaseServerDataType::$INT
			];

			foreach ($sqlConfig['__CONFIG__'] as $sqlParamConfig) {
				$activeRequestDataKey = $sqlParamConfig['activeRequestDataKey'];
				$activeRequestDataKeySubKey = $sqlParamConfig['activeRequestDataKeySubKey'];
				$dataType = isset($sqlParamConfig['dataType'])
					? $sqlParamConfig['dataType'] : DatabaseServerDataType::$Default;
				$isRequired = isset($sqlParamConfig['isRequired'])
					? $sqlParamConfig['isRequired'] : Constant::$FALSE;

				if (
					isset($explainParamArray[$activeRequestDataKeySubKey])
					&& $explainParamArray[$activeRequestDataKeySubKey]['isRequired'] === Constant::$TRUE
				) {
					continue;
				}
				$dataType['isRequired'] = $isRequired ? Constant::$TRUE : Constant::$FALSE;
				$explainParamArray[$activeRequestDataKeySubKey] = $dataType;
			}
		}

		foreach (['__PAYLOAD__', '__SET__', '__WHERE__'] as $option) {
			if (isset($sqlConfig[$option])) {
				foreach ($sqlConfig[$option] as $sqlParamConfig) {
					$activeRequestDataKey = $sqlParamConfig['activeRequestDataKey'];
					$activeRequestDataKeySubKey = $sqlParamConfig['activeRequestDataKeySubKey'];
					$dataType = isset($sqlParamConfig['dataType'])
						? $sqlParamConfig['dataType'] : DatabaseServerDataType::$Default;
					$isRequired = isset($sqlParamConfig['isRequired'])
						? $sqlParamConfig['isRequired'] : Constant::$FALSE;

					if ($activeRequestDataKey !== 'payload') {
						continue;
					}
					if (
						isset($explainParamArray[$activeRequestDataKeySubKey])
						&& $explainParamArray[$activeRequestDataKeySubKey]['isRequired'] === Constant::$TRUE
					) {
						continue;
					}
					$dataType['isRequired'] = $isRequired ? Constant::$TRUE : Constant::$FALSE;
					$explainParamArray[$activeRequestDataKeySubKey] = $dataType;
				}
			}
		}

		// Check for hierarchy
		$foundHierarchy = false;
		if (isset($sqlConfig['__WHERE__'])) {
			foreach ($sqlConfig['__WHERE__'] as $sqlParamConfig) {
				$activeRequestDataKey = $sqlParamConfig['activeRequestDataKey'];
				$activeRequestDataKeySubKey = $sqlParamConfig['activeRequestDataKeySubKey'];
				if (
					in_array(
						needle: $activeRequestDataKey,
						haystack: ['sqlResults', 'sqlParamArray', 'sqlPayload'],
						strict: Constant::$TRUE
					)
				) {
					$foundHierarchy = true;
					break;
				}
			}
			if (
				!$isFirstCall
				&& $maintainHierarchy
				&& !$foundHierarchy
			) {
				throw new \Exception(
					message: 'Invalid config: missing ' . $activeRequestDataKey,
					code: HttpStatus::$InternalServerError
				);
			}
		}

		// Check in subQuery//'__SUB-CONFIG__'
		foreach (['__SUB-CONFIG__', '__SUB-CONFIG__'] as $option) {
			if (isset($sqlConfig[$option])) {
				foreach ($sqlConfig[$option] as $module => &$moduleSqlConfig) {
					$maintainHierarchy = ($maintainHierarchy) ?? $this->getMaintainHierarchy(
						sqlConfig: $moduleSqlConfig
					);
					$moduleExplainParamArray = $this->getExplainParam(
						sqlConfig: $moduleSqlConfig,
						maintainHierarchy: $maintainHierarchy,
						isFirstCall: Constant::$FALSE
					);
					if ($maintainHierarchy) {
						if (!empty($moduleExplainParamArray)) {
							$explainParamArray[$module] = $moduleExplainParamArray;
						}
					} else {
						foreach ($moduleExplainParamArray as $activeRequestDataKeySubKey => $field) {
							if (!isset($explainParamArray[$activeRequestDataKeySubKey])) {
								$explainParamArray[$activeRequestDataKeySubKey] = $field;
							}
						}
					}
				}
			}
		}

		return $explainParamArray;
	}

	/**
	 * Function to reset data for module key wise
	 * 
	 * @param string $activeRequestDataKey sqlResults / sqlParamArray / sqlPayload
	 * @param array  $payloadKeyArray      Module key's in recursion
	 * @param array  $record               Record data fetched from DB
	 * 
	 * @return void
	 */
	private function resetFetchData(
		$activeRequestDataKey,
		$payloadKeyArray,
		$record
	): void {
		if (
			empty($payloadKeyArray)
			|| count(
				value: $payloadKeyArray
			) === 0
		) {
			$this->httpObject->httpRequestObject->activeRequestData[$activeRequestDataKey] = [];
			$this->httpObject->httpRequestObject->activeRequestData[$activeRequestDataKey]['return'] = [];
		}
		$httpReq = &$this->httpObject->httpRequestObject->activeRequestData[$activeRequestDataKey]['return'];
		if (!empty($payloadKeyArray)) {
			foreach ($payloadKeyArray as $moduleKey) {
				if (!isset($httpReq[$moduleKey])) {
					$httpReq[$moduleKey] = [];
				}
				$httpReq = &$httpReq[$moduleKey];
			}
		}
		$httpReq = $record;
	}

	/**
	 * Rate Limiting request on basis of Sql config
	 * 
	 * @param array $sqlConfig Sql config
	 * 
	 * @return void
	 * @throws \Exception
	 */
	private function rateLimitRoute(&$sqlConfig): void
	{
		if (
			$this->httpObject->httpRequestObject->isPublicRequest
			|| !CommonFunction::isEnabled(
				httpObject: $this->httpObject,
				feature: 'customer_enabled_rate_limiting_for_route'
			)
			|| !isset($sqlConfig['rateLimitMaxRequest'])
			|| !isset($sqlConfig['rateLimitMaxRequestWindow'])
		) {
			return;
		}

		$payloadSignature = [
			'httpRequestIp' => $this->httpObject->httpReqData['server']['httpRequestIp'],
			'customerId' => $this->httpObject->httpRequestObject->customerId,
			'httpRequestMethod' => $this->httpObject->httpReqData['server']['httpRequestMethod'],
			'Route' => $this->httpObject->httpReqData['get'][ROUTE_URL_PARAM],
		];
		if (isset($this->httpObject->httpRequestObject->activeRequestData['userData'])) {
			$payloadSignature['customerUserGroupId'] = ($this->httpObject->httpRequestObject->activeRequestData['userData']['customer_user_group_id'] !== Constant::$NULL
				? $this->httpObject->httpRequestObject->activeRequestData['userData']['customer_user_group_id'] : 0);
			$payloadSignature['customerUserId'] = ($this->httpObject->httpRequestObject->customerUserId !== Constant::$NULL
				? $this->httpObject->httpRequestObject->customerUserId : 0);
		}
		$hash = json_encode(
			value: $payloadSignature
		);
		$rateLimitKey = md5(
			string: $hash
		);

		// @throws \Exception
		$this->httpObject->httpRequestObject->rateLimiterObject->checkRateLimit(
			rateLimitPrefix: Env::$rateLimitRoutePrefix,
			rateLimitMaxRequest: $sqlConfig['rateLimitMaxRequest'],
			rateLimitMaxRequestWindow: $sqlConfig['rateLimitMaxRequestWindow'],
			rateLimitKey: $rateLimitKey
		);
	}

	/**
	 * Check Referrer Lag
	 * 
	 * @param array $sqlConfig Sql config
	 * 
	 * @return void
	 * @throws \Exception
	 */
	private function checkReferrerLag(&$sqlConfig): void
	{
		$customerUserId = 0;
		if (isset($this->httpObject->httpRequestObject->customerUserId)) {
			$customerUserId = $this->httpObject->httpRequestObject->customerUserId;
		}
		$customerUserReferrerLagKey = CacheServerKey::customerUserReferrerLag(
			customerId: $this->httpObject->httpRequestObject->customerId,
			customerUserId: $customerUserId
		);
		if (
			isset($sqlConfig['referrerLagWindow'])
			&& count(
				value: $sqlConfig['referrerLagWindow']
			) > 0
		) {
			if (
				!$this->httpObject->httpRequestObject->customerCacheObject->cacheExist(
					cacheKey: $customerUserReferrerLagKey
				)
			) {
				throw new \Exception(
					message: 'Referrer lag not initiated',
					code: HttpStatus::$BadRequest
				);
			}
			$referrerLagData = $this->httpObject->httpRequestObject->customerCacheObject->cacheGet(
				cacheKey: $customerUserReferrerLagKey
			);
			if (
				isset($referrerLagData['initRoute'])
				&& isset($referrerLagData['timestamp'])
			) {
				$found = false;
				foreach ($sqlConfig['referrerLagWindow'] as $referrerSqlConfig) {
					if ($referrerLagData['initRoute'] === $referrerSqlConfig['referrer']) {
						$tsDiff = Env::$timestamp - $referrerSqlConfig['timestamp'];
						if (
							isset($referrerSqlConfig['minimumReferrerLagWindow'])
							&& $tsDiff >= $referrerSqlConfig['minimumReferrerLagWindow']
						) {
							if (isset($referrerSqlConfig['maximumReferrerLagWindow'])) {
								if ($tsDiff <= $referrerSqlConfig['maximumReferrerLagWindow']) {
									$found = true;
								} else {
									$this->httpObject->httpRequestObject->customerCacheObject->cacheDelete(
										cacheKey: $customerUserReferrerLagKey
									);
								}
							} else {
								$found = true;
							}
						} else {
							$this->httpObject->httpRequestObject->customerCacheObject->cacheDelete(
								cacheKey: $customerUserReferrerLagKey
							);
						}
					}
				}
				if (!$found) {
					throw new \Exception(
						message: 'Referrer lag not configured',
						code: HttpStatus::$BadRequest
					);
				}
			}
		}

		if (
			isset($sqlConfig['enableReferrerLag'])
			&& $sqlConfig['enableReferrerLag'] === Constant::$YES
		) {
			if (
				!$this->httpObject->httpRequestObject->customerCacheObject->cacheExist(
					cacheKey: $customerUserReferrerLagKey
				)
			) {
				$this->httpObject->httpRequestObject->customerCacheObject->cacheSet(
					cacheKey: $customerUserReferrerLagKey,
					cacheValue: [
						'initRoute' => $this->httpObject->httpRequestObject->routeParserObject->configuredRoute,
						'timestamp' => Env::$timestamp
					]
				);
			} else {
				throw new \Exception(
					message: 'Referrer lag is enabled',
					code: HttpStatus::$BadRequest
				);
			}
		}
	}

	/**
	 * Check for Idempotent Window
	 * 
	 * @param array $sqlConfig       Sql config
	 * @param array $payloadKeyArray Payload Indexes
	 * 
	 * @return array
	 */
	private function checkIdempotent(
		&$sqlConfig,
		$payloadKeyArray
	): array {
		$idempotentWindow = 0;
		$hashKey = null;
		$hashJson = null;
		if (
			isset($sqlConfig['idempotentWindow'])
			&& is_numeric(
				value: $sqlConfig['idempotentWindow']
			)
			&& $sqlConfig['idempotentWindow'] > 0
		) {
			$idempotentWindow = (int)$sqlConfig['idempotentWindow'];
			if ($idempotentWindow) {
				$payloadSignature = [
					'idempotentSecret' => Env::$idempotentSecret,
					'idempotentWindow' => $idempotentWindow,
					'httpRequestIp' => $this->httpObject->httpReqData['server']['httpRequestIp'],
					'customerId' => $this->httpObject->httpRequestObject->customerId,
					'customerUserId' => $this->httpObject->httpRequestObject->customerUserId,
					'httpRequestMethod' => $this->httpObject->httpReqData['server']['httpRequestMethod'],
					'Route' => $this->httpObject->httpReqData['get'][ROUTE_URL_PARAM],
					'payload' => $this->httpObject->httpRequestObject->dataDecodeObject->get(
						keyString: $this->getPayloadKey(
							payloadKeyArray: $payloadKeyArray
						)
					)
				];
				if (isset($this->httpObject->httpRequestObject->activeRequestData['userData'])) {
					$payloadSignature['customerUserGroupId'] = ($this->httpObject->httpRequestObject->activeRequestData['userData']['customer_user_group_id'] !== Constant::$NULL
						? $this->httpObject->httpRequestObject->activeRequestData['userData']['customer_user_group_id'] : 0);
					$payloadSignature['customerUserId'] = ($this->httpObject->httpRequestObject->customerUserId !== Constant::$NULL
						? $this->httpObject->httpRequestObject->customerUserId : 0);
				}

				$hash = json_encode(
					value: $payloadSignature
				);
				$hashKey = md5(
					string: $hash
				);
				if (
					$this->httpObject->httpRequestObject->isPrivateRequest
					&& $this->httpObject->httpRequestObject->customerCacheObject->cacheExist(
						cacheKey: $hashKey
					)
				) {
					$hashJson = str_replace(
						search: 'JSON',
						replace: json_encode(
							value: $this->httpObject->httpRequestObject->customerCacheObject->cacheGet(
								cacheKey: $hashKey
							)
						),
						subject: '{"Idempotent": JSON, "Status": 200}'
					);
				}
			}
		}

		return [$idempotentWindow, $hashKey, $hashJson];
	}

	/**
	 * Lag response
	 * 
	 * @param array $sqlConfig Sql config
	 * 
	 * @return void
	 */
	private function lagResponse($sqlConfig): void
	{
		if (
			$this->httpObject->httpRequestObject->isPublicRequest
			|| !isset($sqlConfig['responseLag'])
		) {
			return;
		}

		$payloadSignature = [
			'httpRequestIp' => $this->httpObject->httpReqData['server']['httpRequestIp'],
			'customerId' => $this->httpObject->httpRequestObject->customerId,
			'httpRequestMethod' => $this->httpObject->httpReqData['server']['httpRequestMethod'],
			'Route' => $this->httpObject->httpReqData['get'][ROUTE_URL_PARAM],
		];
		if (isset($this->httpObject->httpRequestObject->activeRequestData['userData'])) {
			$payloadSignature['customerUserGroupId'] = ($this->httpObject->httpRequestObject->activeRequestData['userData']['customer_user_group_id'] !== Constant::$NULL
				? $this->httpObject->httpRequestObject->activeRequestData['userData']['customer_user_group_id'] : 0);
			$payloadSignature['customerUserId'] = ($this->httpObject->httpRequestObject->customerUserId !== Constant::$NULL
				? $this->httpObject->httpRequestObject->customerUserId : 0);
		}

		$hash = json_encode(
			value: $payloadSignature
		);
		$hashKey = 'LAG:' . md5(
			string: $hash
		);

		if (
			$this->httpObject->httpRequestObject->customerCacheObject->cacheExist(
				cacheKey: $hashKey
			)
		) {
			$noOfRequest = $this->httpObject->httpRequestObject->customerCacheObject->cacheGet(
				cacheKey: $hashKey
			);
		} else {
			$noOfRequest = 0;
		}

		$this->httpObject->httpRequestObject->customerCacheObject->cacheSet(
			cacheKey: $hashKey,
			cacheValue: ++$noOfRequest,
			cacheExpire: $sqlConfig['responseLagWindow']
		);

		$lag = 0;
		$responseLag = &$sqlConfig['responseLag'];
		if (
			is_array(
				value: $responseLag
			)
		) {
			foreach ($responseLag as $start => $newLag) {
				if ($noOfRequest > $start) {
					$lag = $newLag;
				}
			}
		}

		if ($lag > 0) {
			sleep(
				seconds: $lag
			);
		}
	}

	/**
	 * Get Trigger data
	 * 
	 * @param array $triggerConfig Trigger Config
	 * 
	 * @return mixed
	 */
	public function getTriggerData($triggerConfig): mixed
	{
		if (!isset($this->httpObject->httpRequestObject->activeRequestData['authId'])) {
			throw new \Exception(
				message: 'Missing token',
				code: HttpStatus::$InternalServerError
			);
		}

		$httpReqData = [];

		$isObject = (!isset($triggerConfig[0])) ? Constant::$TRUE : Constant::$FALSE;
		if (
			!$isObject
			&& isset($triggerConfig[0])
			&& count(
				value: $triggerConfig
			) === 1
		) {
			$triggerConfig = $triggerConfig[0];
			$isObject = true;
		}

		$triggerOutput = [];
		if ($isObject) {
			$httpReqData = $this->getTriggerHttp(
				triggerConfig: $triggerConfig
			);
			[$responseHeaderArray, $responseContent, $responseCode] = Start::http(
				httpReqData: $httpReqData
			);
			$triggerOutput = &$responseContent;
		} else {
			$iTriggerCount = count(
				value: $triggerConfig
			);
			for ($iTrigger = 0; $iTrigger < $iTriggerCount; $iTrigger++) {
				$httpReqData = $this->getTriggerHttp(
					triggerConfig: $triggerConfig[$iTrigger]
				);
				[$responseHeaderArray, $responseContent, $responseCode] = Start::http(
					httpReqData: $httpReqData
				);
				$triggerOutput[] = &$responseContent;
			}
		}

		return $triggerOutput;
	}

	/**
	 * Get Trigger detail
	 * 
	 * @param array $triggerConfig Trigger Config
	 * 
	 * @return mixed
	 */
	public function getTriggerHttp($triggerConfig)
	{
		$httpRequestMethod = $triggerConfig['__METHOD__'];
		[$routeElementArrayArray, $errorArray] = $this->getTriggerParam(
			payloadConfig: $triggerConfig['__ROUTE__']
		);

		if ($errorArray) {
			return $errorArray;
		}

		$route = '/' . implode(
			separator: '/', array: $routeElementArrayArray
		);

		$queryStringArray = [];
		$payload = [];

		if (isset($triggerConfig['__QUERY-STRING__'])) {
			[$queryStringArray, $errorArray] = $this->getTriggerParam(
				payloadConfig: $triggerConfig['__QUERY-STRING__']
			);

			if ($errorArray) {
				return $errorArray;
			}
		}
		if (isset($triggerConfig['__PAYLOAD__'])) {
			[$payloadArray, $errorArray] = $this->getTriggerParam(
				payloadConfig: $triggerConfig['__PAYLOAD__']
			);
			if ($errorArray) {
				return $errorArray;
			}
		}

		$httpReqData['streamData'] = false;
		$httpReqData['server']['domainName'] = $this->httpObject->httpReqData['server']['domainName'];
		$httpReqData['server']['httpRequestMethod'] = $httpRequestMethod;
		$httpReqData['server']['httpRequestIp'] = $this->httpObject->httpReqData['server']['httpRequestIp'];
		$httpReqData['header'] = $this->httpObject->httpReqData['header'];
		$httpReqData['post'] = json_encode(
			value: $payloadArray
		);
		$httpReqData['get'] = $queryStringArray;
		$httpReqData['get'][ROUTE_URL_PARAM] = $route;
		$httpReqData['isWebRequest'] = false;

		return $httpReqData;
	}

	/**
	 * Get Trigger param's
	 * 
	 * @param array $payloadConfig API Payload configuration
	 * 
	 * @return array
	 * @throws \Exception
	 */
	private function getTriggerParam(
		&$payloadConfig
	): array {
		$triggerParamArray = [];
		$triggerErrorArray = [];

		// Collect param values as per config respectively
		foreach ($payloadConfig as &$payloadParamConfig) {
			$column = $payloadParamConfig['column'] ?? null;

			$activeRequestDataKey = $payloadParamConfig['activeRequestDataKey'];
			$activeRequestDataKeySubKey = $payloadParamConfig['activeRequestDataKeySubKey'];
			if ($activeRequestDataKey === 'function') {
				$function = $activeRequestDataKeySubKey;
				$value = $function($this->httpObject->httpRequestObject->activeRequestData);
				if ($column === Constant::$NULL) {
					$triggerParamArray[] = $value;
				} else {
					$triggerParamArray[$column] = $value;
				}
				continue;
			} elseif (
				in_array(
					needle: $activeRequestDataKey,
					haystack: ['sqlResults', 'sqlParamArray', 'sqlPayload'],
					strict: Constant::$TRUE
				)
			) {
				$activeRequestDataKeySubKeyArray = explode(
					separator: ':', string: $activeRequestDataKeySubKey
				);
				$value = $this->httpObject->httpRequestObject->activeRequestData[$activeRequestDataKey];
				foreach ($activeRequestDataKeySubKeyArray as $_activeRequestDataKeySubKey) {
					if (!isset($value[$_activeRequestDataKeySubKey])) {
						throw new \Exception(
							message: 'Invalid hierarchy:  Missing hierarchy data',
							code: HttpStatus::$InternalServerError
						);
					}
					$value = $value[$_activeRequestDataKeySubKey];
				}
				if ($column === Constant::$NULL) {
					$triggerParamArray[] = $value;
				} else {
					$triggerParamArray[$column] = $value;
				}
				continue;
			} elseif ($activeRequestDataKey === 'custom') {
				$value = $activeRequestDataKeySubKey;
				if ($column === Constant::$NULL) {
					$triggerParamArray[] = $value;
				} else {
					$triggerParamArray[$column] = $value;
				}
				continue;
			} elseif (isset($this->httpObject->httpRequestObject->activeRequestData[$activeRequestDataKey][$activeRequestDataKeySubKey])) {
				$value = $this->httpObject->httpRequestObject->activeRequestData[$activeRequestDataKey][$activeRequestDataKeySubKey];
				if ($column === Constant::$NULL) {
					$triggerParamArray[] = $value;
				} else {
					$triggerParamArray[$column] = $value;
				}
				continue;
			} else {
				$triggerErrorArray[] = "Invalid configuration of '{$activeRequestDataKey}' for '{$activeRequestDataKeySubKey}'";
				continue;
			}
		}

		return [$triggerParamArray, $triggerErrorArray];
	}

	/**
	 * Process import function of configuration
	 * 
	 * @param array $sqlConfig         Sql config
	 * @param bool  $maintainHierarchy If true - Uses parent payload/results in child
	 * 
	 * @return string
	 */
	private function generateImportSampleCsv(
		&$sqlConfig,
		$maintainHierarchy
	): string {
		$explainParamArray = $this->getExplainParam(
			sqlConfig: $sqlConfig,
			maintainHierarchy: $maintainHierarchy,
			isFirstCall: Constant::$TRUE
		);
		$paramArray = $this->genCsvHelper(
			headerCsv: 'CSV',
			explainParamArray: $explainParamArray
		);

		$header = [];
		$header[] = '__mode__';
		foreach ($paramArray as $r => $p) {
			if (
				is_array(
					value: $p
				)
			) {
				$indexCount = count(
					value: $p
				);
				for ($index = 0; $index < $indexCount; $index++) {
					$header[] = $p[$index];
				}
			} else {
				$header[] = $p;
			}
		}
		$csv = '"' . implode(
			separator: '","',
			array: $header
		) . '"' . PHP_EOL;
		$blankStr = '';
		foreach ($paramArray as $r => $p) {
			if ($r === 'CSV') {
				$indexCount = count(
					value: $header
				);
				for ($index = 1; $index < $indexCount; $index++) {
					$blankStr = ',""';
				}
			}
			$csv .= "{$r}{$blankStr}" . PHP_EOL;
		}

		$filename = date('Ymd-His') . '-import-sample.csv';
		$headerArray = [];
		// Export header
		$headerArray['Content-type'] = 'text/csv';
		$headerArray['Content-Disposition'] = "attachment; filename={$filename}";
		$headerArray['Pragma'] = 'no-cache';
		$headerArray['Expires'] = '0';

		return [$headerArray, $csv, HttpStatus::$Ok];
	}

	/**
	 * Generate sample CSV helper
	 * 
	 * @param string $module
	 * @param array  $explainParamArray
	 * 
	 * @return array
	 */
	private function genCsvHelper(
		$module,
		$explainParamArray
	): array {
		$headerCsvArray = [];
		foreach ($explainParamArray as $explainParamKey => &$explainParamKeyValue) {
			if (isset($explainParamKeyValue['dataType'])) {
				$columnHeader = "{$module}:{$explainParamKey}";
				$headerCsvArray[$module][] = $columnHeader;
			} else {
				$_module = "{$module}:{$explainParamKey}";
				$headerArray = $this->genCsvHelper(
					module: $_module,
					explainParamArray: $explainParamKeyValue
				);
				foreach ($headerArray as $headerKey => &$headerKeyValue) {
					$headerCsvArray[$headerKey] = $headerKeyValue;
				}
			}
		}

		return $headerCsvArray;
	}

	/**
	 * Basic Read Processes for process Function
	 * 
	 * @param array $sqlConfig         Sql config
	 * @param bool  $maintainHierarchy If true - Uses parent payload/results in child
	 * 
	 * @return array
	 */
	private function readBasics(
		&$sqlConfig,
		&$maintainHierarchy
	) {
		// Load Sql
		$sqlConfig = $this->httpObject->httpRequestObject->routeParserObject->sqlConfig;

		// Rate Limiting request if configured for Route Sql.
		$this->rateLimitRoute(
			sqlConfig: $sqlConfig
		);

		// Check for configured referrer Lags
		$this->checkReferrerLag(
			sqlConfig: $sqlConfig
		);

		// Use results in where clause of sub queries recursively
		$maintainHierarchy = $this->getMaintainHierarchy(
			sqlConfig: $sqlConfig
		);

		if (
			$this->httpObject->httpRequestObject->routeParserObject->routeEndingWithReservedKeywordFlag
			&& $this->httpObject->httpRequestObject->routeParserObject->routeEndingReservedKeyword === Env::$explainRequestRouteKeyword
			&& CommonFunction::isEnabled(
				httpObject: $this->httpObject,
				feature: 'customer_enabled_explain_request'
			)
		) {
			return $this->explain(
				sqlConfig: $sqlConfig,
				maintainHierarchy: $maintainHierarchy
			);
		}

		return false;
	}

	/**
	 * Basic Write Processes for process Function (Supplement is considered as Write)
	 * 
	 * @param array $sqlConfig         Sql config
	 * @param bool  $maintainHierarchy If true - Uses parent payload/results in child
	 * 
	 * @return bool
	 */
	private function writeBasics(
		&$sqlConfig,
		&$maintainHierarchy
	): bool {
		// Load Sql
		$sqlConfig = $this->httpObject->httpRequestObject->routeParserObject->sqlConfig;

		// Lag response
		$this->lagResponse(
			sqlConfig: $sqlConfig
		);

		// Check for configured referrer Lags
		$this->checkReferrerLag(
			sqlConfig: $sqlConfig
		);

		// Rate Limiting request if configured for Route Sql.
		$this->rateLimitRoute(
			sqlConfig: $sqlConfig
		);

		// Use results in where clause of sub queries recursively
		$maintainHierarchy = $this->getMaintainHierarchy(
			sqlConfig: $sqlConfig
		);

		if (
			$this->httpObject->httpRequestObject->routeParserObject->routeEndingWithReservedKeywordFlag
			&& $this->httpObject->httpRequestObject->routeParserObject->routeEndingReservedKeyword === Env::$explainRequestRouteKeyword
			&& CommonFunction::isEnabled(
				httpObject: $this->httpObject,
				feature: 'customer_enabled_explain_request'
			)
		) {
			return $this->explain(
				sqlConfig: $sqlConfig,
				maintainHierarchy: $maintainHierarchy
			);
		}

		if (
			$this->httpObject->httpRequestObject->routeParserObject->routeEndingWithReservedKeywordFlag
			&& $this->httpObject->httpRequestObject->routeParserObject->routeEndingReservedKeyword === Env::$importSampleRequestRouteKeyword
		) {
			return $this->generateImportSampleCsv(
				sqlConfig: $sqlConfig,
				maintainHierarchy: $maintainHierarchy
			);
		}

		return false;
	}

	/**
	 * Get results to be cached flag
	 * 
	 * @param array $sqlConfig Sql config
	 * 
	 * @return bool
	 */
	private function getToBeCached(
		$sqlConfig
	): bool {
		$toBeCached = false;
		if (
			CommonFunction::isEnabled(
				httpObject: $this->httpObject,
				feature: 'customer_enabled_response_caching'
			)
			&& isset($sqlConfig['__CACHE-KEY__'])
			&& !isset($this->httpObject->httpRequestObject->activeRequestData['queryParamArray']['orderBy'])
		) {
			$cacheReqCount = 0;
			$queryCacheReqFlag = false;
			for ($index = 0;$index < 5; $index++) {
				$json = $this->httpObject->httpRequestObject->customerQueryCacheObject->queryCacheGet(
					customerId: $this->httpObject->httpRequestObject->customerId,
					queryCacheKey: $sqlConfig['__CACHE-KEY__']
				);
				if ($json !== Constant::$NULL) {
					$cacheHit = 'true';
					$this->httpObject->httpResponseObject->dataEncodeObject->appendKeyData(
						objectKey: 'cacheHit',
						data: $cacheHit
					);
					$this->httpObject->httpResponseObject->dataEncodeObject->appendData(
						data: $json
					);
					return true;
				} else {
					if (!$queryCacheReqFlag) {
						$cacheReqCount = $this->httpObject->httpRequestObject->customerQueryCacheObject->queryCacheIncrement(
							customerId: $this->httpObject->httpRequestObject->customerId,
							queryCacheKey: $sqlConfig['__CACHE-KEY__']
						);
						if ($cacheReqCount === 1) {
							$toBeCached = true;
							break;
						} else {
							$queryCacheReqFlag = true;
						}
					}
					if ($queryCacheReqFlag) {
						sleep(1);
					}
				}
			}
			if (
				$queryCacheReqFlag
				&& $cacheReqCount > 1
			) {
				throw new \Exception(
					message: 'Invalid query cache request flag',
					code: HttpStatus::$InternalServerError
				);
			}
		}

		return $toBeCached;
	}

	/**
	 * Explain configuration
	 * 
	 * @param array $sqlConfig         Sql config
	 * @param bool  $maintainHierarchy If true - Uses parent payload/results in child
	 * 
	 * @return bool
	 */
	private function explain(
		&$sqlConfig,
		$maintainHierarchy
	): bool {
		$this->dataEncodeObject->startObject(
			objectKey: 'Config'
		);
		$this->dataEncodeObject->addKeyData(
			objectKey: 'Route',
			data: $this->httpObject->httpRequestObject->routeParserObject->configuredRoute
		);
		$this->dataEncodeObject->addKeyData(
			objectKey: 'Payload',
			data: $this->getExplainParam(
				sqlConfig: $sqlConfig,
				maintainHierarchy: $maintainHierarchy,
				isFirstCall: Constant::$TRUE
			)
		);
		$this->dataEncodeObject->endObject();

		return true;
	}

	/**
	 * Get Payload Key
	 * 
	 * @param array $payloadKeyArray Payload Key Array
	 * 
	 * @return null|string
	 */
	private function getPayloadKey(
		$payloadKeyArray
	): null|string {
		return (
			is_array(
				value: $payloadKeyArray
			)
			&& count(
				value: $payloadKeyArray
			) > 0
		) ? trim(
			string: implode(
				separator: ':',
				array: $payloadKeyArray
			),
			characters: ':'
		) : null;
	}
}
