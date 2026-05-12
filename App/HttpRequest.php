<?php

/**
 * HTTP request
 * php version 8.3
 *
 * @category  HTTP request
 * @package   Openswoole_Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App;

use Microservices\App\CacheServerKey;
use Microservices\App\Constant;
use Microservices\App\DataRepresentation\DataDecode;
use Microservices\App\DataRepresentation\DataEncode;
use Microservices\App\DbCommonFunction;
use Microservices\App\Env;
use Microservices\App\Http;
use Microservices\App\HttpStatus;
use Microservices\App\Middleware\Auth;
use Microservices\App\RouteParser;

/**
 * HTTP request
 * php version 8.3
 *
 * @category  HTTP request
 * @package   Openswoole_Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class HttpRequest
{
	/**
	 * Auth middleware object
	 *
	 * @var null|Auth
	 */
	public $auth = null;

	/**
	 * JSON Decode object
	 *
	 * @var null|DataDecode
	 */
	public $dataDecode = null;

	/**
	 * HTTP object
	 *
	 * @var null|Http
	 */
	private $http = null;

	/**
	 * Client Cache Object
	 *
	 * @var null|object
	 */
	public $clientCacheObj = null;

	/**
	 * Client DB Object
	 *
	 * @var null|object
	 */
	public $clientDbObj = null;

	/**
	 * Session detail of a request
	 *
	 * @var null|array
	 */
	public $s = null;

	/**
	 * Open To Web request
	 *
	 * @var null|bool
	 */
	public $isOpenToWebRequest = null;

	/**
	 * Payload stream
	 */
	public $payloadStream = null;

	/**
	 * Route Parser object
	 *
	 * @var null|RouteParser
	 */
	public $rParser = null;

	/**
	 * Customer id
	 *
	 * @var null|int
	 */
	public $cID = null;

	/**
	 * Group id
	 *
	 * @var null|int
	 */
	public $gID = null;

	/**
	 * User id
	 *
	 * @var null|int
	 */
	public $uID = null;

	/**
	 * Constructor
	 *
	 * @param Http $http
	 */
	public function __construct(Http &$http)
	{
		$this->http = &$http;

		if (isset($this->http->httpReqDetailArr['get'][ROUTE_URL_PARAM])) {
			$this->http->httpReqDetailArr['get'][ROUTE_URL_PARAM] = '/' . trim(
				string: $this->http->httpReqDetailArr['get'][ROUTE_URL_PARAM],
				characters: '/'
			);
		} else {
			$this->http->httpReqDetailArr['get'][ROUTE_URL_PARAM] = '';
		}

		switch (Env::$authMode) {
			case 'Token':
				if (
					isset($this->http->httpReqDetailArr['header'])
					&& isset($this->http->httpReqDetailArr['header']['tokenHeader'])
					&& $this->http->httpReqDetailArr['header']['tokenHeader'] !== null
				) {
					$this->isOpenToWebRequest = false;
				} elseif ($this->http->httpReqDetailArr['get'][ROUTE_URL_PARAM] === '/login') {
					$this->isOpenToWebRequest = false;
				} elseif (Env::$enableOpenRequest) {
					$this->isOpenToWebRequest = true;
				}
				break;
			case 'Session':
				if (
					isset($_SESSION)
					&& isset($_SESSION['id'])
				) {
					if ($_SESSION['sessionExpiryTimestamp'] <= Env::$timestamp) {
						throw new \Exception(
							message: 'Current session has expired. Please login',
							code: HttpStatus::$InternalServerError
						);
					}
					if (Env::$enableConcurrentLogin) {
						$userConcurrencyKey = CacheServerKey::customerUserConcurrency(
							cID: $this->cID,
							uID: $this->uID
						);
						$sessionID = session_id();
						if ($this->http->req->clientCacheObj->cacheExist(cacheKey: $userConcurrencyKey)) {
							$userConcurrencyKeyData = $this->http->req->clientCacheObj->cacheGet(
								cacheKey: $userConcurrencyKey
							);
							if ($userConcurrencyKeyData !== $sessionID) {
								throw new \Exception(
									message: 'Account already in use. '
										. 'Please try after ' . Env::$concurrentAccessInterval . ' second(s)',
									code: HttpStatus::$Conflict
								);
							}
						} else {
							$this->cacheSet(
								cacheKey: $userConcurrencyKey,
								value: $sessionID,
								expire: Env::$concurrentAccessInterval
							);
						}
					} else {
						if ($this->http->req->s['uDetail']['httpRequestHash'] !== $this->http->httpReqDetailArr['httpRequestHash']) {
							throw new \Exception(
								message: 'Session not supported from this Browser/Device',
								code: HttpStatus::$PreconditionFailed
							);
						}
					}
					$this->isOpenToWebRequest = false;
				} elseif ($this->http->httpReqDetailArr['get'][ROUTE_URL_PARAM] === '/login') {
					$this->isOpenToWebRequest = false;
				} else {
					$this->isOpenToWebRequest = true;
				}
				break;
		}

		if ($this->isOpenToWebRequest === null) {
			throw new \Exception(
				message: "Open to web & Auth based request are disabled",
				code: HttpStatus::$InternalServerError
			);
		}

		if (
			$this->isOpenToWebRequest === true
			&& !Env::$enableOpenRequest
		) {
			throw new \Exception(
				message: "Open to web request are disabled",
				code: HttpStatus::$InternalServerError
			);
		}

		if (
			$this->isOpenToWebRequest === false
			&& !Env::$enableAuthRequest
		) {
			throw new \Exception(
				message: "Auth based request are disabled",
				code: HttpStatus::$InternalServerError
			);
		}

		if (!$this->isOpenToWebRequest) {
			$this->auth = new Auth(http: $this->http);
		}

		$this->rParser = new RouteParser(http: $this->http);
	}

	/**
	 * Initialize
	 *
	 * @return bool
	 */
	public function init(): bool
	{
		$this->loadCustomerDetail();

		if (!$this->isOpenToWebRequest) {
			$this->auth->loadUserDetail();
			$this->auth->loadGroupDetail();
		}

		$this->rParser->parseRoute();

		return true;
	}

	/**
	 * Load Customer detail
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function loadCustomerDetail(): void
	{
		if (isset($this->s['cDetail'])) {
			return;
		}

		DbCommonFunction::connectGlobalCache();

		if ($this->isOpenToWebRequest) {
			$cKey = CacheServerKey::openToWebDomain(domainName: $this->http->httpReqDetailArr['server']['domainName']);
		} else {
			$cKey = CacheServerKey::closedToWebDomain(domainName: $this->http->httpReqDetailArr['server']['domainName']);
		}
		if (!DbCommonFunction::$gCacheServer->cacheExist(cacheKey: $cKey)) {
			throw new \Exception(
				message: "Invalid Host '{$this->http->httpReqDetailArr['server']['domainName']}'",
				code: HttpStatus::$InternalServerError
			);
		}

		$this->s['cDetail'] = json_decode(
			json: DbCommonFunction::$gCacheServer->cacheGet(
				cacheKey: $cKey
			),
			associative: true
		);
		$this->cID = $this->s['cDetail']['id'];
	}

	/**
	 * Load payload
	 *
	 * @return void
	 */
	public function loadPayload(): void
	{
		if (isset($this->s['payloadType'])) {
			return;
		}

		$this->s['queryParamArr'] = &$this->http->httpReqDetailArr['get'];
		if ($this->http->httpReqDetailArr['server']['httpMethod'] === Constant::$GET) {
			$this->urlDecode(value: $this->http->httpReqDetailArr['get']);
			$this->s['payloadType'] = 'Object';
		} else {
			$this->setPayloadStream();
			rewind(stream: $this->payloadStream);

			$this->dataDecode = new DataDecode(
				dataFileHandle: $this->payloadStream
			);
			$this->dataDecode->init();

			$this->dataDecode->indexData();
			$this->s['payloadType'] = $this->dataDecode->dataType();
		}
	}

	/**
	 * Set payload stream
	 *
	 * @return void
	 */
	private function setPayloadStream(): void
	{
		switch (true) {
			case (
				$this->rParser->routeEndingWithReservedKeywordFlag
				&& ($this->rParser->routeEndingReservedKeyword === Env::$importRequestRouteKeyword)
				&& isset($this->http->httpReqDetailArr['files']['file']['tmp_name'])
			):
				$content = $this->formatCsvPayload(
					csvFile: $this->http->httpReqDetailArr['files']['file']['tmp_name']
				);
				break;
			case Env::$iRepresentation === 'XML':
				$content = $this->convertXmlToJson(xmlString: $this->http->httpReqDetailArr['post']);
				break;
			default:
				$content = $this->http->httpReqDetailArr['post'];
		}
		$this->payloadStream = fopen(
			filename: "php://memory",
			mode: "rw+b"
		);
		fwrite(
			stream: $this->payloadStream,
			data: $content
		);
	}

	/**
	 * Convert XML to JSON
	 *
	 * @param string $xmlString
	 *
	 * @return string
	 */
	private function convertXmlToJson($xmlString): string
	{
		$xml = simplexml_load_string(
			data: $xmlString
		);
		$arrayFromXml = json_decode(
			json: json_encode(value: $xml),
			associative: true
		);
		unset($xml);

		$result = [];
		$this->formatXmlArray(arrayFromXml: $arrayFromXml, result: $result);

		return json_encode($result);
	}

	/**
	 * Format Array generated by XML
	 *
	 * @param array $arrayFromXml Array generated by XML
	 * @param array $result       Formatted array
	 *
	 * @return void
	 */
	private function formatXmlArray(&$arrayFromXml, &$result): void
	{
		if (
			isset($arrayFromXml['Rows'])
			&& is_array(value: $arrayFromXml['Rows'])
		) {
			$arrayFromXml = &$arrayFromXml['Rows'];
		}

		if (
			isset($arrayFromXml['Row'])
			&& is_array(value: $arrayFromXml['Row'])
		) {
			$arrayFromXml = &$arrayFromXml['Row'];
		}

		if (
			isset($arrayFromXml[0])
			&& is_array(value: $arrayFromXml[0])
			&& count(value: $arrayFromXml) === 1
		) {
			$arrayFromXml = &$arrayFromXml[0];
			if (empty($arrayFromXml)) {
				return;
			}
		}

		if (!is_array(value: $arrayFromXml)) {
			return;
		}

		$xmlAttributeColumn = 'attribute';
		foreach ($arrayFromXml as $column => &$columnValue) {
			if ($column === $xmlAttributeColumn) {
				foreach ($columnValue as $attributeKey => $attributeValue) {
					$result[$attributeKey] = $attributeValue;
				}
				continue;
			}
			if (is_array(value: $columnValue)) {
				$result[$column] = [];
				$this->formatXmlArray(arrayFromXml: $columnValue, result: $result[$column]);
				continue;
			}
			$result[$column] = $columnValue;
		}
	}

	/**
	 * urldecode string or array
	 *
	 * @param array|string $value Array vales to be decoded. Basically $httpReqDetailArr['get']
	 *
	 * @return void
	 */
	public function urlDecode(&$value): void
	{
		if (is_array(value: $value)) {
			foreach ($value as &$v) {
				if (is_array(value: $v)) {
					$this->urlDecode(value: $v);
				} else {
					$v = urldecode(string: $v);
				}
			}
		} else {
			$value = urldecode(string: $value);
		}
	}

	/**
	 * Format CSV Payload
	 *
	 * @param string $csvFile
	 *
	 * @return string
	 */
	public function formatCsvPayload($csvFile): string
	{
		$dataEncode = new DataEncode(http: $this->http);
		$dataEncode->init(header: false);
		$dataEncode->startObject();

		$csvHeaderDetail = false;
		$counter = null;
		$currentModeArr = [];

		$fp = fopen($csvFile, "r");
		while (($csvString = fgets($fp)) !== false) {
			if (empty($csvString)) {
				continue;
			}
			$csvRecordArr = str_getcsv($csvString, ",", "\"", "\\");
			if (empty($csvRecordArr)) {
				continue;
			}
			if ($csvHeaderDetail === false) {
				$csvHeaderDetail = [];
				foreach ($csvRecordArr as $columnPosition => $value) {
					$v = explode(':', $value);
					$_csvHeaderDetail = &$csvHeaderDetail;
					for (
						$i = 0, $iCount = count($v);
						$i < $iCount;
						$i++
					) {
						if (($i+1) === $iCount) {
							$_csvHeaderDetail['__column__'][$v[$i]] = $columnPosition;
						} else {
							if (!isset($_csvHeaderDetail[$v[$i]])) {
								$_csvHeaderDetail[$v[$i]] = [];
							}
							$_csvHeaderDetail = &$_csvHeaderDetail[$v[$i]];
						}
					}
				}
				$counter = 0;
				continue;
			}

			[$currentModeArr, $csvFieldRecordArr] = $this->formatCsvArray($csvHeaderDetail, $csvRecordArr);

			if ($counter === 0) {
				$headerModeArr = $currentModeArr;
				$dataEncode->startArray(objectKey: $currentModeArr[0]);
				$dataEncode->startObject();
				foreach ($csvFieldRecordArr as $objectKey => $objectValue) {
					$dataEncode->addKeyData(objectKey: $objectKey, data: $objectValue);
				}
				$counter = 1;
				continue;
			}

			if ($headerModeArr === $currentModeArr) {
				$dataEncode->endObject();
				$dataEncode->startObject();
			} else {
				$_headerModeArr = [];
				$headerModeCount = count($headerModeArr);
				$currentModeCount = count($currentModeArr);

				for (
					$i = 0;
					$i < $currentModeCount;
					$i++
				) {
					if (
						!isset($headerModeArr[$i])
						|| ($headerModeArr[$i] !== $currentModeArr[$i])
					) {
						break;
					}
					$_headerModeArr[$i] = $currentModeArr[$i];
				}
				if ($currentModeCount < $headerModeCount) {
					for ($_i = $currentModeCount; $_i < $headerModeCount; $_i++) {
						$dataEncode->endObject();
						$dataEncode->endArray();
					}
					$dataEncode->endObject();
					$dataEncode->startObject();
				}
				if ($i < $currentModeCount) {
					for ($_i = $i; $_i < $headerModeCount; $_i++) {
						$dataEncode->endObject();
						$dataEncode->endArray();
					}
					for ($_i = $i; $_i < $currentModeCount; $_i++) {
						$_headerModeArr[$_i] = $currentModeArr[$_i];
						$dataEncode->startArray(objectKey: $currentModeArr[$_i]);
						$dataEncode->startObject();
					}
				}
				$headerModeArr = $_headerModeArr;
			}
			foreach ($csvFieldRecordArr as $objectKey => $objectValue) {
				$dataEncode->addKeyData(objectKey: $objectKey, data: $objectValue);
			}
		}
		$dataEncode->endObject();
		$json = $dataEncode->getData();
		$dataEncode = null;
		$json = substr($json, 7, (strlen($json)-8));

		return $json;
	}

	/**
	 * Format CSV Payload
	 *
	 * @param string $csvContent CSV string
	 *
	 * @return array
	 */
	public function formatCsvArray($csvHeaderDetail, $csvRecordArr): array
	{
		$csvFieldRecordArr = [];
		$currentModeArr = explode(':', $csvRecordArr[0]);

		foreach ($currentModeArr as $v) {
			if (!isset($csvHeaderDetail[$v])) {
				return [];
			}
			$csvHeaderDetail = &$csvHeaderDetail[$v];
		}

		if (!isset($csvHeaderDetail['__column__'])) {
			throw new \Exception(message: json_encode(value: [$currentModeArr,$csvHeaderDetail]), code: 400);
		}

		foreach ($csvHeaderDetail['__column__'] as $field => $column) {
			if (!isset($csvRecordArr[$column])) {
				return [];
			}
			$csvFieldRecordArr[$field] = $csvRecordArr[$column];
		}
		return [$currentModeArr, $csvFieldRecordArr];
	}
}
