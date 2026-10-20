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
use Microservices\App\Constant;
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
	private $hookObject = null;

	/**
	 * Data Encode object
	 * 
	 * @var null|DataEncode
	 */
	public $encodeObject = null;

	/**
	 * Supplement Class object
	 * 
	 * @var null|object
	 */
	public $supplementObject = null;

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
	 * @param string $supplementClass Supplement class
	 * 
	 * @return bool
	 */
	public function init(
		&$supplementClass
	): bool {
		$this->supplementObject = new $supplementClass(
			$this->httpObject
		);
		return $this->supplementObject->init();
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
			$sqlConfig,
			$followSqlConfigThread
		);

		$operateAsTransaction = isset($sqlConfig['__TRANSACTION__'])
			? $sqlConfig['__TRANSACTION__'] : Constant::$FALSE;

		if ($return !== Constant::$FALSE) {
			return $return;
		}

		$fetchDbMode = $sqlConfig['__FETCH-MODE__'] ?? 'Master';

		// Set Server mode to execute query on - Read / Write Server
		if ($this->httpObject->db === Constant::$NULL) {
			$this->httpObject->db = DbCommonFunction::connectDatabase(
				customerId: $this->httpObject->httpReqData['active']['customerId'],
				fetchDbMode: $fetchDbMode
			);
		}

		$this->supplement(
			supplementSqlConfig: $sqlConfig,
			supplementFollowSqlConfigThread: $followSqlConfigThread,
			supplementTransaction: $operateAsTransaction
		);

		return Constant::$TRUE;
	}

	/**
	 * Process Function to insert/update
	 * 
	 * @param array $supplementSqlConfig             Sql config
	 * @param bool  $supplementFollowSqlConfigThread Follow Sql Config Thread
	 * @param bool  $supplementTransaction           If true - Operates as transaction
	 * 
	 * @return void
	 * @throws \Exception
	 */
	private function supplement(
		&$supplementSqlConfig,
		$supplementFollowSqlConfigThread,
		$supplementTransaction
	): void {
		$supplementOutputRepresentation = CommonFunction::getOutputRepresentation(
			sqlConfig: $supplementSqlConfig,
			httpObject: $this->httpObject
		);

		// Set required fields
		$this->httpObject->httpReqData['active']['requiredFieldArrayCollection'] = $this->getRequired(
			sqlConfig: $supplementSqlConfig,
			followSqlConfigThread: $supplementFollowSqlConfigThread,
			isFirstCall: Constant::$TRUE
		);

		$this->encodeObject->startObject(
			objectKey: 'Results'
		);

		$supplementPayloadDataType = $this->httpObject->request->decodeObject->dataType(
			keyString: Constant::$NULL
		);

		if ($supplementPayloadDataType === 'Array') {
			if (
				in_array(
					needle: $supplementOutputRepresentation['outputRepresentation'],
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
		$indexCount = $supplementPayloadDataType === 'Array'
			? $this->httpObject->request->decodeObject->count() : 1;

		for ($index = 0; $index < $indexCount; $index++) {
			$supplementPayloadKeyArray = Constant::$NULL;

			if ($supplementPayloadDataType === 'Array') {
				$supplementPayloadKeyArray = [];
				$supplementPayloadKeyArray[] = "{$index}";
			}

			// For Idempotent Window
			[
				$idempotentWindow,
				$hashKey,
				$hashJson
			] = $this->checkIdempotent(
				sqlConfig: $supplementSqlConfig,
				payloadKeyArray: $supplementPayloadKeyArray
			);

			// For DML operation
			if ($hashJson === Constant::$NULL) {

				if ($supplementTransaction) {
					$this->httpObject->db->begin();
				}

				$output = [];
				$output['Status'] = HttpStatus::$Ok;
				if ($this->httpObject->Env->ENABLE_PAYLOAD_IN_RESPONSE) {
					$output[$this->httpObject->Env->PAYLOAD_KEY_IN_RESPONSE] = $this->httpObject->request->decodeObject->getCompleteArray(
						keyString: $this->getPayloadKey(
							payloadKeyArray: $supplementPayloadKeyArray
						)
					);
				}

				$supplementResponse = [];

				// For Parent
				$this->supplementParent(
					supplementParentSqlConfig: $supplementSqlConfig,
					supplementParentPayloadKeyArray: $supplementPayloadKeyArray,
					supplementParentRequiredFieldArray: $this->httpObject->httpReqData['active']['requiredFieldArrayCollection'],
					supplementParentResponse: $supplementResponse,
					supplementParentModule: '',
					supplementParentFollowSqlConfigThread: $supplementFollowSqlConfigThread,
					supplementParentTransaction: $supplementTransaction
				);

				if ($this->httpObject->response->httpStatus === HttpStatus::$Ok) {
					if (
						$supplementTransaction
						&& ($this->httpObject->db->beganTransaction === Constant::$TRUE)
					) {
						$this->httpObject->db->commit();
					}
					$output['PayloadResponse'] = $supplementResponse;

					if ($idempotentWindow) {
						$this->httpObject->cache->cacheSet(
							cacheKey: $hashKey,
							cacheValue: $output,
							cacheExpire: $idempotentWindow
						);
					}
				} else { // Failure
					$output['Status'] = $this->httpObject->response->httpStatus;
					$output['Error'] = $supplementResponse;
				}
			} else {
				$output = CommonFunction::jsonDecode(
					value: $hashJson
				);
			}

			if ($supplementPayloadKeyArray === Constant::$NULL) {
				foreach ($output as $outputKey => &$outputKeyValue) {
					$this->encodeObject->addKeyData(
						objectKey: $outputKey,
						data: $outputKeyValue
					);
				}
			} else {
				if (
					in_array(
						needle: $supplementOutputRepresentation['outputRepresentation'],
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

		if ($supplementPayloadDataType === 'Array') {
			if (
				in_array(
					needle: $supplementOutputRepresentation['outputRepresentation'],
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
	 * Supplement Parent Function
	 * 
	 * @param array  $supplementParentSqlConfig             Sql config
	 * @param array  $supplementParentPayloadKeyArray       Payload Indexes
	 * @param array  $supplementParentRequiredFieldArray    Required fields
	 * @param array  $supplementParentResponse              Response by reference
	 * @param string $supplementParentModule                Parent Module
	 * @param bool   $supplementParentFollowSqlConfigThread Follow Sql Config Thread
	 * @param bool   $supplementParentTransaction           If true - Operates as transaction
	 * 
	 * @return void
	 * @throws \Exception
	 */
	private function supplementParent(
		&$supplementParentSqlConfig,
		&$supplementParentPayloadKeyArray,
		&$supplementParentRequiredFieldArray,
		&$supplementParentResponse,
		$supplementParentModule,
		$supplementParentFollowSqlConfigThread,
		$supplementParentTransaction
	): void {
		// For payloadKey
		$supplementParentPayloadKey = $this->getPayloadKey(
			payloadKeyArray: $supplementParentPayloadKeyArray
		);

		// For isObject
		$dataType = $this->httpObject->request->decodeObject->dataType(
			keyString: $supplementParentPayloadKey
		);
		if ($dataType === Constant::$NULL) {
			return;
		}
		$isObject = $dataType === 'Object';

		// For indexCount
		$indexCount = ($isObject)
			? 1 : $this->httpObject->request->decodeObject->count(
				keyString: $supplementParentPayloadKey
			);

		if (isset($supplementParentSqlConfig['__PAYLOAD-TYPE__'])) {
			$supplementPayloadDataType = $isObject ? 'Object' : 'Array';
			if ($supplementPayloadDataType !== $supplementParentSqlConfig['__PAYLOAD-TYPE__']) {
				$errorArray[] = "Payload can't be an {$supplementPayloadDataType}";
			}

			// Check for maximum object's supported when payloadType is Array
			if (
				$supplementParentSqlConfig['__PAYLOAD-TYPE__'] === 'Array'
				&& isset($supplementParentSqlConfig['__MAX-PAYLOAD-OBJECT__'])
				&& ($indexCount > $supplementParentSqlConfig['__MAX-PAYLOAD-OBJECT__'])
			) {
				$errorArray[] = 'Maximum supported payload count is '
						. $supplementParentSqlConfig['__MAX-PAYLOAD-OBJECT__'];
			}
		}

		for ($index = 0; $index < $indexCount; $index++) {
			// For Required Fields
			if (count(value: $supplementParentRequiredFieldArray)) {
				$this->httpObject->httpReqData['active']['requiredFieldArray'] = $supplementParentRequiredFieldArray;
			} else {
				$this->httpObject->httpReqData['active']['requiredFieldArray'] = [];
			}

			// For payloadKeyArray
			$supplementParentCurrentPayloadKeyArray = $supplementParentPayloadKeyArray;
			if (!$isObject) {
				array_push(
					$supplementParentCurrentPayloadKeyArray,
					"{$index}"
				);
			}

			// For payloadKey
			$supplementParentCurrentPayloadKey = $this->getPayloadKey(
				payloadKeyArray: $supplementParentCurrentPayloadKeyArray
			);

			// For Response
			if ($isObject) {
				$supplementParentCurrentResponse = &$supplementParentResponse;
			} else {
				$supplementParentResponse[$index] = [];
				$supplementParentCurrentResponse = &$supplementParentResponse[$index];
			}

			// For Setting Current Values
			$supplementParentCurrentTransaction = $supplementParentTransaction;
			$supplementParentCurrentFollowSqlConfigThread = $supplementParentFollowSqlConfigThread;

			// For Validating Sql Thread
			if (
				$supplementParentCurrentFollowSqlConfigThread
				&& !$this->httpObject->request->decodeObject->isset(
					keyString: $supplementParentCurrentPayloadKey
				)
			) {
				throw new \Exception(
					message: "Payload key '{$supplementParentCurrentPayloadKey}' not set",
					code: HttpStatus::$NotFound
				);
			}

			// For isObject
			$dataType = $this->httpObject->request->decodeObject->dataType(
				keyString: $supplementParentCurrentPayloadKey
			);
			if ($dataType === Constant::$NULL) {
				return;
			}
			$isObject = $dataType === 'Object';

			// For Payload
			$supplementParentPayload = $this->httpObject->request->decodeObject->getObject(
				keyString: $supplementParentCurrentPayloadKey
			);

			// For Validation
			if (
				isset($supplementParentSqlConfig['__VALIDATE__'])
				&& !$this->isValidPayload(
					sqlConfig: $supplementParentSqlConfig,
					response: $supplementParentCurrentResponse
				)
			) {
				continue;
			}

			// For Pre Hook
			if (isset($supplementParentSqlConfig['__PRE-CONFIG-HOOK__'])) {
				if ($this->hookObject === Constant::$NULL) {
					$this->hookObject = new Hook(
						httpObject: $this->httpObject
					);
				}
				$this->hookObject->triggerHook(
					hookArray: $supplementParentSqlConfig['__PRE-CONFIG-HOOK__']
				);
			}

			// For Function
			if ($supplementParentModule === '') {
				$processFunction  = 'process';
			} else {
				$processFunction  = "{$supplementParentModule}" . $this->httpObject->Env->APPEND_SUPPLEMENT_FUNCTION_KEYWORD;
			}

			// For Execute
			$supplementParentCurrentResponse = $this->supplementObject->$processFunction();

			// For Rollback
			if (
				$supplementParentCurrentTransaction
				&& !$this->httpObject->db->beganTransaction
			) {
				$supplementParentCurrentResponse['Error'] = 'Something went wrong';
				return;
			}

			// For Child
			if (isset($supplementParentSqlConfig['__SUB-CONFIG__'])) {
				if ($supplementParentCurrentFollowSqlConfigThread) {
					$this->resetFetchData(
						activeDataKey: 'previousPayload',
						payloadKeyArray: $supplementParentCurrentPayloadKeyArray,
						record: $supplementParentPayload
					);
				}
				$this->supplementChild(
					supplementChildSqlConfig: $supplementParentSqlConfig['__SUB-CONFIG__'],
					supplementChildPayloadKeyArray: $supplementParentCurrentPayloadKeyArray,
					supplementChildRequiredFieldArray: $supplementParentRequiredFieldArray,
					supplementChildResponse: $supplementParentCurrentResponse,
					supplementChildFollowSqlConfigThread: $supplementParentCurrentFollowSqlConfigThread,
					supplementChildTransaction: $supplementParentCurrentTransaction
				);
			}

			// For Triggers
			if (isset($supplementParentSqlConfig['__TRIGGER__'])) {
				$this->encodeObject->addKeyData(
					objectKey: '__TRIGGER__',
					data: $this->getTriggerData(
						triggerConfig: $supplementParentSqlConfig['__TRIGGER__'],
						payload: $supplementParentPayload
					)
				);
			}

			// For Post Hook
			if (isset($supplementParentSqlConfig['__POST-CONFIG-HOOK__'])) {
				if ($this->hookObject === Constant::$NULL) {
					$this->hookObject = new Hook(
						httpObject: $this->httpObject
					);
				}
				$this->hookObject->triggerHook(
					hookArray: $supplementParentSqlConfig['__POST-CONFIG-HOOK__']
				);
			}

			// For Affected Cache
			if (isset($supplementParentSqlConfig['__AFFECTED-CACHE-KEY__'])) {
				$indexCount = count(
					value: $supplementParentSqlConfig['__AFFECTED-CACHE-KEY__']
				);
				for ($index = 0; $index < $indexCount; $index++) {
					$this->httpObject->queryCache->queryCacheDelete(
						customerId: $this->httpObject->httpReqData['active']['customerId'],
						queryCacheKey: $supplementParentSqlConfig['__AFFECTED-CACHE-KEY__'][$index]
					);
				}
			}
		}
	}

	/**
	 * Write Child Function
	 * 
	 * @param array  $supplementChildSqlConfig             Sql config
	 * @param array  $supplementChildPayloadKeyArray       Payload Indexes
	 * @param array  $supplementChildRequiredFieldArray    Required fields
	 * @param array  $supplementChildResponse              Response by reference
	 * @param bool   $supplementChildFollowSqlConfigThread Follow Sql Config Thread
	 * @param bool   $supplementChildTransaction           If true - Operates as transaction
	 * 
	 * @return void
	 */
	private function supplementChild(
		&$supplementChildSqlConfig,
		&$supplementChildPayloadKeyArray,
		&$supplementChildRequiredFieldArray,
		&$supplementChildResponse,
		$supplementChildFollowSqlConfigThread,
		$supplementChildTransaction
	): void {
		if (
			isset($supplementChildPayloadKeyArray[0])
			&& $supplementChildPayloadKeyArray[0] === ''
		) {
			$supplementChildPayloadKeyArray = array_shift(
				$supplementChildPayloadKeyArray
			);
		}
		if (!is_array(value: $supplementChildPayloadKeyArray)) {
			$supplementChildPayloadKeyArray = [];
		}

		foreach ($supplementChildSqlConfig as $supplementModule => &$supplementChildModuleSqlConfig) {
			// For payloadKeyArray
			$supplementChildModulePayloadKeyArray = $supplementChildPayloadKeyArray;
			array_push(
				$supplementChildModulePayloadKeyArray,
				"{$supplementModule}"
			);

			// For payloadKey
			$supplementChildModulePayloadKey = $this->getPayloadKey(
				payloadKeyArray: $supplementChildPayloadKeyArray
			);

			// For Setting Current Values
			$supplementChildModuleTransaction = $supplementChildTransaction ?? isset($supplementChildModuleSqlConfig['__TRANSACTION__'])
				? $supplementChildModuleSqlConfig['__TRANSACTION__'] : Constant::$FALSE;
			$supplementChildModuleFollowSqlConfigThread = $supplementChildFollowSqlConfigThread ?? $this->getFollowSqlConfigThread(
				sqlConfig: $supplementChildModuleSqlConfig
			);

			// For Validating Sql Thread
			if (
				$supplementChildModuleFollowSqlConfigThread
				&& !$this->httpObject->request->decodeObject->isset(
					keyString: $supplementChildModulePayloadKey
				)
			) {
				throw new \Exception(
					message: "Invalid payload: Module '{$supplementModule}' missing",
					code: HttpStatus::$NotFound
				);
			}

			// For isObject
			$dataType = $this->httpObject->request->decodeObject->dataType(
				keyString: $supplementChildModulePayloadKey
			);
			if ($dataType === Constant::$NULL) {
				return;
			}
			$isObject = $dataType === 'Object';

			// For indexCount
			$indexCount = ($isObject || $isObject === Constant::$NULL)
				? 1 : $this->httpObject->request->decodeObject->count(
					keyString: $supplementChildModulePayloadKey
				);

			// For Required Fields
			if (isset($supplementChildRequiredFieldArray[$supplementModule])) {
				$supplementChildModuleRequiredFieldArray = &$supplementChildRequiredFieldArray[$supplementModule];
			} else {
				$supplementChildModuleRequiredFieldArray = &$supplementChildRequiredFieldArray;
			}

			// For Response
			$supplementChildResponse[$supplementModule] = [];
			$supplementChildModuleResponse = &$supplementChildResponse[$supplementModule];

			for ($index = 0; $index < $indexCount; $index++) {
				// For payloadKeyArray
				$supplementChildModuleCurrentPayloadKeyArray = $supplementChildModulePayloadKeyArray;
				if (!$isObject) {
					array_push(
						$supplementChildModuleCurrentPayloadKeyArray,
						"{$index}"
					);
				}

				// For payloadKey
				$supplementChildModuleCurrentPayloadKey = $this->getPayloadKey(
					payloadKeyArray: $supplementChildModuleCurrentPayloadKeyArray
				);

				// For Setting Current Values
				$supplementChildModuleCurrentTransaction = $supplementChildModuleTransaction;
				$supplementChildModuleCurrentFollowSqlConfigThread = $supplementChildModuleFollowSqlConfigThread;

				// For Validating Sql Thread
				if (
					$supplementChildModuleCurrentFollowSqlConfigThread
					&& !$this->httpObject->request->decodeObject->isset(
						keyString: $supplementChildModuleCurrentPayloadKey
					)
				) {
					throw new \Exception(
						message: "Invalid payload: Module '{$supplementModule}' missing",
						code: HttpStatus::$NotFound
					);
				}

				// For Response
				if ($isObject) {
					$supplementChildModuleCurrentResponse = &$supplementChildModuleResponse;
				} else {
					$supplementChildModuleResponse[$index] = [];
					$supplementChildModuleCurrentResponse = &$supplementChildModuleResponse[$index];
				}

				// For Parent
				$this->supplementParent(
					supplementParentSqlConfig: $supplementChildModuleSqlConfig,
					supplementParentPayloadKeyArray: $supplementChildModuleCurrentPayloadKeyArray,
					supplementParentRequiredFieldArray: $supplementChildModuleRequiredFieldArray,
					supplementParentResponse: $supplementChildModuleCurrentResponse,
					supplementParentModule: $supplementModule,
					supplementParentFollowSqlConfigThread: $supplementChildModuleCurrentFollowSqlConfigThread,
					supplementParentTransaction: $supplementChildModuleCurrentTransaction
				);
			}
		}
	}

	/**
	 * Checks if the payload is valid
	 * 
	 * @param array $sqlConfig Sql config
	 * @param array $response  Response by reference
	 * 
	 * @return bool
	 */
	private function isValidPayload(
		$sqlConfig,
		$response
	): bool {
		$return = Constant::$TRUE;
		// $isValidData = Constant::$TRUE;
		// if (isset($sqlConfig['__VALIDATE__'])) {
		// 	[$isValidData, $errorArray] = $this->validate(
		// 		validationConfig: $sqlConfig['__VALIDATE__']
		// 	);
		// 	if ($isValidData !== Constant::$TRUE) {
		// 		$this->httpObject->response->httpStatus = HttpStatus::$BadRequest;
		// 		$response['Error'] = $errorArray;
		// 		$return = Constant::$FALSE;
		// 	}
		// }
		return $return;
	}
}
