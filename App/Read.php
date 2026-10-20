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
	public $encodeObject = null;

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
		return Constant::$TRUE;
	}

	/**
	 * Process
	 * 
	 * @return mixed
	 */
	public function process(): mixed
	{
		// Load Sql
		$sqlConfig = &$this->httpObject->httpReqData['active']['sqlData']['Config'];

		$return = $this->readBasics(
			$sqlConfig,
			$followSqlConfigThread
		);

		if ($return !== Constant::$FALSE) {
			return $return;
		}

		$outputRepresentation = CommonFunction::getOutputRepresentation(
			sqlConfig: $sqlConfig,
			httpObject: $this->httpObject
		);

		if (isset($sqlConfig['__EXPORT__'])) {
			return $this->download(
				readSqlConfig: $sqlConfig
			);
		}

		// Check for cache
		$toBeCached = $this->getToBeCached(
			sqlConfig: $sqlConfig
		);

		if (
			(
				$this->httpObject->Env->ENABLE_QUERY_CACHE_FOR_PUBLIC && $this->httpObject->request->isPublicRequest
				|| $this->httpObject->Env->ENABLE_QUERY_CACHE_FOR_PRIVATE_WEB && $this->httpObject->request->isPrivateWebRequest
				|| $this->httpObject->Env->ENABLE_QUERY_CACHE_FOR_PRIVATE_API && $this->httpObject->request->isPrivateApiRequest
			)
			&& $toBeCached
		) {
			$this->encodeObject = new DataEncode(
				httpObject: $this->httpObject,
				outputRepresentation: $outputRepresentation
			);
			$this->encodeObject->init(
				header: Constant::$FALSE
			);
		} else {
			$this->encodeObject = &$this->httpObject->response->encodeObject;
		}

		// Set Server mode to execute query on - Read / Write Server
		$fetchDbMode = $sqlConfig['__FETCH-MODE__'] ?? 'Slave';
		$placeholderModeKey = strtoupper($fetchDbMode) . '_DB_PLACEHOLDER';
		$this->placeholderMode = $this->httpObject->Env->$placeholderModeKey;
		$this->httpObject->db = DbCommonFunction::connectDatabase(
			customerId: $this->httpObject->httpReqData['active']['customerId'],
			fetchDbMode: $fetchDbMode
		);

		$this->read(
			readSqlConfig: $sqlConfig,
			readOutputRepresentation: $outputRepresentation,
			readEncodeObject: $this->encodeObject,
			readFollowSqlConfigThread: $followSqlConfigThread,
		);

		if (
			(
				$this->httpObject->Env->ENABLE_QUERY_CACHE_FOR_PUBLIC && $this->httpObject->request->isPublicRequest
				|| $this->httpObject->Env->ENABLE_QUERY_CACHE_FOR_PRIVATE_WEB && $this->httpObject->request->isPrivateWebRequest
				|| $this->httpObject->Env->ENABLE_QUERY_CACHE_FOR_PRIVATE_API && $this->httpObject->request->isPrivateApiRequest
			)
			&& $toBeCached
		) {
			$json = $this->encodeObject->getData();
			$this->httpObject->queryCache->queryCacheSet(
				customerId: $this->httpObject->httpReqData['active']['customerId'],
				queryCacheKey: $sqlConfig['__CACHE-KEY__'],
				queryCacheValue: $json
			);
			$this->httpObject->response->encodeObject->appendData(
				data: $json
			);
		}

		return Constant::$TRUE;
	}

	/**
	 * Perform read operation
	 * 
	 * @param array  $readSqlConfig             Sql config
	 * @param array  $readOutputRepresentation  Output reepresentation
	 * @param object $readEncodeObject          Read data encode object
	 * @param bool   $readFollowSqlConfigThread Follow Sql Config Thread
	 * 
	 * @return void
	 */
	private function read(
		&$readSqlConfig,
		&$readOutputRepresentation,
		&$readEncodeObject,
		$readFollowSqlConfigThread,
	): void {
		$readToBeEncoded = false;
		$outputRepresentation = CommonFunction::getOutputRepresentation(
			sqlConfig: $readSqlConfig,
			httpObject: $this->httpObject
		);
		if ($outputRepresentation !== $readOutputRepresentation) {
			$encodeObject = new DataEncode(
				httpObject: $this->httpObject,
				outputRepresentation: $outputRepresentation
			);
			$encodeObject->init(
				header: Constant::$FALSE
			);
			$readToBeEncoded = true;
		} else {
			$encodeObject = &$readEncodeObject;
		}
		// Set required fields
		$this->httpObject->httpReqData['active']['requiredFieldArrayCollection'] = $this->getRequired(
			sqlConfig: $readSqlConfig,
			followSqlConfigThread: $readFollowSqlConfigThread,
			isFirstCall: Constant::$TRUE
		);

		$encodeObject->startObject(
			objectKey: 'Results'
		);

		$readPayloadDataType = $this->httpObject->request->decodeObject->dataType(
			keyString: Constant::$NULL
		);

		$startArray = Constant::$FALSE;
		if ($readPayloadDataType === 'Array') {
			if (
				in_array(
					needle: $readOutputRepresentation['outputRepresentation'],
					haystack: ['XML', 'XSLT', 'HTML'],
					strict: Constant::$TRUE
				)
			) {
				$startArray = Constant::$TRUE;
			}
		}

		if ($startArray) {
			$encodeObject->startArray(
				objectKey: 'Records'
			);
		}

		$indexCount = $readPayloadDataType === 'Array'
			? $this->httpObject->request->decodeObject->count() : 1;

		// Start Read operation
		for ($index = 0; $index < $indexCount; $index++) {
			$readPayloadKeyArray = Constant::$NULL;

			if (isset($this->httpObject->httpReqData['active']['requiredFieldArrayCollection'])) {
				$this->httpObject->httpReqData['active']['requiredFieldArray'] = $this->httpObject->httpReqData['active']['requiredFieldArrayCollection'];
			} else {
				$this->httpObject->httpReqData['active']['requiredFieldArray'] = [];
			}

			if ($readPayloadDataType === 'Array') {
				$readPayloadKeyArray = [];
				$readPayloadKeyArray[] = "{$index}";
				$encodeObject->startObject($index);
			}

			if ($this->httpObject->Env->ENABLE_PAYLOAD_IN_RESPONSE) {
				$readPayloadKey = $this->getPayloadKey(
					payloadKeyArray: $readPayloadKeyArray
				);

				$encodeObject->addKeyData(
					objectKey: $this->httpObject->Env->PAYLOAD_KEY_IN_RESPONSE,
					data: $this->httpObject->request->decodeObject->getCompleteArray(
						keyString: $readPayloadKey
					)
				);
			}

			$this->readParent(
				readParentSqlConfig: $readSqlConfig,
				readParentOutputRepresentation: $outputRepresentation,
				readParentEncodeObject: $encodeObject,
				readParentPayloadKeyArray: $readPayloadKeyArray,
				readParentRequiredFieldArray: $this->httpObject->httpReqData['active']['requiredFieldArrayCollection'],
				readParentFollowSqlConfigThread: $readFollowSqlConfigThread,
				readParentIsFirstCall: Constant::$TRUE
			);

			if ($readPayloadDataType === 'Array') {
				$encodeObject->endObject();
			}
		}
		if ($startArray) {
			$encodeObject->endArray();
		}
		$encodeObject->endObject();
		if ($readToBeEncoded) {
			$data = $encodeObject->getData();
			$encodeObject = null;
			$readEncodeObject->appendData(
				data: $data
			);
		}
	}

	/**
	 * Process Read Parent Config Function
	 * 
	 * @param array  $readParentSqlConfig            Sql config
	 * @param array  $readParentOutputRepresentation 
	 * @param object $readParentEncodeObject.    
	 * @param array  $readParentPayloadKeyArray.     
	 * @param array  $readParentRequiredFieldArray.  
	 * @param bool   $readFollowSqlConfigThread      Follow Sql Config Thread
	 * @param bool   $readIsFirstCall                true to represent the first call in recursion
	 * 
	 * @return void
	 */
	private function readParent(
		&$readParentSqlConfig,
	 	&$readParentOutputRepresentation,
	 	&$readParentEncodeObject,
		&$readParentPayloadKeyArray,
		&$readParentRequiredFieldArray,
		$readParentFollowSqlConfigThread,
		$readParentIsFirstCall
	): void {
		$readToBeEncoded = false;
		$outputRepresentation = CommonFunction::getOutputRepresentation(
			sqlConfig: $readParentSqlConfig,
			httpObject: $this->httpObject
		);
		if ($outputRepresentation !== $readParentOutputRepresentation) {
			$encodeObject = new DataEncode(
				httpObject: $this->httpObject,
				outputRepresentation: $outputRepresentation
			);
			$encodeObject->init(
				header: Constant::$FALSE
			);
			$readToBeEncoded = true;
		} else {
			$encodeObject = &$readParentEncodeObject;
		}
		// For payloadKey
		$readParentPayloadKey = $this->getPayloadKey(
			payloadKeyArray: $readParentPayloadKeyArray
		);

		// For isObject
		$dataType = $this->httpObject->request->decodeObject->dataType(
			keyString: $readParentPayloadKey
		);
		if ($dataType === Constant::$NULL) {
			return;
		}
		$isObject = $dataType === 'Object';

		// For indexCount
		$indexCount = ($isObject || $isObject === Constant::$NULL)
			? 1 : $this->httpObject->request->decodeObject->count(
				keyString: $readParentPayloadKey
			);

		if (isset($readParentSqlConfig['__PAYLOAD-TYPE__'])) {
			$readPayloadDataType = $isObject ? 'Object' : 'Array';
			if ($readPayloadDataType !== $readParentSqlConfig['__PAYLOAD-TYPE__']) {
				$errorArray[] = "Payload can't be an {$readPayloadDataType}";
			}

			// Check for maximum object's supported when payloadType is Array
			if (
				$readParentSqlConfig['__PAYLOAD-TYPE__'] === 'Array'
				&& isset($readParentSqlConfig['__MAX-PAYLOAD-OBJECT__'])
				&& ($indexCount > $readParentSqlConfig['__MAX-PAYLOAD-OBJECT__'])
			) {
				$errorArray[] = 'Maximum supported payload count is '
						. $readParentSqlConfig['__MAX-PAYLOAD-OBJECT__'];
			}
		}

		$mode = $this->httpObject->Env->MASTER_DB_PLACEHOLDER;
		$function = "getSqlAndParam{$mode}Mode";

		// For Required Fields
		if (count(value: $readParentRequiredFieldArray)) {
			$this->httpObject->httpReqData['active']['requiredFieldArray'] = $readParentRequiredFieldArray;
		} else {
			$this->httpObject->httpReqData['active']['requiredFieldArray'] = [];
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

			// For Validating Sql Thread
			$readParentCurrentFollowSqlConfigThread = $readParentFollowSqlConfigThread;
			if (
				$readParentCurrentFollowSqlConfigThread
				&& !$this->httpObject->request->decodeObject->isset(
					keyString: $readParentCurrentPayloadKey
				)
			) {
				throw new \Exception(
					message: "Payload key '{$readParentCurrentPayloadKey}' not set",
					code: HttpStatus::$NotFound
				);
			}

			// For isObject
			$dataType = $this->httpObject->request->decodeObject->dataType(
				keyString: $readParentCurrentPayloadKey
			);
			if ($dataType === Constant::$NULL) {
				return;
			}
			$isObject = $dataType === 'Object';

			// For Payload
			$readParentPayload = $this->httpObject->request->decodeObject->getObject(
				keyString: $readParentCurrentPayloadKey
			);

			if ($readParentCurrentFollowSqlConfigThread) {
				$this->resetFetchData(
					activeDataKey: 'previousPayload',
					payloadKeyArray: $readParentCurrentPayloadKeyArray,
					record: $readParentPayload
				);
			}

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
						$encodeObject->startObject(
							objectKey: 'PayloadResponse'
						);
					} else {
						$encodeObject->startObject();
					}

					$this->fetchSingleRecord(
						readSqlConfig: $readParentSqlConfig,
						outputRepresentation: $outputRepresentation,
						encodeObject: $encodeObject,
						readPayload: $readParentPayload,
						readPayloadKeyArray: $readParentCurrentPayloadKeyArray,
						readFollowSqlConfigThread: $readParentCurrentFollowSqlConfigThread,
						readIsFirstCall: $readParentIsFirstCall
					);
					$encodeObject->endObject();
					break;
				// Query will return multiple rows
				case 'multipleRecordFormat':
					$start = Constant::$NULL;
					$perPage = Constant::$NULL;
					if ($readParentIsFirstCall) {
						if (isset($readParentSqlConfig['__COUNT-SQL__'])) {
							$encodeObject->startObject(
								objectKey: 'PayloadResponse'
							);
						} else {
							$encodeObject->startArray(
								objectKey: 'PayloadResponse'
							);
						}
						if (isset($readParentSqlConfig['__COUNT-SQL__'])) {
							$page  = $readParentPayload['page'] ?? 1;
							$perPage  = $readParentPayload['perPage'] ?? $this->httpObject->Env->DEFAULT_PER_PAGE_COUNT;
							$start = $this->fetchRecordCount(
								readSqlConfig: $readParentSqlConfig,
								outputRepresentation: $outputRepresentation,
								encodeObject: $encodeObject,
								readPayload: $readParentPayload,
								page: $page,
								perPage: $perPage
							);
							$encodeObject->startArray(
								objectKey: 'Data'
							);
						}
					} else {
						$encodeObject->startArray(
							objectKey: $readParentPayloadKeyArray[
								count(
									value: $readParentPayloadKeyArray
								) - 1
							]
						);
					}
					$this->fetchMultipleRecords(
						readSqlConfig: $readParentSqlConfig,
						outputRepresentation: $outputRepresentation,
						encodeObject: $encodeObject,
						readPayload: $readParentPayload,
						readPayloadKeyArray: $readParentCurrentPayloadKeyArray,
						readFollowSqlConfigThread: $readParentCurrentFollowSqlConfigThread,
						readIsFirstCall: $readParentIsFirstCall,
						start: $start,
						offset: $perPage

					);
					$encodeObject->endArray();
					if (
						$readParentIsFirstCall
						&& isset($readParentSqlConfig['__COUNT-SQL__'])
					) {
						$encodeObject->endObject();
					}
					break;
			}

			// For Triggers
			if (isset($readParentSqlConfig['__TRIGGER__'])) {
				$encodeObject->addKeyData(
					objectKey: '__TRIGGER__',
					data: $this->getTriggerData(
						triggerConfig: $readParentSqlConfig['__TRIGGER__'],
						payload: $readParentPayload
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

			// For Affected Cache
			if (isset($readParentSqlConfig['__AFFECTED-CACHE-KEY__'])) {
				$indexCount = count(
					value: $readParentSqlConfig['__AFFECTED-CACHE-KEY__']
				);
				for ($index = 0; $index < $indexCount; $index++) {
					$this->httpObject->queryCache->queryCacheDelete(
						customerId: $this->httpObject->httpReqData['active']['customerId'],
						queryCacheKey: $readParentSqlConfig['__AFFECTED-CACHE-KEY__'][$index]
					);
				}
			}
		}
		if ($readToBeEncoded) {
			$data = $encodeObject->getData();
			$encodeObject = null;
			$readParentEncodeObject->appendData(
				data: $data
			);
		}
	}

	/**
	 * Process Read Child Config Function
	 * 
	 * @param array $readSqlConfig             Sql config
	 * @param array $readPayloadKeyArray
	 * @param array $dbFetchedRecord           Record data fetched from DB
	 * @param bool  $readFollowSqlConfigThread Follow Sql Config Thread
	 * 
	 * @return void
	 */
	private function readChild(
		&$readChildSqlConfig,
		&$readChildOutputRepresentation,
		&$readChildEncodeObject,
		&$readChildPayloadKeyArray,
		&$dbFetchedRecord,
		$readChildFollowSqlConfigThread
	): void {
		$readToBeEncoded = false;
		$outputRepresentation = CommonFunction::getOutputRepresentation(
			sqlConfig: $readChildSqlConfig,
			httpObject: $this->httpObject
		);
		if ($outputRepresentation !== $readChildOutputRepresentation) {
			$encodeObject = new DataEncode(
				httpObject: $this->httpObject,
				outputRepresentation: $outputRepresentation
			);
			$encodeObject->init(
				header: Constant::$FALSE
			);
			$readToBeEncoded = true;
		} else {
			$encodeObject = &$readChildEncodeObject;
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
			$encodeObject->startObject(
				objectKey: $readModule
			);
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

			// For Validating Sql Thread
			$readChildModuleFollowSqlConfigThread = $readChildFollowSqlConfigThread ?? $this->getFollowSqlConfigThread(
				sqlConfig: $readChildModuleSqlConfig
			);
			if (
				$readChildModuleFollowSqlConfigThread
				&& !$this->httpObject->request->decodeObject->isset(
					keyString: $readChildModulePayloadKey
				)
			) {
				throw new \Exception(
					message: "Invalid payload: Module '{$readModule}' missing",
					code: HttpStatus::$NotFound
				);
			}

			// For isObject
			$dataType = $this->httpObject->request->decodeObject->dataType(
				keyString: $readChildModulePayloadKey
			);
			if ($dataType === Constant::$NULL) {
				return;
			}
			$isObject = $dataType === 'Object';

			// For indexCount
			$indexCount = ($isObject || $isObject === Constant::$NULL)
				? 1 : $this->httpObject->request->decodeObject->count(
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

				// For Validating Sql Thread
				$readChildModuleCurrentFollowSqlConfigThread = $readChildModuleFollowSqlConfigThread;
				if (
					$readChildModuleCurrentFollowSqlConfigThread
					&& !$this->httpObject->request->decodeObject->isset(
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
					readParentOutputRepresentation: $outputRepresentation,
					readParentEncodeObject: $encodeObject,
					readParentPayloadKeyArray: $readChildModulePayloadKeyArray,
					readParentRequiredFieldArray: $readChildModuleCurrentPayloadKeyArray,
					readParentFollowSqlConfigThread: $readChildModuleCurrentFollowSqlConfigThread,
					readParentIsFirstCall: Constant::$FALSE
				);
			}
			$encodeObject->endObject();
		}
		if ($readToBeEncoded) {
			$data = $encodeObject->getData();
			$encodeObject = null;
			$readChildEncodeObject->appendData(
				data: $data
			);
		}
	}

	/**
	 * Fetch dbFetchedRecord count
	 * 
	 * @param array  $readSqlConfig        Sql config
	 * @param array  $outputRepresentation Output reepresentation
	 * @param object $encodeObject         Read data encode object
	 * @param array  $readPayload          Payload
	 * @param int    $page                 Page Number
	 * @param int    $perPage              Records Per Page
	 * 
	 * @return int
	 * @throws \Exception
	 */
	private function fetchRecordCount(
		$readSqlConfig,
		&$outputRepresentation,
		&$encodeObject,
		&$readPayload,
		$page,
		$perPage
	): int {
		$readSqlConfig['__SQL__'] = $readSqlConfig['__COUNT-SQL__'];
		if (isset($readSqlConfig['__COUNT-SQL-COMMENT__'])) {
			$readSqlConfig['__SQL-COMMENT__'] = $readSqlConfig['__COUNT-SQL-COMMENT__'];
		}
		unset($readSqlConfig['__COUNT-SQL-COMMENT__']);
		unset($readSqlConfig['__COUNT-SQL__']);

		if ($perPage > $this->httpObject->Env->MAX_PER_PAGE_COUNT) {
			throw new \Exception(
				message: 'perPage exceeds max perPage value of ' . $this->httpObject->Env->MAX_PER_PAGE_COUNT,
				code: HttpStatus::$Forbidden
			);
		}

		$start = ($page - 1) * $perPage;

		$function = "getSqlAndParam{$this->placeholderMode}Mode";
		[
			$id,
			$sql,
			$paramArray,
			$errorArray
		] = $this->$function(
			sqlConfig: $readSqlConfig,
			payload: $readPayload
		);

		if (!empty($errorArray)) {
			throw new \Exception(
				message: json_encode(
					value: $errorArray
				),
				code: HttpStatus::$InternalServerError
			);
		}

		$this->httpObject->db->execQuery(
			sql: $sql,
			paramArray: $paramArray
		);
		$dbFetchedRecord = $this->httpObject->db->fetch();
		$this->httpObject->db->closeCursor();

		$totalRecordsCount = isset($dbFetchedRecord['count']) ? $dbFetchedRecord['count'] : 0;
		$totalPages = ceil(
			num: $totalRecordsCount / $perPage
		);

		$encodeObject->addKeyData(
			objectKey: 'page',
			data: $page
		);
		$encodeObject->addKeyData(
			objectKey: 'perPage',
			data: $perPage
		);
		$encodeObject->addKeyData(
			objectKey: 'totalPages',
			data: $totalPages
		);
		$encodeObject->addKeyData(
			objectKey: 'totalRecords',
			data: $totalRecordsCount
		);

		return $start;
	}

	/**
	 * Fetch single record
	 * 
	 * @param array  $readSqlConfig             Sql config
	 * @param array  $outputRepresentation      Output reepresentation
	 * @param object $encodeObject              Read data encode object
	 * @param array  $readPayload               Payload
	 * @param array  $readPayloadKeyArray
	 * @param bool   $readFollowSqlConfigThread Follow Sql Config Thread
	 * @param bool   $readIsFirstCall           true to represent the first call in recursion
	 * 
	 * @return void
	 * @throws \Exception
	 */
	private function fetchSingleRecord(
		&$readSqlConfig,
		&$outputRepresentation,
		&$encodeObject,
		&$readPayload,
		&$readPayloadKeyArray,
		$readFollowSqlConfigThread,
		$readIsFirstCall
	): void {
		$function = "getSqlAndParam{$this->placeholderMode}Mode";
		[
			$id,
			$sql,
			$paramArray,
			$errorArray
		] = $this->$function(
			sqlConfig: $readSqlConfig,
			payload: $readPayload,
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

		$this->httpObject->db->execQuery(
			sql: $sql,
			paramArray: $paramArray
		);
		if ($dbFetchedRecord = $this->httpObject->db->fetch()) {
			foreach ($dbFetchedRecord as $objectKey => &$objectKeyValue) {
				$encodeObject->addKeyData(
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
				$this->httpObject->response->httpStatus = HttpStatus::$NotFound;
				return;
			}
		}
		$this->httpObject->db->closeCursor();

		// For Child
		if (isset($readSqlConfig['__SUB-CONFIG__'])) {
			$this->readChild(
				readChildSqlConfig: $readSqlConfig,
				readChildOutputRepresentation: $outputRepresentation,
				readChildEncodeObject: $encodeObject,
				readChildPayloadKeyArray: $readPayloadKeyArray,
				dbFetchedRecord: $dbFetchedRecord,
				readChildFollowSqlConfigThread: $readFollowSqlConfigThread
			);
		}
	}

	/**
	 * Fetch multiple record
	 * 
	 * @param array  $readSqlConfig             Sql config
	 * @param array  $outputRepresentation      Output reepresentation
	 * @param object $encodeObject              Read data encode object
	 * @param array  $readPayload               Payload
	 * @param array  $readPayloadKeyArray
	 * @param bool   $readFollowSqlConfigThread Follow Sql Config Thread
	 * @param bool   $readIsFirstCall           true to represent first call in recursion
	 * @param bool   $start                     Start fetching record from
	 * @param bool   $offset                    Number of record to be fetched
	 * 
	 * @return void
	 * @throws \Exception
	 */
	private function fetchMultipleRecords(
		&$readSqlConfig,
		&$outputRepresentation,
		&$encodeObject,
		&$readPayload,
		&$readPayloadKeyArray,
		$readFollowSqlConfigThread,
		$readIsFirstCall,
		$start = null,
		$offset = null
	): void {
		$function = "getSqlAndParam{$this->placeholderMode}Mode";

		[
			$id,
			$sql,
			$paramArray,
			$errorArray
		] = $this->$function(
			sqlConfig: $readSqlConfig,
			payload: $readPayload,
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
			if (isset($this->httpObject->httpReqData['active']['queryParamArray']['orderBy'])) {
				$orderByStrArray = [];
				$orderByArray = CommonFunction::jsonDecode(
					value: $this->httpObject->httpReqData['active']['queryParamArray']['orderBy']
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
			$sql .= " LIMIT {$start}, {$offset}";
		}

		$pushPop = Constant::$TRUE;
		$this->httpObject->db->execQuery(
			sql: $sql,
			paramArray: $paramArray,
			pushPop: $pushPop
		);

		$singleColumn = Constant::$FALSE;
		for ($index = 0; $dbFetchedRecord = $this->httpObject->db->fetch(); $index++) {
			if ($index === 0) {
				if (
					count(
						value: $dbFetchedRecord
					) === 1
				) {
					$singleColumn = Constant::$TRUE;
				}
				$singleColumn = $singleColumn
					&& !isset($readSqlConfig['__SUB-CONFIG__']);
			}
			if ($singleColumn) {
				$encodeObject->encode(
					data: $dbFetchedRecord[
						key(
							array: $dbFetchedRecord
						)
					]
				);
			} elseif (isset($readSqlConfig['__SUB-CONFIG__'])) {
				$encodeObject->startObject();
				foreach ($dbFetchedRecord as $rowKey => &$rowKeyValue) {
					$encodeObject->addKeyData(
						objectKey: $rowKey,
						data: $rowKeyValue
					);
				}

				// For Child
				$this->readChild(
					readChildSqlConfig: $readSqlConfig,
					readChildOutputRepresentation: $outputRepresentation,
					readChildEncodeObject: $encodeObject,
					readChildPayloadKeyArray: $readPayloadKeyArray,
					dbFetchedRecord: $dbFetchedRecord,
					readChildFollowSqlConfigThread: $readFollowSqlConfigThread,
				);
				$encodeObject->endObject();
			} else {
				$encodeObject->encode(
					data: $dbFetchedRecord
				);
			}
		}
		$this->httpObject->db->closeCursor(
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
		&$readSqlConfig,
	): array {
		$return = [[], '', HttpStatus::$Ok];

		if (!$this->httpObject->Env->ENABLE_EXPORT) {
			return [[], '', HttpStatus::$NotFound];
		}

		$readPayload = $this->httpObject->request->decodeObject->getObject(
			keyString: Constant::$NULL
		);

		$function = "getSqlAndParam{$this->placeholderMode}Mode";
		[
			$id,
			$sql,
			$paramArray,
			$errorArray
		] = $this->$function(
			sqlConfig: $readSqlConfig,
			payload: $readPayload
		);
		$fetchDbMode = $readSqlConfig['__FETCH-MODE__'] ?? 'Slave';

		$exportDbData = [];
		switch ($fetchDbMode) {
			case 'Master':
				$exportDbData = DbCommonFunction::getMasterDatabaseCred(
					customerId: $this->httpObject->httpReqData['active']['customerId']
				);
				break;
			case 'Slave':
				$exportDbData = DbCommonFunction::getSlaveDatabaseServerCred(
					customerId: $this->httpObject->httpReqData['active']['customerId']
				);
				break;
		}

		// Export
		CommonFunction::checkCidr(
			ip: $this->httpObject->httpReqData['server']['httpRequestIp'],
			cidrString: $this->httpObject->httpReqData['active']['customerData']['customer_export_cidr']
		);
		$export = new Export(
			httpObject: $this->httpObject,
			dbServerType: $exportDbData['dbServerType']
		);
		$export->init(
			dbServerHost: $exportDbData['dbServerHost'],
			dbServerPort: $exportDbData['dbServerPort'],
			dbServerUser: $exportDbData['dbServerUser'],
			dbServerPassword: $exportDbData['dbServerPassword'],
			dbServerDb: $exportDbData['dbServerDb']
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
