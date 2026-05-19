<?php

/**
 * Write APIs
 * php version 8.3
 *
 * @category  WriteAPI
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App;

use Microservices\App\AppTrait;
use Microservices\App\CommonFunction;
use Microservices\App\DataRepresentation\DataEncode;
use Microservices\App\DbCommonFunction;
use Microservices\App\Env;
use Microservices\App\Hook;
use Microservices\App\Http;
use Microservices\App\HttpStatus;
use Microservices\App\Web;

/**
 * Write APIs
 * php version 8.3
 *
 * @category  WriteAPIs
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class Write
{
	use AppTrait;

	/**
	 * Hook object
	 *
	 * @var null|Hook
	 */
	private $hook = null;

	/**
	 * Operate DML As Transactions
	 *
	 * @var null|Web
	 */
	private $operateAsTransaction = null;

	/**
	 * Data Encode object
	 *
	 * @var null|DataEncode
	 */
	public $dataEncode = null;

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
		$this->dataEncode = &$this->http->res->dataEncode;
	}

	/**
	 * Initialize
	 *
	 * @return bool
	 */
	public function init(): bool
	{
		return true;
	}

	/**
	 * Process
	 *
	 * @return mixed
	 */
	public function process(): mixed
	{
		// Load Sql
		$writeSqlConfig = &$this->http->req->rParser->sqlConfig;

		// Rate Limiting request if configured for Route Sql.
		$this->rateLimitRoute(sqlConfig: $writeSqlConfig);

		// Check for configured referrer Lags
		$this->checkReferrerLag(sqlConfig: $writeSqlConfig);

		// Use results in where clause of sub queries recursively
		$useHierarchy = $this->getUseHierarchy(
			sqlConfig: $writeSqlConfig,
			keyword: 'useHierarchy'
		);

		if (CommonFunction::isEnabled(http: $this->http, feature: 'enableExplainRequest')) {
			if (
				$this->http->req->rParser->routeEndingWithReservedKeywordFlag
				&& ($this->http->req->rParser->routeEndingReservedKeyword === Env::$explainRequestRouteKeyword)
			) {
				return $this->explainWrite(
					writeSqlConfig: $writeSqlConfig,
					useHierarchy: $useHierarchy
				);
			}
			if (
				$this->http->req->rParser->routeEndingWithReservedKeywordFlag
				&& ($this->http->req->rParser->routeEndingReservedKeyword === Env::$importSampleRequestRouteKeyword)
			) {
				return $this->processImportSqlConfig(
					writeSqlConfig: $writeSqlConfig,
					useHierarchy: $useHierarchy
				);
			}
		}

		// Lag Response
		$this->lagResponse(sqlConfig: $writeSqlConfig);

		// Operate as Transaction (BEGIN COMMIT else ROLLBACK on error)
		$this->operateAsTransaction = isset($writeSqlConfig['isTransaction'])
			? $writeSqlConfig['isTransaction'] : false;

		// Set Server mode to execute query on - Read / Write Server
		$this->http->req->clientDbObj = DbCommonFunction::connectClientDb(
			customerData: $this->http->req->s['customerData'],
			fetchFrom: 'Master'
		);

		$this->processWrite(
			writeSqlConfig: $writeSqlConfig,
			useHierarchy: $useHierarchy
		);
		if (isset($writeSqlConfig['affectedQueryCacheKeyArr'])) {
			for (
				$i = 0, $iCount = count(value: $writeSqlConfig['affectedQueryCacheKeyArr']);
				$i < $iCount;
				$i++
			) {
				DbCommonFunction::queryCacheDelete(
					customerId: $this->http->req->customerId,
					queryCacheKey: $writeSqlConfig['affectedQueryCacheKeyArr'][$i]
				);
			}
		}

		return true;
	}

	/**
	 * Explain write configuration
	 *
	 * @param array $writeSqlConfig Write SQL config
	 * @param bool  $useHierarchy   Use results in where clause of sub queries
	 *
	 * @return bool
	 */
	private function explainWrite(&$writeSqlConfig, $useHierarchy): bool
	{
		$this->dataEncode->startObject(objectKey: 'Config');
		$this->dataEncode->addKeyData(
			objectKey: 'Route',
			data: $this->http->req->rParser->configuredRoute
		);
		if (CommonFunction::isEnabled(http: $this->http, feature: 'enablePayloadInResponse')) {
			$this->dataEncode->addKeyData(
				objectKey: Env::$payloadKeyInResponse,
				data: $this->getExplainParam(
					sqlConfig: $writeSqlConfig,
					isFirstCall: true,
					flag: $useHierarchy
				)
			);
		}
		$this->dataEncode->endObject();

		return true;
	}

	/**
	 * Process for insert/update
	 *
	 * @param array $writeSqlConfig Write SQL config
	 * @param bool  $useHierarchy   Use results in where clause of sub queries
	 *
	 * @return void
	 * @throws \Exception
	 */
	private function processWrite(&$writeSqlConfig, $useHierarchy): void
	{
		// Check for payloadType
		if (isset($writeSqlConfig['__PAYLOAD-TYPE__'])) {
			$payloadType = $this->http->req->s['payloadType'];
			if ($payloadType !== $writeSqlConfig['__PAYLOAD-TYPE__']) {
				throw new \Exception(
					message: 'Invalid payload type',
					code: HttpStatus::$BadRequest
				);
			}
			// Check for maximum object's supported when payloadType is Array
			if (
				$writeSqlConfig['__PAYLOAD-TYPE__'] === 'Array'
				&& isset($writeSqlConfig['__MAX-PAYLOAD-OBJECTS__'])
				&& ($objCount = $this->http->req->dataDecode->count())
				&& ($objCount > $writeSqlConfig['__MAX-PAYLOAD-OBJECTS__'])
			) {
				throw new \Exception(
					message: 'Maximum supported payload count is '
						. $writeSqlConfig['__MAX-PAYLOAD-OBJECTS__'],
					code: HttpStatus::$BadRequest
				);
			}
		}

		// Set required fields
		$this->http->req->s['requiredFieldArrCollection'] = $this->getRequired(
			sqlConfig: $writeSqlConfig,
			isFirstCall: true,
			flag: $useHierarchy
		);

		$this->dataEncode->startObject(objectKey: 'Results');
		if (
			isset($this->http->req->s['payloadType'])
			&& $this->http->req->s['payloadType'] === 'Array'
		) {
			if (in_array($this->http->res->oRepresentation, ['XML', 'XSLT', 'HTML'])) {
				$this->dataEncode->startArray(objectKey: 'Rows');
			}
		}

		// Perform action
		$iCount = $this->http->req->s['payloadType'] === 'Array'
			? $this->http->req->dataDecode->count() : 1;

		for ($i = 0; $i < $iCount; $i++) {
			$configKeyArr = [];
			$payloadIndexArr = [];
			if ($i === 0) {
				if ($this->http->req->s['payloadType'] === 'Array') {
					$payloadIndexArr[] = "{$i}";
				} else {
					$payloadIndexArr[] = '';
				}
			} else {
				$payloadIndexArr[] = "{$i}";
			}

			// Check for Idempotent Window
			[$idempotentWindow, $hashKey, $hashJson] = $this->checkIdempotent(
				sqlConfig: $writeSqlConfig,
				payloadIndexArr: $payloadIndexArr
			);

			// Begin DML operation
			if ($hashJson === null) {
				if ($this->operateAsTransaction) {
					$this->http->req->clientDbObj->begin();
				}
				$response = [];
				$this->writeDb(
					writeSqlConfig: $writeSqlConfig,
					payloadIndexArr: $payloadIndexArr,
					configKeyArr: $configKeyArr,
					useHierarchy: $useHierarchy,
					response: $response,
					requiredFieldArr: $this->http->req->s['requiredFieldArrCollection']
				);

				if ($this->http->res->httpStatus === HttpStatus::$Ok) {
					if (
						$this->operateAsTransaction
						&& ($this->http->req->clientDbObj->beganTransaction === true)
					) {
						$this->http->req->clientDbObj->commit();
					}

					$arr = [];
					$arr['Status'] = HttpStatus::$Ok;
					if (CommonFunction::isEnabled(http: $this->http, feature: 'enablePayloadInResponse')) {
						$arr[Env::$payloadKeyInResponse] = $this->http->req->dataDecode->getCompleteArray(
							keyString: implode(
								separator: ':',
								array: $payloadIndexArr
							)
						);
					}
					$arr['Response'] = $response;

					if (
						$this->http->req->isPrivateRequest
						&& $idempotentWindow
					) {
						$this->http->req->clientCacheObj->cacheSet(
							cacheKey: $hashKey,
							cacheValue: json_encode(value: $arr),
							cacheExpire: $idempotentWindow
						);
					}
				} else { // Failure
					$arr = [];
					$arr['Status'] = $this->http->res->httpStatus;
					if (CommonFunction::isEnabled(http: $this->http, feature: 'enablePayloadInResponse')) {
						$arr[Env::$payloadKeyInResponse] = $this->http->req->dataDecode->getCompleteArray(
							keyString: implode(
								separator: ':',
								array: $payloadIndexArr
							)
						);
					}
					$arr['Error'] = $response;
				}
			} else {
				$arr = json_decode(json: $hashJson, associative: true);
			}

			if ($payloadIndexArr[0] === '') {
				foreach ($arr as $k => $v) {
					$this->dataEncode->addKeyData(objectKey: $k, data: $v);
				}
			} else {
				if (in_array($this->http->res->oRepresentation, ['XML', 'XSLT', 'HTML'])) {
					$this->dataEncode->startObject(objectKey: 'Row');
					foreach ($arr as $k => $v) {
						$this->dataEncode->addKeyData(objectKey: $k, data: $v);
					}
					$this->dataEncode->endObject();
				} else {
					$this->dataEncode->addKeyData(objectKey: $i, data: $arr);
				}
			}
		}

		if ($this->http->req->s['payloadType'] === 'Array') {
			if (in_array($this->http->res->oRepresentation, ['XML', 'XSLT', 'HTML'])) {
				$this->dataEncode->endArray();
			}
		}
		$this->dataEncode->endObject();
	}

	/**
	 * Process $writeSqlConfig recursively
	 *
	 * @param array $writeSqlConfig   Write SQL config
	 * @param array $payloadIndexArr  Payload Indexes
	 * @param array $configKeyArr     Config key's
	 * @param bool  $useHierarchy     Use results in where clause of sub queries
	 * @param array $response         Response by reference
	 * @param array $requiredFieldArr Required fields
	 *
	 * @return void
	 * @throws \Exception
	 */
	private function writeDb(
		&$writeSqlConfig,
		$payloadIndexArr,
		$configKeyArr,
		$useHierarchy,
		&$response,
		&$requiredFieldArr
	): void {
		$payloadIndex = is_array(value: $payloadIndexArr)
			? trim(
				string: implode(
					separator: ':',
					array: $payloadIndexArr
				),
				characters: ':'
			) : null;

		$isObject = null;
		if ($payloadIndex !== null) {
			$isObject = $this->http->req->dataDecode->dataType(
				keyString: $payloadIndex
			) === 'Object';
		}

		$iCount = ($isObject || $isObject === null)
			? 1 : $this->http->req->dataDecode->count(keyString: $payloadIndex);

		$mode = getenv(name: $this->http->req->s['customerData']['master_db_server_query_placeholder']);
		$function = "getSqlAndParam{$mode}Mode";

		for ($i = 0; $i < $iCount; $i++) {
			if (
				$isObject
				|| $isObject === null
			) {
				$_response = &$response;
			} else {
				$response[$i] = [];
				$_response = &$response[$i];
			}

			$payloadIndexArr = $payloadIndexArr;
			if (
				$this->operateAsTransaction
				&& !$this->http->req->clientDbObj->beganTransaction
			) {
				$_response['Error'] = 'Transaction rolled back';
				return;
			}

			if (
				$isObject
				&& $i > 0
			) {
				return;
			}

			if (
				!$isObject
				&& !$useHierarchy
			) {
				array_push($payloadIndexArr, $i);
			}
			$payloadIndex = is_array(value: $payloadIndexArr)
				? implode(separator: ':', array: $payloadIndexArr) : '';

			if (!$this->http->req->dataDecode->isset(keyString: $payloadIndex)) {
				throw new \Exception(
					message: "Payload key '{$payloadIndex}' not set",
					code: HttpStatus::$NotFound
				);
			}

			$this->http->req->s['payload'] = $this->http->req->dataDecode->get(
				keyString: $payloadIndex
			);

			if (count(value: $requiredFieldArr)) {
				$this->http->req->s['requiredFieldArr'] = $requiredFieldArr;
			} else {
				$this->http->req->s['requiredFieldArr'] = [];
			}

			if (
				CommonFunction::isEnabled(http: $this->http, feature: 'enableGlobalCounter')
				&& isset($writeSqlConfig['__VARIABLES__']['__GLOBAL_COUNTER__'])
			) {
				$writeSqlConfig['__VARIABLES__']['__GLOBAL_COUNTER__'] = Counter::getGlobalCounter();
			}

			// Validation
			if (
				isset($writeSqlConfig['__VALIDATE__'])
				&& !$this->isValidPayload(writeSqlConfig: $writeSqlConfig, response: $_response)
			) {
				continue;
			}

			// Execute Pre SQL Hook
			if (isset($writeSqlConfig['__PRE-SQL-HOOKS__'])) {
				if ($this->hook === null) {
					$this->hook = new Hook(http: $this->http);
				}
				$this->hook->triggerHook(
					hookArr: $writeSqlConfig['__PRE-SQL-HOOKS__']
				);
			}

			// Get SQL and ParamArr
			[$id, $sql, $paramArr, $errorArr, $missExecution] = $this->$function(
				sqlConfig: $writeSqlConfig
			);

			if (!empty($errorArr)) {
				$_response['Error'] = $errorArr;
				$this->http->req->clientDbObj->rollBack();
				return;
			}

			if ($missExecution) {
				return;
			}

			// Execute Query
			$this->http->req->clientDbObj->execDbQuery(sql: $sql, paramArr: $paramArr);
			if (
				$this->operateAsTransaction
				&& !$this->http->req->clientDbObj->beganTransaction
			) {
				$_response['Error'] = 'Something went wrong';
				return;
			}

			if (isset($writeSqlConfig['__INSERT-IDs__'])) {
				if (
					CommonFunction::isEnabled(http: $this->http, feature: 'enableGlobalCounter')
					&& isset($writeSqlConfig['__VARIABLES__']['__GLOBAL_COUNTER__'])
				) {
					$id = $writeSqlConfig['__VARIABLES__']['__GLOBAL_COUNTER__'];
				} else {
					$id = $this->http->req->clientDbObj->lastInsertId();
				}
				$_response[$writeSqlConfig['__INSERT-IDs__']] = $id;
				$this->http->req->s['__INSERT-IDs__'][$writeSqlConfig['__INSERT-IDs__']] = $id;
			} else {
				$affectedRowCount = $this->http->req->clientDbObj->affectedRowCount();
				$_response['affectedRowCount'] = $affectedRowCount;
			}
			$this->http->req->clientDbObj->closeCursor();

			// triggers
			if (isset($writeSqlConfig['__TRIGGERS__'])) {
				$this->dataEncode->addKeyData(
					objectKey: '__TRIGGERS__',
					data: $this->getTriggerData(
						triggerConfig: $writeSqlConfig['__TRIGGERS__']
					)
				);
			}

			// Execute Post SQL Hook
			if (isset($writeSqlConfig['__POST-SQL-HOOKS__'])) {
				if ($this->hook === null) {
					$this->hook = new Hook(http: $this->http);
				}
				$this->hook->triggerHook(
					hookArr: $writeSqlConfig['__POST-SQL-HOOKS__']
				);
			}

			// subQuery for payload
			if (isset($writeSqlConfig['__SUB-QUERY__'])) {
				$this->callWriteDb(
					writeSqlConfig: $writeSqlConfig,
					payloadIndexArr: $payloadIndexArr,
					configKeyArr: $configKeyArr,
					useHierarchy: $useHierarchy,
					response: $_response,
					requiredFieldArr: $requiredFieldArr
				);
			}
		}
	}

	/**
	 * Function writeDb recursive helper
	 *
	 * @param array $writeSqlConfig   Write SQL config
	 * @param array $payloadIndexArr  Payload Indexes
	 * @param array $configKeyArr     Config key's
	 * @param bool  $useHierarchy     Use results in where clause of sub queries
	 * @param array $response         Response by reference
	 * @param array $requiredFieldArr Required fields
	 *
	 * @return void
	 */
	private function callWriteDb(
		&$writeSqlConfig,
		$payloadIndexArr,
		$configKeyArr,
		$useHierarchy,
		&$response,
		&$requiredFieldArr
	): void {
		if ($useHierarchy) {
			$row = $this->http->req->s['payload'];
			$this->resetFetchData(
				fetchFrom: 'sqlPayload',
				moduleKeyArr: $configKeyArr,
				row: $row
			);
		}

		if (
			isset($payloadIndexArr[0])
			&& $payloadIndexArr[0] === ''
		) {
			$payloadIndexArr = array_shift($payloadIndexArr);
		}
		if (!is_array(value: $payloadIndexArr)) {
			$payloadIndexArr = [];
		}

		if (
			isset($writeSqlConfig['__SUB-QUERY__'])
			&& $this->isObject(arr: $writeSqlConfig['__SUB-QUERY__'])
		) {
			foreach ($writeSqlConfig['__SUB-QUERY__'] as $module => &$writeSqlConfig) {
				$dataExist = false;
				$modulePayloadIndexArr = $payloadIndexArr;
				$moduleConfigKeyArr = $configKeyArr;
				array_push($modulePayloadIndexArr, $module);
				array_push($moduleConfigKeyArr, $module);

				$modulePayloadIndexKey = is_array(value: $modulePayloadIndexArr)
					? implode(separator: ':', array: $modulePayloadIndexArr) : null;

				$isObject = null;
				if ($modulePayloadIndexKey !== null) {
					$isObject = $this->http->req->dataDecode->dataType(
						keyString: $modulePayloadIndexKey
					) === 'Object';
				}

				$iCount = ($isObject || $isObject === null)
					? 1 : $this->http->req->dataDecode->count(keyString: $modulePayloadIndexKey);

				for ($i = 0; $i < $iCount; $i++) {
					$modulePayloadIndexItt = $modulePayloadIndexArr;
					if (
						$isObject
						|| $isObject === null
					) {
						$modulePayloadIndexIttKey = $modulePayloadIndexKey;
					} else {
						$modulePayloadIndexIttKey = "{$modulePayloadIndexKey}:{$i}";
						array_push($modulePayloadIndexItt, $i);
					}

					$dataExist = $this->http->req->dataDecode->isset(
						keyString: $modulePayloadIndexIttKey
					);

					if (
						$useHierarchy
						&& !$dataExist
					) { // use parent data of a payload
						throw new \Exception(
							message: "Invalid payload: Module '{$module}' missing",
							code: HttpStatus::$NotFound
						);
					}
					if ($dataExist) {
						$requiredFieldArr = $requiredFieldArr[$module] ?? $requiredFieldArr;
						$useHierarchy = $useHierarchy ?? $this->getUseHierarchy(
							sqlConfig: $writeSqlConfig,
							keyword: 'useHierarchy'
						);
						$response[$module] = [];
						$response = &$response[$module];
						$this->writeDb(
							writeSqlConfig: $writeSqlConfig,
							payloadIndexArr: $modulePayloadIndexItt,
							configKeyArr: $moduleConfigKeyArr,
							useHierarchy: $useHierarchy,
							response: $response,
							requiredFieldArr: $requiredFieldArr
						);
					}
				}
			}
		}
	}

	/**
	 * Validate payload
	 *
	 * @param array $writeSqlConfig Write SQL config
	 * @param array $response       Response by reference
	 *
	 * @return bool
	 */
	private function isValidPayload($writeSqlConfig, &$response): bool
	{
		$return = true;
		$isValidData = true;
		if (isset($writeSqlConfig['__VALIDATE__'])) {
			[$isValidData, $errorArr] = $this->validate(
				validationConfig: $writeSqlConfig['__VALIDATE__']
			);
			if ($isValidData !== true) {
				$this->http->res->httpStatus = HttpStatus::$BadRequest;
				$response = $errorArr;
				$return = false;
			}
		}
		return $return;
	}
}
