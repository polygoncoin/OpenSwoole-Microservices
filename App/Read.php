<?php

/**
 * Read APIs
 * php version 8.3
 *
 * @category  ReadAPI
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
use Microservices\App\Export;
use Microservices\App\Hook;
use Microservices\App\Http;
use Microservices\App\HttpStatus;

/**
 * Read APIs
 * php version 8.3
 *
 * @category  ReadAPIs
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class Read
{
	use AppTrait;

	/**
	 * Hook object
	 *
	 * @var null|Hook
	 */
	private $hook = null;

	/**
	 * Data Encode object
	 *
	 * @var null|DataEncode
	 */
	public $dataEncode = null;

	/**
	 * Fetch mode
	 *
	 * @var null|string
	 */
	public $modeColumn = null;

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
		$readSqlConfig = &$this->http->req->rParser->sqlConfig;

		// Rate Limiting request if configured for Route Sql.
		$this->rateLimitRoute(sqlConfig: $readSqlConfig);

		// Check for configured referrer Lags
		$this->checkReferrerLag(sqlConfig: $readSqlConfig);

		// Lag response
		$this->lagResponse(sqlConfig: $readSqlConfig);

		if (isset($readSqlConfig['__DOWNLOAD__'])) {
			return $this->download(readSqlConfig: $readSqlConfig);
		}

		// Check for cache
		$toBeCached = false;
		if (
			CommonFunction::isEnabled(http: $this->http, feature: 'enableResponseCaching')
			&& isset($readSqlConfig['queryCacheKey'])
			&& !isset($this->http->req->s['queryParamArr']['orderBy'])
		) {
			$cacheReqCount = 0;
			$queryCacheReqFlag = false;
			for ($i = 0;$i < 5; $i++) {
				$json = DbCommonFunction::queryCacheGet(
					customerId: $this->http->req->customerId,
					queryCacheKey: $readSqlConfig['queryCacheKey']
				);
				if ($json !== null) {
					$cacheHit = 'true';
					$this->http->res->dataEncode->appendKeyData(
						objectKey: 'cacheHit',
						data: $cacheHit
					);
					$this->http->res->dataEncode->appendData(data: $json);
					return true;
				} else {
					if (!$queryCacheReqFlag) {
						$cacheReqCount = DbCommonFunction::queryCacheIncrement(
							customerId: $this->http->req->customerId,
							queryCacheKey: $readSqlConfig['queryCacheKey']
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

		if (
			CommonFunction::isEnabled(http: $this->http, feature: 'enableResponseCaching')
			&& $toBeCached
		) {
			$this->dataEncode = new DataEncode(http: $this->http);
			$this->dataEncode->init(header: false);
		} else {
			$this->dataEncode = &$this->http->res->dataEncode;
		}

		// Set Server mode to execute query on - Read / Write Server
		$fetchFrom = $readSqlConfig['fetchFrom'] ?? 'Slave';
		$this->modeColumn = strtolower($fetchFrom) . '_db_server_query_placeholder';
		$this->http->req->clientDbObj = DbCommonFunction::connectClientDb(
			customerData: $this->http->req->s['customerData'],
			fetchFrom: $fetchFrom
		);

		// Use result set recursively flag
		$useResultSet = $this->getUseHierarchy(
			sqlConfig: $readSqlConfig,
			keyword: 'useResultSet'
		);

		if (
			CommonFunction::isEnabled(http: $this->http, feature: 'enableExplainRequest')
			&& $this->http->req->rParser->routeEndingWithReservedKeywordFlag
			&& ($this->http->req->rParser->routeEndingReservedKeyword === Env::$explainRequestRouteKeyword)
		) {
			$this->explainRead(
				readSqlConfig: $readSqlConfig,
				useResultSet: $useResultSet
			);
		} else {
			$this->processRead(
				readSqlConfig: $readSqlConfig,
				useResultSet: $useResultSet
			);
		}

		if (
			CommonFunction::isEnabled(http: $this->http, feature: 'enableResponseCaching')
			&& $toBeCached
		) {
			$json = $this->dataEncode->getData();
			DbCommonFunction::queryCacheSet(
				customerId: $this->http->req->customerId,
				queryCacheKey: $readSqlConfig['queryCacheKey'],
				queryCacheValue: $json
			);
			$this->http->res->dataEncode->appendData(data: $json);
		}

		return true;
	}

	/**
	 * Explain read configuration
	 *
	 * @param array $readSqlConfig Read SQL config
	 * @param bool  $useResultSet  Use result set recursively flag
	 *
	 * @return void
	 */
	private function explainRead(&$readSqlConfig, $useResultSet): void
	{
		$this->dataEncode->startObject(objectKey: 'Config');
		$this->dataEncode->addKeyData(
			objectKey: 'Route',
			data: $this->http->req->rParser->configuredRoute
		);
		$this->dataEncode->addKeyData(
			objectKey: 'Payload',
			data: $this->getExplainParam(
				sqlConfig: $readSqlConfig,
				isFirstCall: true,
				flag: $useResultSet
			)
		);
		$this->dataEncode->endObject();
	}

	/**
	 * Process read operation
	 *
	 * @param array $readSqlConfig Read SQL config
	 * @param bool  $useResultSet  Use result set recursively flag
	 *
	 * @return void
	 */
	private function processRead(&$readSqlConfig, $useResultSet): void
	{
		$this->http->req->s['requiredFieldArrCollection'] = $this->getRequired(
			sqlConfig: $readSqlConfig,
			isFirstCall: true,
			flag: $useResultSet
		);

		if (isset($this->http->req->s['requiredFieldArrCollection'])) {
			$this->http->req->s['requiredFieldArr'] = $this->http->req->s['requiredFieldArrCollection'];
		} else {
			$this->http->req->s['requiredFieldArr'] = [];
		}

		// Start Read operation
		$configKeyArr = [];
		$this->readDb(
			readSqlConfig: $readSqlConfig,
			isFirstCall: true,
			configKeyArr: $configKeyArr,
			useResultSet: $useResultSet
		);
	}

	/**
	 * Process $readSqlConfig recursively
	 *
	 * @param array $readSqlConfig Read SQL config
	 * @param bool  $isFirstCall   true to represent the first call in recursion
	 * @param array $configKeyArr  Config key's in recursion
	 * @param bool  $useResultSet  Use result set recursively flag
	 *
	 * @return void
	 */
	private function readDb(
		&$readSqlConfig,
		$isFirstCall,
		&$configKeyArr,
		$useResultSet
	): void {
		$isObject = $this->isObject(arr: $readSqlConfig);

		// Execute Pre SQL Hook
		if (isset($readSqlConfig['__PRE-SQL-HOOKS__'])) {
			if ($this->hook === null) {
				$this->hook = new Hook(http: $this->http);
			}
			$this->hook->triggerHook(
				hookArr: $readSqlConfig['__PRE-SQL-HOOKS__']
			);
		}

		if ($isObject) {
			switch ($readSqlConfig['__MODE__']) {
				// Query will return single row
				case 'singleRowFormat':
					if ($isFirstCall) {
						$this->dataEncode->startObject(objectKey: 'Results');
					} else {
						$this->dataEncode->startObject();
					}
					$this->fetchSingleRow(
						readSqlConfig: $readSqlConfig,
						isFirstCall: $isFirstCall,
						configKeyArr: $configKeyArr,
						useResultSet: $useResultSet
					);
					$this->dataEncode->endObject();
					break;
				// Query will return multiple rows
				case 'multipleRowFormat':
					if ($isFirstCall) {
						if (isset($readSqlConfig['countQuery'])) {
							$this->dataEncode->startObject(objectKey: 'Results');
							$this->fetchRowsCount(readSqlConfig: $readSqlConfig);
							$this->dataEncode->startArray(objectKey: 'Data');
						} else {
							$this->dataEncode->startArray(objectKey: 'Results');
						}
					} else {
						$this->dataEncode->startArray(
							objectKey: $configKeyArr[count(value: $configKeyArr) - 1]
						);
					}
					$this->fetchMultipleRows(
						readSqlConfig: $readSqlConfig,
						isFirstCall: $isFirstCall,
						configKeyArr: $configKeyArr,
						useResultSet: $useResultSet
					);
					$this->dataEncode->endArray();
					if (
						$isFirstCall
						&& isset($readSqlConfig['countQuery'])
					) {
						$this->dataEncode->endObject();
					}
					break;
			}
		}

		// triggers
		if (isset($readSqlConfig['__TRIGGERS__'])) {
			$this->dataEncode->addKeyData(
				objectKey: '__TRIGGERS__',
				data: $this->getTriggerData(
					triggerConfig: $readSqlConfig['__TRIGGERS__']
				)
			);
		}

		// Execute Post SQL Hook
		if (isset($readSqlConfig['__POST-SQL-HOOKS__'])) {
			if ($this->hook === null) {
				$this->hook = new Hook(http: $this->http);
			}
			$this->hook->triggerHook(
				hookArr: $readSqlConfig['__POST-SQL-HOOKS__']
			);
		}
	}

	/**
	 * Fetch single record
	 *
	 * @param array $readSqlConfig Read SQL config
	 * @param bool  $isFirstCall   true to represent the first call in recursion
	 * @param array $configKeyArr  Config key's
	 * @param bool  $useResultSet  Use result set recursively flag
	 *
	 * @return void
	 * @throws \Exception
	 */
	private function fetchSingleRow(
		&$readSqlConfig,
		$isFirstCall,
		&$configKeyArr,
		$useResultSet
	): void {
		$mode = getenv(name: $this->http->req->s['customerData'][$this->modeColumn]);
		$function = "getSqlAndParam{$mode}Mode";
		[$id, $sql, $paramArr, $errorArr, $missExecution] = $this->$function(
			sqlConfig: $readSqlConfig,
			configKeyArr: $configKeyArr
		);

		if (!empty($errorArr)) {
			throw new \Exception(
				message: $errorArr,
				code: HttpStatus::$InternalServerError
			);
		}

		if ($missExecution) {
			return;
		}

		$this->http->req->clientDbObj->execDbQuery(sql: $sql, paramArr: $paramArr);
		if ($row = $this->http->req->clientDbObj->fetch()) {
			foreach ($row as $objectKey => $value) {
				$this->dataEncode->addKeyData(objectKey: $objectKey, data: $value);
			}
			// check if selected column-name mismatches or conflicts with
			// configured module/submodule names
			if (isset($readSqlConfig['__SUB-QUERY__'])) {
				$subQueryKeyArr = array_keys(array: $readSqlConfig['__SUB-QUERY__']);
				foreach ($row as $objectKey => $value) {
					if (in_array(needle: $objectKey, haystack: $subQueryKeyArr)) {
						throw new \Exception(
							message: 'Invalid config: Conflicting column names',
							code: HttpStatus::$InternalServerError
						);
					}
				}
			}
		} else {
			if ($isFirstCall) {
				$this->http->res->httpStatus = HttpStatus::$NotFound;
				return;
			}
		}
		$this->http->req->clientDbObj->closeCursor();

		if (isset($readSqlConfig['__SUB-QUERY__'])) {
			$this->callReadDb(
				readSqlConfig: $readSqlConfig,
				configKeyArr: $configKeyArr,
				row: $row,
				useResultSet: $useResultSet
			);
		}
	}

	/**
	 * Fetch row count
	 *
	 * @param array $readSqlConfig Read SQL config
	 *
	 * @return void
	 * @throws \Exception
	 */
	private function fetchRowsCount($readSqlConfig): void
	{
		if (!isset($readSqlConfig['countQuery'])) {
			return;
		}
		$readSqlConfig['__QUERY__'] = $readSqlConfig['countQuery'];
		if (isset($readSqlConfig['__COUNT-SQL-COMMENT__'])) {
			$readSqlConfig['__SQL-COMMENT__'] = $readSqlConfig['__COUNT-SQL-COMMENT__'];
		}
		unset($readSqlConfig['__COUNT-SQL-COMMENT__']);
		unset($readSqlConfig['countQuery']);

		$this->http->req->s['queryParamArr']['page']  = $this->http->httpReqData['get']['page'] ?? 1;
		$this->http->req->s['queryParamArr']['perPage']  = $this->http->httpReqData['get']['perPage'] ??
			Env::$defaultPerPage;

		if ($this->http->req->s['queryParamArr']['perPage'] > Env::$maxResultsPerPage) {
			throw new \Exception(
				message: 'perPage exceeds max perPage value of ' . Env::$maxResultsPerPage,
				code: HttpStatus::$Forbidden
			);
		}

		$this->http->req->s['queryParamArr']['start'] = (
			($this->http->req->s['queryParamArr']['page'] - 1) *
			$this->http->req->s['queryParamArr']['perPage']
		);

		$mode = getenv(name: $this->http->req->s['customerData'][$this->modeColumn]);
		$function = "getSqlAndParam{$mode}Mode";
		[$id, $sql, $paramArr, $errorArr, $missExecution] = $this->$function(
			sqlConfig: $readSqlConfig
		);

		if (!empty($errorArr)) {
			throw new \Exception(
				message: $errorArr,
				code: HttpStatus::$InternalServerError
			);
		}

		if ($missExecution) {
			return;
		}

		$this->http->req->clientDbObj->execDbQuery(sql: $sql, paramArr: $paramArr);
		$row = $this->http->req->clientDbObj->fetch();
		$this->http->req->clientDbObj->closeCursor();

		$totalRowsCount = $row['count'];
		$totalPages = ceil(
			num: $totalRowsCount / $this->http->req->s['queryParamArr']['perPage']
		);

		$this->dataEncode->addKeyData(
			objectKey: 'page',
			data: $this->http->req->s['queryParamArr']['page']
		);
		$this->dataEncode->addKeyData(
			objectKey: 'perPage',
			data: $this->http->req->s['queryParamArr']['perPage']
		);
		$this->dataEncode->addKeyData(
			objectKey: 'totalPages',
			data: $totalPages
		);
		$this->dataEncode->addKeyData(
			objectKey: 'totalRecords',
			data: $totalRowsCount
		);
	}

	/**
	 * Fetch multiple record
	 *
	 * @param array $readSqlConfig Read SQL config
	 * @param bool  $isFirstCall   true to represent the first call in recursion
	 * @param array $configKeyArr  Config key's
	 * @param bool  $useResultSet  Use result set recursively flag
	 *
	 * @return void
	 * @throws \Exception
	 */
	private function fetchMultipleRows(
		&$readSqlConfig,
		$isFirstCall,
		&$configKeyArr,
		$useResultSet
	): void {
		$mode = getenv(name: $this->http->req->s['customerData'][$this->modeColumn]);
		$function = "getSqlAndParam{$mode}Mode";
		[$id, $sql, $paramArr, $errorArr, $missExecution] = $this->$function(
			sqlConfig: $readSqlConfig,
			configKeyArr: $configKeyArr
		);

		if (!empty($errorArr)) {
			throw new \Exception(
				message: $errorArr,
				code: HttpStatus::$InternalServerError
			);
		}

		if ($missExecution) {
			return;
		}

		if ($isFirstCall) {
			if (isset($this->http->req->s['queryParamArr']['orderBy'])) {
				$orderByStrArr = [];
				$orderByArr = json_decode(
					json: $this->http->req->s['queryParamArr']['orderBy'],
					associative: true
				);
				foreach ($orderByArr as $k => $v) {
					$k = str_replace(search: ['`', ' '], replace: '', subject: $k);
					$v = strtoupper(string: $v);
					if (in_array(needle: $v, haystack: ['ASC', 'DESC'])) {
						$orderByStrArr[] = "`{$k}` {$v}";
					}
				}
				if (count(value: $orderByStrArr) > 0) {
					$sql .= ' ORDER BY ' . implode(
						separator: ', ',
						array: $orderByStrArr
					);
				}
			}
		}

		if (isset($readSqlConfig['countQuery'])) {
			$start = $this->http->req->s['queryParamArr']['start'];
			$offset = $this->http->req->s['queryParamArr']['perPage'];
			$sql .= " LIMIT {$start}, {$offset}";
		}

		$singleColumn = false;
		$pushPop = true;
		$this->http->req->clientDbObj->execDbQuery(sql: $sql, paramArr: $paramArr, pushPop: $pushPop);
		for ($i = 0; $row = $this->http->req->clientDbObj->fetch();) {
			if ($i === 0) {
				if (count(value: $row) === 1) {
					$singleColumn = true;
				}
				$singleColumn = $singleColumn
					&& !isset($readSqlConfig['__SUB-QUERY__']);
				$i++;
			}
			if ($singleColumn) {
				$this->dataEncode->encode(data: $row[key(array: $row)]);
			} elseif (isset($readSqlConfig['__SUB-QUERY__'])) {
				$this->dataEncode->startObject();
				foreach ($row as $objectKey => $value) {
					$this->dataEncode->addKeyData(objectKey: $objectKey, data: $value);
				}
				$this->callReadDb(
					readSqlConfig: $readSqlConfig,
					configKeyArr: $configKeyArr,
					row: $row,
					useResultSet: $useResultSet
				);
				$this->dataEncode->endObject();
			} else {
				$this->dataEncode->encode(data: $row);
			}
		}
		$this->http->req->clientDbObj->closeCursor(pushPop: $pushPop);
	}

	/**
	 * Function readDb recursive helper
	 *
	 * @param array $readSqlConfig Read SQL config
	 * @param array $configKeyArr  Config key's
	 * @param array $row           Row data fetched from DB
	 * @param bool  $useResultSet  Use result set recursively flag
	 *
	 * @return void
	 */
	private function callReadDb(
		&$readSqlConfig,
		&$configKeyArr,
		$row,
		$useResultSet
	): void {
		if (
			$useResultSet
			&& !empty($row)
		) {
			$this->resetFetchData(
				fetchFrom: 'sqlResults',
				moduleKeyArr: $configKeyArr,
				row: $row
			);
		}

		if (
			isset($readSqlConfig['__SUB-QUERY__'])
			&& $this->isObject(arr: $readSqlConfig['__SUB-QUERY__'])
		) {
			foreach ($readSqlConfig['__SUB-QUERY__'] as $module => &$readSqlConfig) {
				$moduleConfigKeyArr = $configKeyArr;
				$moduleConfigKeyArr[] = $module;
				$useResultSet = $useResultSet ??
					$this->getUseHierarchy(
						sqlConfig: $readSqlConfig,
						keyword: 'useResultSet'
					);
				$this->readDb(
					readSqlConfig: $readSqlConfig,
					isFirstCall: false,
					configKeyArr: $moduleConfigKeyArr,
					useResultSet: $useResultSet
				);
			}
		}
	}

	/**
	 * Download data
	 *
	 * @param array $readSqlConfig Read SQL config
	 *
	 * @return array
	 */
	private function download($readSqlConfig): array
	{
		$return = [[], '', HttpStatus::$Ok];

		if (!CommonFunction::isEnabled(http: $this->http, feature: 'enableDownloadRequest')) {
			return [[], '', HttpStatus::$NotFound];
		}

		$mode = getenv(name: $this->http->req->s['customerData'][$this->modeColumn]);
		$function = "getSqlAndParam{$mode}Mode";
		[$id, $sql, $paramArr, $errorArr, $missExecution] = $this->$function(
			sqlConfig: $readSqlConfig
		);
		$serverMode = isset($readSqlConfig['fetchFrom'])
			? $readSqlConfig['fetchFrom'] : 'Slave';

		$exportDbData = [];
		switch ($serverMode) {
			case 'Master':
				$exportDbData = DbCommonFunction::clientMasterDatabaseServerCred(customerData: $this->http->req->s['customerData']);
				break;
			case 'Slave':
				$exportDbData = DbCommonFunction::clientSlaveDatabaseServerCred(customerData: $this->http->req->s['customerData']);
				break;
		}

		// Export
		$export = new Export(http: $this->http, dbServerType: $exportDbData['dbServerType']);
		$export->init(
			dbServerHostname: $exportDbData['dbServerHostname'],
			dbServerPort: $exportDbData['dbServerPort'],
			dbServerUsername: $exportDbData['dbServerUsername'],
			dbServerPassword: $exportDbData['dbServerPassword'],
			dbServerDatabase: $exportDbData['dbServerDatabase']
		);

		if (isset($readSqlConfig['downloadFile'])) {
			$downloadFile = date('Ymd-His') . '-' . $readSqlConfig['downloadFile'];
			if (
				isset($readSqlConfig['exportFile'])
				&& !empty($readSqlConfig['exportFile'])
			) {
				$return = $export->initDownload(
					downloadFile: $downloadFile,
					sql: $sql,
					paramArr: $paramArr,
					exportFile: $readSqlConfig['exportFile']
				);
			} else {
				$return = $export->initDownload(
					downloadFile: $downloadFile,
					sql: $sql,
					paramArr: $paramArr
				);
			}
		} else {
			if (isset($readSqlConfig['exportFile'])) {
				$return = $export->saveExport(
					sql: $sql,
					paramArr: $paramArr,
					exportFile: $readSqlConfig['exportFile']
				);
			}
		}

		return $return;
	}
}
