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
	 * @param array $sqlConfig             Sql config
	 * @param bool  $followSqlConfigThread Follow Sql Config Thread
	 * @param bool  $isFirstCall           true to represent the first call in recursion
	 * 
	 * @return array
	 * @throws \Exception
	 */
	private function getRequired(
		&$sqlConfig,
		$followSqlConfigThread,
		$isFirstCall
	): array {
		$requiredFieldArray = [];

		foreach (['__PAYLOAD__', '__SET__', '__WHERE__'] as $option) {
			if (isset($sqlConfig[$option])) {
				foreach ($sqlConfig[$option] as $sqlParamConfig) {
					$activeDataKey = $sqlParamConfig['activeDataKey'];
					if ($activeDataKey === 'function') {
						continue;
					}
					$isRequired = isset($sqlParamConfig['isRequired'])
						? $sqlParamConfig['isRequired'] : Constant::$FALSE;

					if ($isRequired) {
						$activeDataKeySubKey = $sqlParamConfig['activeDataKeySubKey'];

						if (!isset($requiredFieldArray[$activeDataKey])) {
							$requiredFieldArray[$activeDataKey] = [];
						}
						if (
							!in_array(
								needle: $activeDataKeySubKey,
								haystack: $requiredFieldArray[$activeDataKey],
								strict: Constant::$TRUE
							)
						) {
							$requiredFieldArray[$activeDataKey][] = $activeDataKeySubKey;
						}
					}
				}
			}
		}

		// Check for Sql Thread setting
		$foundFollowSqlConfigThread = Constant::$FALSE;
		if (isset($sqlConfig['__WHERE__'])) {
			foreach ($sqlConfig['__WHERE__'] as $sqlParamConfig) {
				$activeDataKey = $sqlParamConfig['activeDataKey'];
				$activeDataKeySubKey = $sqlParamConfig['activeDataKeySubKey'];

				if (
					$isFirstCall
					&& in_array(
						needle: $activeDataKey,
						haystack: ['sqlResults', 'sqlParamArray', 'previousPayload'],
						strict: Constant::$TRUE
					)
				) {
					throw new \Exception(
						message: "First query can not have {$activeDataKey} config",
						code: HttpStatus::$InternalServerError
					);
				}
				if (
					in_array(
						needle: $activeDataKey,
						haystack: ['sqlResults', 'sqlParamArray', 'previousPayload'],
						strict: Constant::$TRUE
					)
				) {
					$foundFollowSqlConfigThread = Constant::$TRUE;
					break;
				}
			}
			// if (
			// 	!$isFirstCall
			// 	&& $followSqlConfigThread
			// 	&& !$foundFollowSqlConfigThread
			// ) {
			//     throw new \Exception(
			//          message: 'Invalid config: missing ' . $activeDataKey,
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
						$followSqlConfigThread = ($followSqlConfigThread) ?? $this->getFollowSqlConfigThread(
							sqlConfig: $moduleSqlConfig
						);
						$moduleRequiredFieldArray = $this->getRequired(
							sqlConfig: $moduleSqlConfig,
							followSqlConfigThread: $followSqlConfigThread,
							isFirstCall: Constant::$FALSE
						);
						if ($followSqlConfigThread) {
							$requiredFieldArray[$module] = $moduleRequiredFieldArray;
						} else {
							foreach ($moduleRequiredFieldArray as $activeDataKey => &$activeDataKeySubKeyArray) {
								if (!isset($requiredFieldArray[$activeDataKey])) {
									$requiredFieldArray[$activeDataKey] = [];
								}
								foreach ($activeDataKeySubKeyArray as $activeDataKeySubKey) {
									if (
										!in_array(
											needle: $activeDataKeySubKey,
											haystack: $requiredFieldArray[$activeDataKey],
											strict: Constant::$TRUE
										)
									) {
										$requiredFieldArray[$activeDataKey][] = $activeDataKeySubKey;
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
	public function validate(
		&$validationConfig
	): array {
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
	 * @param array      $payload         Payload
	 * @param array|null $payloadKeyArray Payload key's
	 * 
	 * @return array
	 */
	private function getSqlAndParamNamedMode(
		&$sqlConfig,
		&$payload,
		$payloadKeyArray = null
	): array {
		$insertId = Constant::$NULL;
		$sql = '';
		/*!999999 comment goes here */
		if (isset($sqlConfig['__SQL-COMMENT__'])) {
			$sql .= '/' . '*!999999 ';
			$sql .= $sqlConfig['__SQL-COMMENT__'];
			$sql .= ' */';
		}
		switch (Constant::$TRUE) {
			case isset($sqlConfig['__SQL__']):
				$sql .= $sqlConfig['__SQL__'];
				break;
			case isset($sqlConfig['__EXPORT__']):
				$sql .= $sqlConfig['__EXPORT__'];
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
			[
				$setParamArray,
				$errorArray
			] = $this->getSqlParam(
				sqlConfig: $sqlConfig['__SET__'],
				sqlConfigVariables: $sqlConfig['__VARIABLE__'] ?? [],
				payload: $payload
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
						&& Env::$ENABLE_SYSTEM_LEVEL_PRIMARY_KEY
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
			[
				$whereParamArray,
				$wErrorArray
			] = $this->getSqlParam(
				sqlConfig: $sqlConfig['__WHERE__'],
				sqlConfigVariables: $sqlConfig['__VARIABLE__'] ?? [],
				payload: $payload
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
				$errorArray = array_merge(
					$errorArray,
					$wErrorArray
				);
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
				activeDataKey: 'sqlParamArray',
				payloadKeyArray: $payloadKeyArray,
				record: $record
			);
		}

		return [
			$insertId,
			$sql,
			$paramArray,
			$errorArray
		];
	}

	/**
	 * Generate Sql query and its param's in Unnamed format
	 * 
	 * @param array      $sqlConfig       Sql config
	 * @param array      $payload         Payload
	 * @param array|null $payloadKeyArray Payload key's
	 * 
	 * @return array
	 */
	private function getSqlAndParamUnnamedMode(
		&$sqlConfig,
		&$payload,
		$payloadKeyArray = null
	): array {
		$insertId = Constant::$NULL;
		$sql = '';
		/*!999999 comment goes here */
		if (isset($sqlConfig['__SQL-COMMENT__'])) {
			$sql .= '/' . '*!999999 ';
			$sql .= $sqlConfig['__SQL-COMMENT__'];
			$sql .= ' */';
		}
		switch (Constant::$TRUE) {
			case isset($sqlConfig['__SQL__']):
				$sql .= $sqlConfig['__SQL__'];
				break;
			case isset($sqlConfig['__EXPORT__']):
				$sql .= $sqlConfig['__EXPORT__'];
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
			[
				$setParamArray,
				$errorArray
			] = $this->getSqlParam(
				sqlConfig: $sqlConfig['__SET__'],
				sqlConfigVariables: $sqlConfig['__VARIABLE__'] ?? [],
				payload: $payload
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
						&& Env::$ENABLE_SYSTEM_LEVEL_PRIMARY_KEY
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
			[
				$whereParamArray,
				$wErrorArray
			] = $this->getSqlParam(
				sqlConfig: $sqlConfig['__WHERE__'],
				sqlConfigVariables: $sqlConfig['__VARIABLE__'] ?? [],
				payload: $payload
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
				$errorArray = array_merge(
					$errorArray,
					$wErrorArray
				);
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
				activeDataKey: 'sqlParamArray',
				payloadKeyArray: $payloadKeyArray,
				record: $record
			);
		}

		return [
			$insertId,
			$sql,
			$paramArray,
			$errorArray
		];
	}

	/**
	 * Generates ParamArray for statement to execute
	 * 
	 * @param array $sqlConfig          Sql config
	 * @param array $sqlConfigVariables Payload Variables
	 * @param array $payload.           Payload
	 * 
	 * @return array
	 * @throws \Exception
	 */
	private function getSqlParam(
		&$sqlConfig,
		$sqlConfigVariables,
		&$payload
	): array {
		$paramArray = [];
		$errorArray = [];

		// Collect param values as per config respectively
		foreach ($sqlConfig as $sqlParamConfig) {
			$column = $sqlParamConfig['column'];
			$activeDataKey = $sqlParamConfig['activeDataKey'];
			$activeDataKeySubKey = $sqlParamConfig['activeDataKeySubKey'];
			if ($activeDataKey === 'function') {
				$function = $activeDataKeySubKey;
				$value = $function(
					$this->httpObject->httpReqData['active'],
					$payload
				);
				$paramArray[$column] = $value;
				continue;
			} elseif (
				in_array(
					needle: $activeDataKey,
					haystack: ['sqlParamArray', 'previousPayload'],
					strict: Constant::$TRUE
				)
			) {
				if (!isset($this->httpObject->httpReqData['active'][$activeDataKey])) {
					$errorArray[] = "Missing key '{$activeDataKeySubKey}' in '{$activeDataKey}'";
					continue;
				}
				$value = $this->httpObject->httpReqData['active'][$activeDataKey];
				$break = Constant::$FALSE;
				foreach (
					explode(
						separator: ':',
						string: $activeDataKeySubKey
					) as $_activeDataKeySubKey
				) {
					if (isset($value[$_activeDataKeySubKey])) {
						$value = &$value[$_activeDataKeySubKey];
						continue;
					}
					$errorArray[] = "Missing '{$activeDataKey}' for '{$_activeDataKeySubKey}'";
					$break = Constant::$TRUE;
					break;
				}
				if (!$break) {
					$paramArray[$column] = $value;
				}
				continue;
			} elseif ($activeDataKey === 'sqlResults') {
				if (!isset($this->httpObject->httpReqData['active'][$activeDataKey])) {
					$errorArray[] = "Missing '{$activeDataKey}'";
					continue;
				}
				$activeDataKeySubKeyArray = explode(
					separator: ':',
					string: $activeDataKeySubKey
				);
				$value = $this->httpObject->httpReqData['active'][$activeDataKey];
				foreach ($activeDataKeySubKeyArray as $_activeDataKeySubKey) {
					if (isset($value[$_activeDataKeySubKey])) {
						$value = &$value[$_activeDataKeySubKey];
						continue;
					}
					$errorArray[] = "Missing '{$activeDataKey}' for '{$_activeDataKeySubKey}'";
					break;
				}
				$paramArray[$column] = $value;
				continue;
			} elseif (
				$activeDataKey === 'payload'
				&& isset($payload[$activeDataKeySubKey])
			) {
				$paramArray[$column] = $payload[$activeDataKeySubKey];
				continue;
			} elseif ($activeDataKey === 'custom') {
				$value = $activeDataKeySubKey;
				$paramArray[$column] = $value;
				continue;
			} elseif ($activeDataKey === 'variables') {
				if (isset($sqlConfigVariables[$activeDataKeySubKey])) {
					$paramArray[$column] = $sqlConfigVariables[$activeDataKeySubKey];
				} else {
					$errorArray[] = "Missing '{$activeDataKey}' for '{$activeDataKeySubKey}'";
				}
				continue;
			} elseif (isset($this->httpObject->httpReqData['active'][$activeDataKey][$activeDataKeySubKey])) {
				if (
					isset($this->httpObject->httpReqData['active']['requiredFieldArray'][$activeDataKey])
					&& in_array(
						needle: $activeDataKeySubKey,
						haystack: $this->httpObject->httpReqData['active']['requiredFieldArray'][$activeDataKey],
						strict: Constant::$TRUE
					)
				) {
					if (isset($sqlParamConfig['dataType'])) {
						if (
							!DatabaseServerDataType::validateDataType(
								data: $this->httpObject->httpReqData['active'][$activeDataKey][$activeDataKeySubKey],
								dataType: $sqlParamConfig['dataType']
							)
						) {
							$errorArray[] = "Invalid required field data-type of '{$activeDataKey}' for '{$activeDataKeySubKey}'";
							continue;
						}
					}
				}
				$paramArray[$column] = $this->httpObject->httpReqData['active'][$activeDataKey][$activeDataKeySubKey];
				continue;
			} elseif (
				isset($this->httpObject->httpReqData['active']['requiredFieldArray'][$activeDataKey])
				&& in_array(
					needle: $activeDataKeySubKey,
					haystack: $this->httpObject->httpReqData['active']['requiredFieldArray'][$activeDataKey],
					strict: Constant::$TRUE
				)
			) {
				$errorArray[] = "Missing required field '{$activeDataKey}' for '{$activeDataKeySubKey}'";
				continue;
			} else {
				$errorArray[] = "Invalid configuration of '{$activeDataKey}' for '{$activeDataKeySubKey}'";
				continue;
			}
		}

		return [
			$paramArray,
			$errorArray
		];
	}

	/**
	 * Function to find array is associative/simple array
	 * 
	 * @param array $arr Array to search for associative/simple array
	 * 
	 * @return bool
	 */
	private function isObject(
		&$arr
	): bool {
		$isObject = Constant::$FALSE;

		$index = 0;
		foreach ($arr as $key => &$value) {
			if ($key !== $index++) {
				$isObject = Constant::$TRUE;
				break;
			}
		}

		return $isObject;
	}

	/**
	 * Use results in where clause of sub queries recursively
	 * 
	 * @param array $sqlConfig Sql config
	 * 
	 * @return bool
	 */
	private function getFollowSqlConfigThread(
		&$sqlConfig
	): bool {
		if (
			isset($sqlConfig['__FOLLOW-SQL_CONFIG-THREAD__'])
			&& $sqlConfig['__FOLLOW-SQL_CONFIG-THREAD__'] === Constant::$TRUE
		) {
			return Constant::$TRUE;
		}
		return Constant::$FALSE;
	}

	/**
	 * Return explain params recursively
	 * 
	 * @param array $sqlConfig             Sql config
	 * @param bool  $followSqlConfigThread Follow Sql Config Thread
	 * @param bool  $isFirstCall           Flag to check if this is first request
	 * 
	 * @return array
	 * @throws \Exception
	 */
	private function getExplainParam(
		&$sqlConfig,
		$followSqlConfigThread,
		$isFirstCall
	): array {
		$explainParamArray = [];

		if (isset($sqlConfig['__COUNT-SQL__'])) {
			$sqlConfig['__CONFIG__'][] = [
				'column' => 'page',
				'activeDataKey' => 'queryParamArray',
				'activeDataKeySubKey' => 'page',
				'dataType' => DatabaseServerDataType::$INT,
				'isRequired' => Constant::$REQUIRED
			];
			$sqlConfig['__CONFIG__'][] = [
				'column' => 'perPage',
				'activeDataKey' => 'queryParamArray',
				'activeDataKeySubKey' => 'perPage',
				'dataType' => DatabaseServerDataType::$INT
			];

			foreach ($sqlConfig['__CONFIG__'] as $sqlParamConfig) {
				$activeDataKey = $sqlParamConfig['activeDataKey'];
				$activeDataKeySubKey = $sqlParamConfig['activeDataKeySubKey'];
				$dataType = isset($sqlParamConfig['dataType'])
					? $sqlParamConfig['dataType'] : DatabaseServerDataType::$Default;
				$isRequired = isset($sqlParamConfig['isRequired'])
					? $sqlParamConfig['isRequired'] : Constant::$FALSE;

				if (
					isset($explainParamArray[$activeDataKeySubKey])
					&& $explainParamArray[$activeDataKeySubKey]['isRequired'] === Constant::$TRUE
				) {
					continue;
				}
				$dataType['isRequired'] = $isRequired ? Constant::$TRUE : Constant::$FALSE;
				$explainParamArray[$activeDataKeySubKey] = $dataType;
			}
		}

		foreach (['__PAYLOAD__', '__SET__', '__WHERE__'] as $option) {
			if (isset($sqlConfig[$option])) {
				foreach ($sqlConfig[$option] as $sqlParamConfig) {
					$activeDataKey = $sqlParamConfig['activeDataKey'];
					$activeDataKeySubKey = $sqlParamConfig['activeDataKeySubKey'];
					$dataType = isset($sqlParamConfig['dataType'])
						? $sqlParamConfig['dataType'] : DatabaseServerDataType::$Default;
					$isRequired = isset($sqlParamConfig['isRequired'])
						? $sqlParamConfig['isRequired'] : Constant::$FALSE;

					if ($activeDataKey !== 'payload') {
						continue;
					}
					if (
						isset($explainParamArray[$activeDataKeySubKey])
						&& $explainParamArray[$activeDataKeySubKey]['isRequired'] === Constant::$TRUE
					) {
						continue;
					}
					$dataType['isRequired'] = $isRequired ? Constant::$TRUE : Constant::$FALSE;
					$explainParamArray[$activeDataKeySubKey] = $dataType;
				}
			}
		}

		// Check for Sql Thread
		$foundFollowSqlConfigThread = Constant::$FALSE;
		if (isset($sqlConfig['__WHERE__'])) {
			foreach ($sqlConfig['__WHERE__'] as $sqlParamConfig) {
				$activeDataKey = $sqlParamConfig['activeDataKey'];
				$activeDataKeySubKey = $sqlParamConfig['activeDataKeySubKey'];
				if (
					in_array(
						needle: $activeDataKey,
						haystack: ['sqlResults', 'sqlParamArray', 'previousPayload'],
						strict: Constant::$TRUE
					)
				) {
					$foundFollowSqlConfigThread = Constant::$TRUE;
					break;
				}
			}
			if (
				!$isFirstCall
				&& $followSqlConfigThread
				&& !$foundFollowSqlConfigThread
			) {
				throw new \Exception(
					message: 'Invalid config: missing ' . $activeDataKey,
					code: HttpStatus::$InternalServerError
				);
			}
		}

		// Check in subQuery//'__SUB-CONFIG__'
		foreach (['__SUB-CONFIG__', '__SUB-CONFIG__'] as $option) {
			if (isset($sqlConfig[$option])) {
				foreach ($sqlConfig[$option] as $module => &$moduleSqlConfig) {
					$followSqlConfigThread = ($followSqlConfigThread) ?? $this->getFollowSqlConfigThread(
						sqlConfig: $moduleSqlConfig
					);
					$moduleExplainParamArray = $this->getExplainParam(
						sqlConfig: $moduleSqlConfig,
						followSqlConfigThread: $followSqlConfigThread,
						isFirstCall: Constant::$FALSE
					);
					if ($followSqlConfigThread) {
						if (!empty($moduleExplainParamArray)) {
							$explainParamArray[$module] = $moduleExplainParamArray;
						}
					} else {
						foreach ($moduleExplainParamArray as $activeDataKeySubKey => $field) {
							if (!isset($explainParamArray[$activeDataKeySubKey])) {
								$explainParamArray[$activeDataKeySubKey] = $field;
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
	 * @param string $activeDataKey   sqlResults / sqlParamArray / previousPayload
	 * @param array  $payloadKeyArray Module key's in recursion
	 * @param array  $record          Record data fetched from DB
	 * 
	 * @return void
	 */
	private function resetFetchData(
		$activeDataKey,
		$payloadKeyArray,
		$record
	): void {
		if (
			empty($payloadKeyArray)
			|| count(
				value: $payloadKeyArray
			) === 0
		) {
			$this->httpObject->httpReqData['active'][$activeDataKey] = [];
			$this->httpObject->httpReqData['active'][$activeDataKey]['return'] = [];
		}
		$httpReq = &$this->httpObject->httpReqData['active'][$activeDataKey]['return'];
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
	private function rateLimitRoute(
		&$sqlConfig
	): void {
		$this->httpObject->rateLimiter->rateLimitFeature(
			feature: 'USER_ROUTE'
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
	private function checkReferrerLag(
		&$sqlConfig
	): void {
		$customerUserId = 0;
		if (isset($this->httpObject->httpReqData['active']['customerUserId'])) {
			$customerUserId = $this->httpObject->httpReqData['active']['customerUserId'];
		}
		$customerUserReferrerLagKey = CacheServerKey::customerUserReferrerLag(
			customerId: $this->httpObject->httpReqData['active']['customerId'],
			customerUserId: $customerUserId
		);
		if (
			isset($sqlConfig['referrerLagWindow'])
			&& count(
				value: $sqlConfig['referrerLagWindow']
			) > 0
		) {
			if (
				!$this->httpObject->cache->cacheExist(
					cacheKey: $customerUserReferrerLagKey
				)
			) {
				throw new \Exception(
					message: 'Referrer lag not initiated',
					code: HttpStatus::$BadRequest
				);
			}
			$referrerLagData = $this->httpObject->cache->cacheGet(
				cacheKey: $customerUserReferrerLagKey
			);
			if (
				isset($referrerLagData['initRoute'])
				&& isset($referrerLagData['timestamp'])
			) {
				$found = Constant::$FALSE;
				foreach ($sqlConfig['referrerLagWindow'] as $referrerSqlConfig) {
					if ($referrerLagData['initRoute'] === $referrerSqlConfig['referrer']) {
						$tsDiff = Env::$timestamp - $referrerSqlConfig['timestamp'];
						if (
							isset($referrerSqlConfig['minimumReferrerLagWindow'])
							&& $tsDiff >= $referrerSqlConfig['minimumReferrerLagWindow']
						) {
							if (isset($referrerSqlConfig['maximumReferrerLagWindow'])) {
								if ($tsDiff <= $referrerSqlConfig['maximumReferrerLagWindow']) {
									$found = Constant::$TRUE;
								} else {
									$this->httpObject->cache->cacheDelete(
										cacheKey: $customerUserReferrerLagKey
									);
								}
							} else {
								$found = Constant::$TRUE;
							}
						} else {
							$this->httpObject->cache->cacheDelete(
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
				!$this->httpObject->cache->cacheExist(
					cacheKey: $customerUserReferrerLagKey
				)
			) {
				$this->httpObject->cache->cacheSet(
					cacheKey: $customerUserReferrerLagKey,
					cacheValue: [
						'initRoute' => $this->httpObject->request->routeParser->configuredRoute,
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
		$hashKey = Constant::$NULL;
		$hashJson = Constant::$NULL;
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
					'SECRET' => $this->httpObject->Env->SECRET,
					'idempotentWindow' => $idempotentWindow,
					'httpRequestIp' => $this->httpObject->httpReqData['server']['httpRequestIp'],
					'customerId' => $this->httpObject->httpReqData['active']['customerId'],
					'customerUserId' => $this->httpObject->httpReqData['active']['customerUserId'],
					'httpRequestMethod' => $this->httpObject->httpReqData['server']['httpRequestMethod'],
					'Route' => $this->httpObject->httpReqData['get'][ROUTE_URL_PARAM],
					'payload' => $this->httpObject->request->decodeObject->get(
						keyString: $this->getPayloadKey(
							payloadKeyArray: $payloadKeyArray
						)
					)
				];
				if (isset($this->httpObject->httpReqData['active']['customerUserData'])) {
					$payloadSignature['customerUserGroupId'] = ($this->httpObject->httpReqData['active']['customerUserData']['customer_user_group_id'] !== Constant::$NULL
						? $this->httpObject->httpReqData['active']['customerUserData']['customer_user_group_id'] : 0);
					$payloadSignature['customerUserId'] = ($this->httpObject->httpReqData['active']['customerUserId'] !== Constant::$NULL
						? $this->httpObject->httpReqData['active']['customerUserId'] : 0);
				}

				$hash = json_encode(
					value: $payloadSignature
				);
				$hashKey = md5(
					string: $hash
				);
				if (
					(
						$this->httpObject->request->isPrivateWebRequest
						|| $this->httpObject->request->isPrivateApiRequest
					) && $this->httpObject->cache->cacheExist(
						cacheKey: $hashKey
					)
				) {
					$hashJson = str_replace(
						search: 'JSON',
						replace: json_encode(
							value: $this->httpObject->cache->cacheGet(
								cacheKey: $hashKey
							)
						),
						subject: '{"Idempotent": JSON, "Status": 200}'
					);
				}
			}
		}

		return [
			$idempotentWindow,
			$hashKey,
			$hashJson
		];
	}

	/**
	 * Lag response
	 * 
	 * @param array $sqlConfig Sql config
	 * 
	 * @return void
	 */
	private function lagResponse(
		&$sqlConfig
	): void {
		if (
			$this->httpObject->request->isPublicRequest
			|| !isset($sqlConfig['responseLag'])
		) {
			return;
		}

		$payloadSignature = [
			'httpRequestIp' => $this->httpObject->httpReqData['server']['httpRequestIp'],
			'customerId' => $this->httpObject->httpReqData['active']['customerId'],
			'httpRequestMethod' => $this->httpObject->httpReqData['server']['httpRequestMethod'],
			'Route' => $this->httpObject->httpReqData['get'][ROUTE_URL_PARAM],
		];
		if (isset($this->httpObject->httpReqData['active']['customerUserData'])) {
			$payloadSignature['customerUserGroupId'] = ($this->httpObject->httpReqData['active']['customerUserData']['customer_user_group_id'] !== Constant::$NULL
				? $this->httpObject->httpReqData['active']['customerUserData']['customer_user_group_id'] : 0);
			$payloadSignature['customerUserId'] = ($this->httpObject->httpReqData['active']['customerUserId'] !== Constant::$NULL
				? $this->httpObject->httpReqData['active']['customerUserId'] : 0);
		}

		$hash = json_encode(
			value: $payloadSignature
		);
		$hashKey = 'LAG:' . md5(
			string: $hash
		);

		if (
			$this->httpObject->cache->cacheExist(
				cacheKey: $hashKey
			)
		) {
			$currentNoOfRequest = $this->httpObject->cache->cacheGet(
				cacheKey: $hashKey
			);
		} else {
			$currentNoOfRequest = 0;
		}

		$this->httpObject->cache->cacheSet(
			cacheKey: $hashKey,
			cacheValue: ++$noOfRequest,
			cacheExpire: $sqlConfig['responseLagWindow']
		);

		$lagResponse = 0;
		$responseLagArray = &$sqlConfig['responseLag'];
		if (
			is_array(
				value: $responseLagArray
			)
		) {
			foreach ($responseLagArray as $responseLag) {
				if ($currentNoOfRequest > $responseLag['requestCount']) {
					$lagResponse = $responseLag['lagResponse'];
				}
			}
		}

		if ($lagResponse > 0) {
			sleep(
				seconds: $lagResponse
			);
		}
	}

	/**
	 * Get Trigger data
	 * 
	 * @param array $triggerConfig Trigger Config
	 * @param array $payload       Payload
	 * 
	 * @return mixed
	 */
	public function getTriggerData(
		&$triggerConfig,
		&$payload
	): mixed {
		if (!isset($this->httpObject->httpReqData['active']['authId'])) {
			throw new \Exception(
				message: 'Missing auth id',
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
			$isObject = Constant::$TRUE;
		}

		$triggerOutput = [];
		if ($isObject) {
			$httpReqData = $this->getTriggerHttp(
				triggerConfig: $triggerConfig,
				payload: $payload
			);
			[
				$responseHeaderArray,
				$responseContent,
				$responseCode
			] = Start::http(
				httpReqData: $httpReqData
			);
			$triggerOutput = &$responseContent;
		} else {
			$iTriggerCount = count(
				value: $triggerConfig
			);
			for ($iTrigger = 0; $iTrigger < $iTriggerCount; $iTrigger++) {
				$httpReqData = $this->getTriggerHttp(
					triggerConfig: $triggerConfig[$iTrigger],
					payload: $payload
				);
				[
					$responseHeaderArray,
					$responseContent,
					$responseCode
				] = Start::http(
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
	 * @param array $payload       Payload
	 * 
	 * @return mixed
	 */
	public function getTriggerHttp(
		&$triggerConfig,
		&$payload
	): mixed {
		$httpRequestMethod = $triggerConfig['__METHOD__'];
		[
			$routeElementArrayArray,
			$errorArray
		] = $this->getTriggerParam(
			payloadConfig: $triggerConfig['__ROUTE__'],
			payload: $payload
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
			[
				$queryStringArray,
				$errorArray
			] = $this->getTriggerParam(
				payloadConfig: $triggerConfig['__QUERY-STRING__'],
				payload: $payload
			);

			if ($errorArray) {
				return $errorArray;
			}
		}
		if (isset($triggerConfig['__PAYLOAD__'])) {
			[
				$payloadArray,
				$errorArray
			] = $this->getTriggerParam(
				payloadConfig: $triggerConfig['__PAYLOAD__'],
				payload: $payload
			);
			if ($errorArray) {
				return $errorArray;
			}
		}

		$httpReqData['streamData'] = Constant::$FALSE;
		$httpReqData['server']['domainName'] = $this->httpObject->httpReqData['server']['domainName'];
		$httpReqData['server']['httpRequestMethod'] = $httpRequestMethod;
		$httpReqData['server']['httpRequestIp'] = $this->httpObject->httpReqData['server']['httpRequestIp'];
		$httpReqData['header'] = $this->httpObject->httpReqData['header'];
		$httpReqData['post'] = json_encode(
			value: $payloadArray
		);
		$httpReqData['get'] = $queryStringArray;
		$httpReqData['get'][ROUTE_URL_PARAM] = $route;
		$httpReqData['isWebRequest'] = Constant::$FALSE;

		return $httpReqData;
	}

	/**
	 * Get Trigger param's
	 * 
	 * @param array $payloadConfig API Payload configuration
	 * @param array $payload       Payload
	 * 
	 * @return array
	 * @throws \Exception
	 */
	private function getTriggerParam(
		&$payloadConfig,
		&$payload
	): array {
		$triggerParamArray = [];
		$triggerErrorArray = [];

		// Collect param values as per config respectively
		foreach ($payloadConfig as &$payloadParamConfig) {
			$column = $payloadParamConfig['column'] ?? Constant::$NULL;

			$activeDataKey = $payloadParamConfig['activeDataKey'];
			$activeDataKeySubKey = $payloadParamConfig['activeDataKeySubKey'];
			if ($activeDataKey === 'function') {
				$function = $activeDataKeySubKey;
				$value = $function(
					$this->httpObject->httpReqData['active'],
					$payload
				);
				if ($column === Constant::$NULL) {
					$triggerParamArray[] = $value;
				} else {
					$triggerParamArray[$column] = $value;
				}
				continue;
			} elseif (
				in_array(
					needle: $activeDataKey,
					haystack: ['sqlResults', 'sqlParamArray', 'previousPayload'],
					strict: Constant::$TRUE
				)
			) {
				$activeDataKeySubKeyArray = explode(
					separator: ':', string: $activeDataKeySubKey
				);
				$value = $this->httpObject->httpReqData['active'][$activeDataKey];
				foreach ($activeDataKeySubKeyArray as $_activeDataKeySubKey) {
					if (!isset($value[$_activeDataKeySubKey])) {
						throw new \Exception(
							message: 'Invalid Sql Thread:  Missing Sql Thread data',
							code: HttpStatus::$InternalServerError
						);
					}
					$value = $value[$_activeDataKeySubKey];
				}
				if ($column === Constant::$NULL) {
					$triggerParamArray[] = $value;
				} else {
					$triggerParamArray[$column] = $value;
				}
				continue;
			} elseif ($activeDataKey === 'custom') {
				$value = $activeDataKeySubKey;
				if ($column === Constant::$NULL) {
					$triggerParamArray[] = $value;
				} else {
					$triggerParamArray[$column] = $value;
				}
				continue;
			} elseif (isset($this->httpObject->httpReqData['active'][$activeDataKey][$activeDataKeySubKey])) {
				$value = $this->httpObject->httpReqData['active'][$activeDataKey][$activeDataKeySubKey];
				if ($column === Constant::$NULL) {
					$triggerParamArray[] = $value;
				} else {
					$triggerParamArray[$column] = $value;
				}
				continue;
			} else {
				$triggerErrorArray[] = "Invalid configuration of '{$activeDataKey}' for '{$activeDataKeySubKey}'";
				continue;
			}
		}

		return [
			$triggerParamArray,
			$triggerErrorArray
		];
	}

	/**
	 * Process import function of configuration
	 * 
	 * @param array $sqlConfig             Sql config
	 * @param bool  $followSqlConfigThread Follow Sql Config Thread
	 * 
	 * @return string
	 */
	private function generateImportSampleCsv(
		&$sqlConfig,
		$followSqlConfigThread
	): string {
		CommonFunction::checkCidr(
			ip: $this->httpObject->httpReqData['server']['httpRequestIp'],
			cidrString: $this->httpObject->httpReqData['active']['customerData']['customer_import_sample_cidr']
		);
		$explainParamArray = $this->getExplainParam(
			sqlConfig: $sqlConfig,
			followSqlConfigThread: $followSqlConfigThread,
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

		return [
			$headerArray,
			$csv,
			HttpStatus::$Ok
		];
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
	 * @param array $sqlConfig             Sql config
	 * @param bool  $followSqlConfigThread Follow Sql Config Thread
	 * 
	 * @return array
	 */
	private function readBasics(
		&$sqlConfig,
		&$followSqlConfigThread
	) {
		// Rate Limiting request if configured for Route Sql.
		$this->rateLimitRoute(
			sqlConfig: $sqlConfig
		);

		// Check for configured referrer Lags
		$this->checkReferrerLag(
			sqlConfig: $sqlConfig
		);

		// Use results in where clause of sub queries recursively
		$followSqlConfigThread = $this->getFollowSqlConfigThread(
			sqlConfig: $sqlConfig
		);

		if (
			$this->httpObject->request->routeParser->routeEndingWithReservedKeywordFlag
			&& $this->httpObject->Env->ENABLE_EXPLAIN
			&& $this->httpObject->request->routeParser->routeEndingReservedKeyword === $this->httpObject->Env->EXPLAIN_KEYWORD
		) {
			return $this->explain(
				sqlConfig: $sqlConfig,
				followSqlConfigThread: $followSqlConfigThread
			);
		}

		return Constant::$FALSE;
	}

	/**
	 * Basic Write Processes for process Function (Supplement is considered as Write)
	 * 
	 * @param array $sqlConfig             Sql config
	 * @param bool  $followSqlConfigThread Follow Sql Config Thread
	 * 
	 * @return bool
	 */
	private function writeBasics(
		&$sqlConfig,
		&$followSqlConfigThread
	): bool {
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
		$followSqlConfigThread = $this->getFollowSqlConfigThread(
			sqlConfig: $sqlConfig
		);

		if (
			$this->httpObject->request->routeParser->routeEndingWithReservedKeywordFlag
			&& $this->httpObject->Env->ENABLE_EXPLAIN
			&& $this->httpObject->request->routeParser->routeEndingReservedKeyword === $this->httpObject->Env->EXPLAIN_KEYWORD
		) {
			return $this->explain(
				sqlConfig: $sqlConfig,
				followSqlConfigThread: $followSqlConfigThread
			);
		}

		if (
			$this->httpObject->request->routeParser->routeEndingWithReservedKeywordFlag
			&& $this->httpObject->request->routeParser->routeEndingReservedKeyword === $this->httpObject->Env->IMPORT_SAMPLE_KEYWORD
		) {
			return $this->generateImportSampleCsv(
				sqlConfig: $sqlConfig,
				followSqlConfigThread: $followSqlConfigThread
			);
		}

		return Constant::$FALSE;
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
		$toBeCached = Constant::$FALSE;
		if (
			(
				$this->httpObject->Env->ENABLE_QUERY_CACHE_FOR_PUBLIC && $this->httpObject->request->isPublicRequest
				|| $this->httpObject->Env->ENABLE_QUERY_CACHE_FOR_PRIVATE_WEB && $this->httpObject->request->isPrivateWebRequest
				|| $this->httpObject->Env->ENABLE_QUERY_CACHE_FOR_PRIVATE_API && $this->httpObject->request->isPrivateApiRequest
			)
			&& isset($sqlConfig['__CACHE-KEY__'])
			&& !isset($this->httpObject->httpReqData['active']['queryParamArray']['orderBy'])
		) {
			$cacheReqCount = 0;
			$queryCacheReqFlag = Constant::$FALSE;
			for ($index = 0;$index < 5; $index++) {
				$json = $this->httpObject->queryCache->queryCacheGet(
					customerId: $this->httpObject->httpReqData['active']['customerId'],
					queryCacheKey: $sqlConfig['__CACHE-KEY__']
				);
				if ($json !== Constant::$NULL) {
					$cacheHit = 'true';
					$this->httpObject->response->encodeObject->appendKeyData(
						objectKey: 'cacheHit',
						data: $cacheHit
					);
					$this->httpObject->response->encodeObject->appendData(
						data: $json
					);
					return Constant::$TRUE;
				} else {
					if (!$queryCacheReqFlag) {
						$cacheReqCount = $this->httpObject->queryCache->queryCacheIncrement(
							customerId: $this->httpObject->httpReqData['active']['customerId'],
							queryCacheKey: $sqlConfig['__CACHE-KEY__']
						);
						if ($cacheReqCount === 1) {
							$toBeCached = Constant::$TRUE;
							break;
						} else {
							$queryCacheReqFlag = Constant::$TRUE;
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
	 * @param array $sqlConfig             Sql config
	 * @param bool  $followSqlConfigThread Follow Sql Config Thread
	 * 
	 * @return bool
	 */
	private function explain(
		&$sqlConfig,
		$followSqlConfigThread
	): bool {
		CommonFunction::checkCidr(
			ip: $this->httpObject->httpReqData['server']['httpRequestIp'],
			cidrString: $this->httpObject->httpReqData['active']['customerData']['customer_explain_cidr']
		);
		$this->encodeObject->startObject(
			objectKey: 'Config'
		);
		$this->encodeObject->addKeyData(
			objectKey: 'Route',
			data: $this->httpObject->request->routeParser->configuredRoute
		);
		$this->encodeObject->addKeyData(
			objectKey: 'HttpMethod',
			data: $this->httpObject->httpReqData['server']['httpRequestMethod']
		);
		$this->encodeObject->addKeyData(
			objectKey: 'Payload',
			data: $this->getExplainParam(
				sqlConfig: $sqlConfig,
				followSqlConfigThread: $followSqlConfigThread,
				isFirstCall: Constant::$TRUE
			)
		);
		$this->encodeObject->endObject();

		return Constant::$TRUE;
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
		) : Constant::$NULL;
	}
}
