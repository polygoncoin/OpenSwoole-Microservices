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
use Microservices\App\DbCommonFunction;
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
	public $validator = null;

	/**
	 * Function to help execute PHP functions enclosed with double quotes
	 *
	 * @param mixed $param Returned values by PHP inbuilt functions
	 *
	 * @return mixed
	 */
	public function execPhpFunc($param): mixed
	{
		return $param;
	}

	/**
	 * Get required payload
	 *
	 * @param array $sqlConfig   SQL config
	 * @param bool  $isFirstCall true to represent the first call in recursion
	 * @param bool  $flag        useHierarchy / useResultSet flag
	 *
	 * @return array
	 * @throws \Exception
	 */
	private function getRequired(&$sqlConfig, $isFirstCall, $flag): array
	{
		$requiredFieldArr = [];

		foreach (['__PAYLOAD__', '__SET__', '__WHERE__'] as $option) {
			if (isset($sqlConfig[$option])) {
				foreach ($sqlConfig[$option] as $sqlParamConfig) {
					$fetchFrom = $sqlParamConfig['fetchFrom'];
					if ($fetchFrom === 'function') {
						continue;
					}
					$isRequired = isset($sqlParamConfig['isRequired'])
						? $sqlParamConfig['isRequired'] : false;

					if ($isRequired) {
						$fetchFromData = $sqlParamConfig['fetchFromData'];

						if (!isset($requiredFieldArr[$fetchFrom])) {
							$requiredFieldArr[$fetchFrom] = [];
						}
						if (!in_array($fetchFromData, $requiredFieldArr[$fetchFrom])) {
							$requiredFieldArr[$fetchFrom][] = $fetchFromData;
						}
					}
				}
			}
		}

		// Check for hierarchy setting
		$foundHierarchy = false;
		if (isset($sqlConfig['__WHERE__'])) {
			foreach ($sqlConfig['__WHERE__'] as $sqlParamConfig) {
				$fetchFrom = $sqlParamConfig['fetchFrom'];
				$fetchFromData = $sqlParamConfig['fetchFromData'];

				if (
					$isFirstCall
					&& in_array(
						needle: $fetchFrom,
						haystack: ['sqlResults', 'sqlParamArr', 'sqlPayload']
					)
				) {
					throw new \Exception(
						message: "First query can not have {$fetchFrom} config",
						code: HttpStatus::$InternalServerError
					);
				}
				if (
					in_array(
						needle: $fetchFrom,
						haystack: ['sqlResults', 'sqlParamArr', 'sqlPayload']
					)
				) {
					$foundHierarchy = true;
					break;
				}
			}
			// if (
			// 	!$isFirstCall
			// 	&& $flag
			// 	&& !$foundHierarchy
			// ) {
			//     throw new \Exception(
			//          message: 'Invalid config: missing ' . $fetchFrom,
			//          code: HttpStatus::$InternalServerError
			//      );
			// }
		}

		// Check in subQuery
		if (
			isset($sqlConfig['__SUB-QUERY__'])
			|| isset($sqlConfig['__SUB-PAYLOAD__'])
		) {
			if (
				isset($sqlConfig['__SUB-QUERY__'])
				&& !$this->isObject(arr: $sqlConfig['__SUB-QUERY__'])
			) {
				throw new \Exception(
					message: 'Sub-Query should be an associative array',
					code: HttpStatus::$InternalServerError
				);
			}
			if (
				isset($sqlConfig['__SUB-PAYLOAD__'])
				&& !$this->isObject(arr: $sqlConfig['__SUB-PAYLOAD__'])
			) {
				throw new \Exception(
					message: 'Sub-Payload should be an associative array',
					code: HttpStatus::$InternalServerError
				);
			}
			foreach (['__SUB-QUERY__', '__SUB-PAYLOAD__'] as $option) {
				if (isset($sqlConfig[$option])) {
					foreach ($sqlConfig[$option] as $module => &$moduleSqlConfig) {
						$flag = ($flag) ?? $this->getUseHierarchy(
							sqlConfig: $moduleSqlConfig
						);
						$moduleRequiredFieldArr = $this->getRequired(
							$moduleSqlConfig,
							false,
							$flag
						);
						if ($flag) {
							$requiredFieldArr[$module] = $moduleRequiredFieldArr;
						} else {
							foreach ($moduleRequiredFieldArr as $fetchFrom => &$fetchFromDataArr) {
								if (!isset($requiredFieldArr[$fetchFrom])) {
									$requiredFieldArr[$fetchFrom] = [];
								}
								foreach ($fetchFromDataArr as $fetchFromData) {
									if (!in_array($fetchFromData, $requiredFieldArr[$fetchFrom])) {
										$requiredFieldArr[$fetchFrom][] = $fetchFromData;
									}
								}
							}
						}
					}
				}
			}
		}

		return $requiredFieldArr;
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
		if ($this->validator === null) {
			$this->validator = new Validator(http: $this->http);
		}

		return $this->validator->validate(validationConfig: $validationConfig);
	}

	/**
	 * Generate SQL query and its param's in Named format
	 *
	 * @param array      $sqlConfig    SQL config
	 * @param array|null $configKeyArr Config key's
	 *
	 * @return array
	 */
	private function getSqlAndParamNamedMode(
		&$sqlConfig,
		$configKeyArr = null
	): array {
		$id = null;
		$sql = '';
		/*!999999 comment goes here */
		if (isset($sqlConfig['__SQL-COMMENT__'])) {
			$sql .= '/' . '*!999999 ';
			$sql .= $sqlConfig['__SQL-COMMENT__'];
			$sql .= ' */';
		}
		switch (true) {
			case isset($sqlConfig['__QUERY__']):
				$sql .= $sqlConfig['__QUERY__'];
				break;
			case isset($sqlConfig['__DOWNLOAD__']):
				$sql .= $sqlConfig['__DOWNLOAD__'];
				break;
		}
		$paramArr = [];
		$paramKeyArr = [];
		$errorArr = [];
		$row = [];
		$__SET__ = [];

		$missExecution = $wMissExecution = false;
		// Check __SET__
		if (
			isset($sqlConfig['__SET__'])
			&& count(value: $sqlConfig['__SET__']) !== 0
		) {
			$payloadVariableArr = $sqlConfig['__VARIABLES__'] ?? [];
			[$setParamArr, $errorArr, $missExecution] = $this->getSqlParam(
				$sqlConfig['__SET__'],
				$payloadVariableArr
			);
			if (
				empty($errorArr)
				&& !$missExecution
			) {
				if (!empty($setParamArr)) {
					// __SET__ not compulsory in query
					$found = strpos(haystack: $sql, needle: '__SET__') !== false;
					foreach ($setParamArr as $paramKey => &$paramValue) {
						$paramKey = str_replace(
							search: ['`', ' '],
							replace: '',
							subject: $paramKey
						);
						$paramKeyArr[] = $paramKey;
						if ($found) {
							$__SET__[] = "`{$paramKey}` = :{$paramKey}";
						}
						$paramArr[":{$paramKey}"] = $paramValue;
						$row[$paramKey] = $paramValue;
					}
				}
			}
		}

		// Check __WHERE__
		if (
			empty($errorArr)
			&& !$missExecution
			&& isset($sqlConfig['__WHERE__'])
			&& count(value: $sqlConfig['__WHERE__']) !== 0
		) {
			$wErrorArr = [];
			$payloadVariableArr = $sqlConfig['__VARIABLES__'] ?? [];
			[$whereParamArr, $wErrorArr, $wMissExecution] = $this->getSqlParam(
				$sqlConfig['__WHERE__'],
				$payloadVariableArr
			);
			if (
				empty($wErrorArr)
				&& !$wMissExecution
			) {
				if (!empty($whereParamArr)) {
					// __WHERE__ not compulsory in query
					$whereFound = strpos(haystack: $sql, needle: '__WHERE__') !== false;
					if ($whereFound) {
						$__WHERE__ = [];
						foreach ($whereParamArr as $param => &$v) {
							$wparam = $param = str_replace(
								search: ['`', ' '],
								replace: '',
								subject: $param
							);
							$i = 0;
							while (in_array(needle: $wparam, haystack: $paramKeyArr)) {
								$i++;
								$wparam = "{$param}{$i}";
							}
							$paramKeyArr[] = $wparam;
							$__WHERE__[] = "`{$param}` = :{$wparam}";
							$paramArr[":{$wparam}"] = $v;
							$row[$wparam] = $v;
						}
						$sql = str_replace(
							search: '__WHERE__',
							replace: implode(separator: ' AND ', array: $__WHERE__),
							subject: $sql
						);
					}
				}
			} else {
				$errorArr = array_merge($errorArr, $wErrorArr);
			}
		}
		if (!empty($__SET__)) {
			$sql = str_replace(
				search: '__SET__',
				replace: implode(separator: ', ', array: $__SET__),
				subject: $sql
			);
		}

		if (!empty($row)) {
			$this->resetFetchData('sqlParamArr', $configKeyArr, $row);
		}

		return [$id, $sql, $paramArr, $errorArr, ($missExecution || $wMissExecution)];
	}

	/**
	 * Generate SQL query and its param's in Unnamed format
	 *
	 * @param array      $sqlConfig    SQL config
	 * @param array|null $configKeyArr Config key's
	 *
	 * @return array
	 */
	private function getSqlAndParamUnnamedMode(
		&$sqlConfig,
		$configKeyArr = null
	): array {
		$id = null;
		$sql = '';
		/*!999999 comment goes here */
		if (isset($sqlConfig['__SQL-COMMENT__'])) {
			$sql .= '/' . '*!999999 ';
			$sql .= $sqlConfig['__SQL-COMMENT__'];
			$sql .= ' */';
		}
		switch (true) {
			case isset($sqlConfig['__QUERY__']):
				$sql .= $sqlConfig['__QUERY__'];
				break;
			case isset($sqlConfig['__DOWNLOAD__']):
				$sql .= $sqlConfig['__DOWNLOAD__'];
				break;
		}
		$paramArr = [];
		$paramKeyArr = [];
		$errorArr = [];
		$row = [];
		$__SET__ = [];

		$missExecution = $wMissExecution = false;
		// Check __SET__
		if (
			isset($sqlConfig['__SET__'])
			&& count(value: $sqlConfig['__SET__']) !== 0
		) {
			$payloadVariableArr = $sqlConfig['__VARIABLES__'] ?? [];
			[$paramArr, $errorArr, $missExecution] = $this->getSqlParam(
				$sqlConfig['__SET__'],
				$payloadVariableArr
			);
			if (
				empty($errorArr)
				&& !$missExecution
			) {
				if (!empty($paramArr)) {
					// __SET__ not compulsory in query
					$found = strpos(haystack: $sql, needle: '__SET__') !== false;
					foreach ($paramArr as $paramKey => &$paramValue) {
						$paramKeyArr[] = $paramKey;
						if ($found) {
							$__SET__[] = "{$paramKey} = ?";
						}
						$paramArr[] = $paramValue;
						$row[$paramKey] = $paramValue;
					}
				}
			}
		}

		// Check __WHERE__
		if (
			empty($errorArr)
			&& !$missExecution
			&& isset($sqlConfig['__WHERE__'])
			&& count(value: $sqlConfig['__WHERE__']) !== 0
		) {
			$wErrorArr = [];
			$payloadVariableArr = $sqlConfig['__VARIABLES__'] ?? [];
			[$sqlWhereParamArr, $wErrorArr, $wMissExecution] = $this->getSqlParam(
				$sqlConfig['__WHERE__'],
				$payloadVariableArr
			);
			if (
				empty($wErrorArr)
				&& !$wMissExecution
			) {
				if (!empty($sqlWhereParamArr)) {
					// __WHERE__ not compulsory in query
					$whereFound = strpos(haystack: $sql, needle: '__WHERE__') !== false;
					if ($whereFound) {
						$__WHERE__ = [];
						foreach ($sqlWhereParamArr as $param => &$v) {
							$wparam = $param;
							$i = 0;
							while (in_array(needle: $wparam, haystack: $paramKeyArr)) {
								$i++;
								$wparam = "{$param}{$i}";
							}
							$paramKeyArr[] = $wparam;
							$__WHERE__[] = "{$param} = ?";
							$paramArr[] = $v;
							$row[$wparam] = $v;
						}
						$sql = str_replace(
							search: '__WHERE__',
							replace: implode(separator: ' AND ', array: $__WHERE__),
							subject: $sql
						);
					}
				}
			} else {
				$errorArr = array_merge($errorArr, $wErrorArr);
			}
		}
		if (!empty($__SET__)) {
			$sql = str_replace(
				search: '__SET__',
				replace: implode(separator: ', ', array: $__SET__),
				subject: $sql
			);
		}

		if (!empty($row)) {
			$this->resetFetchData('sqlParamArr', $configKeyArr, $row);
		}

		return [$id, $sql, $paramArr, $errorArr, ($missExecution || $wMissExecution)];
	}

	/**
	 * Generates ParamArr for statement to execute
	 *
	 * @param array $sqlConfig          SQL config
	 * @param array $payloadVariableArr Payload Variables
	 *
	 * @return array
	 * @throws \Exception
	 */
	private function getSqlParam(&$sqlConfig, &$payloadVariableArr): array
	{
		$missExecution = false;
		$paramArr = [];
		$errorArr = [];

		// Collect param values as per config respectively
		foreach ($sqlConfig as $sqlParamConfig) {
			$column = $sqlParamConfig['column'];
			$fetchFrom = $sqlParamConfig['fetchFrom'];
			$fetchFromData = $sqlParamConfig['fetchFromData'];
			if ($fetchFrom === 'function') {
				$function = $fetchFromData;
				$value = $function($this->http->req->s);
				$paramArr[$column] = $value;
				continue;
			} elseif (
				in_array(
					needle: $fetchFrom,
					haystack: ['sqlParamArr', 'sqlPayload']
				)
			) {
				if (!isset($this->http->req->s[$fetchFrom])) {
					$errorArr[] = "Missing key '{$fetchFromData}' in '{$fetchFrom}'";
					continue;
				}
				$fetchFromDataArr = explode(separator: ':', string: $fetchFromData);
				$value = $this->http->req->s[$fetchFrom];
				foreach ($fetchFromDataArr as $_fetchFromData) {
					if (!isset($value[$_fetchFromData])) {
						$errorArr[] = "Missing hierarchy key '{$_fetchFromData}' of '{$fetchFromData}' in '{$fetchFrom}'";
						continue;
					}
					$value = &$value[$_fetchFromData];
				}
				$paramArr[$column] = $value;
				continue;
			} elseif ($fetchFrom === 'sqlResults') {
				if (!isset($this->http->req->s[$fetchFrom])) {
					$missExecution = true;
					continue;
				}
				$fetchFromDataArr = explode(separator: ':', string: $fetchFromData);
				$value = $this->http->req->s[$fetchFrom];
				foreach ($fetchFromDataArr as $_fetchFromData) {
					if (!isset($value[$_fetchFromData])) {
						$missExecution = true;
						continue;
					}
					$value = &$value[$_fetchFromData];
				}
				$paramArr[$column] = $value;
				continue;
			} elseif ($fetchFrom === 'custom') {
				$value = $fetchFromData;
				$paramArr[$column] = $value;
				continue;
			} elseif ($fetchFrom === 'variables') {
				if (isset($payloadVariableArr[$fetchFromData])) {
					$paramArr[$column] = $payloadVariableArr[$fetchFromData];
				} else {
					$errorArr[] = "Missing '{$fetchFrom}' for '{$fetchFromData}'";
				}
				continue;
			} elseif (isset($this->http->req->s[$fetchFrom][$fetchFromData])) {
				if (
					isset($this->http->req->s['requiredFieldArr'][$fetchFrom])
					&& in_array($fetchFromData, $this->http->req->s['requiredFieldArr'][$fetchFrom])
				) {
					if (isset($sqlParamConfig['dataType'])) {
						if (
							!DatabaseServerDataType::validateDataType(
								data: $this->http->req->s[$fetchFrom][$fetchFromData],
								dataType: $sqlParamConfig['dataType']
							)
						) {
							$errorArr[] = "Invalid required field data-type of '{$fetchFrom}' for '{$fetchFromData}'";
							continue;
						}
					}
				}
				$paramArr[$column] = $this->http->req->s[$fetchFrom][$fetchFromData];
				continue;
			} elseif (in_array($fetchFromData, $this->http->req->s['requiredFieldArr'][$fetchFrom])) {
				$errorArr[] = "Missing required field '{$fetchFrom}' for '{$fetchFromData}'";
				continue;
			} else {
				$errorArr[] = "Invalid configuration of '{$fetchFrom}' for '{$fetchFromData}'";
				continue;
			}
		}

		return [$paramArr, $errorArr, $missExecution];
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

		$i = 0;
		foreach ($arr as $k => &$v) {
			if ($k !== $i++) {
				$isObject = true;
				break;
			}
		}

		return $isObject;
	}

	/**
	 * Use results in where clause of sub queries recursively
	 *
	 * @param array  $sqlConfig SQL config
	 * @param string $keyword   useHierarchy/useResultSet
	 *
	 * @return bool
	 */
	private function getUseHierarchy(&$sqlConfig, $keyword = ''): bool
	{
		if (
			isset($sqlConfig[$keyword])
			&& $sqlConfig[$keyword] === true
		) {
			return true;
		}
		if (
			isset($sqlConfig['useHierarchy'])
			&& $sqlConfig['useHierarchy'] === true
		) {
			return true;
		}
		if (
			isset($sqlConfig['useResultSet'])
			&& $sqlConfig['useResultSet'] === true
		) {
			return true;
		}
		return false;
	}

	/**
	 * Return explain params recursively
	 *
	 * @param array $sqlConfig   SQL config
	 * @param bool  $isFirstCall Flag to check if this is first request
	 * @param bool  $flag        useHierarchy/useResultSet flag
	 *
	 * @return array
	 * @throws \Exception
	 */
	private function getExplainParam(&$sqlConfig, $isFirstCall, $flag): array
	{
		$explainParamArr = [];

		if (isset($sqlConfig['countQuery'])) {
			$sqlConfig['__CONFIG__'][] = [
				'column' => 'page',
				'fetchFrom' => 'queryParamArr',
				'fetchFromData' => 'page',
				'dataType' => DatabaseServerDataType::$INT,
				'isRequired' => Constant::$REQUIRED
			];
			$sqlConfig['__CONFIG__'][] = [
				'column' => 'perPage',
				'fetchFrom' => 'queryParamArr',
				'fetchFromData' => 'perPage',
				'dataType' => DatabaseServerDataType::$INT
			];

			foreach ($sqlConfig['__CONFIG__'] as $sqlParamConfig) {
				$fetchFrom = $sqlParamConfig['fetchFrom'];
				$fetchFromData = $sqlParamConfig['fetchFromData'];
				$dataType = isset($sqlParamConfig['dataType'])
					? $sqlParamConfig['dataType'] : DatabaseServerDataType::$Default;
				$isRequired = isset($sqlParamConfig['isRequired'])
					? $sqlParamConfig['isRequired'] : false;

				if (
					isset($explainParamArr[$fetchFromData])
					&& $explainParamArr[$fetchFromData]['isRequired'] === true
				) {
					continue;
				}
				$dataType['isRequired'] = $isRequired ? true : false;
				$explainParamArr[$fetchFromData] = $dataType;
			}
		}

		foreach (['__PAYLOAD__', '__SET__', '__WHERE__'] as $option) {
			if (isset($sqlConfig[$option])) {
				foreach ($sqlConfig[$option] as $sqlParamConfig) {
					$fetchFrom = $sqlParamConfig['fetchFrom'];
					$fetchFromData = $sqlParamConfig['fetchFromData'];
					$dataType = isset($sqlParamConfig['dataType'])
						? $sqlParamConfig['dataType'] : DatabaseServerDataType::$Default;
					$isRequired = isset($sqlParamConfig['isRequired'])
						? $sqlParamConfig['isRequired'] : false;

					if ($fetchFrom !== 'payload') {
						continue;
					}
					if (
						isset($explainParamArr[$fetchFromData])
						&& $explainParamArr[$fetchFromData]['isRequired'] === true
					) {
						continue;
					}
					$dataType['isRequired'] = $isRequired ? true : false;
					$explainParamArr[$fetchFromData] = $dataType;
				}
			}
		}

		// Check for hierarchy
		$foundHierarchy = false;
		if (isset($sqlConfig['__WHERE__'])) {
			foreach ($sqlConfig['__WHERE__'] as $sqlParamConfig) {
				$fetchFrom = $sqlParamConfig['fetchFrom'];
				$fetchFromData = $sqlParamConfig['fetchFromData'];
				if (
					in_array(
						needle: $fetchFrom,
						haystack: ['sqlResults', 'sqlParamArr', 'sqlPayload']
					)
				) {
					$foundHierarchy = true;
					break;
				}
			}
			if (
				!$isFirstCall
				&& $flag
				&& !$foundHierarchy
			) {
				throw new \Exception(
					message: 'Invalid config: missing ' . $fetchFrom,
					code: HttpStatus::$InternalServerError
				);
			}
		}

		// Check in subQuery//'__SUB-PAYLOAD__'
		foreach (['__SUB-PAYLOAD__', '__SUB-QUERY__'] as $option) {
			if (isset($sqlConfig[$option])) {
				foreach ($sqlConfig[$option] as $module => &$moduleSqlConfig) {
					$flag = ($flag) ?? $this->getUseHierarchy(
						sqlConfig: $moduleSqlConfig
					);
					$moduleExplainParamArr = $this->getExplainParam(
						$moduleSqlConfig,
						false,
						$flag
					);
					if ($flag) {
						if (!empty($moduleExplainParamArr)) {
							$explainParamArr[$module] = $moduleExplainParamArr;
						}
					} else {
						foreach ($moduleExplainParamArr as $fetchFromData => $field) {
							if (!isset($explainParamArr[$fetchFromData])) {
								$explainParamArr[$fetchFromData] = $field;
							}
						}
					}
				}
			}
		}

		return $explainParamArr;
	}

	/**
	 * Function to reset data for module key wise
	 *
	 * @param string $fetchFrom    sqlResults / sqlParamArr / sqlPayload
	 * @param array  $moduleKeyArr Module key's in recursion
	 * @param array  $row          Row data fetched from DB
	 *
	 * @return void
	 */
	private function resetFetchData($fetchFrom, $moduleKeyArr, $row): void
	{
		if (
			empty($moduleKeyArr)
			|| count(value: $moduleKeyArr) === 0
		) {
			$this->http->req->s[$fetchFrom] = [];
			$this->http->req->s[$fetchFrom]['return'] = [];
		}
		$httpReq = &$this->http->req->s[$fetchFrom]['return'];
		if (!empty($moduleKeyArr)) {
			foreach ($moduleKeyArr as $moduleKey) {
				if (!isset($httpReq[$moduleKey])) {
					$httpReq[$moduleKey] = [];
				}
				$httpReq = &$httpReq[$moduleKey];
			}
		}
		$httpReq = $row;
	}

	/**
	 * Rate Limiting request on basis of SQL config
	 *
	 * @param array $sqlConfig SQL config
	 *
	 * @return void
	 * @throws \Exception
	 */
	private function rateLimitRoute(&$sqlConfig): void
	{
		if (
			!$this->http->req->isPrivateRequest
			|| !CommonFunction::isEnabled(http: $this->http, feature: 'enableRateLimitForRoute')
			|| !isset($sqlConfig['rateLimitMaxRequest'])
			|| !isset($sqlConfig['rateLimitMaxRequestWindow'])
		) {
			return;
		}

		$payloadSignature = [
			'IP' => $this->http->httpReqData['server']['httpRequestIP'],
			'customerId' => $this->http->req->customerId,
			'httpMethod' => $this->http->httpReqData['server']['httpMethod'],
			'Route' => $this->http->httpReqData['get'][ROUTE_URL_PARAM],
		];
		if (isset($this->http->req->s['userData'])) {
			$payloadSignature['groupId'] = ($this->http->req->s['groupData']['id'] !== null
				? $this->http->req->s['groupData']['id'] : 0);
			$payloadSignature['userId'] = ($this->http->req->userId !== null
				? $this->http->req->userId : 0);
		}
		$hash = json_encode(value: $payloadSignature);
		$rateLimitKey = md5(string: $hash);

		// @throws \Exception
		$this->http->req->rateLimiter->checkRateLimit(
			rateLimitPrefix: Env::$rateLimitRoutePrefix,
			rateLimitMaxRequest: $sqlConfig['rateLimitMaxRequest'],
			rateLimitMaxRequestWindow: $sqlConfig['rateLimitMaxRequestWindow'],
			rateLimitKey: $rateLimitKey
		);
	}

	/**
	 * Check Referrer Lag
	 *
	 * @param array $sqlConfig SQL config
	 *
	 * @return void
	 * @throws \Exception
	 */
	private function checkReferrerLag(&$sqlConfig): void
	{
		$customerUserReferrerLagKey = CacheServerKey::customerUserReferrerLag(
			customerId: $this->http->req->customerId,
			userId: $this->http->req->userId
		);
		if (
			isset($sqlConfig['referrerLagWindow'])
			&& count($sqlConfig['referrerLagWindow']) > 0
		) {
			if (!$this->http->req->clientCacheObj->cacheExist(cacheKey: $customerUserReferrerLagKey)) {
				throw new \Exception(
					message: 'Referrer lag not initiated',
					code: HttpStatus::$BadRequest
				);
			}
			$referrerLagData = json_decode(
				json: $this->http->req->clientCacheObj->cacheGet(
					cacheKey: $customerUserReferrerLagKey
				),
				associative: true
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
									$this->http->req->clientCacheObj->cacheDelete(cacheKey: $customerUserReferrerLagKey);
								}
							} else {
								$found = true;
							}
						} else {
							$this->http->req->clientCacheObj->cacheDelete(cacheKey: $customerUserReferrerLagKey);
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
			&& $sqlConfig['enableReferrerLag'] === 'Yes'
		) {
			if (!$this->http->req->clientCacheObj->cacheExist(cacheKey: $customerUserReferrerLagKey)) {
				$this->http->req->clientCacheObj->cacheSet(
					cacheKey: $customerUserReferrerLagKey,
					cacheValue: json_encode(value: [
						'initRoute' => $this->http->req->rParser->configuredRoute,
						'timestamp' => Env::$timestamp
					])
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
	 * @param array $sqlConfig       SQL config
	 * @param array $payloadIndexArr Payload Indexes
	 *
	 * @return array
	 */
	private function checkIdempotent(&$sqlConfig, $payloadIndexArr): array
	{
		$idempotentWindow = 0;
		$hashKey = null;
		$hashJson = null;
		if (
			isset($sqlConfig['idempotentWindow'])
			&& is_numeric(value: $sqlConfig['idempotentWindow'])
			&& $sqlConfig['idempotentWindow'] > 0
		) {
			$idempotentWindow = (int)$sqlConfig['idempotentWindow'];
			if ($idempotentWindow) {
				$payloadSignature = [
					'idempotentSecret' => Env::$idempotentSecret,
					'idempotentWindow' => $idempotentWindow,
					'IP' => $this->http->httpReqData['server']['httpRequestIP'],
					'customerId' => $this->http->req->customerId,
					'httpMethod' => $this->http->httpReqData['server']['httpMethod'],
					'Route' => $this->http->httpReqData['get'][ROUTE_URL_PARAM],
					'payload' => $this->http->req->dataDecode->get(
						keyString: implode(separator: ':', array: $payloadIndexArr)
					)
				];
				if (isset($this->http->req->s['userData'])) {
					$payloadSignature['groupId'] = ($this->http->req->s['groupData']['id'] !== null
						? $this->http->req->s['groupData']['id'] : 0);
					$payloadSignature['userId'] = ($this->http->req->userId !== null
						? $this->http->req->userId : 0);
				}

				$hash = json_encode(value: $payloadSignature);
				$hashKey = md5(string: $hash);
				if (
					$this->http->req->isPrivateRequest
					&& $this->http->req->clientCacheObj->cacheExist(cacheKey: $hashKey)
				) {
					$hashJson = str_replace(
						search: 'JSON',
						replace: $this->http->req->clientCacheObj->cacheGet(cacheKey: $hashKey),
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
	 * @param array $sqlConfig SQL config
	 *
	 * @return void
	 */
	private function lagResponse($sqlConfig): void
	{
		if (
			!$this->http->req->isPrivateRequest
			|| !isset($sqlConfig['responseLag'])
		) {
			return;
		}

		$payloadSignature = [
			'IP' => $this->http->httpReqData['server']['httpRequestIP'],
			'customerId' => $this->http->req->customerId,
			'httpMethod' => $this->http->httpReqData['server']['httpMethod'],
			'Route' => $this->http->httpReqData['get'][ROUTE_URL_PARAM],
		];
		if (isset($this->http->req->s['userData'])) {
			$payloadSignature['groupId'] = ($this->http->req->s['groupData']['id'] !== null
				? $this->http->req->s['groupData']['id'] : 0);
			$payloadSignature['userId'] = ($this->http->req->userId !== null
				? $this->http->req->userId : 0);
		}

		$hash = json_encode(value: $payloadSignature);
		$hashKey = 'LAG:' . md5(string: $hash);

		if ($this->http->req->clientCacheObj->cacheExist(cacheKey: $hashKey)) {
			$noOfRequest = $this->http->req->clientCacheObj->cacheGet(cacheKey: $hashKey);
		} else {
			$noOfRequest = 0;
		}

		$this->http->req->clientCacheObj->cacheSet(
			cacheKey: $hashKey,
			cacheValue: ++$noOfRequest,
			cacheExpire: 3600
		);

		$lag = 0;
		$responseLag = &$sqlConfig['responseLag'];
		if (is_array(value: $responseLag)) {
			foreach ($responseLag as $start => $newLag) {
				if ($noOfRequest > $start) {
					$lag = $newLag;
				}
			}
		}

		if ($lag > 0) {
			sleep(seconds: $lag);
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
		if (!isset($this->http->req->s['token'])) {
			throw new \Exception(
				message: 'Missing token',
				code: HttpStatus::$InternalServerError
			);
		}

		$httpReqData = [];

		$isObject = (!isset($triggerConfig[0])) ? true : false;
		if (
			!$isObject
			&& isset($triggerConfig[0])
			&& count(value: $triggerConfig) === 1
		) {
			$triggerConfig = $triggerConfig[0];
			$isObject = true;
		}

		$triggerOutput = [];
		if ($isObject) {
			$httpReqData = $this->getTriggerHttp(triggerConfig: $triggerConfig);
			[$responseHeaderArr, $responseContent, $responseCode] = Start::http(httpReqData: $httpReqData);
			$triggerOutput = &$responseContent;
		} else {
			for (
				$iTrigger = 0, $iTriggerCount = count($triggerConfig);
				$iTrigger < $iTriggerCount;
				$iTrigger++
			) {
				$httpReqData = $this->getTriggerHttp(triggerConfig: $triggerConfig[$iTrigger]);
				[$responseHeaderArr, $responseContent, $responseCode] = Start::http(httpReqData: $httpReqData);
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
		$method = $triggerConfig['__METHOD__'];
		[$routeElementArrArr, $errorArr] = $this->getTriggerParam(
			payloadConfig: $triggerConfig['__ROUTE__']
		);

		if ($errorArr) {
			return $errorArr;
		}

		$route = '/' . implode(separator: '/', array: $routeElementArrArr);

		$queryStringArr = [];
		$payloadArr = [];

		if (isset($triggerConfig['__QUERY-STRING__'])) {
			[$queryStringArr, $errorArr] = $this->getTriggerParam(
				payloadConfig: $triggerConfig['__QUERY-STRING__']
			);

			if ($errorArr) {
				return $errorArr;
			}
		}
		if (isset($triggerConfig['__PAYLOAD__'])) {
			[$payloadArr, $errorArr] = $this->getTriggerParam(
				payloadConfig: $triggerConfig['__PAYLOAD__']
			);
			if ($errorArr) {
				return $errorArr;
			}
		}

		$httpReqData['streamData'] = false;
		$httpReqData['server']['domainName'] = $this->http->httpReqData['server']['domainName'];
		$httpReqData['server']['httpMethod'] = $method;
		$httpReqData['server']['httpRequestIP'] = $this->http->httpReqData['server']['httpRequestIP'];
		$httpReqData['header'] = $this->http->httpReqData['header'];
		$httpReqData['post'] = json_encode(value: $payloadArr);
		$httpReqData['get'] = $queryStringArr;
		$httpReqData['get'][ROUTE_URL_PARAM] = $route;
		$httpReqData['isWebRequest'] = false;

		return $httpReqData;
	}

	/**
	 * Get Trigger param's
	 *
	 * @param array $payloadConfig      API Payload configuration
	 * @param array $payloadVariableArr Payload Variables
	 *
	 * @return array
	 * @throws \Exception
	 */
	private function getTriggerParam(&$payloadConfig): array
	{
		$paramArr = [];
		$errorArr = [];

		// Collect param values as per config respectively
		foreach ($payloadConfig as &$payloadParamConfig) {
			$column = $payloadParamConfig['column'] ?? null;

			$fetchFrom = $payloadParamConfig['fetchFrom'];
			$fetchFromData = $payloadParamConfig['fetchFromData'];
			if ($fetchFrom === 'function') {
				$function = $fetchFromData;
				$value = $function($this->http->req->s);
				if ($column === null) {
					$paramArr[] = $value;
				} else {
					$paramArr[$column] = $value;
				}
				continue;
			} elseif (
				in_array(
					needle: $fetchFrom,
					haystack: ['sqlResults', 'sqlParamArr', 'sqlPayload']
				)
			) {
				$fetchFromDataArr = explode(separator: ':', string: $fetchFromData);
				$value = $this->http->req->s[$fetchFrom];
				foreach ($fetchFromDataArr as $_fetchFromData) {
					if (!isset($value[$_fetchFromData])) {
						throw new \Exception(
							message: 'Invalid hierarchy:  Missing hierarchy data',
							code: HttpStatus::$InternalServerError
						);
					}
					$value = $value[$_fetchFromData];
				}
				if ($column === null) {
					$paramArr[] = $value;
				} else {
					$paramArr[$column] = $value;
				}
				continue;
			} elseif ($fetchFrom === 'custom') {
				$value = $fetchFromData;
				if ($column === null) {
					$paramArr[] = $value;
				} else {
					$paramArr[$column] = $value;
				}
				continue;
			} elseif (isset($this->http->req->s[$fetchFrom][$fetchFromData])) {
				$value = $this->http->req->s[$fetchFrom][$fetchFromData];
				if ($column === null) {
					$paramArr[] = $value;
				} else {
					$paramArr[$column] = $value;
				}
				continue;
			} else {
				$errorArr[] = "Invalid configuration of '{$fetchFrom}' for '{$fetchFromData}'";
				continue;
			}
		}

		return [$paramArr, $errorArr];
	}

	/**
	 * Process import function of configuration
	 *
	 * @param array $writeSqlConfig Write SQL config
	 * @param bool  $useHierarchy   Use results in where clause of sub queries
	 *
	 * @return string
	 */
	private function processImportSqlConfig(&$writeSqlConfig, $useHierarchy): string
	{
		$explainParamArr = $this->getExplainParam(
			sqlConfig: $writeSqlConfig,
			isFirstCall: true,
			flag: $useHierarchy
		);
		$paramArr = $this->genCsvHelper(
			headerCsv: 'CSV',
			explainParamArr: $explainParamArr
		);

		$header = [];
		$header[] = '__mode__';
		foreach ($paramArr as $r => $p) {
			if (is_array($p)) {
				for ($i = 0, $iCount = count($p); $i < $iCount; $i++) {
					$header[] = $p[$i];
				}
			} else {
				$header[] = $p;
			}
		}
		$csv = '"' . implode(separator: '","', array: $header) . '"' . PHP_EOL;
		$blankStr = '';
		foreach ($paramArr as $r => $p) {
			if ($r === 'CSV') {
				for ($i = 1, $iCount = count($header); $i < $iCount; $i++) {
					$blankStr = ',""';
				}
			}
			$csv .= "{$r}{$blankStr}" . PHP_EOL;
		}

		return $csv;
	}

	/**
	 * Generate sample CSV helper
	 *
	 * @param string $module
	 * @param array  $explainParamArr
	 *
	 * @return array
	 */
	private function genCsvHelper($module, $explainParamArr): array
	{
		$headerCsvArr = [];
		foreach ($explainParamArr as $hierarchyKey => $_explainParamArr) {
			if (isset($_explainParamArr['dataType'])) {
				$columnHeader = "{$module}:{$hierarchyKey}";
				$headerCsvArr[$module][] = $columnHeader;
			} else {
				$_module = "{$module}:{$hierarchyKey}";
				$returnHeaderArr = $this->genCsvHelper(
					module: $_module,
					explainParamArr: $_explainParamArr
				);
				foreach ($returnHeaderArr as $_module => $columnHeader) {
					$headerCsvArr[$_module] = $columnHeader;
				}
			}
		}

		return $headerCsvArr;
	}
}
