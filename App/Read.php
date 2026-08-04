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
use Microservices\App\Constant;
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
	private $hookObject = null;

	/**
	 * Data Encode object
	 * 
	 * @var null|DataEncode
	 */
	public $dataEncodeObject = null;

	/**
	 * Placeholder Mode
	 * 
	 * @var null|string
	 */
	public $placeholderMode = null;

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
		$return = $this->readBasics(
			$sqlConfig,
			$maintainHierarchy
		);

		if ($return !== Constant::$FALSE) {
			return $return;
		}

		if (isset($sqlConfig['__DOWNLOAD__'])) {
			return $this->download(
				readSqlConfig: $sqlConfig
			);
		}

		// Check for cache
		$toBeCached = $this->getToBeCached(
			sqlConfig: $sqlConfig
		);

		if (
			CommonFunction::isEnabled(
				httpObject: $this->httpObject,
				feature: 'customer_enabled_response_caching'
			)
			&& $toBeCached
		) {
			$this->dataEncodeObject = new DataEncode(
				httpObject: $this->httpObject
			);
			$this->dataEncodeObject->init(
				header: Constant::$FALSE
			);
		} else {
			$this->dataEncodeObject = &$this->httpObject->httpResponseObject->dataEncodeObject;
		}

		// Set Server mode to execute query on - Read / Write Server
		$fetchDbMode = $sqlConfig['__FETCH-MODE__'] ?? 'Slave';
		$placeholderModeKey = 'customer_' . strtolower($fetchDbMode) . '_db_server_query_placeholder';
		$this->placeholderMode = getenv(name: $this->httpObject->httpRequestObject->activeRequestData['customerData'][$placeholderModeKey]);
		$this->httpObject->httpRequestObject->customerDbObject = DbCommonFunction::connectCustomerDb(
			customerData: $this->httpObject->httpRequestObject->activeRequestData['customerData'],
			fetchDbMode: $fetchDbMode
		);

		$this->read(
			readSqlConfig: $sqlConfig,
			readMaintainHierarchy: $maintainHierarchy
		);

		if (
			CommonFunction::isEnabled(
				httpObject: $this->httpObject,
				feature: 'customer_enabled_response_caching'
			)
			&& $toBeCached
		) {
			$json = $this->dataEncodeObject->getData();
			$this->httpObject->httpRequestObject->customerQueryCacheObject->queryCacheSet(
				customerId: $this->httpObject->httpRequestObject->customerId,
				queryCacheKey: $sqlConfig['__CACHE-KEY__'],
				queryCacheValue: $json
			);
			$this->httpObject->httpResponseObject->dataEncodeObject->appendData(
				data: $json
			);
		}

		return true;
	}

	/**
	 * Perform read operation
	 * 
	 * @param array $readSqlConfig         Sql config
	 * @param bool  $readMaintainHierarchy If true - Uses parent payload/results in child
	 * 
	 * @return void
	 */
	private function read(
		&$readSqlConfig,
		$readMaintainHierarchy
	): void {
		// Check for payloadType
		if (isset($readSqlConfig['__PAYLOAD-TYPE__'])) {
			$readPayloadType = $this->httpObject->httpRequestObject->activeRequestData['payloadType'];
			if ($readPayloadType !== $readSqlConfig['__PAYLOAD-TYPE__']) {
				throw new \Exception(
					message: 'Invalid payload type',
					code: HttpStatus::$BadRequest
				);
			}

			// Check for maximum object's supported when payloadType is Array
			if (
				$readSqlConfig['__PAYLOAD-TYPE__'] === 'Array'
				&& isset($readSqlConfig['__MAX-PAYLOAD-OBJECT__'])
				&& ($objCount = $this->httpObject->httpRequestObject->dataDecodeObject->count())
				&& ($objCount > $readSqlConfig['__MAX-PAYLOAD-OBJECT__'])
			) {
				throw new \Exception(
					message: 'Maximum supported payload count is '
						. $readSqlConfig['__MAX-PAYLOAD-OBJECT__'],
					code: HttpStatus::$BadRequest
				);
			}
		}

		// Set required fields
		$this->httpObject->httpRequestObject->activeRequestData['requiredFieldArrayCollection'] = $this->getRequired(
			sqlConfig: $readSqlConfig,
			maintainHierarchy: $readMaintainHierarchy,
			isFirstCall: Constant::$TRUE
		);

		$this->dataEncodeObject->startObject(
			objectKey: 'Results'
		);

		$startArray = false;
		if (
			isset($this->httpObject->httpRequestObject->activeRequestData['payloadType'])
			&& $this->httpObject->httpRequestObject->activeRequestData['payloadType'] === 'Array'
		) {
			if (
				in_array(
					needle: $this->httpObject->httpResponseObject->outputRepresentation,
					haystack: ['XML', 'XSLT', 'HTML'],
					strict: Constant::$TRUE
				)
			) {
				$startArray = true;
			}
		}

		if ($startArray) {
			$this->dataEncodeObject->startArray(
				objectKey: 'Records'
			);
		}

		$indexCount = $this->httpObject->httpRequestObject->activeRequestData['payloadType'] === 'Array'
			? $this->httpObject->httpRequestObject->dataDecodeObject->count() : 1;

		// Start Read operation
		for ($index = 0; $index < $indexCount; $index++) {
			$readPayloadKeyArray = null;

			if (isset($this->httpObject->httpRequestObject->activeRequestData['requiredFieldArrayCollection'])) {
				$this->httpObject->httpRequestObject->activeRequestData['requiredFieldArray'] = $this->httpObject->httpRequestObject->activeRequestData['requiredFieldArrayCollection'];
			} else {
				$this->httpObject->httpRequestObject->activeRequestData['requiredFieldArray'] = [];
			}

			if ($this->httpObject->httpRequestObject->activeRequestData['payloadType'] === 'Array') {
				$readPayloadKeyArray = [];
				$readPayloadKeyArray[] = "{$index}";
				$this->dataEncodeObject->startObject($index);
			}

			if (
				CommonFunction::isEnabled(
					httpObject: $this->httpObject,
					feature: 'customer_enabled_payload_in_response'
				)
			) {
				$readPayloadKey = $this->getPayloadKey(
					payloadKeyArray: $readPayloadKeyArray
				);

				$this->dataEncodeObject->addKeyData(
					objectKey: Env::$payloadKeyInResponse,
					data: $this->httpObject->httpRequestObject->dataDecodeObject->getCompleteArray(
						keyString: $readPayloadKey
					)
				);
			}

			$this->readParent(
				readParentSqlConfig: $readSqlConfig,
				readParentPayloadKeyArray: $readPayloadKeyArray,
				readParentRequiredFieldArray: $this->httpObject->httpRequestObject->activeRequestData['requiredFieldArrayCollection'],
				readParentMaintainHierarchy: $readMaintainHierarchy,
				readParentIsFirstCall: Constant::$TRUE
			);

			if ($this->httpObject->httpRequestObject->activeRequestData['payloadType'] === 'Array') {
				$this->dataEncodeObject->endObject();
			}
		}
		if ($startArray) {
			$this->dataEncodeObject->endArray();
		}
		$this->dataEncodeObject->endObject();
	}

	/**
	 * Process Read Parent Config Function
	 * 
	 * @param array $readParentSqlConfig          Sql config
	 * @param array $readParentPayloadKeyArray.
	 * @param array $readParentRequiredFieldArray
	 * @param bool  $readMaintainHierarchy        If true - Uses parent payload/results in child
	 * @param bool  $readIsFirstCall              true to represent the first call in recursion
	 * 
	 * @return void
	 */
	private function readParent(
		&$readParentSqlConfig,
		&$readParentPayloadKeyArray,
		&$readParentRequiredFieldArray,
		$readParentMaintainHierarchy,
		$readParentIsFirstCall
	): void {
		// For payloadKey
		$readParentPayloadKey = $this->getPayloadKey(
			payloadKeyArray: $readParentPayloadKeyArray
		);

		// For isObject
		$isObject = $this->httpObject->httpRequestObject->dataDecodeObject->dataType(
			keyString: $readParentPayloadKey
		) === 'Object';
		if ($isObject === Constant::$NULL) {
			return;
		}

		// For indexCount
		$indexCount = ($isObject || $isObject === Constant::$NULL)
			? 1 : $this->httpObject->httpRequestObject->dataDecodeObject->count(
				keyString: $readParentPayloadKey
			);

		$mode = getenv(name: $this->httpObject->httpRequestObject->activeRequestData['customerData']['customer_master_db_server_query_placeholder']);
		$function = "getSqlAndParam{$mode}Mode";

		// For Required Fields
		if (count(value: $readParentRequiredFieldArray)) {
			$this->httpObject->httpRequestObject->activeRequestData['requiredFieldArray'] = $readParentRequiredFieldArray;
		} else {
			$this->httpObject->httpRequestObject->activeRequestData['requiredFieldArray'] = [];
		}

		for ($index = 0; $index < $indexCount; $index++) {
			// For payloadKeyArray
			$readParentCurrentPayloadKeyArray = $readParentPayloadKeyArray;
			if (!$isObject) {
				array_push(
					$readParentCurrentPayloadKeyArray,
					"{$index}"
				);
			}

			// For payloadKey
			$readParentCurrentPayloadKey = $this->getPayloadKey(
				payloadKeyArray: $readParentCurrentPayloadKeyArray
			);

			// For Validating Hierarchy
			$readParentCurrentMaintainHierarchy = $readParentMaintainHierarchy;
			if (
				$readParentCurrentMaintainHierarchy
				&& !$this->httpObject->httpRequestObject->dataDecodeObject->isset(
					keyString: $readParentCurrentPayloadKey
				)
			) {
				throw new \Exception(
					message: "Payload key '{$readParentCurrentPayloadKey}' not set",
					code: HttpStatus::$NotFound
				);
			}

			// For isObject
			$isObject = $this->httpObject->httpRequestObject->dataDecodeObject->dataType(
				keyString: $readParentCurrentPayloadKey
			) === 'Object';
			if ($isObject === Constant::$NULL) {
				return;
			}

			// For Payload
			$this->httpObject->httpRequestObject->activeRequestData['payload'] = $this->httpObject->httpRequestObject->dataDecodeObject->get(
				keyString: $readParentCurrentPayloadKey
			);

			// For Validation
			if (
				isset($readParentSqlConfig['__VALIDATE__'])
				&& !$this->isValidPayload(
					sqlConfig: $readParentSqlConfig,
					response: $readParentCurrentResponse
				)
			) {
				continue;
			}

			// For Pre Hook
			if (isset($readParentSqlConfig['__PRE-CONFIG-HOOK__'])) {
				if ($this->hookObject === Constant::$NULL) {
					$this->hookObject = new Hook(
						httpObject: $this->httpObject
					);
				}
				$this->hookObject->triggerHook(
					hookArray: $readParentSqlConfig['__PRE-CONFIG-HOOK__']
				);
			}

			// For Execute
			switch ($readParentSqlConfig['__MODE__']) {
				// Query will return single dbFetchedRecord
				case 'singleRecordFormat':
					if ($readParentIsFirstCall) {
						$this->dataEncodeObject->startObject(
							objectKey: 'PayloadResponse'
						);
					} else {
						$this->dataEncodeObject->startObject();
					}

					$this->fetchSingleRecord(
						readSqlConfig: $readParentSqlConfig,
						readPayloadKeyArray: $readParentCurrentPayloadKeyArray,
						readMaintainHierarchy: $readParentCurrentMaintainHierarchy,
						readIsFirstCall: $readParentIsFirstCall
					);
					$this->dataEncodeObject->endObject();
					break;
				// Query will return multiple rows
				case 'multipleRecordFormat':
					if ($readParentIsFirstCall) {
						if (isset($readParentSqlConfig['__COUNT-SQL__'])) {
							$this->dataEncodeObject->startObject(
								objectKey: 'PayloadResponse'
							);
						} else {
							$this->dataEncodeObject->startArray(
								objectKey: 'PayloadResponse'
							);
						}
						if (isset($readParentSqlConfig['__COUNT-SQL__'])) {
							$this->fetchRecordCount(
								readSqlConfig: $readParentSqlConfig
							);
							$this->dataEncodeObject->startArray(
								objectKey: 'Data'
							);
						}
					} else {
						$this->dataEncodeObject->startArray(
							objectKey: $readParentPayloadKeyArray[
								count(
									value: $readParentPayloadKeyArray
								) - 1
							]
						);
					}
					$this->fetchMultipleRecords(
						readSqlConfig: $readParentSqlConfig,
						readPayloadKeyArray: $readParentCurrentPayloadKeyArray,
						readMaintainHierarchy: $readParentCurrentMaintainHierarchy,
						readIsFirstCall: $readParentIsFirstCall
					);
					$this->dataEncodeObject->endArray();
					if (
						$readParentIsFirstCall
						&& isset($readParentSqlConfig['__COUNT-SQL__'])
					) {
						$this->dataEncodeObject->endObject();
					}
					break;
			}

			// For Triggers
			if (isset($readParentSqlConfig['__TRIGGER__'])) {
				$this->dataEncodeObject->addKeyData(
					objectKey: '__TRIGGER__',
					data: $this->getTriggerData(
						triggerConfig: $readParentSqlConfig['__TRIGGER__']
					)
				);
			}

			// For Post Hook
			if (isset($readParentSqlConfig['__POST-CONFIG-HOOK__'])) {
				if ($this->hookObject === Constant::$NULL) {
					$this->hookObject = new Hook(
						httpObject: $this->httpObject
					);
				}
				$this->hookObject->triggerHook(
					hookArray: $readParentSqlConfig['__POST-CONFIG-HOOK__']
				);
			}
		}
	}

	/**
	 * Process Read Child Config Function
	 * 
	 * @param array $readSqlConfig         Sql config
	 * @param array $readPayloadKeyArray
	 * @param array $dbFetchedRecord       Record data fetched from DB
	 * @param bool  $readMaintainHierarchy If true - Uses parent payload/results in child
	 * 
	 * @return void
	 */
	private function readChild(
		&$readChildSqlConfig,
		&$readChildPayloadKeyArray,
		&$dbFetchedRecord,
		$readChildMaintainHierarchy
	): void {
		if ($readChildMaintainHierarchy) {
			$this->resetFetchData(
				activeRequestDataKey: 'sqlPayload',
				payloadKeyArray: $readChildPayloadKeyArray,
				record: $dbFetchedRecord
			);
		}

		if (
			isset($readChildPayloadKeyArray[0])
			&& $readChildPayloadKeyArray[0] === ''
		) {
			$readChildPayloadKeyArray = array_shift(
				$readChildPayloadKeyArray
			);
		}
		if (!is_array(value: $readChildPayloadKeyArray)) {
			$readChildPayloadKeyArray = [];
		}

		if (
			!(
				isset($readChildSqlConfig['__SUB-CONFIG__'])
				&& !$this->isObject(
					arr: $readChildSqlConfig['__SUB-CONFIG__']
				)
			)
		) {
			return;
		}

		foreach ($readChildSqlConfig['__SUB-CONFIG__'] as $readModule => &$readChildModuleSqlConfig) {
			// For payloadKeyArray
			$readChildModulePayloadKeyArray = $readChildPayloadKeyArray;
			array_push(
				$readChildModulePayloadKeyArray,
				"{$readModule}"
			);

			// For payloadKey
			$readChildModulePayloadKey = $this->getPayloadKey(
				payloadKeyArray: $readChildModulePayloadKeyArray
			);

			// For Validating Hierarchy
			$readChildModuleMaintainHierarchy = $readChildMaintainHierarchy ?? $this->getMaintainHierarchy(
				sqlConfig: $readChildModuleSqlConfig
			);
			if (
				$readChildModuleMaintainHierarchy
				&& !$this->httpObject->httpRequestObject->dataDecodeObject->isset(
					keyString: $readChildModulePayloadKey
				)
			) {
				throw new \Exception(
					message: "Invalid payload: Module '{$readModule}' missing",
					code: HttpStatus::$NotFound
				);
			}

			// For isObject
			$isObject = $this->httpObject->httpRequestObject->dataDecodeObject->dataType(
				keyString: $readChildModulePayloadKey
			) === 'Object';
			if ($isObject === Constant::$NULL) {
				return;
			}

			// For indexCount
			$indexCount = ($isObject || $isObject === Constant::$NULL)
				? 1 : $this->httpObject->httpRequestObject->dataDecodeObject->count(
					keyString: $readChildModulePayloadKey
				);

			// For Required Fields
			if (isset($readChildRequiredFieldArray[$readModule])) {
				$readChildModuleRequiredFieldArray = &$readChildRequiredFieldArray[$readModule];
			} else {
				$readChildModuleRequiredFieldArray = &$readChildRequiredFieldArray;
			}

			for ($index = 0; $index < $indexCount; $index++) {
				// For payloadKeyArray
				$readChildModuleCurrentPayloadKeyArray = $readChildModulePayloadKeyArray;
				if (!$isObject) {
					array_push(
						$readChildModuleCurrentPayloadKeyArray,
						"{$index}"
					);
				}

				// For payloadKey
				$readChildModuleCurrentPayloadKey = $this->getPayloadKey(
					payloadKeyArray: $readChildModuleCurrentPayloadKeyArray
				);

				// For Validating Hierarchy
				$readChildModuleCurrentMaintainHierarchy = $readChildModuleMaintainHierarchy;
				if (
					$readChildModuleCurrentMaintainHierarchy
					&& !$this->httpObject->httpRequestObject->dataDecodeObject->isset(
						keyString: $readChildModuleCurrentPayloadKey
					)
				) {
					throw new \Exception(
						message: "Invalid payload: Module '{$readModule}' missing",
						code: HttpStatus::$NotFound
					);
				}

				// For Parent
				$this->readParent(
					readParentSqlConfig: $readChildModuleSqlConfig,
					readParentPayloadKeyArray: $readChildModulePayloadKeyArray,
					readParentRequiredFieldArray: $readChildModuleCurrentPayloadKeyArray,
					readParentMaintainHierarchy: $readChildModuleCurrentMaintainHierarchy,
					readParentIsFirstCall: Constant::$FALSE
				);
			}
		}
	}

	/**
	 * Fetch dbFetchedRecord count
	 * 
	 * @param array $readSqlConfig Sql config
	 * 
	 * @return void
	 * @throws \Exception
	 */
	private function fetchRecordCount(
		$readSqlConfig
	): void {
		if (!isset($readSqlConfig['__COUNT-SQL__'])) {
			return;
		}
		$readSqlConfig['__SQL__'] = $readSqlConfig['__COUNT-SQL__'];
		if (isset($readSqlConfig['__COUNT-SQL-COMMENT__'])) {
			$readSqlConfig['__SQL-COMMENT__'] = $readSqlConfig['__COUNT-SQL-COMMENT__'];
		}
		unset($readSqlConfig['__COUNT-SQL-COMMENT__']);
		unset($readSqlConfig['__COUNT-SQL__']);

		$this->httpObject->httpRequestObject->activeRequestData['queryParamArray']['page']  = $this->httpObject->httpRequestObject->activeRequestData['payload']['page'] ?? 1;
		$this->httpObject->httpRequestObject->activeRequestData['queryParamArray']['perPage']  = $this->httpObject->httpRequestObject->activeRequestData['payload']['perPage'] ??
			Env::$defaultPerPage;

		if ($this->httpObject->httpRequestObject->activeRequestData['queryParamArray']['perPage'] > Env::$maxResultsPerPage) {
			throw new \Exception(
				message: 'perPage exceeds max perPage value of ' . Env::$maxResultsPerPage,
				code: HttpStatus::$Forbidden
			);
		}

		$this->httpObject->httpRequestObject->activeRequestData['queryParamArray']['start'] = (
			($this->httpObject->httpRequestObject->activeRequestData['queryParamArray']['page'] - 1) * 
			$this->httpObject->httpRequestObject->activeRequestData['queryParamArray']['perPage']
		);

		$function = "getSqlAndParam{$this->placeholderMode}Mode";
		[$id, $sql, $paramArray, $errorArray] = $this->$function(
			sqlConfig: $readSqlConfig
		);

		if (!empty($errorArray)) {
			throw new \Exception(
				message: json_encode(
					value: $errorArray
				),
				code: HttpStatus::$InternalServerError
			);
		}

		$this->httpObject->httpRequestObject->customerDbObject->execQuery(
			sql: $sql,
			paramArray: $paramArray
		);
		$dbFetchedRecord = $this->httpObject->httpRequestObject->customerDbObject->fetch();
		$this->httpObject->httpRequestObject->customerDbObject->closeCursor();

		$totalRecordsCount = isset($dbFetchedRecord['count']) ? $dbFetchedRecord['count'] : 0;
		$totalPages = ceil(
			num: $totalRecordsCount / $this->httpObject->httpRequestObject->activeRequestData['queryParamArray']['perPage']
		);

		$this->dataEncodeObject->addKeyData(
			objectKey: 'page',
			data: $this->httpObject->httpRequestObject->activeRequestData['queryParamArray']['page']
		);
		$this->dataEncodeObject->addKeyData(
			objectKey: 'perPage',
			data: $this->httpObject->httpRequestObject->activeRequestData['queryParamArray']['perPage']
		);
		$this->dataEncodeObject->addKeyData(
			objectKey: 'totalPages',
			data: $totalPages
		);
		$this->dataEncodeObject->addKeyData(
			objectKey: 'totalRecords',
			data: $totalRecordsCount
		);
	}

	/**
	 * Fetch single record
	 * 
	 * @param array $readSqlConfig          Sql config
	 * @param array $readPayloadKeyArray
	 * @param bool  $readMaintainHierarchy  If true - Uses parent payload/results in child
	 * @param bool  $readIsFirstCall        true to represent the first call in recursion
	 * 
	 * @return void
	 * @throws \Exception
	 */
	private function fetchSingleRecord(
		&$readSqlConfig,
		&$readPayloadKeyArray,
		$readMaintainHierarchy,
		$readIsFirstCall
	): void {
		$function = "getSqlAndParam{$this->placeholderMode}Mode";
		[$id, $sql, $paramArray, $errorArray] = $this->$function(
			sqlConfig: $readSqlConfig,
			payloadKeyArray: $readPayloadKeyArray
		);

		if (!empty($errorArray)) {
			throw new \Exception(
				message: json_encode(
					value: $errorArray
				),
				code: HttpStatus::$InternalServerError
			);
		}

		$this->httpObject->httpRequestObject->customerDbObject->execQuery(
			sql: $sql,
			paramArray: $paramArray
		);
		if ($dbFetchedRecord = $this->httpObject->httpRequestObject->customerDbObject->fetch()) {
			foreach ($dbFetchedRecord as $objectKey => &$objectKeyValue) {
				$this->dataEncodeObject->addKeyData(
					objectKey: $objectKey,
					data: $objectKeyValue
				);
			}
			// check if selected column-name mismatches or conflicts with
			// configured module/submodule names
			if (isset($readSqlConfig['__SUB-CONFIG__'])) {
				$subQueryKeyArray = array_keys(
					array: $readSqlConfig['__SUB-CONFIG__']
				);
				foreach ($dbFetchedRecord as $objectKey => &$objectKeyValue) {
					if (
						in_array(
							needle: $objectKey,
							haystack: $subQueryKeyArray,
							strict: Constant::$TRUE
						)
					) {
						throw new \Exception(
							message: 'Invalid config: Conflicting column names',
							code: HttpStatus::$InternalServerError
						);
					}
				}
			}
		} else {
			if ($readIsFirstCall) {
				$this->httpObject->httpResponseObject->httpStatus = HttpStatus::$NotFound;
				return;
			}
		}
		$this->httpObject->httpRequestObject->customerDbObject->closeCursor();

		// For Child
		if (isset($readSqlConfig['__SUB-CONFIG__'])) {
			$this->readChild(
				readChildSqlConfig: $readSqlConfig,
				readChildPayloadKeyArray: $readPayloadKeyArray,
				dbFetchedRecord: $dbFetchedRecord,
				readChildMaintainHierarchy: $readMaintainHierarchy
			);
		}
	}

	/**
	 * Fetch multiple record
	 * 
	 * @param array $readSqlConfig         Sql config
	 * @param array $readPayloadKeyArray
	 * @param bool  $readMaintainHierarchy If true - Uses parent payload/results in child
	 * @param bool  $readIsFirstCall       true to represent first call in recursion
	 * 
	 * @return void
	 * @throws \Exception
	 */
	private function fetchMultipleRecords(
		&$readSqlConfig,
		&$readPayloadKeyArray,
		$readMaintainHierarchy,
		$readIsFirstCall
	): void {
		$function = "getSqlAndParam{$this->placeholderMode}Mode";

		[$id, $sql, $paramArray, $errorArray] = $this->$function(
			sqlConfig: $readSqlConfig,
			payloadKeyArray: $readPayloadKeyArray
		);

		if (!empty($errorArray)) {
			throw new \Exception(
				message: json_encode(
					value: $errorArray
				),
				code: HttpStatus::$InternalServerError
			);
		}

		if ($readIsFirstCall) {
			if (isset($this->httpObject->httpRequestObject->activeRequestData['queryParamArray']['orderBy'])) {
				$orderByStrArray = [];
				$orderByArray = CommonFunction::jsonDecode(
					value: $this->httpObject->httpRequestObject->activeRequestData['queryParamArray']['orderBy']
				);
				foreach ($orderByArray as $orderByKey => &$orderByKeyValue) {
					$orderByKey = str_replace(
						search: ['`', ' '],
						replace: '',
						subject: $orderByKey
					);
					$orderByKeyValue = strtoupper(
						string: $orderByKeyValue
					);
					if (
						in_array(
							needle: $orderByKeyValue,
							haystack: ['ASC', 'DESC'],
							strict: Constant::$TRUE
						)
					) {
						$orderByStrArray[] = "`{$orderByKey}` {$orderByKeyValue}";
					}
				}
				if (
					count(
						value: $orderByStrArray
					) > 0
				) {
					$sql .= ' ORDER BY ' . implode(
						separator: ', ',
						array: $orderByStrArray
					);
				}
			}
		}

		if (isset($readSqlConfig['__COUNT-SQL__'])) {
			$start = $this->httpObject->httpRequestObject->activeRequestData['queryParamArray']['start'];
			$offset = $this->httpObject->httpRequestObject->activeRequestData['queryParamArray']['perPage'];
			$sql .= " LIMIT {$start}, {$offset}";
		}

		$pushPop = true;
		$this->httpObject->httpRequestObject->customerDbObject->execQuery(
			sql: $sql,
			paramArray: $paramArray,
			pushPop: $pushPop
		);

		$singleColumn = false;
		for ($index = 0; $dbFetchedRecord = $this->httpObject->httpRequestObject->customerDbObject->fetch(); $index++) {
			if ($index === 0) {
				if (
					count(
						value: $dbFetchedRecord
					) === 1
				) {
					$singleColumn = true;
				}
				$singleColumn = $singleColumn
					&& !isset($readSqlConfig['__SUB-CONFIG__']);
			}
			if ($singleColumn) {
				$this->dataEncodeObject->encode(
					data: $dbFetchedRecord[
						key(
							array: $dbFetchedRecord
						)
					]
				);
			} elseif (isset($readSqlConfig['__SUB-CONFIG__'])) {
				$this->dataEncodeObject->startObject();
				foreach ($dbFetchedRecord as $rowKey => &$rowKeyValue) {
					$this->dataEncodeObject->addKeyData(
						objectKey: $rowKey,
						data: $rowKeyValue
					);
				}

				// For Child
				$this->readChild(
					readChildSqlConfig: $readSqlConfig,
					readChildPayloadKeyArray: $readPayloadKeyArray,
					dbFetchedRecord: $dbFetchedRecord,
					readChildMaintainHierarchy: $readMaintainHierarchy
				);
				$this->dataEncodeObject->endObject();
			} else {
				$this->dataEncodeObject->encode(
					data: $dbFetchedRecord
				);
			}
		}
		$this->httpObject->httpRequestObject->customerDbObject->closeCursor(
			pushPop: $pushPop
		);
	}

	/**
	 * Download data
	 * 
	 * @param array $readSqlConfig Sql config
	 * 
	 * @return array
	 */
	private function download(
		$readSqlConfig
	): array {
		$return = [[], '', HttpStatus::$Ok];

		if (
			!CommonFunction::isEnabled(
				httpObject: $this->httpObject,
				feature: 'customer_enabled_download_request'
			)
		) {
			return [[], '', HttpStatus::$NotFound];
		}

		$function = "getSqlAndParam{$this->placeholderMode}Mode";
		[$id, $sql, $paramArray, $errorArray] = $this->$function(
			sqlConfig: $readSqlConfig
		);
		$fetchDbMode = $readSqlConfig['__FETCH-MODE__'] ?? 'Slave';

		$exportDbData = [];
		switch ($fetchDbMode) {
			case 'Master':
				$exportDbData = DbCommonFunction::customerMasterDatabaseServerCred(
					customerData: $this->httpObject->httpRequestObject->activeRequestData['customerData']
				);
				break;
			case 'Slave':
				$exportDbData = DbCommonFunction::customerSlaveDatabaseServerCred(
					customerData: $this->httpObject->httpRequestObject->activeRequestData['customerData']
				);
				break;
		}

		// Export
		$export = new Export(
			httpObject: $this->httpObject,
			dbServerType: $exportDbData['dbServerType']
		);
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
					paramArray: $paramArray,
					exportFile: $readSqlConfig['exportFile']
				);
			} else {
				$return = $export->initDownload(
					downloadFile: $downloadFile,
					sql: $sql,
					paramArray: $paramArray
				);
			}
		} else {
			if (isset($readSqlConfig['exportFile'])) {
				$return = $export->saveExport(
					sql: $sql,
					paramArray: $paramArray,
					exportFile: $readSqlConfig['exportFile']
				);
			}
		}

		return $return;
	}
}
