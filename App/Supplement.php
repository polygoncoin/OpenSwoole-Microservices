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
	public $dataEncodeObject = null;

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
		$this->dataEncodeObject = &$this->httpObject->httpResponseObject->dataEncodeObject;
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
		$return = $this->writeBasics(
			$sqlConfig,
			$maintainHierarchy
		);

		$operateAsTransaction = isset($sqlConfig['__TRANSACTION__'])
			? $sqlConfig['__TRANSACTION__'] : Constant::$FALSE;

		if ($return !== Constant::$FALSE) {
			return $return;
		}

		$fetchDbMode = $sqlConfig['__FETCH-MODE__'] ?? 'Master';

		// Set Server mode to execute query on - Read / Write Server
		if ($this->httpObject->httpRequestObject->customerDbObject === Constant::$NULL) {
			$this->httpObject->httpRequestObject->customerDbObject = DbCommonFunction::connectCustomerDb(
				customerData: $this->httpObject->httpRequestObject->activeRequestData['customerData'],
				fetchDbMode: $fetchDbMode
			);
		}

		$this->supplement(
			supplementSqlConfig: $sqlConfig,
			supplementMaintainHierarchy: $maintainHierarchy,
			supplementOperateAsTransaction: $operateAsTransaction
		);

		return Constant::$TRUE;
	}

	/**
	 * Process Function to insert/update
	 * 
	 * @param array $supplementSqlConfig            Sql config
	 * @param bool  $supplementMaintainHierarchy    If true - Uses parent payload/results in child
	 * @param bool  $supplementOperateAsTransaction If true - Operates as transaction
	 * 
	 * @return void
	 * @throws \Exception
	 */
	private function supplement(
		&$supplementSqlConfig,
		$supplementMaintainHierarchy,
		$supplementOperateAsTransaction
	): void {
		$supplementOutputRepresentation = CommonFunction::getOutputRepresentation(
			sqlConfig: $supplementSqlConfig,
			httpReqData: $this->httpObject->httpReqData
		);

		// Set required fields
		$this->httpObject->httpRequestObject->activeRequestData['requiredFieldArrayCollection'] = $this->getRequired(
			sqlConfig: $supplementSqlConfig,
			maintainHierarchy: $supplementMaintainHierarchy,
			isFirstCall: Constant::$TRUE
		);

		$this->dataEncodeObject->startObject(
			objectKey: 'Results'
		);

		$supplementPayloadType = $this->httpObject->httpRequestObject->dataDecodeObject->dataType(
			keyString: Constant::$NULL
		);

		if ($supplementPayloadType === 'Array') {
			if (
				in_array(
					needle: $supplementOutputRepresentation['outputRepresentation'],
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
		$indexCount = $supplementPayloadType === 'Array'
			? $this->httpObject->httpRequestObject->dataDecodeObject->count() : 1;

		for ($index = 0; $index < $indexCount; $index++) {
			$supplementPayloadKeyArray = Constant::$NULL;

			if ($supplementPayloadType === 'Array') {
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

				if ($supplementOperateAsTransaction) {
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
							payloadKeyArray: $supplementPayloadKeyArray
						)
					);
				}

				$supplementResponse = [];

				// For Parent
				$this->supplementParent(
					supplementParentSqlConfig: $supplementSqlConfig,
					supplementParentPayloadKeyArray: $supplementPayloadKeyArray,
					supplementParentRequiredFieldArray: $this->httpObject->httpRequestObject->activeRequestData['requiredFieldArrayCollection'],
					supplementParentResponse: $supplementResponse,
					supplementParentModule: '',
					supplementParentMaintainHierarchy: $supplementMaintainHierarchy,
					supplementParentOperateAsTransaction: $supplementOperateAsTransaction
				);

				if ($this->httpObject->httpResponseObject->httpStatus === HttpStatus::$Ok) {
					if (
						$supplementOperateAsTransaction
						&& ($this->httpObject->httpRequestObject->customerDbObject->beganTransaction === Constant::$TRUE)
					) {
						$this->httpObject->httpRequestObject->customerDbObject->commit();
					}
					$output['PayloadResponse'] = $supplementResponse;

					if ($idempotentWindow) {
						$this->httpObject->httpRequestObject->customerCacheObject->cacheSet(
							cacheKey: $hashKey,
							cacheValue: $output,
							cacheExpire: $idempotentWindow
						);
					}
				} else { // Failure
					$output['Status'] = $this->httpObject->httpResponseObject->httpStatus;
					$output['Error'] = $supplementResponse;
				}
			} else {
				$output = CommonFunction::jsonDecode(
					value: $hashJson
				);
			}

			if ($supplementPayloadKeyArray === Constant::$NULL) {
				foreach ($output as $outputKey => &$outputKeyValue) {
					$this->dataEncodeObject->addKeyData(
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

		if ($supplementPayloadType === 'Array') {
			if (
				in_array(
					needle: $supplementOutputRepresentation['outputRepresentation'],
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
	 * Supplement Parent Function
	 * 
	 * @param array  $supplementParentSqlConfig            Sql config
	 * @param array  $supplementParentPayloadKeyArray      Payload Indexes
	 * @param array  $supplementParentRequiredFieldArray   Required fields
	 * @param array  $supplementParentResponse             Response by reference
	 * @param string $supplementParentModule               Parent Module
	 * @param bool   $supplementParentMaintainHierarchy    If true - Uses parent payload/results in child
	 * @param bool   $supplementParentOperateAsTransaction If true - Operates as transaction
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
		$supplementParentMaintainHierarchy,
		$supplementParentOperateAsTransaction
	): void {
		// For payloadKey
		$supplementParentPayloadKey = $this->getPayloadKey(
			payloadKeyArray: $supplementParentPayloadKeyArray
		);

		// For isObject
		$isObject = $this->httpObject->httpRequestObject->dataDecodeObject->dataType(
			keyString: $supplementParentPayloadKey
		) === 'Object';
		if ($isObject === Constant::$NULL) {
			return;
		}

		// For indexCount
		$indexCount = ($isObject)
			? 1 : $this->httpObject->httpRequestObject->dataDecodeObject->count(
				keyString: $supplementParentPayloadKey
			);

		if (isset($supplementParentSqlConfig['__PAYLOAD-TYPE__'])) {
			$supplementPayloadType = $isObject ? 'Object' : 'Array';
			if ($supplementPayloadType !== $supplementParentSqlConfig['__PAYLOAD-TYPE__']) {
				$errorArray[] = "Payload can't be an {$supplementPayloadType}";
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
				$this->httpObject->httpRequestObject->activeRequestData['requiredFieldArray'] = $supplementParentRequiredFieldArray;
			} else {
				$this->httpObject->httpRequestObject->activeRequestData['requiredFieldArray'] = [];
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
			$supplementParentCurrentOperateAsTransaction = $supplementParentOperateAsTransaction;
			$supplementParentCurrentMaintainHierarchy = $supplementParentMaintainHierarchy;

			// For Validating Hierarchy
			if (
				$supplementParentCurrentMaintainHierarchy
				&& !$this->httpObject->httpRequestObject->dataDecodeObject->isset(
					keyString: $supplementParentCurrentPayloadKey
				)
			) {
				throw new \Exception(
					message: "Payload key '{$supplementParentCurrentPayloadKey}' not set",
					code: HttpStatus::$NotFound
				);
			}

			// For isObject
			$isObject = $this->httpObject->httpRequestObject->dataDecodeObject->dataType(
				keyString: $supplementParentCurrentPayloadKey
			) === 'Object';
			if ($isObject === Constant::$NULL) {
				return;
			}

			// For Payload
			$supplementParentPayload = $this->httpObject->httpRequestObject->dataDecodeObject->getObject(
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
				$processFunction  = "{$supplementParentModule}" . Env::$appendSupplementFunctionWith;
			}

			// For Execute
			$supplementParentCurrentResponse = $this->supplementObject->$processFunction();

			// For Rollback
			if (
				$supplementParentCurrentOperateAsTransaction
				&& !$this->httpObject->httpRequestObject->customerDbObject->beganTransaction
			) {
				$supplementParentCurrentResponse['Error'] = 'Something went wrong';
				return;
			}

			// For Child
			if (isset($supplementParentSqlConfig['__SUB-CONFIG__'])) {
				if ($supplementParentCurrentMaintainHierarchy) {
					$this->resetFetchData(
						activeRequestDataKey: 'previousPayload',
						payloadKeyArray: $supplementParentCurrentPayloadKeyArray,
						record: $supplementParentPayload
					);
				}
				$this->supplementChild(
					supplementChildSqlConfig: $supplementParentSqlConfig['__SUB-CONFIG__'],
					supplementChildPayloadKeyArray: $supplementParentCurrentPayloadKeyArray,
					supplementChildRequiredFieldArray: $supplementParentRequiredFieldArray,
					supplementChildResponse: $supplementParentCurrentResponse,
					supplementChildMaintainHierarchy: $supplementParentCurrentMaintainHierarchy,
					supplementChildOperateAsTransaction: $supplementParentCurrentOperateAsTransaction
				);
			}

			// For Triggers
			if (isset($supplementParentSqlConfig['__TRIGGER__'])) {
				$this->dataEncodeObject->addKeyData(
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
					$this->httpObject->httpRequestObject->customerQueryCacheObject->queryCacheDelete(
						customerId: $this->httpObject->httpRequestObject->customerId,
						queryCacheKey: $supplementParentSqlConfig['__AFFECTED-CACHE-KEY__'][$index]
					);
				}
			}
		}
	}

	/**
	 * Write Child Function
	 * 
	 * @param array  $supplementChildSqlConfig            Sql config
	 * @param array  $supplementChildPayloadKeyArray      Payload Indexes
	 * @param array  $supplementChildRequiredFieldArray   Required fields
	 * @param array  $supplementChildResponse             Response by reference
	 * @param bool   $supplementChildMaintainHierarchy    If true - Uses parent payload/results in child
	 * @param bool   $supplementChildOperateAsTransaction If true - Operates as transaction
	 * 
	 * @return void
	 */
	private function supplementChild(
		&$supplementChildSqlConfig,
		&$supplementChildPayloadKeyArray,
		&$supplementChildRequiredFieldArray,
		&$supplementChildResponse,
		$supplementChildMaintainHierarchy,
		$supplementChildOperateAsTransaction
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
			$supplementChildModuleOperateAsTransaction = $supplementChildOperateAsTransaction ?? isset($supplementChildModuleSqlConfig['__TRANSACTION__'])
				? $supplementChildModuleSqlConfig['__TRANSACTION__'] : Constant::$FALSE;
			$supplementChildModuleMaintainHierarchy = $supplementChildMaintainHierarchy ?? $this->getMaintainHierarchy(
				sqlConfig: $supplementChildModuleSqlConfig
			);

			// For Validating Hierarchy
			if (
				$supplementChildModuleMaintainHierarchy
				&& !$this->httpObject->httpRequestObject->dataDecodeObject->isset(
					keyString: $supplementChildModulePayloadKey
				)
			) {
				throw new \Exception(
					message: "Invalid payload: Module '{$supplementModule}' missing",
					code: HttpStatus::$NotFound
				);
			}

			// For isObject
			$isObject = $this->httpObject->httpRequestObject->dataDecodeObject->dataType(
				keyString: $supplementChildModulePayloadKey
			) === 'Object';
			if ($isObject === Constant::$NULL) {
				return;
			}

			// For indexCount
			$indexCount = ($isObject || $isObject === Constant::$NULL)
				? 1 : $this->httpObject->httpRequestObject->dataDecodeObject->count(
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
				$supplementChildModuleCurrentOperateAsTransaction = $supplementChildModuleOperateAsTransaction;
				$supplementChildModuleCurrentMaintainHierarchy = $supplementChildModuleMaintainHierarchy;

				// For Validating Hierarchy
				if (
					$supplementChildModuleCurrentMaintainHierarchy
					&& !$this->httpObject->httpRequestObject->dataDecodeObject->isset(
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
					supplementParentMaintainHierarchy: $supplementChildModuleCurrentMaintainHierarchy,
					supplementParentOperateAsTransaction: $supplementChildModuleCurrentOperateAsTransaction
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
		// 		$this->httpObject->httpResponseObject->httpStatus = HttpStatus::$BadRequest;
		// 		$response['Error'] = $errorArray;
		// 		$return = Constant::$FALSE;
		// 	}
		// }
		return $return;
	}
}
