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

use Microservices\App\CacheServerKey;
use Microservices\App\CommonFunction;
use Microservices\App\Constant;
use Microservices\App\DataRepresentation\DataDecode;
use Microservices\App\DataRepresentation\DataEncode;
use Microservices\App\DbCommonFunction;
use Microservices\App\Env;
use Microservices\App\Http;
use Microservices\App\HttpStatus;
use Microservices\App\Middleware\Auth;
use Microservices\App\RateLimiter;
use Microservices\App\RouteParser;
use Microservices\App\Server\CacheServer\CacheServerInterface;
use Microservices\App\Server\DatabaseServer\DatabaseServerInterface;

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
	public $HTML_DIR = null;
	public $PHP_DIR = null;
	public $XSLT_DIR = null;
	public $ROUTES_DIR = null;
	public $QUERIES_DIR = null;

	/**
	 * Rate Limiter
	 *
	 * @var null|RateLimiter
	 */
	public $rateLimiter = null;

	/**
	 * Auth middleware object
	 *
	 * @var null|Auth
	 */
	public $auth = null;

	/**
	 * Data Decode object
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
	 * @var null|CacheServerInterface
	 */
	public $clientCacheObj = null;

	/**
	 * Client Database Object
	 *
	 * @var null|DatabaseServerInterface
	 */
	public $clientDbObj = null;

	/**
	 * Session detail of a request
	 *
	 * @var null|array
	 */
	public $s = null;

	/**
	 * Flag for Private request
	 *
	 * @var bool
	 */
	public $isPrivateRequest = false;

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
	public $groupId = null;

	/**
	 * User Id
	 *
	 * @var null|int
	 */
	public $userId = null;

	/**
	 * Constructor
	 *
	 * @param Http $http
	 */
	public function __construct(Http &$http)
	{
		$this->http = &$http;

		switch (Env::$authMode) {
			case 'Token':
				if (
					isset($this->http->httpReqData['header'])
					&& isset($this->http->httpReqData['header']['tokenHeader'])
					&& $this->http->httpReqData['header']['tokenHeader'] !== null
				) {
					$this->isPrivateRequest = true;
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
					$this->isPrivateRequest = true;
				}
				break;
		}

		if ($this->http->httpReqData['get'][ROUTE_URL_PARAM] === '/login') {
			$this->isPrivateRequest = true;
		}

		if ($this->isPrivateRequest) {
			$this->HTML_DIR = Constant::$HTML_PRIVATE_DIR;
			$this->PHP_DIR = Constant::$PHP_PRIVATE_DIR;
			$this->XSLT_DIR = Constant::$XSLT_PRIVATE_DIR;
			$this->ROUTES_DIR = Constant::$ROUTES_PRIVATE_DIR;
			$this->QUERIES_DIR = Constant::$QUERIES_PRIVATE_DIR;
		} else {
			$this->HTML_DIR = Constant::$HTML_PUBLIC_DIR;
			$this->PHP_DIR = Constant::$PHP_PUBLIC_DIR;
			$this->XSLT_DIR = Constant::$XSLT_PUBLIC_DIR;
			$this->ROUTES_DIR = Constant::$ROUTES_PUBLIC_DIR;
			$this->QUERIES_DIR = Constant::$QUERIES_PUBLIC_DIR;
		}
	}

	/**
	 * Initialize
	 *
	 * @return bool
	 */
	public function init(): bool
	{
		$this->loadCustomerData();

		if (
			!$this->isPrivateRequest
			&& !CommonFunction::isEnabled(http: $this->http, feature: 'enablePublicRequest')
		) {
			throw new \Exception(
				message: 'Public request are disabled',
				code: HttpStatus::$InternalServerError
			);
		}

		if (
			$this->isPrivateRequest
			&& !CommonFunction::isEnabled(http: $this->http, feature: 'enablePrivateRequest')
		) {
			throw new \Exception(
				message: 'Private request are disabled',
				code: HttpStatus::$InternalServerError
			);
		}

		if ($this->http->httpReqData['get'][ROUTE_URL_PARAM] !== '/login') {
			$this->rParser = new RouteParser(http: $this->http);

			if ($this->isPrivateRequest) {
				$this->auth = new Auth(http: $this->http);
				$this->auth->loadUserData();
				$this->auth->loadGroupData();
			}

			$this->rParser->parseRoute();
		}

		return true;
	}

	/**
	 * Load Customer Data
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function loadCustomerData(): void
	{
		if (isset($this->s['customerData'])) {
			return;
		}

		DbCommonFunction::connectGlobalCache();

		if ($this->isPrivateRequest) {
			$cacheKey = CacheServerKey::privateDomain(domainName: $this->http->httpReqData['server']['domainName']);
		} else {
			$cacheKey = CacheServerKey::publicDomain(domainName: $this->http->httpReqData['server']['domainName']);
		}

		if (!DbCommonFunction::$gCacheServer->cacheExist(cacheKey: $cacheKey)) {
			throw new \Exception(
				message: "Invalid Host '{$this->http->httpReqData['server']['domainName']}'",
				code: HttpStatus::$InternalServerError
			);
		}

		$this->s['customerData'] = json_decode(
			json: DbCommonFunction::$gCacheServer->cacheGet(
				cacheKey: $cacheKey
			),
			associative: true
		);
		$this->customerId = $this->s['customerData']['id'];

		if ($this->isPrivateRequest) {
			$this->clientCacheObj = DbCommonFunction::connectClientCache(
				customerData: $this->s['customerData']
			);
			if (CommonFunction::isEnabled(http: $this->http, feature: 'enableRateLimiting')) {
				$this->rateLimiter = new RateLimiter(cacheObj: $this->clientCacheObj);
			}
		}
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

		$this->s['queryParamArr'] = &$this->http->httpReqData['get'];
		if ($this->http->httpReqData['server']['httpMethod'] === Constant::$GET) {
			$this->urlDecode(value: $this->http->httpReqData['get']);
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
				$this->http->httpReqData['get'][ROUTE_URL_PARAM] !== '/login'
				&& $this->rParser->routeEndingWithReservedKeywordFlag
				&& ($this->rParser->routeEndingReservedKeyword === Env::$importRequestRouteKeyword)
				&& isset($this->http->httpReqData['files']['file']['tmp_name'])
			):
				$content = $this->formatCsvPayload(
					csvFile: $this->http->httpReqData['files']['file']['tmp_name']
				);
				break;
			case Env::$iRepresentation === 'XML':
				$content = $this->convertXmlToJson(xmlString: $this->http->httpReqData['post']);
				break;
			default:
				$content = $this->http->httpReqData['post'];
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

		return json_encode(value: $result);
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
	 * @param array|string $value Array vales to be decoded. Basically $httpReqData['get']
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

		$csvHeaderData = false;
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
			if ($csvHeaderData === false) {
				$csvHeaderData = [];
				foreach ($csvRecordArr as $columnPosition => $value) {
					$v = explode(':', $value);
					$_csvHeaderData = &$csvHeaderData;
					for (
						$i = 0, $iCount = count($v);
						$i < $iCount;
						$i++
					) {
						if (($i+1) === $iCount) {
							$_csvHeaderData['__column__'][$v[$i]] = $columnPosition;
						} else {
							if (!isset($_csvHeaderData[$v[$i]])) {
								$_csvHeaderData[$v[$i]] = [];
							}
							$_csvHeaderData = &$_csvHeaderData[$v[$i]];
						}
					}
				}
				$counter = 0;
				continue;
			}

			[$currentModeArr, $csvFieldRecordArr] = $this->formatCsvArray(
				csvHeaderData: $csvHeaderData,
				csvRecordArr: $csvRecordArr
			);

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
	 * @param array $csvHeaderData
	 * @param array $csvRecordArr
	 *
	 * @return array
	 */
	public function formatCsvArray($csvHeaderData, $csvRecordArr): array
	{
		$csvFieldRecordArr = [];
		$currentModeArr = explode(':', $csvRecordArr[0]);

		foreach ($currentModeArr as $v) {
			if (!isset($csvHeaderData[$v])) {
				return [];
			}
			$csvHeaderData = &$csvHeaderData[$v];
		}

		if (!isset($csvHeaderData['__column__'])) {
			throw new \Exception(message: json_encode(value: [$currentModeArr,$csvHeaderData]), code: 400);
		}

		foreach ($csvHeaderData['__column__'] as $field => $column) {
			if (!isset($csvRecordArr[$column])) {
				return [];
			}
			$csvFieldRecordArr[$field] = $csvRecordArr[$column];
		}
		return [$currentModeArr, $csvFieldRecordArr];
	}
}
