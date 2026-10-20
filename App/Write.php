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
	public $encodeObject = null;

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
		$this->encodeObject = &$this->httpObject->response->encodeObject;
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

		$return = $this->writeBasics(
			sqlConfig: $sqlConfig,
			followSqlConfigThread: $followSqlConfigThread
		);

		$operateAsTransaction = isset($sqlConfig['__TRANSACTION__'])
			? $sqlConfig['__TRANSACTION__'] : Constant::$FALSE;

		if ($return !== Constant::$FALSE) {
			return $return;
		}

		$fetchDbMode = 'Master';

		// Set Server mode to execute query on - Read / Write Server
		if ($this->httpObject->db === Constant::$NULL) {
			$this->httpObject->db = DbCommonFunction::connectDatabase(
				customerId: $this->httpObject->httpReqData['active']['customerId'],
				fetchDbMode: $fetchDbMode
			);
		}

		$this->write(
			writeSqlConfig: $sqlConfig,
			writeFollowSqlConfigThread: $followSqlConfigThread,
			writeTransaction: $operateAsTransaction
		);

		return Constant::$TRUE;
	}

	/**
	 * Perform write operation
	 * 
	 * @param array $writeSqlConfig             Sql config
	 * @param bool  $writeFollowSqlConfigThread Follow Sql Config Thread
	 * @param bool  $writeTransaction           If true - Operates as transaction
	 * 
	 * @return void
	 * @throws \Exception
	 */
	private function write(
		&$writeSqlConfig,
		$writeFollowSqlConfigThread,
		$writeTransaction
	): void {
		$writeOutputRepresentation = CommonFunction::getOutputRepresentation(
			sqlConfig: $writeSqlConfig,
			httpObject: $this->httpObject
		);

		// Set required fields
		$this->httpObject->httpReqData['active']['requiredFieldArrayCollection'] = $this->getRequired(
			sqlConfig: $writeSqlConfig,
			followSqlConfigThread: $writeFollowSqlConfigThread,
			isFirstCall: Constant::$TRUE
		);

		$this->encodeObject->startObject(
			objectKey: 'Results'
		);

		$writePayloadDataType = $this->httpObject->request->decodeObject->dataType(
			keyString: Constant::$NULL
		);

		if ($writePayloadDataType === 'Array') {
			if (
				in_array(
					needle: $writeOutputRepresentation['outputRepresentation'],
					haystack: ['XML', 'XSLT', 'HTML'],
					strict: Constant::$TRUE
				)
			) {
				$this->encodeObject->startArray(
					objectKey: 'Records'
				);
			}
		}

		// For indexCount
		$indexCount = $writePayloadDataType === 'Array'
			? $this->httpObject->request->decodeObject->count() : 1;

		for ($index = 0; $index < $indexCount; $index++) {
			$writePayloadKeyArray = Constant::$NULL;

			if ($writePayloadDataType === 'Array') {
				$writePayloadKeyArray = [];
				$writePayloadKeyArray[] = "{$index}";
			}

			// For Idempotent Window
			[
				$idempotentWindow,
				$hashKey,
				$hashJson
			] = $this->checkIdempotent(
				sqlConfig: $writeSqlConfig,
				payloadKeyArray: $writePayloadKeyArray
			);

			// For DML operation
			if ($hashJson === Constant::$NULL) {
				if ($writeTransaction) {
					$this->httpObject->db->begin();
				}

				$output = [];
				$output['Status'] = HttpStatus::$Ok;
				if ($this->httpObject->Env->ENABLE_PAYLOAD_IN_RESPONSE) {
					$output[$this->httpObject->Env->PAYLOAD_KEY_IN_RESPONSE] = $this->httpObject->request->decodeObject->getCompleteArray(
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
					writeParentRequiredFieldArray: $this->httpObject->httpReqData['active']['requiredFieldArrayCollection'],
					writeParentResponse: $writeResponse,
					writeParentFollowSqlConfigThread: $writeFollowSqlConfigThread,
					writeParentTransaction: $writeTransaction
				);

				if ($this->httpObject->response->httpStatus === HttpStatus::$Ok) {
					if (
						$writeTransaction
						&& ($this->httpObject->db->beganTransaction === Constant::$TRUE)
					) {
						$this->httpObject->db->commit();
					}
					$output['PayloadResponse'] = $writeResponse;

					if (
						(
							$this->httpObject->request->isPrivateWebRequest
							|| $this->httpObject->request->isPrivateApiRequest
						) && $idempotentWindow
					) {
						$this->httpObject->cache->cacheSet(
							cacheKey: $hashKey,
							cacheValue: $output,
							cacheExpire: $idempotentWindow
						);
					}
				} else { // Failure
					$output['Status'] = $this->httpObject->response->httpStatus;
					$output['Error'] = $writeResponse;
				}
			} else {
				$output = CommonFunction::jsonDecode(
					value: $hashJson
				);
			}

			if ($writePayloadKeyArray === Constant::$NULL) {
				foreach ($output as $outputKey => &$outputKeyValue) {
					$this->encodeObject->addKeyData(
						objectKey: $outputKey,
						data: $outputKeyValue
					);
				}
			} else {
				if (
					in_array(
						needle: $writeOutputRepresentation['outputRepresentation'],
						haystack: ['XML', 'XSLT', 'HTML'],
						strict: Constant::$TRUE
					)
				) {
					$this->encodeObject->startObject(
						objectKey: 'Record'
					);
					foreach ($output as $outputKey => &$outputKeyValue) {
						$this->encodeObject->addKeyData(
							objectKey: $outputKey,
							data: $outputKeyValue
						);
					}
					$this->encodeObject->endObject();
				} else {
					$this->encodeObject->addKeyData(
						objectKey: $index,
						data: $output
					);
				}
			}
		}

		if ($writePayloadDataType === 'Array') {
			if (
				in_array(
					needle: $writeOutputRepresentation['outputRepresentation'],
					haystack: ['XML', 'XSLT', 'HTML'],
					strict: Constant::$TRUE
				)
			) {
				$this->encodeObject->endArray();
			}
		}
		$this->encodeObject->endObject();
	}

	/**
	 * Write Parent Function
	 * 
	 * @param array $writeParentSqlConfig             Sql config
	 * @param array $writeParentPayloadKeyArray       Payload Indexes
	 * @param array $writeParentRequiredFieldArray    Required fields
	 * @param array $writeParentResponse              Response by reference
	 * @param bool  $writeParentFollowSqlConfigThread Follow Sql Config Thread
	 * @param bool  $writeParentTransaction           If true - Operates as transaction
	 * 
	 * @return void
	 * @throws \Exception
	 */
	private function writeParent(
		&$writeParentSqlConfig,
		&$writeParentPayloadKeyArray,
		&$writeParentRequiredFieldArray,
		&$writeParentResponse,
		$writeParentFollowSqlConfigThread,
		$writeParentTransaction
	): void {
		// For payloadKey
		$writeParentPayloadKey = $this->getPayloadKey(
			payloadKeyArray: $writeParentPayloadKeyArray
		);

		// For isObject
		$dataType = $this->httpObject->request->decodeObject->dataType(
			keyString: $writeParentPayloadKey
		);
		if ($dataType === Constant::$NULL) {
			return;
		}
		$isObject = $dataType === 'Object';

		// For indexCount
		$indexCount = ($isObject || $isObject === Constant::$NULL)
			? 1 : $this->httpObject->request->decodeObject->count(
				keyString: $writeParentPayloadKey
			);

		if (isset($writeParentSqlConfig['__PAYLOAD-TYPE__'])) {
			$writePayloadDataType = $isObject ? 'Object' : 'Array';
			if ($writePayloadDataType !== $writeParentSqlConfig['__PAYLOAD-TYPE__']) {
				$errorArray[] = "Payload can't be an {$writePayloadDataType}";
			}

			// Check for maximum object's supported when payloadType is Array
			if (
				$writeParentSqlConfig['__PAYLOAD-TYPE__'] === 'Array'
				&& isset($writeParentSqlConfig['__MAX-PAYLOAD-OBJECT__'])
				&& ($indexCount > $writeParentSqlConfig['__MAX-PAYLOAD-OBJECT__'])
			) {
				$errorArray[] = 'Maximum supported payload count is '
						. $writeParentSqlConfig['__MAX-PAYLOAD-OBJECT__'];
			}
		}

		$mode = $this->httpObject->Env->MASTER_DB_PLACEHOLDER;
		$function = "getSqlAndParam{$mode}Mode";

		for ($index = 0; $index < $indexCount; $index++) {
			// For Required Fields
			if (count(value: $writeParentRequiredFieldArray)) {
				$this->httpObject->httpReqData['active']['requiredFieldArray'] = $writeParentRequiredFieldArray;
			} else {
				$this->httpObject->httpReqData['active']['requiredFieldArray'] = [];
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

			// For Setting Current Values
			$writeParentCurrentTransaction = $writeParentTransaction;
			$writeParentCurrentFollowSqlConfigThread = $writeParentFollowSqlConfigThread;

			// For Validating Sql Thread
			if (
				$writeParentCurrentFollowSqlConfigThread
				&& !$this->httpObject->request->decodeObject->isset(
					keyString: $writeParentCurrentPayloadKey
				)
			) {
				throw new \Exception(
					message: "Payload key '{$writeParentCurrentPayloadKey}' not set",
					code: HttpStatus::$NotFound
				);
			}

			// For isObject
			$dataType = $this->httpObject->request->decodeObject->dataType(
				keyString: $writeParentCurrentPayloadKey
			);
			if ($dataType === Constant::$NULL) {
				return;
			}
			$isObject = $dataType === 'Object';

			// Load Payload
			$writeParentPayload = $this->httpObject->request->decodeObject->getObject(
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
			if (isset($writeParentSqlConfig['__PRE-CONFIG-HOOK__'])) {
				if ($this->hookObject === Constant::$NULL) {
					$this->hookObject = new Hook(
						httpObject: $this->httpObject
					);
				}
				$this->hookObject->triggerHook(
					hookArray: $writeParentSqlConfig['__PRE-CONFIG-HOOK__']
				);
			}

			// Set Sql and ParamArray
			[
				$insertId,
				$sql,
				$paramArray,
				$errorArray
			] = $this->$function(
				sqlConfig: $writeParentSqlConfig,
				payload: $writeParentPayload
			);

			if (!empty($errorArray)) {
				$writeParentCurrentResponse['Error'] = $errorArray;
				$this->httpObject->db->rollBack();
				return;
			}

			// Execute
			$this->httpObject->db->execQuery(
				sql: $sql,
				paramArray: $paramArray
			);

			// For Rollback
			if (
				$writeParentCurrentTransaction
				&& !$this->httpObject->db->beganTransaction
			) {
				$writeParentCurrentResponse['Error'] = 'Something went wrong';
				return;
			}

			// For Setting Data
			if (isset($writeParentSqlConfig['__INSERT-ID__'])) {
				if ($insertId === Constant::$NULL) {
					$insertId = $this->httpObject->db->lastInsertId();
				}

				if ($isObject) {
					$writeParentCurrentResponse[$writeParentSqlConfig['__INSERT-ID__']] = $insertId;
				} else {
					if (!is_array($writeParentCurrentResponse[$writeParentSqlConfig['__INSERT-ID__']])) {
						$writeParentCurrentResponse[$writeParentSqlConfig['__INSERT-ID__']] = [];
					}
					$writeParentCurrentResponse[$writeParentSqlConfig['__INSERT-ID__']][] = $insertId;
				}

				$this->httpObject->httpReqData['active']['__INSERT-ID__'][$writeParentSqlConfig['__INSERT-ID__']] = $insertId;
			} else {
				$affectedRecordCount = $this->httpObject->db->affectedRecordCount();
				$writeParentCurrentResponse['affectedRecordCount'] = $affectedRecordCount;
			}

			// For Close Cursor
			$this->httpObject->db->closeCursor();

			// For Child
			if (isset($writeParentSqlConfig['__SUB-CONFIG__'])) {
				if ($writeParentCurrentFollowSqlConfigThread) {
					$this->resetFetchData(
						activeDataKey: 'previousPayload',
						payloadKeyArray: $writeParentCurrentPayloadKeyArray,
						record: $writeParentPayload
					);
				}

				$this->writeChild(
					writeChildSqlConfig: $writeParentSqlConfig['__SUB-CONFIG__'],
					writeChildPayloadKeyArray: $writeParentCurrentPayloadKeyArray,
					writeChildRequiredFieldArray: $writeParentRequiredFieldArray,
					writeChildResponse: $writeParentCurrentResponse,
					writeChildFollowSqlConfigThread: $writeParentCurrentFollowSqlConfigThread,
					writeChildTransaction: $writeParentCurrentTransaction
				);
			}

			// For Triggers
			if (isset($writeParentSqlConfig['__TRIGGER__'])) {
				$this->encodeObject->addKeyData(
					objectKey: '__TRIGGER__',
					data: $this->getTriggerData(
						triggerConfig: $writeParentSqlConfig['__TRIGGER__'],
						payload: $writeParentPayload
					)
				);
			}

			// For Post Hook
			if (isset($writeParentSqlConfig['__POST-CONFIG-HOOK__'])) {
				if ($this->hookObject === Constant::$NULL) {
					$this->hookObject = new Hook(
						httpObject: $this->httpObject
					);
				}
				$this->hookObject->triggerHook(
					hookArray: $writeParentSqlConfig['__POST-CONFIG-HOOK__']
				);
			}

			// For Affected Cache
			if (isset($writeParentSqlConfig['__AFFECTED-CACHE-KEY__'])) {
				$indexCount = count(
					value: $writeParentSqlConfig['__AFFECTED-CACHE-KEY__']
				);
				for ($index = 0; $index < $indexCount; $index++) {
					$this->httpObject->queryCache->queryCacheDelete(
						customerId: $this->httpObject->httpReqData['active']['customerId'],
						queryCacheKey: $writeParentSqlConfig['__AFFECTED-CACHE-KEY__'][$index]
					);
				}
			}
		}
	}

	/**
	 * Write Child Function
	 * 
	 * @param array $writeChildSqlConfig             Sql config
	 * @param array $writeChildPayloadKeyArray       Payload Key's
	 * @param array $writeChildRequiredFieldArray    Required fields
	 * @param array $writeChildResponse              Response by reference
	 * @param bool  $writeChildFollowSqlConfigThread Follow Sql Config Thread
	 * @param bool  $writeChildTransaction           If true - Operates as transaction
	 * 
	 * @return void
	 */
	private function writeChild(
		&$writeChildSqlConfig,
		&$writeChildPayloadKeyArray,
		&$writeChildRequiredFieldArray,
		&$writeChildResponse,
		$writeChildFollowSqlConfigThread,
		$writeChildTransaction
	): void {
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

			// For Setting Current Values
			$writeChildModuleTransaction = $writeChildTransaction ?? isset($writeChildModuleSqlConfig['__TRANSACTION__'])
				? $writeChildModuleSqlConfig['__TRANSACTION__'] : Constant::$FALSE;
			$writeChildModuleFollowSqlConfigThread = $writeChildFollowSqlConfigThread ?? $this->getFollowSqlConfigThread(
				sqlConfig: $writeChildModuleSqlConfig
			);

			// For Validating Sql Thread
			if (
				$writeChildModuleFollowSqlConfigThread
				&& !$this->httpObject->request->decodeObject->isset(
					keyString: $writeChildModulePayloadKey
				)
			) {
				throw new \Exception(
					message: "Invalid payload: Module '{$writeModule}' missing",
					code: HttpStatus::$NotFound
				);
			}

			// For isObject
			$dataType = $this->httpObject->request->decodeObject->dataType(
				keyString: $writeChildModulePayloadKey
			);
			if ($dataType === Constant::$NULL) {
				return;
			}
			$isObject = $dataType === 'Object';

			// For indexCount
			$indexCount = ($isObject)
				? 1 : $this->httpObject->request->decodeObject->count(
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

				// For Setting Current Values
				$writeChildModuleCurrentTransaction = $writeChildModuleTransaction;
				$writeChildModuleCurrentFollowSqlConfigThread = $writeChildModuleFollowSqlConfigThread;

				// For Validating Sql Thread
				if (
					$writeChildModuleCurrentFollowSqlConfigThread
					&& !$this->httpObject->request->decodeObject->isset(
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
					writeParentFollowSqlConfigThread: $writeChildModuleCurrentFollowSqlConfigThread,
					writeParentTransaction: $writeChildModuleCurrentTransaction
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
		$return = Constant::$TRUE;
		// $isValidData = Constant::$TRUE;
		// if (isset($sqlConfig['__VALIDATE__'])) {
		// 	[$isValidData, $errorArray] = $this->validate(
		// 		validationConfig: $sqlConfig['__VALIDATE__']
		// 	);
		// 	if ($isValidData !== Constant::$TRUE) {
		// 		$this->httpObject->response->httpStatus = HttpStatus::$BadRequest;
		// 		$response = $errorArray;
		// 		$return = Constant::$FALSE;
		// 	}
		// }
		return $return;
	}
}
