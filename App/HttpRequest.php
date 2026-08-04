<?php

/**
 * HTTP request
 * php version 8.3
 * 
 * @category  HTTP request
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App;

use Microservices\App\Auth;
use Microservices\App\CacheServerKey;
use Microservices\App\CommonFunction;
use Microservices\App\Constant;
use Microservices\App\DataRepresentation\DataDecode;
use Microservices\App\DataRepresentation\DataEncode;
use Microservices\App\DbCommonFunction;
use Microservices\App\Env;
use Microservices\App\Http;
use Microservices\App\HttpStatus;
use Microservices\App\QueryCache;
use Microservices\App\RateLimiter;
use Microservices\App\RouteParser;
use Microservices\App\Server\CacheServer;
use Microservices\App\Server\DatabaseServer;
use Microservices\App\SessionHandler\Session;

/**
 * HTTP request
 * php version 8.3
 * 
 * @category  HTTP request
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class HttpRequest
{
	/**
	 * Input Representation
	 * 
	 * @var null|string
	 */
	public $inputRepresentation = null;

	/**
	 * Routes Configuration Directory
	 * 
	 * @var null|string
	 */
	public $routesDirectory = null;

	/**
	 * Sql & Payload Configuration Directory
	 * Payload Configuration Directory for Supplement
	 * 
	 * @var null|string
	 */
	public $sqlDirectory = null;

	/**
	 * Rate Limiter
	 * 
	 * @var null|RateLimiter
	 */
	public $rateLimiterObject = null;

	/**
	 * Auth middleware object
	 * 
	 * @var null|Auth
	 */
	public $authObject = null;

	/**
	 * Request id
	 * 
	 * @var null|int
	 */
	public $requestId = null;

	/**
	 * Data Decode object
	 * 
	 * @var null|DataDecode
	 */
	public $dataDecodeObject = null;

	/**
	 * HTTP object
	 * 
	 * @var null|Http
	 */
	private $httpObject = null;

	/**
	 * Customer Cache Object
	 * 
	 * @var null|CacheServer
	 */
	public $customerCacheObject = null;

	/**
	 * Customer Query Cache Object
	 * 
	 * @var null|CacheServer
	 */
	public $customerQueryCacheObject = null;

	/**
	 * Customer Database Object
	 * 
	 * @var null|DatabaseServer
	 */
	public $customerDbObject = null;

	/**
	 * Active Request Data Collection Array
	 * 
	 * @var null|array
	 */
	public $activeRequestData = null;

	/**
	 * Public domain cache key exist flag
	 * 
	 * @var null|bool
	 */
	public $isPublicDomain = null;

	/**
	 * Private session domain cache key exist flag
	 * 
	 * @var null|bool
	 */
	public $isPrivateSessionDomain = null;

	/**
	 * Private token domain cache key exist flag
	 * 
	 * @var null|bool
	 */
	public $isPrivateTokenDomain = null;

	/**
	 * Domain cache key
	 * 
	 * @var null|bool
	 */
	public $domainCacheKey = null;

	/**
	 * Flag for Private request
	 * 
	 * @var null|bool
	 */
	public $isPrivateRequest = null;

	/**
	 * Flag for Public request
	 * 
	 * @var null|bool
	 */
	public $isPublicRequest = null;

	/**
	 * Payload stream
	 */
	public $payloadStream = null;

	/**
	 * Route Parser object
	 * 
	 * @var null|RouteParser
	 */
	public $routeParserObject = null;

	/**
	 * Customer Id
	 * 
	 * @var null|int
	 */
	public $customerId = null;

	/**
	 * Group Id
	 * 
	 * @var null|int
	 */
	public $customerUserGroupId = null;

	/**
	 * User Id
	 * 
	 * @var null|int
	 */
	public $customerUserId = null;

	/**
	 * Session object
	 * 
	 * @var null|Session
	 */
	public $sessionObject = null;

	/**
	 * Constructor
	 * 
	 * @param Http $httpObject
	 */
	public function __construct(
		Http &$httpObject
	) {
		$this->httpObject = &$httpObject;
		$this->inputRepresentation = Env::$inputRepresentation;

		DbCommonFunction::connectGlobalCache();

		$this->isPublicDomain = false;
		$this->isPrivateSessionDomain = false;
		$this->isPrivateTokenDomain = false;

		$publicDomainCacheKey = CacheServerKey::publicDomain(
			domainName: $this->httpObject->httpReqData['server']['domainName']
		);
		if (
			DbCommonFunction::$globalCacheServerObject->cacheExist(
				cacheKey: $publicDomainCacheKey
			)
		) {
			$this->isPublicDomain = true;
			$this->domainCacheKey = $publicDomainCacheKey;
			$this->isPrivateRequest = false;
			$this->isPublicRequest = true;
		}
		if (!$this->isPublicDomain) {
			$privateSessionDomainCacheKey = CacheServerKey::privateSessionDomain(
				domainName: $this->httpObject->httpReqData['server']['domainName']
			);
			if (
				DbCommonFunction::$globalCacheServerObject->cacheExist(
					cacheKey: $privateSessionDomainCacheKey
				)
			) {
				$this->isPrivateSessionDomain = true;
				$this->domainCacheKey = $privateSessionDomainCacheKey;
				$this->isPrivateRequest = true;
				$this->isPublicRequest = false;
			}
		}
		if (
			!$this->isPublicDomain
			&& !$this->isPrivateSessionDomain
		) {
			$privateTokenDomainCacheKey = CacheServerKey::privateTokenDomain(
				domainName: $this->httpObject->httpReqData['server']['domainName']
			);
			if (
				DbCommonFunction::$globalCacheServerObject->cacheExist(
					cacheKey: $privateTokenDomainCacheKey
				)
			) {
				$this->isPrivateTokenDomain = true;
				$this->domainCacheKey = $privateTokenDomainCacheKey;
				$this->isPrivateRequest = true;
				$this->isPublicRequest = false;
			}
		}
	}

	/**
	 * Initialize
	 * 
	 * @return bool
	 */
	public function init(): bool
	{
		if (
			!$this->isPublicDomain
			&& !$this->isPrivateSessionDomain
			&& !$this->isPrivateTokenDomain
			&& $this->httpObject->httpReqData['get'][ROUTE_URL_PARAM] !== '/' . Env::$reloadRequestRoutePrefix
		) {
			throw new \Exception(
				message: "Invalid domain: '{$this->httpObject->httpReqData['server']['domainName']}'",
				code: HttpStatus::$BadRequest
			);
		}

		$this->activeRequestData['customerData'] = DbCommonFunction::$globalCacheServerObject->cacheGet(
			cacheKey: $this->domainCacheKey
		);
		$this->customerId = $this->activeRequestData['customerData']['customer_id'];

		if ($this->isPrivateSessionDomain) {
			$this->sessionObject = new Session();
			$this->sessionObject->sessionDomain = $this->httpObject->httpReqData['server']['domainName'];
			$this->sessionObject->initSessionHandler(
				customerData: $this->activeRequestData['customerData'],
				options: []
			);
			$this->sessionObject->sessionStartReadonly();
		}

		if (
			$this->isPublicRequest
			&& !CommonFunction::isEnabled(
				httpObject: $this->httpObject,
				feature: 'customer_enabled_public_request'
			)
		) {
			throw new \Exception(
				message: 'Public request are disabled',
				code: HttpStatus::$BadRequest
			);
		}

		if (
			$this->isPrivateRequest
			&& !CommonFunction::isEnabled(
				httpObject: $this->httpObject,
				feature: 'customer_enabled_private_request'
			)
		) {
			throw new \Exception(
				message: 'Private request are disabled',
				code: HttpStatus::$BadRequest
			);
		}

		if (
			(
				$this->isPublicRequest
				&& CommonFunction::isEnabled(
					httpObject: $this->httpObject,
					feature: 'customer_enabled_query_cache_for_public_request'
				)
			)
			|| (
				$this->isPrivateRequest
				&& CommonFunction::isEnabled(
					httpObject: $this->httpObject,
					feature: 'customer_enabled_query_cache_for_private_request'
				)
			)
		) {
			$this->customerQueryCacheObject = new QueryCache(
				$this->httpObject
			);
		}

		if ($this->isPrivateRequest) {
			$this->customerCacheObject = DbCommonFunction::connectCustomerCache(
				customerData: $this->activeRequestData['customerData']
			);
			if (
				CommonFunction::isEnabled(
					httpObject: $this->httpObject,
					feature: 'customer_enabled_rate_limiting'
				)
			) {
				$this->rateLimiterObject = new RateLimiter(
					cacheObject: $this->customerCacheObject
				);
			}
		}

		if ($this->httpObject->httpReqData['get'][ROUTE_URL_PARAM] !== '/login') {
			if ($this->isPrivateRequest) {
				$this->authObject = new Auth(
					httpObject: $this->httpObject
				);
				$this->authObject->loadUserData();
				$this->authObject->loadGroupData();
			}

			$this->routeParserObject = new RouteParser(
				httpObject: $this->httpObject
			);
			$this->routeParserObject->parseRoute();
		}

		return true;
	}

	/**
	 * Load payload
	 * 
	 * @return void
	 */
	public function loadPayload(): void
	{
		if (isset($this->activeRequestData['payloadType'])) {
			return;
		}

		$payloadJson = "{}";

		$this->urlDecode(
			values: $this->httpObject->httpReqData['get']
		);
		$this->activeRequestData['queryParamArray'] = &$this->httpObject->httpReqData['get'];

		$this->payloadStream = fopen(
			filename: "php://memory",
			mode: "rw+b"
		);
		$payloadJson = $this->setPayloadStream();

		$this->dataDecodeObject = new DataDecode(
			inputRepresentation: $this->inputRepresentation,
			dataFileHandle: $this->payloadStream
		);

		$this->dataDecodeObject->init();
		$this->dataDecodeObject->indexData();

		$this->activeRequestData['payloadType'] = $this->dataDecodeObject->dataType();

	}

	/**
	 * Set payload stream
	 * 
	 * @return string
	 */
	private function setPayloadStream(): string
	{
		$payloadJson = '{}';
		switch ($this->httpObject->httpReqData['server']['httpRequestMethod']) {
			case Constant::$GET:
				$payloadJson = json_encode($this->httpObject->httpReqData['get']);
				break;
			case Constant::$QUERY:
			case Constant::$POST:
			case Constant::$PUT:
			case Constant::$PATCH:
			case Constant::$DELETE:
				switch (true) {
					case (
						$this->httpObject->httpReqData['get'][ROUTE_URL_PARAM] !== '/login'
						&& $this->routeParserObject->routeEndingWithReservedKeywordFlag
						&& ($this->routeParserObject->routeEndingReservedKeyword === Env::$importRequestRouteKeyword)
						&& isset($this->httpObject->httpReqData['files']['file']['tmp_name'])
					):
						$uploadedFileName = $this->httpObject->httpReqData['files']['file']['tmp_name'];
						$uploadedFileMd5 = md5_file(
							$this->httpObject->httpReqData['files']['file']['tmp_name']
						);

						$this->customerDbObject = DbCommonFunction::connectCustomerDb(
							customerData: $this->httpObject->httpRequestObject->activeRequestData['customerData'],
							fetchDbMode: 'Master'
						);

						// Check uploaded file is duplicate
						if (false) {
							$uploadedFileMd5Data = $this->getUploadedFileMd5Data(uploadedFileMd5: $uploadedFileMd5);

							if ($uploadedFileMd5Data !== Constant::$FALSE) {
								throw new \Exception(
									message: "Same file was already uploaded on '{$uploadedFileMd5Data['uploaded_on']}'",
									code: HttpStatus::$BadRequest
								);
							}

						}

						$sql = 'INSERT INTO `import_file_detail` SET
							customer_id = :customer_id,
							customer_user_group_id = :customer_user_group_id,
							customer_user_id = :customer_user_id,
							uploaded_file_name = :uploaded_file_name,
							uploaded_file_md5 = :uploaded_file_md5,
							request_ip = :request_ip
						';
						$paramArray[':customer_id'] = $this->customerId;
						$paramArray[':customer_user_group_id'] = $this->customerUserGroupId;
						$paramArray[':customer_user_id'] = $this->customerUserId;
						$paramArray[':uploaded_file_name'] = $uploadedFileName;
						$paramArray[':uploaded_file_md5'] = $uploadedFileMd5;
						$paramArray[':request_ip'] = $this->httpObject->httpReqData['server']['httpRequestIp'];

						$this->customerDbObject->execQuery(
							sql: $sql,
							paramArray: $paramArray
						);
						$importFileMd5Id = $this->customerDbObject->lastInsertId();

						$payloadJson = $this->formatCsvPayload(
							csvFile: $this->httpObject->httpReqData['files']['file']['tmp_name']
						);
						break;
					case $this->inputRepresentation === 'XML':
						$payloadJson = $this->convertXmlToJson(
							xmlString: $this->httpObject->httpReqData['post']
						);
						break;
					default:
						$payloadJson = $this->httpObject->httpReqData['post'];
				}
				break;
		}

		fwrite(
			stream: $this->payloadStream,
			data: $payloadJson
		);
		rewind(
			stream: $this->payloadStream
		);

		$this->requestId = $this->getRequestId(
			customerId: $this->customerId,
			customerUserGroupId: $this->customerUserGroupId,
			customerUserId: $this->customerUserId,
			route: $this->httpObject->httpReqData['get'][ROUTE_URL_PARAM],
			httpRequestMethod: $this->httpObject->httpReqData['server']['httpRequestMethod'],
			httpRequestIp: $this->httpObject->httpReqData['server']['httpRequestIp'],
			payloadJson: $payloadJson
		);

		return $payloadJson;
	}

	/**
	 * Get Request Id
	 * 
	 * @param string $uploadedFileMd5
	 * 
	 * @return mixed
	 */
	public function getUploadedFileMd5Data(
		$uploadedFileMd5
	): mixed {
		$uploadedFileMd5Data = false;

		$sql = "SELECT
				*
			FROM
				`import_file_detail`
			WHERE
				`uploaded_file_md5` = :uploaded_file_md5
				AND `is_disabled` = 'No'
				AND `is_deleted` = 'No'
		";
		$paramArray[':uploaded_file_md5'] = $uploadedFileMd5;

		$this->customerDbObject->execQuery(
			sql: $sql,
			paramArray: $paramArray
		);
		if ($record = $this->customerDbObject->fetch()) {
			$uploadedFileMd5Data = &$record;
		}

		return $uploadedFileMd5Data;
	}

	/**
	 * Get Request Id
	 * 
	 * @param int    $customerId
	 * @param int    $customerUserGroupId
	 * @param int    $customerUserId
	 * @param string $route
	 * @param string $httpRequestMethod
	 * @param string $httpRequestIp
	 * @param string $payloadJson
	 * 
	 * @return int
	 */
	public function getRequestId(
		&$customerId,
		&$customerUserGroupId,
		&$customerUserId,
		&$route,
		&$httpRequestMethod,
		&$httpRequestIp,
		&$payloadJson
	): int {
		$requestId = 0;
		if ($this->isPrivateRequest) {
			DbCommonFunction::connectGlobalDb();
			$sql = 'INSERT INTO `request` SET
				customer_id = :customer_id,
				customer_user_group_id = :customer_user_group_id,
				customer_user_id = :customer_user_id,
				request_route = :request_route,
				request_method = :request_method,
				request_ip = :request_ip,
				request_payload_json = :request_payload_json
			';
			$paramArray[':customer_id'] = $customerId;
			$paramArray[':customer_user_group_id'] = $customerUserGroupId;
			$paramArray[':customer_user_id'] = $customerUserId;
			$paramArray[':request_route'] = $route;
			$paramArray[':request_method'] = $httpRequestMethod;
			$paramArray[':request_ip'] = $httpRequestIp;
			$paramArray[':request_payload_json'] = $payloadJson;

			DbCommonFunction::$gDbServer->execQuery(
				sql: $sql,
				paramArray: $paramArray
			);
			$requestId = DbCommonFunction::$gDbServer->lastInsertId();
		}

		return $requestId;
	}

	/**
	 * Log Debug Data
	 * 
	 * @param string $debugMode
	 * @param string $debugJson
	 * 
	 * @return int
	 */
	public function logDebugData(
		$debugMode,
		$debugJson
	): int {
		$logId = 0;
		if ($this->isPrivateRequest) {
			DbCommonFunction::connectGlobalDb();
			$sql = 'INSERT INTO `debug_log` SET
				debug_mode = :debug_mode,
				request_id = :request_id,
				customer_id = :customer_id,
				customer_user_group_id = :customer_user_group_id,
				customer_user_id = :customer_user_id,
				request_route = :request_route,
				request_method = :request_method,
				request_payload_json = :request_payload_json,
				request_config_json = :request_config_json,
				request_session_json = :request_session_json,
				request_exception_json = :request_exception_json,
				request_ip = :request_ip
			';
			$paramArray[':debug_mode'] = $debugMode;
			$paramArray[':request_id'] = $this->requestId;
			$paramArray[':customer_id'] = $this->customerId;
			$paramArray[':customer_user_group_id'] = $this->customerUserGroupId;
			$paramArray[':customer_user_id'] = $this->customerUserId;
			$paramArray[':request_route'] = $this->httpObject->httpReqData['get'][ROUTE_URL_PARAM];
			$paramArray[':request_method'] = $this->httpObject->httpReqData['server']['httpRequestMethod'];
			$paramArray[':request_payload_json'] = isset($this->activeRequestData['payload']) ? json_encode(
				value: $this->activeRequestData['payload']
			) : '{}';
			$paramArray[':request_config_json'] = isset($this->routeParserObject->sqlConfig) ? json_encode(
				value: $this->routeParserObject->sqlConfig
			) : '{}';
			$paramArray[':request_session_json'] = isset($this->activeRequestData) ? json_encode(
				value: $this->activeRequestData
			) : '{}';
			$paramArray[':request_debug_json'] = $debugJson;
			$paramArray[':request_ip'] = $this->httpObject->httpReqData['server']['httpRequestIp'];

			DbCommonFunction::$gDbServer->execQuery(
				sql: $sql,
				paramArray: $paramArray
			);
			$logId = DbCommonFunction::$gDbServer->lastInsertId();
		}

		return $logId;
	}

	/**
	 * Log Error Data
	 * 
	 * @param string $exceptionJson
	 * 
	 * @return int
	 */
	public function logErrorData(
		$exceptionJson
	): int {
		$logId = 0;
		if ($this->isPrivateRequest) {
			DbCommonFunction::connectGlobalDb();
			$sql = 'INSERT INTO `error_log` SET
				request_id = :request_id,
				customer_id = :customer_id,
				customer_user_group_id = :customer_user_group_id,
				customer_user_id = :customer_user_id,
				request_route = :request_route,
				request_method = :request_method,
				request_payload_json = :request_payload_json,
				request_config_json = :request_config_json,
				request_session_json = :request_session_json,
				request_exception_json = :request_exception_json,
				request_ip = :request_ip
			';
			$paramArray[':request_id'] = $this->requestId;
			$paramArray[':customer_id'] = $this->customerId;
			$paramArray[':customer_user_group_id'] = $this->customerUserGroupId;
			$paramArray[':customer_user_id'] = $this->customerUserId;
			$paramArray[':request_route'] = $this->httpObject->httpReqData['get'][ROUTE_URL_PARAM];
			$paramArray[':request_method'] = $this->httpObject->httpReqData['server']['httpRequestMethod'];
			$paramArray[':request_payload_json'] = isset($this->activeRequestData['payload']) ? json_encode(
				value: $this->activeRequestData['payload']
			) : '{}';
			$paramArray[':request_config_json'] = isset($this->routeParserObject->sqlConfig) ? json_encode(
				value: $this->routeParserObject->sqlConfig
			) : '{}';
			$paramArray[':request_session_json'] = isset($this->activeRequestData) ? json_encode(
				value: $this->activeRequestData
			) : '{}';
			$paramArray[':request_exception_json'] = $exceptionJson;
			$paramArray[':request_ip'] = $this->httpObject->httpReqData['server']['httpRequestIp'];

			DbCommonFunction::$gDbServer->execQuery(
				sql: $sql,
				paramArray: $paramArray
			);
			$logId = DbCommonFunction::$gDbServer->lastInsertId();
		}

		return $logId;
	}

	/**
	 * Convert XML to JSON
	 * 
	 * @param string $xmlString
	 * 
	 * @return string
	 */
	private function convertXmlToJson(
		$xmlString
	): string {
		$xml = simplexml_load_string(
			data: $xmlString
		);
		$arrayFromXml = CommonFunction::jsonDecode(
			value: json_encode(
				value: $xml
			)
		);
		unset($xml);

		$result = [];
		$this->formatXmlArray(
			arrayFromXml: $arrayFromXml,
			result: $result
		);

		return json_encode(
			value: $result
		);
	}

	/**
	 * Format Array generated by XML
	 * 
	 * @param array $arrayFromXml Array generated by XML
	 * @param array $result       Formatted array
	 * 
	 * @return void
	 */
	private function formatXmlArray(
		&$arrayFromXml,
		&$result
	): void {
		if (
			isset($arrayFromXml['Records'])
			&& is_array(
				value: $arrayFromXml['Records']
			)
		) {
			$arrayFromXml = &$arrayFromXml['Records'];
		}

		if (
			isset($arrayFromXml['Record'])
			&& is_array(
				value: $arrayFromXml['Record']
			)
		) {
			$arrayFromXml = &$arrayFromXml['Record'];
		}

		if (
			isset($arrayFromXml[0])
			&& is_array(
				value: $arrayFromXml[0]
			)
			&& count(
				value: $arrayFromXml
			) === 1
		) {
			$arrayFromXml = &$arrayFromXml[0];
			if (empty($arrayFromXml)) {
				return;
			}
		}

		if (
			!is_array(
				value: $arrayFromXml
			)
		) {
			return;
		}

		$xmlAttributeColumn = 'attribute';
		foreach ($arrayFromXml as $column => &$columnValue) {
			if ($column === $xmlAttributeColumn) {
				foreach ($columnValue as $attributeKey => &$attributeKeyValue) {
					$result[$attributeKey] = $attributeKeyValue;
				}
				continue;
			}
			if (
				is_array(
					value: $columnValue
				)
			) {
				$result[$column] = [];
				$this->formatXmlArray(
					arrayFromXml: $columnValue,
					result: $result[$column]
				);
				continue;
			}
			$result[$column] = $columnValue;
		}
	}

	/**
	 * urldecode string or array
	 * 
	 * @param array|string $value Array vales to be decoded. Basically $httpReqData['get']
	 * 
	 * @return void
	 */
	public function urlDecode(
		&$values
	): void {
		if (
			is_array(
				value: $values
			)
		) {
			foreach ($values as &$value) {
				if (
					is_array(
						value: $value
					)
				) {
					$this->urlDecode(
						values: $value
					);
				} else {
					$value = urldecode(
						string: $value
					);
				}
			}
		} else {
			$values = urldecode(
				string: $values
			);
		}
	}

	/**
	 * Format CSV Payload
	 * 
	 * @param string $csvFile
	 * 
	 * @return string
	 */
	public function formatCsvPayload(
		$csvFile
	): string {
		$dataEncodeObject = new DataEncode(
			httpObject: $this->httpObject
		);
		$dataEncodeObject->init(
			header: Constant::$FALSE
		);
		$dataEncodeObject->startObject();

		$csvHeaderData = false;
		$counter = null;
		$currentModeArray = [];

		$fp = fopen($csvFile, "r");
		while (($csvString = fgets($fp)) !== Constant::$FALSE) {
			if (empty($csvString)) {
				continue;
			}
			$csvRecordArray = str_getcsv(
				$csvString,
				",",
				"\"",
				"\\"
			);
			if (empty($csvRecordArray)) {
				continue;
			}
			if ($csvHeaderData === Constant::$FALSE) {
				$csvHeaderData = [];
				foreach ($csvRecordArray as $columnPosition => $value) {
					$values = explode(
						':',
						$value
					);
					$_csvHeaderData = &$csvHeaderData;
					$indexCount = count(
						value: $values
					);
					for ($index = 0; $index < $indexCount; $index++) {
						if (($index+1) === $indexCount) {
							$_csvHeaderData['__column__'][$values[$index]] = $columnPosition;
						} else {
							if (!isset($_csvHeaderData[$values[$index]])) {
								$_csvHeaderData[$values[$index]] = [];
							}
							$_csvHeaderData = &$_csvHeaderData[$values[$index]];
						}
					}
				}
				$counter = 0;
				continue;
			}

			[$currentModeArray, $csvFieldRecordArray] = $this->formatCsvArray(
				csvHeaderData: $csvHeaderData,
				csvRecordArray: $csvRecordArray
			);

			if ($counter === 0) {
				$headerModeArray = $currentModeArray;
				$dataEncodeObject->startArray(
					objectKey: $currentModeArray[0]
				);
				$dataEncodeObject->startObject();
				foreach ($csvFieldRecordArray as $objectKey => &$objectKeyValue) {
					$dataEncodeObject->addKeyData(
						objectKey: $objectKey,
						data: $objectKeyValue
					);
				}
				$counter = 1;
				continue;
			}

			if ($headerModeArray === $currentModeArray) {
				$dataEncodeObject->endObject();
				$dataEncodeObject->startObject();
			} else {
				$_headerModeArray = [];
				$headerModeCount = count(
					value: $headerModeArray
				);
				$currentModeCount = count(
					value: $currentModeArray
				);

				for (
					$index = 0;
					$index < $currentModeCount;
					$index++
				) {
					if (
						!isset($headerModeArray[$index])
						|| ($headerModeArray[$index] !== $currentModeArray[$index])
					) {
						break;
					}
					$_headerModeArray[$index] = $currentModeArray[$index];
				}
				if ($currentModeCount < $headerModeCount) {
					for ($_i = $currentModeCount; $_i < $headerModeCount; $_i++) {
						$dataEncodeObject->endObject();
						$dataEncodeObject->endArray();
					}
					$dataEncodeObject->endObject();
					$dataEncodeObject->startObject();
				}
				if ($index < $currentModeCount) {
					for ($_i = $index; $_i < $headerModeCount; $_i++) {
						$dataEncodeObject->endObject();
						$dataEncodeObject->endArray();
					}
					for ($_i = $index; $_i < $currentModeCount; $_i++) {
						$_headerModeArray[$_i] = $currentModeArray[$_i];
						$dataEncodeObject->startArray(
							objectKey: $currentModeArray[$_i]
						);
						$dataEncodeObject->startObject();
					}
				}
				$headerModeArray = $_headerModeArray;
			}
			foreach ($csvFieldRecordArray as $objectKey => &$objectKeyValue) {
				$dataEncodeObject->addKeyData(
					objectKey: $objectKey,
					data: $objectKeyValue
				);
			}
		}
		$dataEncodeObject->endObject();
		$json = $dataEncodeObject->getData();
		$dataEncodeObject = null;
		$json = substr(
			string: $json,
			offset: 7,
			length: (strlen($json)-8)
		);

		return $json;
	}

	/**
	 * Format CSV Payload
	 * 
	 * @param array $csvHeaderData
	 * @param array $csvRecordArray
	 * 
	 * @return array
	 */
	public function formatCsvArray(
		$csvHeaderData,
		$csvRecordArray
	): array {
		$csvFieldRecordArray = [];
		$currentModeArray = explode(
			':',
			$csvRecordArray[0]
		);

		foreach ($currentModeArray as $currentMode) {
			if (!isset($csvHeaderData[$currentMode])) {
				return [];
			}
			$csvHeaderData = &$csvHeaderData[$currentMode];
		}

		if (!isset($csvHeaderData['__column__'])) {
			throw new \Exception(
				message: json_encode(
					value: [$currentModeArray,$csvHeaderData]
				),
				code: HttpStatus::$BadRequest
			);
		}

		foreach ($csvHeaderData['__column__'] as $field => $column) {
			if (!isset($csvRecordArray[$column])) {
				return [];
			}
			$csvFieldRecordArray[$field] = $csvRecordArray[$column];
		}
		return [$currentModeArray, $csvFieldRecordArray];
	}
}
