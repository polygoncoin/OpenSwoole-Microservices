<?php

/**
 * Supplement APIs
 * php version 8.3
 *
 * @category  Supplement
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
 * Supplement APIs
 * php version 8.3
 *
 * @category  Supplement
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class Supplement
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
	 * Supplement Class object
	 *
	 * @var null|object
	 */
	public $supplementObj = null;

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
	 * @param object $supplementObj Supplement API object
	 *
	 * @return bool
	 */
	public function init(&$supplementObj): bool
	{
		$this->supplementObj = &$supplementObj;
		return $this->supplementObj->init();
	}

	/**
	 * Process
	 *
	 * @return bool|array
	 */
	public function process(): bool|array
	{
		// Load Sql
		$sSqlConfig = &$this->http->req->rParser->sqlConfig;

		// Rate Limiting request if configured for Route Sql.
		$this->rateLimitRoute(sqlConfig: $sSqlConfig);

		// Check for configured referrer Lags
		$this->checkReferrerLag(sqlConfig: $sSqlConfig);

		// Use results in where clause of sub queries recursively
		$useHierarchy = $this->getUseHierarchy(
			sqlConfig: $sSqlConfig,
			keyword: 'useHierarchy'
		);

		if (CommonFunction::isEnabled(http: $this->http, feature: 'enableExplainRequest')) {
			if (
				$this->http->req->rParser->routeEndingWithReservedKeywordFlag
				&& ($this->http->req->rParser->routeEndingReservedKeyword === Env::$explainRequestRouteKeyword)
			) {
				$this->explainSupplement(
					sSqlConfig: $sSqlConfig,
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
					writeSqlConfig: $writeSqlConfig,
					useHierarchy: $useHierarchy
				);

				return [$headerArr, $csv, HttpStatus::$Ok];
			}
		}

		if (
			$this->http->res->oRepresentation === 'XSLT'
			&& isset($sSqlConfig['xsltFile'])
		) {
			$this->dataEncode->xsltFile = $sSqlConfig['xsltFile'];
		} elseif (
			$this->http->res->oRepresentation === 'HTML'
			&& isset($sSqlConfig['htmlFile'])
		) {
			$this->dataEncode->htmlFile = $sSqlConfig['htmlFile'];
		} elseif (
			$this->http->res->oRepresentation === 'PHP'
			&& isset($sSqlConfig['phpFile'])
		) {
			$this->dataEncode->phpFile = $sSqlConfig['phpFile'];
		}

		// Lag response
		$this->lagResponse(sqlConfig: $sSqlConfig);

		// Operate as Transaction (BEGIN COMMIT else ROLLBACK on error)
		$this->operateAsTransaction = isset($sSqlConfig['isTransaction'])
			? $sSqlConfig['isTransaction'] : false;

		// Set Server mode to execute query on - Read / Write Server
		$this->http->req->clientDbObj = DbCommonFunction::connectClientDb(
			customerData: $this->http->req->s['customerData'],
			fetchFrom: 'Master'
		);

		$this->processPrivateSupplement(
			sSqlConfig: $sSqlConfig,
			useHierarchy: $useHierarchy
		);
		if (isset($sSqlConfig['affectedQueryCacheKeyArr'])) {
			for (
				$i = 0, $iCount = count(value: $sSqlConfig['affectedQueryCacheKeyArr']);
				$i < $iCount;
				$i++
			) {
				DbCommonFunction::queryCacheDelete(
					customerId: $this->http->req->customerId,
					queryCacheKey: $sSqlConfig['affectedQueryCacheKeyArr'][$i]
				);
			}
		}

		return true;
	}

	/**
	 * Explain supplement configuration
	 *
	 * @param array $sSqlConfig   SQL config
	 * @param bool  $useHierarchy Use results in where clause of sub queries
	 *
	 * @return void
	 */
	private function explainSupplement(&$sSqlConfig, $useHierarchy): void
	{
		$this->dataEncode->startObject(objectKey: 'Config');
		$this->dataEncode->addKeyData(
			objectKey: 'Route',
			data: $this->http->req->rParser->configuredRoute
		);
		$this->dataEncode->addKeyData(
			objectKey: 'Payload',
			data: $this->getExplainParam(
				sqlConfig: $sSqlConfig,
				isFirstCall: true,
				flag: $useHierarchy
			)
		);
		$this->dataEncode->endObject();
	}

	/**
	 * Process Function to insert/update
	 *
	 * @param array $sSqlConfig   SQL config
	 * @param bool  $useHierarchy Use results in where clause of sub queries
	 *
	 * @return void
	 * @throws \Exception
	 */
	private function processPrivateSupplement(&$sSqlConfig, $useHierarchy): void
	{
		// Check for payloadType
		if (isset($sSqlConfig['__PAYLOAD-TYPE__'])) {
			$payloadType = $this->http->req->s['payloadType'];
			if ($payloadType !== $sSqlConfig['__PAYLOAD-TYPE__']) {
				throw new \Exception(
					message: 'Invalid payload type',
					code: HttpStatus::$BadRequest
				);
			}
			// Check for maximum object's supported when payloadType is Array
			if (
				$sSqlConfig['__PAYLOAD-TYPE__'] === 'Array'
				&& isset($sSqlConfig['__MAX-PAYLOAD-OBJECTS__'])
				&& ($objCount = $this->http->req->dataDecode->count())
				&& ($objCount > $sSqlConfig['__MAX-PAYLOAD-OBJECTS__'])
			) {
				throw new \Exception(
					message: 'Maximum supported payload count is '
						. $sSqlConfig['__MAX-PAYLOAD-OBJECTS__'],
					code: HttpStatus::$BadRequest
				);
			}
		}

		// Set required fields
		$this->http->req->s['requiredFieldArrCollection'] = $this->getRequired(
			sqlConfig: $sSqlConfig,
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
			if ($this->http->req->isPrivateRequest) {
				[$idempotentWindow, $hashKey, $hashJson] = $this->checkIdempotent(
					sqlConfig: $sSqlConfig,
					payloadIndexArr: $payloadIndexArr
				);

				// Begin DML operation
				if ($hashJson === null) {
					if ($this->operateAsTransaction) {
						$this->http->req->clientDbObj->begin();
					}
					$response = [];
					$this->execSupplement(
						sSqlConfig: $sSqlConfig,
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

						if ($idempotentWindow) {
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
	 * Function to execute supplement recursively
	 *
	 * @param array $sSqlConfig       SQL config
	 * @param array $payloadIndexArr  Payload Indexes
	 * @param array $configKeyArr     Config key's
	 * @param bool  $useHierarchy     Use results in where clause of sub queries
	 * @param array $response         Response by reference
	 * @param array $requiredFieldArr Required fields
	 *
	 * @return void
	 * @throws \Exception
	 */
	private function execSupplement(
		&$sSqlConfig,
		$payloadIndexArr,
		$configKeyArr,
		$useHierarchy,
		&$response,
		&$requiredFieldArr
	): void {
		// Return if function is not set
		if (!isset($sSqlConfig['__FUNCTION__'])) {
			return;
		}

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
				if ($useHierarchy) {
					throw new \Exception(
						message: "Payload key '{$payloadIndex}' not set",
						code: HttpStatus::$NotFound
					);
				} else {
					continue;
				}
			}

			$this->http->req->s['payload'] = $this->http->req->dataDecode->get(
				keyString: $payloadIndex
			);

			if (count(value: $requiredFieldArr)) {
				$this->http->req->s['requiredFieldArr'] = $requiredFieldArr;
			} else {
				$this->http->req->s['requiredFieldArr'] = [];
			}

			// Validation
			if (!$this->isValidPayload(sSqlConfig: $sSqlConfig, response: $_response)) {
				continue;
			}

			// Execute Pre SQL Hook
			if (isset($sSqlConfig['__PRE-SQL-HOOKS__'])) {
				if ($this->hook === null) {
					$this->hook = new Hook(http: $this->http);
				}
				$this->hook->triggerHook(
					hookConfig: $sSqlConfig['__PRE-SQL-HOOKS__']
				);
			}

			// Execute function
			$_response = $this->supplementObj->process(
				$sSqlConfig['__FUNCTION__'],
				$this->http->req->s['payload']
			);

			if (
				$this->operateAsTransaction
				&& !$this->http->req->clientDbObj->beganTransaction
			) {
				$_response['Error'] = 'Something went wrong';
				return;
			}

			$this->http->req->clientDbObj->closeCursor();

			// triggers
			if (isset($sSqlConfig['__TRIGGERS__'])) {
				$this->dataEncode->addKeyData(
					objectKey: '__TRIGGERS__',
					data: $this->getTriggerData(
						triggerConfig: $sSqlConfig['__TRIGGERS__']
					)
				);
			}

			// Execute Post SQL Hook
			if (isset($sSqlConfig['__POST-SQL-HOOKS__'])) {
				if ($this->hook === null) {
					$this->hook = new Hook(http: $this->http);
				}
				$this->hook->triggerHook(
					hookConfig: $sSqlConfig['__POST-SQL-HOOKS__']
				);
			}

			// subQuery for payload
			if (isset($sSqlConfig['__SUB-PAYLOAD__'])) {
				$this->callExecSupplement(
					sSqlConfig: $sSqlConfig,
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
	 * Function execSupplement recursive helper
	 *
	 * @param array $sSqlConfig       SQL config
	 * @param array $payloadIndexArr  Payload Indexes
	 * @param array $configKeyArr     Config key's
	 * @param bool  $useHierarchy     Use results in where clause of sub queries
	 * @param array $response         Response by reference
	 * @param array $requiredFieldArr Required fields
	 *
	 * @return void
	 */
	private function callExecSupplement(
		&$sSqlConfig,
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
			isset($sSqlConfig['__SUB-PAYLOAD__'])
			&& $this->isObject(arr: $sSqlConfig['__SUB-PAYLOAD__'])
		) {
			foreach ($sSqlConfig['__SUB-PAYLOAD__'] as $module => &$sSqlConfig) {
				$dataExist = false;
				$payloadIndexArr = $payloadIndexArr;
				$configKeyArr = $configKeyArr;
				array_push($payloadIndexArr, $module);
				array_push($configKeyArr, $module);
				$modulePayloadKey = is_array(value: $payloadIndexArr)
					? implode(separator: ':', array: $payloadIndexArr) : '';
				$dataExist = $this->http->req->dataDecode->isset(
					keyString: $modulePayloadKey
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
						sqlConfig: $sSqlConfig,
						keyword: 'useHierarchy'
					);
					$response[$module] = [];
					$response = &$response[$module];
					$this->execSupplement(
						sSqlConfig: $sSqlConfig,
						payloadIndexArr: $payloadIndexArr,
						configKeyArr: $configKeyArr,
						useHierarchy: $useHierarchy,
						response: $response,
						requiredFieldArr: $requiredFieldArr
					);
				}
			}
		}
	}

	/**
	 * Checks if the payload is valid
	 *
	 * @param array $sSqlConfig SQL config
	 * @param array $response   Response by reference
	 *
	 * @return bool
	 */
	private function isValidPayload($sSqlConfig, $response): bool
	{
		$return = true;
		$isValidData = true;
		if (isset($sSqlConfig['__VALIDATE__'])) {
			[$isValidData, $errorArr] = $this->validate(
				validationConfig: $sSqlConfig['__VALIDATE__']
			);
			if ($isValidData !== true) {
				$this->http->res->httpStatus = HttpStatus::$BadRequest;
				$response['Error'] = $errorArr;
				$return = false;
			}
		}
		return $return;
	}
}
