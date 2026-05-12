<?php

/**
 * Read APIs
 * php version 8.3
 *
 * @category  ReadAPI
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
use Microservices\App\Export;
use Microservices\App\Hook;
use Microservices\App\Http;
use Microservices\App\HttpStatus;

/**
 * Read APIs
 * php version 8.3
 *
 * @category  ReadAPIs
 * @package   Openswoole_Microservices
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
	 * JSON Encode object
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
	 * @return bool|array
	 */
	public function process(): bool|array
	{
		// Load Sql
		$rSqlConfig = &$this->http->req->rParser->sqlConfig;

		// Rate Limiting request if configured for Route Sql.
		$this->rateLimitRoute(sqlConfig: $rSqlConfig);

		// Check for configured referrer Lags
		$this->checkReferrerLag(sqlConfig: $rSqlConfig);

		// Lag response
		$this->lagResponse(sqlConfig: $rSqlConfig);

		if (isset($rSqlConfig['__DOWNLOAD__'])) {
			return $this->download($rSqlConfig);
		}

		// Check for cache
		$toBeCached = false;
		if (
			Env::$enableResponseCaching
			&& isset($rSqlConfig['cacheKey'])
			&& !isset($this->http->req->s['queryParamArr']['orderBy'])
		) {
			$cacheReqCount = 0;
			$queryCacheReqFlag = false;
			for ($i = 0;$i < 5; $i++) {
				$json = DbCommonFunction::queryCacheGet(
					queryCacheKey: $rSqlConfig['cacheKey']
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
						$cacheReqCount = DbCommonFunction::queryCacheIncrement(queryCacheKey: $rSqlConfig['cacheKey']);
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
			Env::$enableResponseCaching
			&& $toBeCached
		) {
			$this->dataEncode = new DataEncode(http: $this->http);
			$this->dataEncode->init(header: false);
		} else {
			$this->dataEncode = &$this->http->res->dataEncode;
		}

		if (
			$this->http->res->oRepresentation === 'XSLT'
			&& isset($rSqlConfig['xsltFile'])
		) {
			$this->dataEncode->xsltFile = $rSqlConfig['xsltFile'];
		} elseif (
			$this->http->res->oRepresentation === 'HTML'
			&& isset($rSqlConfig['htmlFile'])
		) {
			$this->dataEncode->htmlFile = $rSqlConfig['htmlFile'];
		} elseif (
			$this->http->res->oRepresentation === 'PHP'
			&& isset($rSqlConfig['phpFile'])
		) {
			$this->dataEncode->phpFile = $rSqlConfig['phpFile'];
		}

		// Set Server mode to execute query on - Read / Write Server
		$fetchFrom = $rSqlConfig['fetchFrom'] ?? 'Slave';
		$this->modeColumn = strtolower($fetchFrom) . '_db_server_query_placeholder';
		$this->http->req->clientDbObj = DbCommonFunction::connectClientDb($this->http->req, fetchFrom: $fetchFrom);

		// Use result set recursively flag
		$useResultSet = $this->getUseHierarchy(
			sqlConfig: $rSqlConfig,
			keyword: 'useResultSet'
		);

		if (
			Env::$enableExplainRequest
			&& $this->http->req->rParser->routeEndingWithReservedKeywordFlag
			&& ($this->http->req->rParser->routeEndingReservedKeyword === Env::$explainRequestRouteKeyword)
		) {
			$this->explainRead(
				rSqlConfig: $rSqlConfig,
				useResultSet: $useResultSet
			);
		} else {
			$this->processRead(
				rSqlConfig: $rSqlConfig,
				useResultSet: $useResultSet
			);
		}

		if (
			Env::$enableResponseCaching
			&& $toBeCached
		) {
			$json = $this->dataEncode->getData();
			DbCommonFunction::queryCacheSet(
				queryCacheKey: $rSqlConfig['cacheKey'],
				json: $json
			);
			$this->http->res->dataEncode->appendData(data: $json);
		}

		return true;
	}

	/**
	 * Explain read configuration
	 *
	 * @param array $rSqlConfig   Read SQL config
	 * @param bool  $useResultSet Use result set recursively flag
	 *
	 * @return void
	 */
	private function explainRead(&$rSqlConfig, $useResultSet): void
	{
		$this->dataEncode->startObject(objectKey: 'Config');
		$this->dataEncode->addKeyData(
			objectKey: 'Route',
			data: $this->http->req->rParser->configuredRoute
		);
		$this->dataEncode->addKeyData(
			objectKey: 'Payload',
			data: $this->getExplainParam(
				sqlConfig: $rSqlConfig,
				isFirstCall: true,
				flag: $useResultSet
			)
		);
		$this->dataEncode->endObject();
	}

	/**
	 * Process read operation
	 *
	 * @param array $rSqlConfig   Read SQL config
	 * @param bool  $useResultSet Use result set recursively flag
	 *
	 * @return void
	 */
	private function processRead(&$rSqlConfig, $useResultSet): void
	{
		$this->http->req->s['requiredFieldArrCollection'] = $this->getRequired(
			sqlConfig: $rSqlConfig,
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
			rSqlConfig: $rSqlConfig,
			isFirstCall: true,
			configKeyArr: $configKeyArr,
			useResultSet: $useResultSet
		);
	}

	/**
	 * Process $rSqlConfig recursively
	 *
	 * @param array $rSqlConfig   Read SQL config
	 * @param bool  $isFirstCall  true to represent the first call in recursion
	 * @param array $configKeyArr Config key's in recursion
	 * @param bool  $useResultSet Use result set recursively flag
	 *
	 * @return void
	 */
	private function readDb(
		&$rSqlConfig,
		$isFirstCall,
		&$configKeyArr,
		$useResultSet
	): void {
		$isObject = $this->isObject(arr: $rSqlConfig);

		// Execute Pre SQL Hook
		if (isset($rSqlConfig['__PRE-SQL-HOOKS__'])) {
			if ($this->hook === null) {
				$this->hook = new Hook($this->http);
			}
			$this->hook->triggerHook(
				hookConfig: $rSqlConfig['__PRE-SQL-HOOKS__']
			);
		}

		if ($isObject) {
			switch ($rSqlConfig['__MODE__']) {
				// Query will return single row
				case 'singleRowFormat':
					if ($isFirstCall) {
						$this->dataEncode->startObject(objectKey: 'Results');
					} else {
						$this->dataEncode->startObject();
					}
					$this->fetchSingleRow(
						rSqlConfig: $rSqlConfig,
						isFirstCall: $isFirstCall,
						configKeyArr: $configKeyArr,
						useResultSet: $useResultSet
					);
					$this->dataEncode->endObject();
					break;
				// Query will return multiple rows
				case 'multipleRowFormat':
					if ($isFirstCall) {
						if (isset($rSqlConfig['countQuery'])) {
							$this->dataEncode->startObject(objectKey: 'Results');
							$this->fetchRowsCount(rSqlConfig: $rSqlConfig);
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
						rSqlConfig: $rSqlConfig,
						isFirstCall: $isFirstCall,
						configKeyArr: $configKeyArr,
						useResultSet: $useResultSet
					);
					$this->dataEncode->endArray();
					if (
						$isFirstCall
						&& isset($rSqlConfig['countQuery'])
					) {
						$this->dataEncode->endObject();
					}
					break;
			}
		}

		// triggers
		if (isset($rSqlConfig['__TRIGGERS__'])) {
			$this->dataEncode->addKeyData(
				objectKey: '__TRIGGERS__',
				data: $this->getTriggerData(
					triggerConfig: $rSqlConfig['__TRIGGERS__']
				)
			);
		}

		// Execute Post SQL Hook
		if (isset($rSqlConfig['__POST-SQL-HOOKS__'])) {
			if ($this->hook === null) {
				$this->hook = new Hook($this->http);
			}
			$this->hook->triggerHook(
				hookConfig: $rSqlConfig['__POST-SQL-HOOKS__']
			);
		}
	}

	/**
	 * Fetch single record
	 *
	 * @param array $rSqlConfig   Read SQL config
	 * @param bool  $isFirstCall  true to represent the first call in recursion
	 * @param array $configKeyArr Config key's
	 * @param bool  $useResultSet Use result set recursively flag
	 *
	 * @return void
	 * @throws \Exception
	 */
	private function fetchSingleRow(
		&$rSqlConfig,
		$isFirstCall,
		&$configKeyArr,
		$useResultSet
	): void {
		$mode = getenv(name: $this->http->req->s['cDetail'][$this->modeColumn]);
		$function = "getSqlAndParam{$mode}Mode";
		[$id, $sql, $sqlParamArr, $errorArr, $missExecution] = $this->$function(
			sqlConfig: $rSqlConfig,
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

		$this->http->req->clientDbObj->execDbQuery(sql: $sql, paramArr: $sqlParamArr);
		if ($row = $this->http->req->clientDbObj->fetch()) {
			foreach ($row as $objectKey => $value) {
				$this->dataEncode->addKeyData(objectKey: $objectKey, data: $value);
			}
			// check if selected column-name mismatches or conflicts with
			// configured module/submodule names
			if (isset($rSqlConfig['__SUB-QUERY__'])) {
				$subQueryKeyArr = array_keys(array: $rSqlConfig['__SUB-QUERY__']);
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

		if (isset($rSqlConfig['__SUB-QUERY__'])) {
			$this->callReadDb(
				rSqlConfig: $rSqlConfig,
				configKeyArr: $configKeyArr,
				row: $row,
				useResultSet: $useResultSet
			);
		}
	}

	/**
	 * Fetch row count
	 *
	 * @param array $rSqlConfig Read SQL config
	 *
	 * @return void
	 * @throws \Exception
	 */
	private function fetchRowsCount($rSqlConfig): void
	{
		if (!isset($rSqlConfig['countQuery'])) {
			return;
		}
		$rSqlConfig['__QUERY__'] = $rSqlConfig['countQuery'];
		if (isset($rSqlConfig['__COUNT-SQL-COMMENT__'])) {
			$rSqlConfig['__SQL-COMMENT__'] = $rSqlConfig['__COUNT-SQL-COMMENT__'];
		}
		unset($rSqlConfig['__COUNT-SQL-COMMENT__']);
		unset($rSqlConfig['countQuery']);

		$this->http->req->s['queryParamArr']['page']  = $this->http->httpReqDetailArr['get']['page'] ?? 1;
		$this->http->req->s['queryParamArr']['perPage']  = $this->http->httpReqDetailArr['get']['perPage'] ??
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

		$mode = getenv(name: $this->http->req->s['cDetail'][$this->modeColumn]);
		$function = "getSqlAndParam{$mode}Mode";
		[$id, $sql, $sqlParamArr, $errorArr, $missExecution] = $this->$function(
			sqlConfig: $rSqlConfig
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

		$this->http->req->clientDbObj->execDbQuery(sql: $sql, paramArr: $sqlParamArr);
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
	 * @param array $rSqlConfig   Read SQL config
	 * @param bool  $isFirstCall  true to represent the first call in recursion
	 * @param array $configKeyArr Config key's
	 * @param bool  $useResultSet Use result set recursively flag
	 *
	 * @return void
	 * @throws \Exception
	 */
	private function fetchMultipleRows(
		&$rSqlConfig,
		$isFirstCall,
		&$configKeyArr,
		$useResultSet
	): void {
		$mode = getenv(name: $this->http->req->s['cDetail'][$this->modeColumn]);
		$function = "getSqlAndParam{$mode}Mode";
		[$id, $sql, $sqlParamArr, $errorArr, $missExecution] = $this->$function(
			sqlConfig: $rSqlConfig,
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

		if (isset($rSqlConfig['countQuery'])) {
			$start = $this->http->req->s['queryParamArr']['start'];
			$offset = $this->http->req->s['queryParamArr']['perPage'];
			$sql .= " LIMIT {$start}, {$offset}";
		}

		$singleColumn = false;
		$pushPop = true;
		$this->http->req->clientDbObj->execDbQuery(sql: $sql, paramArr: $sqlParamArr, pushPop: $pushPop);
		for ($i = 0; $row = $this->http->req->clientDbObj->fetch();) {
			if ($i === 0) {
				if (count(value: $row) === 1) {
					$singleColumn = true;
				}
				$singleColumn = $singleColumn
					&& !isset($rSqlConfig['__SUB-QUERY__']);
				$i++;
			}
			if ($singleColumn) {
				$this->dataEncode->encode(data: $row[key(array: $row)]);
			} elseif (isset($rSqlConfig['__SUB-QUERY__'])) {
				$this->dataEncode->startObject();
				foreach ($row as $objectKey => $value) {
					$this->dataEncode->addKeyData(objectKey: $objectKey, data: $value);
				}
				$this->callReadDb(
					rSqlConfig: $rSqlConfig,
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
	 * @param array $rSqlConfig   Read SQL config
	 * @param array $configKeyArr Config key's
	 * @param array $row          Row data fetched from DB
	 * @param bool  $useResultSet Use result set recursively flag
	 *
	 * @return void
	 */
	private function callReadDb(
		&$rSqlConfig,
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
			isset($rSqlConfig['__SUB-QUERY__'])
			&& $this->isObject(arr: $rSqlConfig['__SUB-QUERY__'])
		) {
			foreach ($rSqlConfig['__SUB-QUERY__'] as $module => &$rSqlConfig) {
				$moduleConfigKeyArr = $configKeyArr;
				$moduleConfigKeyArr[] = $module;
				$useResultSet = $useResultSet ??
					$this->getUseHierarchy(
						sqlConfig: $rSqlConfig,
						keyword: 'useResultSet'
					);
				$this->readDb(
					rSqlConfig: $rSqlConfig,
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
	 * @param array $rSqlConfig Read SQL config
	 *
	 * @return array
	 */
	private function download($rSqlConfig): array
	{
		$return = [[], '', HttpStatus::$Ok];

		if (!Env::$enableDownloadRequest) {
			return [[], '', HttpStatus::$NotFound];
		}

		$mode = getenv(name: $this->http->req->s['cDetail'][$this->modeColumn]);
		$function = "getSqlAndParam{$mode}Mode";
		[$id, $sql, $sqlParamArr, $errorArr, $missExecution] = $this->$function(
			sqlConfig: $rSqlConfig
		);
		$serverMode = isset($rSqlConfig['fetchFrom'])
			? $rSqlConfig['fetchFrom'] : 'Slave';

		$exportDbDetail = [];
		switch ($serverMode) {
			case 'Master':
				$exportDbDetail = DbCommonFunction::clientDbMasterDetail($this->http->req);
				break;
			case 'Slave':
				$exportDbDetail = DbCommonFunction::dbSlaveDetail($this->http->req);
				break;
		}

		// Export
		$export = new Export(http: $this->http, dbServerType: $exportDbDetail['dbServerType']);
		$export->init(
			dbServerHostname: $exportDbDetail['dbServerHostname'],
			dbServerPort: $exportDbDetail['dbServerPort'],
			dbServerUsername: $exportDbDetail['dbServerUsername'],
			dbServerPassword: $exportDbDetail['dbServerPassword'],
			dbServerDb: $exportDbDetail['dbServerDb']
		);

		if (isset($rSqlConfig['downloadFile'])) {
			$downloadFile = date('Ymd-His') . '-' . $rSqlConfig['downloadFile'];
			if (
				isset($rSqlConfig['exportFile'])
				&& !empty($rSqlConfig['exportFile'])
			) {
				$return = $export->initDownload(
					downloadFile: $downloadFile,
					sql: $sql,
					paramArr: $sqlParamArr,
					exportFile: $rSqlConfig['exportFile']
				);
			} else {
				$return = $export->initDownload(
					downloadFile: $downloadFile,
					sql: $sql,
					paramArr: $sqlParamArr
				);
			}
		} else {
			if (isset($rSqlConfig['exportFile'])) {
				$return = $export->saveExport(
					sql: $sql,
					paramArr: $sqlParamArr,
					exportFile: $rSqlConfig['exportFile']
				);
			}
		}

		return $return;
	}
}
