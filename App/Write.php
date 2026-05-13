<?php

/**
 * Write APIs
 * php version 8.3
 *
 * @category  WriteAPI
 * @package   Openswoole_Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App;

use Microservices\App\AppTrait;
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
 * @package   Openswoole_Microservices
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
	 * @return bool|array
	 */
	public function process(): bool|array
	{
		// Load Sql
		$wSqlConfig = &$this->http->req->rParser->sqlConfig;

		// Rate Limiting request if configured for Route Sql.
		$this->rateLimitRoute(sqlConfig: $wSqlConfig);

		// Check for configured referrer Lags
		$this->checkReferrerLag(sqlConfig: $wSqlConfig);

		// Use results in where clause of sub queries recursively
		$useHierarchy = $this->getUseHierarchy(
			sqlConfig: $wSqlConfig,
			keyword: 'useHierarchy'
		);

		if (Env::$enableExplainRequest) {
			if (
				$this->http->req->rParser->routeEndingWithReservedKeywordFlag
				&& ($this->http->req->rParser->routeEndingReservedKeyword === Env::$explainRequestRouteKeyword)
			) {
				$this->explainWrite(
					wSqlConfig: $wSqlConfig,
					useHierarchy: $useHierarchy
				);
				return true;
			}
			if (
				$this->http->req->rParser->routeEndingWithReservedKeywordFlag
				&& ($this->http->req->rParser->routeEndingReservedKeyword === Env::$importSampleRequestRouteKeyword)
			) {
				$filename = date('Ymd-His') . '-import-sample.csv';
				$headerArr = [];
				// Export header
				$headerArr['Content-type'] = 'text/csv';
				$headerArr['Content-Disposition'] = "attachment; filename={$filename}";
				$headerArr['Pragma'] = 'no-cache';
				$headerArr['Expires'] = '0';

				$csv = $this->processImportSqlConfig(
					wSqlConfig: $wSqlConfig,
					useHierarchy: $useHierarchy
				);

				return [$headerArr, $csv, HttpStatus::$Ok];
			}
		}

		if (
			$this->http->res->oRepresentation === 'XSLT'
			&& isset($wSqlConfig['xsltFile'])
		) {
			$this->dataEncode->xsltFile = $wSqlConfig['xsltFile'];
		} elseif (
			$this->http->res->oRepresentation === 'HTML'
			&& isset($wSqlConfig['htmlFile'])
		) {
			$this->dataEncode->htmlFile = $wSqlConfig['htmlFile'];
		} elseif (
			$this->http->res->oRepresentation === 'PHP'
			&& isset($wSqlConfig['phpFile'])
		) {
			$this->dataEncode->phpFile = $wSqlConfig['phpFile'];
		}

		// Lag Response
		$this->lagResponse(sqlConfig: $wSqlConfig);

		// Operate as Transaction (BEGIN COMMIT else ROLLBACK on error)
		$this->operateAsTransaction = isset($wSqlConfig['isTransaction'])
			? $wSqlConfig['isTransaction'] : false;

		// Set Server mode to execute query on - Read / Write Server
		$this->http->req->clientDbObj = DbCommonFunction::connectClientDb(req: $this->http->req, fetchFrom: 'Master');

		$this->processWrite(
			wSqlConfig: $wSqlConfig,
			useHierarchy: $useHierarchy
		);
		if (isset($wSqlConfig['affectedCacheKeyArr'])) {
			for (
				$i = 0, $iCount = count(value: $wSqlConfig['affectedCacheKeyArr']);
				$i < $iCount;
				$i++
			) {
				DbCommonFunction::queryCacheDelete(
					queryCacheKey: $wSqlConfig['affectedCacheKeyArr'][$i]
				);
			}
		}

		return true;
	}

	/**
	 * Explain write configuration
	 *
	 * @param array $wSqlConfig   Write SQL config
	 * @param bool  $useHierarchy Use results in where clause of sub queries
	 *
	 * @return void
	 */
	private function explainWrite(&$wSqlConfig, $useHierarchy): void
	{
		$this->dataEncode->startObject(objectKey: 'Config');
		$this->dataEncode->addKeyData(
			objectKey: 'Route',
			data: $this->http->req->rParser->configuredRoute
		);
		if (Env::$enablePayloadInResponse) {
			$this->dataEncode->addKeyData(
				objectKey: Env::$payloadKeyInResponse,
				data: $this->getExplainParam(
					sqlConfig: $wSqlConfig,
					isFirstCall: true,
					flag: $useHierarchy
				)
			);
		}
		$this->dataEncode->endObject();
	}

	/**
	 * Process for insert/update
	 *
	 * @param array $wSqlConfig   Write SQL config
	 * @param bool  $useHierarchy Use results in where clause of sub queries
	 *
	 * @return void
	 * @throws \Exception
	 */
	private function processWrite(&$wSqlConfig, $useHierarchy): void
	{
		// Check for payloadType
		if (isset($wSqlConfig['__PAYLOAD-TYPE__'])) {
			$payloadType = $this->http->req->s['payloadType'];
			if ($payloadType !== $wSqlConfig['__PAYLOAD-TYPE__']) {
				throw new \Exception(
					message: 'Invalid payload type',
					code: HttpStatus::$BadRequest
				);
			}
			// Check for maximum object's supported when payloadType is Array
			if (
				$wSqlConfig['__PAYLOAD-TYPE__'] === 'Array'
				&& isset($wSqlConfig['__MAX-PAYLOAD-OBJECTS__'])
				&& ($objCount = $this->http->req->dataDecode->count())
				&& ($objCount > $wSqlConfig['__MAX-PAYLOAD-OBJECTS__'])
			) {
				throw new \Exception(
					message: 'Maximum supported payload count is '
						. $wSqlConfig['__MAX-PAYLOAD-OBJECTS__'],
					code: HttpStatus::$BadRequest
				);
			}
		}

		// Set required fields
		$this->http->req->s['requiredFieldArrCollection'] = $this->getRequired(
			sqlConfig: $wSqlConfig,
			isFirstCall: true,
			flag: $useHierarchy
		);

		if ($this->http->req->s['payloadType'] === 'Object') {
			$this->dataEncode->startObject(objectKey: 'Results');
		} else {
			$this->dataEncode->startObject(objectKey: 'Results');
			if (in_array($this->http->res->oRepresentation, ['XML', 'XSLT', 'HTML'])) {
				$this->dataEncode->startArray(objectKey: 'Rows');
			}
		}

		// Perform action
		$iCount = $this->http->req->s['payloadType'] === 'Object'
			? 1 : $this->http->req->dataDecode->count();

		for ($i = 0; $i < $iCount; $i++) {
			$configKeyArr = [];
			$payloadIndexArr = [];
			if ($i === 0) {
				if ($this->http->req->s['payloadType'] === 'Object') {
					$payloadIndexArr[] = '';
				} else {
					$payloadIndexArr[] = "{$i}";
				}
			} else {
				$payloadIndexArr[] = "{$i}";
			}

			// Check for Idempotent Window
			[$idempotentWindow, $hashKey, $hashJson] = $this->checkIdempotent(
				sqlConfig: $wSqlConfig,
				payloadIndexArr: $payloadIndexArr
			);

			// Begin DML operation
			if ($hashJson === null) {
				if ($this->operateAsTransaction) {
					$this->http->req->clientDbObj->begin();
				}
				$response = [];
				$this->writeDb(
					wSqlConfig: $wSqlConfig,
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
					if (Env::$enablePayloadInResponse) {
						$arr[Env::$payloadKeyInResponse] = $this->http->req->dataDecode->getCompleteArray(
							keyString: implode(
								separator: ':',
								array: $payloadIndexArr
							)
						);
					}
					$arr['Response'] = $response;

					if ($idempotentWindow) {
						$this->http->req->clientCacheObj->cacheSet(
							cacheKey: $hashKey,
							value: json_encode(value: $arr),
							expire: $idempotentWindow
						);
					}
				} else { // Failure
					$arr = [];
					$arr['Status'] = $this->http->res->httpStatus;
					if (Env::$enablePayloadInResponse) {
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

		if ($this->http->req->s['payloadType'] === 'Object') {
			$this->dataEncode->endObject();
		} else {
			if (in_array($this->http->res->oRepresentation, ['XML', 'XSLT', 'HTML'])) {
				$this->dataEncode->endArray();
			}
			$this->dataEncode->endObject();
		}
	}

	/**
	 * Process $wSqlConfig recursively
	 *
	 * @param array $wSqlConfig       Write SQL config
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
		&$wSqlConfig,
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
			) : '';

		$isObject = $this->http->req->dataDecode->dataType(
			keyString: $payloadIndex
		) === 'Object';

		$iCount = $isObject
			? 1 : $this->http->req->dataDecode->count(keyString: $payloadIndex);

		$mode = getenv(name: $this->http->req->s['cDetail']['master_db_server_query_placeholder']);
		$function = "getSqlAndParam{$mode}Mode";

		for ($i = 0; $i < $iCount; $i++) {
			if ($isObject) {
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
				Env::$enableGlobalCounter
				&& isset($wSqlConfig['__VARIABLES__']['__GLOBAL_COUNTER__'])
			) {
				$wSqlConfig['__VARIABLES__']['__GLOBAL_COUNTER__'] = Counter::getGlobalCounter();
			}

			// Validation
			if (
				isset($wSqlConfig['__VALIDATE__'])
				&& !$this->isValidPayload(wSqlConfig: $wSqlConfig, response: $_response)
			) {
				continue;
			}

			// Execute Pre SQL Hook
			if (isset($wSqlConfig['__PRE-SQL-HOOKS__'])) {
				if ($this->hook === null) {
					$this->hook = new Hook($this->http);
				}
				$this->hook->triggerHook(
					hookConfig: $wSqlConfig['__PRE-SQL-HOOKS__']
				);
			}

			// Get SQL and ParamArr
			[$id, $sql, $sqlParamArr, $errorArr, $missExecution] = $this->$function(
				sqlConfig: $wSqlConfig
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
			$this->http->req->clientDbObj->execDbQuery(sql: $sql, paramArr: $sqlParamArr);
			if (
				$this->operateAsTransaction
				&& !$this->http->req->clientDbObj->beganTransaction
			) {
				$_response['Error'] = 'Something went wrong';
				return;
			}

			if (isset($wSqlConfig['__INSERT-IDs__'])) {
				if (
					Env::$enableGlobalCounter
					&& isset($wSqlConfig['__VARIABLES__']['__GLOBAL_COUNTER__'])
				) {
					$id = $wSqlConfig['__VARIABLES__']['__GLOBAL_COUNTER__'];
				} else {
					$id = $this->http->req->clientDbObj->lastInsertId();
				}
				$_response[$wSqlConfig['__INSERT-IDs__']] = $id;
				$this->http->req->s['__INSERT-IDs__'][$wSqlConfig['__INSERT-IDs__']] = $id;
			} else {
				$affectedRowCount = $this->http->req->clientDbObj->affectedRowCount();
				$_response['affectedRowCount'] = $affectedRowCount;
			}
			$this->http->req->clientDbObj->closeCursor();

			// triggers
			if (isset($wSqlConfig['__TRIGGERS__'])) {
				$this->dataEncode->addKeyData(
					objectKey: '__TRIGGERS__',
					data: $this->getTriggerData(
						triggerConfig: $wSqlConfig['__TRIGGERS__']
					)
				);
			}

			// Execute Post SQL Hook
			if (isset($wSqlConfig['__POST-SQL-HOOKS__'])) {
				if ($this->hook === null) {
					$this->hook = new Hook($this->http);
				}
				$this->hook->triggerHook(
					hookConfig: $wSqlConfig['__POST-SQL-HOOKS__']
				);
			}

			// subQuery for payload
			if (isset($wSqlConfig['__SUB-QUERY__'])) {
				$this->callWriteDb(
					wSqlConfig: $wSqlConfig,
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
	 * @param array $wSqlConfig       Write SQL config
	 * @param array $payloadIndexArr  Payload Indexes
	 * @param array $configKeyArr     Config key's
	 * @param bool  $useHierarchy     Use results in where clause of sub queries
	 * @param array $response         Response by reference
	 * @param array $requiredFieldArr Required fields
	 *
	 * @return void
	 */
	private function callWriteDb(
		&$wSqlConfig,
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
			isset($wSqlConfig['__SUB-QUERY__'])
			&& $this->isObject(arr: $wSqlConfig['__SUB-QUERY__'])
		) {
			foreach ($wSqlConfig['__SUB-QUERY__'] as $module => &$wSqlConfig) {
				$dataExist = false;
				$modulePayloadIndex = $payloadIndexArr;
				$moduleConfigKeyArr = $configKeyArr;
				array_push($modulePayloadIndex, $module);
				array_push($moduleConfigKeyArr, $module);

				$modulePayloadIndexKey = is_array(value: $modulePayloadIndex)
					? implode(separator: ':', array: $modulePayloadIndex) : '';
				$isObject = $this->http->req->dataDecode->dataType(
					keyString: $modulePayloadIndexKey
				) === 'Object';

				$iCount = $isObject
					? 1 : $this->http->req->dataDecode->count(keyString: $modulePayloadIndexKey);

				for ($i = 0; $i < $iCount; $i++) {
					$modulePayloadIndexItt = $modulePayloadIndex;
					if ($isObject) {
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
							sqlConfig: $wSqlConfig,
							keyword: 'useHierarchy'
						);
						$response[$module] = [];
						$response = &$response[$module];
						$this->writeDb(
							wSqlConfig: $wSqlConfig,
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
	 * @param array $wSqlConfig Write SQL config
	 * @param array $response   Response by reference
	 *
	 * @return bool
	 */
	private function isValidPayload($wSqlConfig, &$response): bool
	{
		$return = true;
		$isValidData = true;
		if (isset($wSqlConfig['__VALIDATE__'])) {
			[$isValidData, $errorArr] = $this->validate(
				validationConfig: $wSqlConfig['__VALIDATE__']
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
