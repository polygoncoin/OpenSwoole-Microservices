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
use Microservices\App\Constant;
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
	private $hookObject = null;

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
	public $dataEncodeObject = null;

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
		$this->dataEncodeObject = &$this->httpObject->httpResponseObject->dataEncodeObject;
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
		$return = $this->writeBasics(
			sqlConfig: $sqlConfig,
			maintainHierarchy: $maintainHierarchy
		);


		if ($return !== Constant::$FALSE) {
			return $return;
		}

		// Operate as Transaction (BEGIN COMMIT else ROLLBACK on error)
		$this->operateAsTransaction = isset($sqlConfig['isTransaction'])
			? $sqlConfig['isTransaction'] : Constant::$FALSE;

		$fetchDbMode = 'Master';

		// Set Server mode to execute query on - Read / Write Server
		if ($this->httpObject->httpRequestObject->customerDbObject === Constant::$NULL) {
			$this->httpObject->httpRequestObject->customerDbObject = DbCommonFunction::connectCustomerDb(
				customerData: $this->httpObject->httpRequestObject->activeRequestData['customerData'],
				fetchDbMode: $fetchDbMode
			);
		}

		$this->write(
			writeSqlConfig: $sqlConfig,
			writeMaintainHierarchy: $maintainHierarchy
		);

		if (isset($sqlConfig['affectedQueryCacheKeyArray'])) {
			$indexCount = count(
				value: $sqlConfig['affectedQueryCacheKeyArray']
			);
			for ($index = 0; $index < $indexCount; $index++) {
				$this->httpObject->httpRequestObject->customerQueryCacheObject->queryCacheDelete(
					customerId: $this->httpObject->httpRequestObject->customerId,
					queryCacheKey: $sqlConfig['affectedQueryCacheKeyArray'][$index]
				);
			}
		}

		return true;
	}

	/**
	 * Perform write operation
	 * 
	 * @param array $writeSqlConfig         Sql config
	 * @param bool  $writeMaintainHierarchy If true - Uses parent payload/results in child
	 * 
	 * @return void
	 * @throws \Exception
	 */
	private function write(
		&$writeSqlConfig,
		$writeMaintainHierarchy
	): void {
		// Check for payloadType
		if (isset($writeSqlConfig['__PAYLOAD-TYPE__'])) {
			$writePayloadType = $this->httpObject->httpRequestObject->activeRequestData['payloadType'];
			if ($writePayloadType !== $writeSqlConfig['__PAYLOAD-TYPE__']) {
				throw new \Exception(
					message: 'Invalid payload type',
					code: HttpStatus::$BadRequest
				);
			}

			// Check for maximum object's supported when payloadType is Array
			if (
				$writeSqlConfig['__PAYLOAD-TYPE__'] === 'Array'
				&& isset($writeSqlConfig['__MAX-PAYLOAD-OBJECTS__'])
				&& ($objCount = $this->httpObject->httpRequestObject->dataDecodeObject->count())
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
		$this->httpObject->httpRequestObject->activeRequestData['requiredFieldArrayCollection'] = $this->getRequired(
			sqlConfig: $writeSqlConfig,
			maintainHierarchy: $writeMaintainHierarchy,
			isFirstCall: Constant::$TRUE
		);

		$this->dataEncodeObject->startObject(
			objectKey: 'Results'
		);

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
				$this->dataEncodeObject->startArray(
					objectKey: 'Records'
				);
			}
		}

		// For indexCount
		$indexCount = $this->httpObject->httpRequestObject->activeRequestData['payloadType'] === 'Array'
			? $this->httpObject->httpRequestObject->dataDecodeObject->count() : 1;

		for ($index = 0; $index < $indexCount; $index++) {
			$writePayloadKeyArray = null;

			if ($this->httpObject->httpRequestObject->activeRequestData['payloadType'] === 'Array') {
				$writePayloadKeyArray = [];
				$writePayloadKeyArray[] = "{$index}";
			}

			// For Idempotent Window
			[$idempotentWindow, $hashKey, $hashJson] = $this->checkIdempotent(
				sqlConfig: $writeSqlConfig,
				payloadKeyArray: $writePayloadKeyArray
			);

			// For DML operation
			if ($hashJson === Constant::$NULL) {
				if ($this->operateAsTransaction) {
					$this->httpObject->httpRequestObject->customerDbObject->begin();
				}

				$output = [];
				$output['Status'] = HttpStatus::$Ok;
				if (
					CommonFunction::isEnabled(
						httpObject: $this->httpObject,
						feature: 'customer_enabled_payload_in_response'
					)
				) {
					$output[Env::$payloadKeyInResponse] = $this->httpObject->httpRequestObject->dataDecodeObject->getCompleteArray(
						keyString: $this->getPayloadKey(
							payloadKeyArray: $writePayloadKeyArray
						)
					);
				}

				$writeResponse = [];

				// For Parent
				$this->writeParent(
					writeParentSqlConfig: $writeSqlConfig,
					writeParentPayloadKeyArray: $writePayloadKeyArray,
					writeParentRequiredFieldArray: $this->httpObject->httpRequestObject->activeRequestData['requiredFieldArrayCollection'],
					writeParentResponse: $writeResponse,
					writeParentMaintainHierarchy: $writeMaintainHierarchy
				);

				if ($this->httpObject->httpResponseObject->httpStatus === HttpStatus::$Ok) {
					if (
						$this->operateAsTransaction
						&& ($this->httpObject->httpRequestObject->customerDbObject->beganTransaction === Constant::$TRUE)
					) {
						$this->httpObject->httpRequestObject->customerDbObject->commit();
					}
					$output['PayloadResponse'] = $writeResponse;

					if (
						$this->httpObject->httpRequestObject->isPrivateRequest
						&& $idempotentWindow
					) {
						$this->httpObject->httpRequestObject->customerCacheObject->cacheSet(
							cacheKey: $hashKey,
							cacheValue: $output,
							cacheExpire: $idempotentWindow
						);
					}
				} else { // Failure
					$output['Status'] = $this->httpObject->httpResponseObject->httpStatus;
					$output['Error'] = $writeResponse;
				}
			} else {
				$output = CommonFunction::jsonDecode(
					value: $hashJson
				);
			}

			if ($writePayloadKeyArray === Constant::$NULL) {
				foreach ($output as $outputKey => &$outputKeyValue) {
					$this->dataEncodeObject->addKeyData(
						objectKey: $outputKey,
						data: $outputKeyValue
					);
				}
			} else {
				if (
					in_array(
						needle: $this->httpObject->httpResponseObject->outputRepresentation,
						haystack: ['XML', 'XSLT', 'HTML'],
						strict: Constant::$TRUE
					)
				) {
					$this->dataEncodeObject->startObject(
						objectKey: 'Record'
					);
					foreach ($output as $outputKey => &$outputKeyValue) {
						$this->dataEncodeObject->addKeyData(
							objectKey: $outputKey,
							data: $outputKeyValue
						);
					}
					$this->dataEncodeObject->endObject();
				} else {
					$this->dataEncodeObject->addKeyData(
						objectKey: $index,
						data: $output
					);
				}
			}
		}

		if ($this->httpObject->httpRequestObject->activeRequestData['payloadType'] === 'Array') {
			if (
				in_array(
					needle: $this->httpObject->httpResponseObject->outputRepresentation,
					haystack: ['XML', 'XSLT', 'HTML'],
					strict: Constant::$TRUE
				)
			) {
				$this->dataEncodeObject->endArray();
			}
		}
		$this->dataEncodeObject->endObject();
	}

	/**
	 * Write Parent Function
	 * 
	 * @param array $writeParentSqlConfig          Sql config
	 * @param array $writeParentPayloadKeyArray    Payload Indexes
	 * @param array $writeParentRequiredFieldArray Required fields
	 * @param array $writeParentResponse           Response by reference
	 * @param bool  $writeParentMaintainHierarchy  If true - Uses parent payload/results in child
	 * 
	 * @return void
	 * @throws \Exception
	 */
	private function writeParent(
		&$writeParentSqlConfig,
		&$writeParentPayloadKeyArray,
		&$writeParentRequiredFieldArray,
		&$writeParentResponse,
		$writeParentMaintainHierarchy
	): void {
		// For payloadKey
		$writeParentPayloadKey = $this->getPayloadKey(
			payloadKeyArray: $writeParentPayloadKeyArray
		);

		// For isObject
		$isObject = $this->httpObject->httpRequestObject->dataDecodeObject->dataType(
			keyString: $writeParentPayloadKey
		) === 'Object';
		if ($isObject === Constant::$NULL) {
			return;
		}

		// For indexCount
		$indexCount = ($isObject || $isObject === Constant::$NULL)
			? 1 : $this->httpObject->httpRequestObject->dataDecodeObject->count(
				keyString: $writeParentPayloadKey
			);

		$mode = getenv(name: $this->httpObject->httpRequestObject->activeRequestData['customerData']['customer_master_db_server_query_placeholder']);
		$function = "getSqlAndParam{$mode}Mode";

		for ($index = 0; $index < $indexCount; $index++) {
			// For Required Fields
			if (count(value: $writeParentRequiredFieldArray)) {
				$this->httpObject->httpRequestObject->activeRequestData['requiredFieldArray'] = $writeParentRequiredFieldArray;
			} else {
				$this->httpObject->httpRequestObject->activeRequestData['requiredFieldArray'] = [];
			}

			// For payloadKeyArray
			$writeParentCurrentPayloadKeyArray = $writeParentPayloadKeyArray;
			if (!$isObject) {
				array_push(
					$writeParentCurrentPayloadKeyArray,
					"{$index}"
				);
			}

			// For payloadKey
			$writeParentCurrentPayloadKey = $this->getPayloadKey(
				payloadKeyArray: $writeParentCurrentPayloadKeyArray
			);

			// For Response
			if ($isObject) {
				$writeParentCurrentResponse = &$writeParentResponse;
			} else {
				$writeParentResponse[$index] = [];
				$writeParentCurrentResponse = &$writeParentResponse[$index];
			}

			// For Validating Hierarchy
			$writeParentCurrentMaintainHierarchy = $writeParentMaintainHierarchy;
			if (
				$writeParentCurrentMaintainHierarchy
				&& !$this->httpObject->httpRequestObject->dataDecodeObject->isset(
					keyString: $writeParentCurrentPayloadKey
				)
			) {
				throw new \Exception(
					message: "Payload key '{$writeParentCurrentPayloadKey}' not set",
					code: HttpStatus::$NotFound
				);
			}

			// For isObject
			$isObject = $this->httpObject->httpRequestObject->dataDecodeObject->dataType(
				keyString: $writeParentCurrentPayloadKey
			) === 'Object';
			if ($isObject === Constant::$NULL) {
				return;
			}

			// Load Payload
			$this->httpObject->httpRequestObject->activeRequestData['payload'] = $this->httpObject->httpRequestObject->dataDecodeObject->get(
				keyString: $writeParentCurrentPayloadKey
			);

			// For Validation
			if (
				isset($writeParentSqlConfig['__VALIDATE__'])
				&& !$this->isValidPayload(
					sqlConfig: $writeParentSqlConfig,
					response: $writeParentCurrentResponse
				)
			) {
				continue;
			}

			// For Pre Hook
			if (isset($writeParentSqlConfig['__PRE-SQL-HOOKS__'])) {
				if ($this->hookObject === Constant::$NULL) {
					$this->hookObject = new Hook(
						httpObject: $this->httpObject
					);
				}
				$this->hookObject->triggerHook(
					hookArray: $writeParentSqlConfig['__PRE-SQL-HOOKS__']
				);
			}

			// Set Sql and ParamArray
			[$insertId, $sql, $paramArray, $errorArray] = $this->$function(
				sqlConfig: $writeParentSqlConfig
			);

			if (!empty($errorArray)) {
				$writeParentCurrentResponse['Error'] = $errorArray;
				$this->httpObject->httpRequestObject->customerDbObject->rollBack();
				return;
			}

			// Execute
			$this->httpObject->httpRequestObject->customerDbObject->execQuery(
				sql: $sql,
				paramArray: $paramArray
			);

			// For Rollback
			if (
				$this->operateAsTransaction
				&& !$this->httpObject->httpRequestObject->customerDbObject->beganTransaction
			) {
				$writeParentCurrentResponse['Error'] = 'Something went wrong';
				return;
			}

			// For Setting Data
			if (isset($writeParentSqlConfig['__INSERT-IDs__'])) {
				if ($insertId === Constant::$NULL) {
					$insertId = $this->httpObject->httpRequestObject->customerDbObject->lastInsertId();
				}

				if ($isObject) {
					$writeParentCurrentResponse[$writeParentSqlConfig['__INSERT-IDs__']] = $insertId;
				} else {
					if (!is_array($writeParentCurrentResponse[$writeParentSqlConfig['__INSERT-IDs__']])) {
						$writeParentCurrentResponse[$writeParentSqlConfig['__INSERT-IDs__']] = [];
					}
					$writeParentCurrentResponse[$writeParentSqlConfig['__INSERT-IDs__']][] = $insertId;
				}

				$this->httpObject->httpRequestObject->activeRequestData['__INSERT-IDs__'][$writeParentSqlConfig['__INSERT-IDs__']] = $insertId;
			} else {
				$affectedRecordCount = $this->httpObject->httpRequestObject->customerDbObject->affectedRecordCount();
				$writeParentCurrentResponse['affectedRecordCount'] = $affectedRecordCount;
			}

			// For Close Cursor
			$this->httpObject->httpRequestObject->customerDbObject->closeCursor();

			// For Child
			if (isset($writeParentSqlConfig['__SUB-QUERY__'])) {
				$this->writeChild(
					writeChildSqlConfig: $writeParentSqlConfig['__SUB-QUERY__'],
					writeChildPayloadKeyArray: $writeParentCurrentPayloadKeyArray,
					writeChildRequiredFieldArray: $writeParentRequiredFieldArray,
					writeChildResponse: $writeParentCurrentResponse,
					writeChildMaintainHierarchy: $writeParentCurrentMaintainHierarchy
				);
			}

			// For Triggers
			if (isset($writeParentSqlConfig['__TRIGGERS__'])) {
				$this->dataEncodeObject->addKeyData(
					objectKey: '__TRIGGERS__',
					data: $this->getTriggerData(
						triggerConfig: $writeParentSqlConfig['__TRIGGERS__']
					)
				);
			}

			// For Post Hook
			if (isset($writeParentSqlConfig['__POST-SQL-HOOKS__'])) {
				if ($this->hookObject === Constant::$NULL) {
					$this->hookObject = new Hook(
						httpObject: $this->httpObject
					);
				}
				$this->hookObject->triggerHook(
					hookArray: $writeParentSqlConfig['__POST-SQL-HOOKS__']
				);
			}
		}
	}

	/**
	 * Write Child Function
	 * 
	 * @param array $writeChildSqlConfig          Sql config
	 * @param array $writeChildPayloadKeyArray    Payload Key's
	 * @param array $writeChildRequiredFieldArray Required fields
	 * @param array $writeChildResponse           Response by reference
	 * @param bool  $writeChildMaintainHierarchy  If true - Uses parent payload/results in child
	 * 
	 * @return void
	 */
	private function writeChild(
		&$writeChildSqlConfig,
		&$writeChildPayloadKeyArray,
		&$writeChildRequiredFieldArray,
		&$writeChildResponse,
		$writeChildMaintainHierarchy
	): void {
		if ($writeChildMaintainHierarchy) {
			$record = $this->httpObject->httpRequestObject->activeRequestData['payload'];
			$this->resetFetchData(
				activeRequestDataKey: 'sqlPayload',
				payloadKeyArray: $writeChildPayloadKeyArray,
				record: $record
			);
		}

		if (
			isset($writeChildPayloadKeyArray[0])
			&& $writeChildPayloadKeyArray[0] === ''
		) {
			$writeChildPayloadKeyArray = array_shift(
				$writeChildPayloadKeyArray
			);
		}
		if (!is_array(value: $writeChildPayloadKeyArray)) {
			$writeChildPayloadKeyArray = [];
		}

		foreach ($writeChildSqlConfig as $writeModule => $writeChildModuleSqlConfig) {
			// For payloadKeyArray
			$writeChildModulePayloadKeyArray = $writeChildPayloadKeyArray;
			array_push(
				$writeChildModulePayloadKeyArray,
				"{$writeModule}"
			);

			// For payloadKey
			$writeChildModulePayloadKey = $this->getPayloadKey(
				payloadKeyArray: $writeChildModulePayloadKeyArray
			);

			// For Validating Hierarchy
			$writeChildModuleMaintainHierarchy = $writeChildMaintainHierarchy ?? $this->getMaintainHierarchy(
				sqlConfig: $writeChildModuleSqlConfig
			);
			if (
				$writeChildModuleMaintainHierarchy
				&& !$this->httpObject->httpRequestObject->dataDecodeObject->isset(
					keyString: $writeChildModulePayloadKey
				)
			) {
				throw new \Exception(
					message: "Invalid payload: Module '{$writeModule}' missing",
					code: HttpStatus::$NotFound
				);
			}

			// For isObject
			$isObject = $this->httpObject->httpRequestObject->dataDecodeObject->dataType(
				keyString: $writeChildModulePayloadKey
			) === 'Object';
			if ($isObject === Constant::$NULL) {
				return;
			}

			// For indexCount
			$indexCount = ($isObject)
				? 1 : $this->httpObject->httpRequestObject->dataDecodeObject->count(
					keyString: $writeChildModulePayloadKey
				);

			// For Required Fields
			if (isset($writeChildRequiredFieldArray[$writeModule])) {
				$writeChildModuleRequiredFieldArray = &$writeChildRequiredFieldArray[$writeModule];
			} else {
				$writeChildModuleRequiredFieldArray = &$writeChildRequiredFieldArray;
			}

			// For Response
			$writeChildResponse[$writeModule] = [];
			$writeChildModuleResponse = &$writeChildResponse[$writeModule];

			for ($index = 0; $index < $indexCount; $index++) {
				// For payloadKeyArray
				$writeChildModuleCurrentPayloadKeyArray = $writeChildModulePayloadKeyArray;
				if (!$isObject) {
					array_push(
						$writeChildModuleCurrentPayloadKeyArray,
						"{$index}"
					);
				}

				// For payloadKey
				$writeChildModuleCurrentPayloadKey = $this->getPayloadKey(
					payloadKeyArray: $writeChildModuleCurrentPayloadKeyArray
				);

				// For Validating Hierarchy
				$writeChildModuleCurrentMaintainHierarchy = $writeChildModuleMaintainHierarchy;
				if (
					$writeChildModuleCurrentMaintainHierarchy
					&& !$this->httpObject->httpRequestObject->dataDecodeObject->isset(
						keyString: $writeChildModuleCurrentPayloadKey
					)
				) {
					throw new \Exception(
						message: "Invalid payload: Module '{$writeModule}' missing",
						code: HttpStatus::$NotFound
					);
				}

				// For Response
				if ($isObject) {
					$writeChildModuleCurrentResponse = &$writeChildModuleResponse;
				} else {
					$writeChildModuleResponse[$index] = [];
					$writeChildModuleCurrentResponse = &$writeChildModuleResponse[$index];
				}

				// For Parent
				$this->writeParent(
					writeParentSqlConfig: $writeChildModuleSqlConfig,
					writeParentPayloadKeyArray: $writeChildModuleCurrentPayloadKeyArray,
					writeParentRequiredFieldArray: $writeChildModuleRequiredFieldArray,
					writeParentResponse: $writeChildModuleCurrentResponse,
					writeParentMaintainHierarchy: $writeChildModuleCurrentMaintainHierarchy
				);
			}
		}
	}

	/**
	 * Validate payload
	 * 
	 * @param array $sqlConfig Sql config
	 * @param array $response  Response by reference
	 * 
	 * @return bool
	 */
	private function isValidPayload(
		$sqlConfig,
		&$response
	): bool {
		$return = true;
		$isValidData = true;
		if (isset($sqlConfig['__VALIDATE__'])) {
			[$isValidData, $errorArray] = $this->validate(
				validationConfig: $sqlConfig['__VALIDATE__']
			);
			if ($isValidData !== Constant::$TRUE) {
				$this->httpObject->httpResponseObject->httpStatus = HttpStatus::$BadRequest;
				$response = $errorArray;
				$return = false;
			}
		}
		return $return;
	}
}
