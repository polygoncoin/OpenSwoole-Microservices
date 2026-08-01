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
	 * Operate DML As Transactions
	 * 
	 * @var null|bool
	 */
	private $operateAsTransaction = null;

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

		if ($return !== Constant::$FALSE) {
			return $return;
		}

		// Operate as Transaction (BEGIN COMMIT else ROLLBACK on error)
		$this->operateAsTransaction = isset($sqlConfig['isTransaction'])
			? $sqlConfig['isTransaction'] : Constant::$FALSE;

		$fetchDbMode = $sqlConfig['fetchDbMode'] ?? 'Master';

		// Set Server mode to execute query on - Read / Write Server
		if ($this->httpObject->httpRequestObject->customerDbObject === Constant::$NULL) {
			$this->httpObject->httpRequestObject->customerDbObject = DbCommonFunction::connectCustomerDb(
				customerData: $this->httpObject->httpRequestObject->activeRequestData['customerData'],
				fetchDbMode: $fetchDbMode
			);
		}

		$this->supplement(
			supplementSqlConfig: $sqlConfig,
			supplementMaintainHierarchy: $maintainHierarchy
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
	 * Process Function to insert/update
	 * 
	 * @param array $supplementSqlConfig         Sql config
	 * @param bool  $supplementMaintainHierarchy If true - Uses parent payload/results in child
	 * 
	 * @return void
	 * @throws \Exception
	 */
	private function supplement(
		&$supplementSqlConfig,
		$supplementMaintainHierarchy
	): void {
		// Check for payloadType
		if (isset($supplementSqlConfig['__PAYLOAD-TYPE__'])) {
			$supplementPayloadType = $this->httpObject->httpRequestObject->activeRequestData['payloadType'];
			if ($supplementPayloadType !== $supplementSqlConfig['__PAYLOAD-TYPE__']) {
				throw new \Exception(
					message: 'Invalid payload type',
					code: HttpStatus::$BadRequest
				);
			}

			// Check for maximum object's supported when payloadType is Array
			if (
				$supplementSqlConfig['__PAYLOAD-TYPE__'] === 'Array'
				&& isset($supplementSqlConfig['__MAX-PAYLOAD-OBJECTS__'])
				&& ($objCount = $this->httpObject->httpRequestObject->dataDecodeObject->count())
				&& ($objCount > $supplementSqlConfig['__MAX-PAYLOAD-OBJECTS__'])
			) {
				throw new \Exception(
					message: 'Maximum supported payload count is '
						. $supplementSqlConfig['__MAX-PAYLOAD-OBJECTS__'],
					code: HttpStatus::$BadRequest
				);
			}
		}

		// Set required fields
		$this->httpObject->httpRequestObject->activeRequestData['requiredFieldArrayCollection'] = $this->getRequired(
			sqlConfig: $supplementSqlConfig,
			maintainHierarchy: $supplementMaintainHierarchy,
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
			$supplementPayloadKeyArray = null;

			if ($this->httpObject->httpRequestObject->activeRequestData['payloadType'] === 'Array') {
				$supplementPayloadKeyArray = [];
				$supplementPayloadKeyArray[] = "{$index}";
			}

			// For Idempotent Window
			[$idempotentWindow, $hashKey, $hashJson] = $this->checkIdempotent(
				sqlConfig: $supplementSqlConfig,
				payloadKeyArray: $supplementPayloadKeyArray
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
					supplementParentMaintainHierarchy: $supplementMaintainHierarchy
				);

				if ($this->httpObject->httpResponseObject->httpStatus === HttpStatus::$Ok) {
					if (
						$this->operateAsTransaction
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
					$output['Error'] = $writeResponse;
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
	 * Supplement Parent Function
	 * 
	 * @param array  $supplementParentSqlConfig          Sql config
	 * @param array  $supplementParentPayloadKeyArray    Payload Indexes
	 * @param array  $supplementParentRequiredFieldArray Required fields
	 * @param array  $supplementParentResponse           Response by reference
	 * @param string $supplementParentModule             Parent Module
	 * @param bool   $supplementParentMaintainHierarchy  If true - Uses parent payload/results in child
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
		$supplementParentMaintainHierarchy
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

			// For Validating Hierarchy
			$supplementParentCurrentMaintainHierarchy = $supplementParentMaintainHierarchy;
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
			$this->httpObject->httpRequestObject->activeRequestData['payload'] = $this->httpObject->httpRequestObject->dataDecodeObject->get(
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
			if (isset($supplementParentSqlConfig['__PRE-SQL-HOOKS__'])) {
				if ($this->hookObject === Constant::$NULL) {
					$this->hookObject = new Hook(
						httpObject: $this->httpObject
					);
				}
				$this->hookObject->triggerHook(
					hookArray: $supplementParentSqlConfig['__PRE-SQL-HOOKS__']
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
				$this->operateAsTransaction
				&& !$this->httpObject->httpRequestObject->customerDbObject->beganTransaction
			) {
				$supplementParentCurrentResponse['Error'] = 'Something went wrong';
				return;
			}

			// For Triggers
			if (isset($supplementParentSqlConfig['__TRIGGERS__'])) {
				$this->dataEncodeObject->addKeyData(
					objectKey: '__TRIGGERS__',
					data: $this->getTriggerData(
						triggerConfig: $supplementParentSqlConfig['__TRIGGERS__']
					)
				);
			}

			// For Post Hook
			if (isset($supplementParentSqlConfig['__POST-SQL-HOOKS__'])) {
				if ($this->hookObject === Constant::$NULL) {
					$this->hookObject = new Hook(
						httpObject: $this->httpObject
					);
				}
				$this->hookObject->triggerHook(
					hookArray: $supplementParentSqlConfig['__POST-SQL-HOOKS__']
				);
			}

			// For Child
			if (isset($supplementParentSqlConfig['__SUB-QUERY__'])) {
				$this->supplementChild(
					supplementChildSqlConfig: $supplementParentSqlConfig['__SUB-PAYLOAD__'],
					supplementChildPayloadKeyArray: $supplementParentCurrentPayloadKeyArray,
					supplementChildRequiredFieldArray: $supplementParentRequiredFieldArray,
					supplementChildResponse: $supplementParentCurrentResponse,
					supplementChildMaintainHierarchy: $supplementParentCurrentMaintainHierarchy
				);
			}
		}
	}

	/**
	 * Write Child Function
	 * 
	 * @param array $supplementChildSqlConfig          Sql config
	 * @param array $supplementChildPayloadKeyArray    Payload Indexes
	 * @param array $supplementChildRequiredFieldArray Required fields
	 * @param array $supplementChildResponse           Response by reference
	 * @param bool  $supplementChildMaintainHierarchy  If true - Uses parent payload/results in child
	 * 
	 * @return void
	 */
	private function supplementChild(
		&$supplementChildSqlConfig,
		&$supplementChildPayloadKeyArray,
		&$supplementChildRequiredFieldArray,
		&$supplementChildResponse,
		$supplementChildMaintainHierarchy
	): void {
		if ($supplementChildMaintainHierarchy) {
			$record = $this->httpObject->httpRequestObject->activeRequestData['payload'];
			$this->resetFetchData(
				activeRequestDataKey: 'sqlPayload',
				payloadKeyArray: $supplementChildPayloadKeyArray,
				record: $record
			);
		}

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
				payloadKeyArray: $supplementParentPayloadKeyArray
			);

			// For Validating Hierarchy
			$supplementChildModuleMaintainHierarchy = $supplementChildMaintainHierarchy ?? $this->getMaintainHierarchy(
				sqlConfig: $supplementChildModuleSqlConfig
			);
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

				// For Validating Hierarchy
				$supplementChildModuleCurrentMaintainHierarchy = $supplementChildModuleMaintainHierarchy;
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
					supplementParentMaintainHierarchy: $supplementChildModuleCurrentMaintainHierarchy
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
		$return = true;
		$isValidData = true;
		if (isset($sqlConfig['__VALIDATE__'])) {
			[$isValidData, $errorArray] = $this->validate(
				validationConfig: $sqlConfig['__VALIDATE__']
			);
			if ($isValidData !== Constant::$TRUE) {
				$this->httpObject->httpResponseObject->httpStatus = HttpStatus::$BadRequest;
				$response['Error'] = $errorArray;
				$return = false;
			}
		}
		return $return;
	}
}
