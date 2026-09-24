<?php

/**
 * Creates Data Representation Output
 * php version 8.3
 *
 * @category  DataEncode
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App\DataRepresentation;

use Microservices\App\Constant;
use Microservices\App\DataRepresentation\Encode\PhpEncode;
use Microservices\App\DataRepresentation\Encode\JsonEncode;
use Microservices\App\DataRepresentation\Encode\XmlEncode;
use Microservices\App\Http;

/**
 * Creates Data Representation Output
 * php version 8.3
 *
 * @category  DataEncoder
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class DataEncode
{
	/**
	 * Temporary Stream
	 *
	 * @var null|resource|array
	 */
	private $tempStream = null;

	/**
	 * HTTP object
	 *
	 * @var null|Http
	 */
	private $httpObject = null;

	/**
	 * Output Representation
	 *
	 * @var null|string
	 */
	private $OUTPUT_REPRESENTATION = null;

	/**
	 * Output Representation File
	 *
	 * @var null|string
	 */
	public $outputRepresentationFileLocation = null;

	/**
	 * Temporary Stream
	 *
	 * @var null|Object
	 */
	private $dataEncoderObject = null;

	/**
	 * Constructor
	 *
	 * @param Http       $httpObject
	 * @param null|array $OUTPUT_REPRESENTATION
	 */
	public function __construct(
		Http &$httpObject,
		$OUTPUT_REPRESENTATION = null
	) {
		$this->httpObject = &$httpObject;

		if ($OUTPUT_REPRESENTATION === Constant::$NULL) {
			$OUTPUT_REPRESENTATION = [
				'OUTPUT_REPRESENTATION' => 'JSON',
				'OUTPUT_REPRESENTATION_FILE' => Constant::$FALSE
			];
		}
		$this->OUTPUT_REPRESENTATION = $OUTPUT_REPRESENTATION['OUTPUT_REPRESENTATION'];
		$this->outputRepresentationFileLocation = $OUTPUT_REPRESENTATION['OUTPUT_REPRESENTATION_FILE'];
	}

	/**
	 * Initialize
	 *
	 * @param bool $header Append XML header flag
	 *
	 * @return void
	 */
	public function init(
		$header = true
	): void {
		if ($this->httpObject->httpReqData['server']['httpRequestMethod'] === Constant::$GET) {
			if ($this->OUTPUT_REPRESENTATION === 'PHP') {
				$this->tempStream = [];
			} else {
				$this->tempStream = fopen(
					filename: "php://temp",
					mode: "rw+b"
				);
			}
		} else {
			if ($this->OUTPUT_REPRESENTATION === 'PHP') {
				$this->tempStream = [];
			} else {
				$this->tempStream = fopen(
					filename: "php://memory",
					mode: "rw+b"
				);
			}
		}

		switch ($this->OUTPUT_REPRESENTATION) {
			case 'JSON':
				$this->dataEncoderObject = new JsonEncode(
					tempStream: $this->tempStream,
					header: $header
				);
				break;
			case 'PHP':
				$this->dataEncoderObject = new PhpEncode(
					tempStream: $this->tempStream,
					header: $header
				);
				break;
			case 'XML':
			case 'XSLT':
			case 'HTML':
				$this->dataEncoderObject = new XmlEncode(
					tempStream: $this->tempStream,
					header: $header
				);
				break;
			default:
				break;
		}
	}

	/**
	 * Start array
	 *
	 * @param null|string $objectKey Used while creating simple array inside an object
	 *
	 * @return void
	 */
	public function startArray(
		$objectKey = null
	): void {
		$this->dataEncoderObject->startArray(
			objectKey: $objectKey
		);
	}

	/**
	 * Add array/value as in the data format
	 *
	 * @param string|array $data Representation Data
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function addArrayData(
		$data
	): void {
		$this->dataEncoderObject->addArrayData(
			data: $data
		);
	}

	/**
	 * End array
	 *
	 * @return void
	 */
	public function endArray(): void
	{
		$this->dataEncoderObject->endArray();
	}

	/**
	 * Start object
	 *
	 * @param null|string $objectKey Used while creating associative array inside an object
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function startObject(
		$objectKey = null
	): void {
		$this->dataEncoderObject->startObject(
			objectKey: $objectKey
		);
	}

	/**
	 * Add array/value as in the data format
	 *
	 * @param string       $objectKey Key of associative array
	 * @param string|array $data      Representation Data
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function addKeyData(
		$objectKey,
		$data
	): void {
		$this->dataEncoderObject->addKeyData(
			objectKey: $objectKey,
			data: $data
		);
	}

	/**
	 * End object
	 *
	 * @return void
	 */
	public function endObject(): void
	{
		$this->dataEncoderObject->endObject();
	}

	/**
	 * Encode data
	 *
	 * @param string|array $data Representation Data
	 *
	 * @return void
	 */
	public function encode(
		$data
	): void {
		$this->dataEncoderObject->encode(
			data: $data
		);
	}

	/**
	 * Append raw data string
	 *
	 * @param string $data Representation Data
	 *
	 * @return void
	 */
	public function appendData(
		&$data
	): void {
		$this->dataEncoderObject->appendData(
			data: $data
		);
	}

	/**
	 * Append object data
	 *
	 * @param string $objectKey Key of associative array
	 * @param string $data      Representation Data
	 *
	 * @return void
	 */
	public function appendKeyData(
		$objectKey,
		&$data
	): void {
		$this->dataEncoderObject->appendKeyData(
			objectKey: $objectKey,
			data: $data
		);
	}

	/**
	 * End encoding
	 *
	 * @return void
	 */
	public function end(): void
	{
		$this->dataEncoderObject->end();
	}

	/**
	 * Stream encoded data
	 *
	 * @return void
	 */
	public function streamData(): void
	{
		$this->end();

		switch (Constant::$TRUE) {
			case (
					$this->OUTPUT_REPRESENTATION === 'XSLT'
					&& $this->outputRepresentationFileLocation !== Constant::$NULL
					&& file_exists(
						filename: $this->outputRepresentationFileLocation
					)
				):
				echo $this->processPublicXml(
					xmlFile: $this->outputRepresentationFileLocation
				);
				fclose(
					stream: $this->tempStream
				);
				break;
			case (
					$this->OUTPUT_REPRESENTATION === 'HTML'
					&& $this->outputRepresentationFileLocation !== Constant::$NULL
					&& file_exists(
						filename: $this->outputRepresentationFileLocation
					)
				):
				echo $this->processPublicXml(
					xmlFile: $this->outputRepresentationFileLocation
				);
				fclose(
					stream: $this->tempStream
				);
				break;
			case (
					$this->OUTPUT_REPRESENTATION === 'PHP'
					&& $this->outputRepresentationFileLocation !== Constant::$NULL
					&& file_exists(
						filename: $this->outputRepresentationFileLocation
					)
				):
				$finalArray = &$this->tempStream->finalArray;
				include_once $this->outputRepresentationFileLocation;
				$this->tempStream = Constant::$NULL;
				break;
			default:
				rewind(
					stream: $this->tempStream
				);
				$outputStream = fopen(
					filename: 'php://output',
					mode: 'wb'
				);
				stream_copy_to_stream(
					from: $this->tempStream,
					to: $outputStream
				);
				fclose(
					stream: $outputStream
				);
				fclose(
					stream: $this->tempStream
				);
				break;
		}
	}

	/**
	 * Get encoded data
	 *
	 * @return bool|string
	 */
	public function getData(): bool|string
	{
		$this->end();

		switch (Constant::$TRUE) {
			case (
					$this->OUTPUT_REPRESENTATION === 'XSLT'
					&& $this->outputRepresentationFileLocation !== Constant::$NULL
					&& file_exists(
						filename: $this->outputRepresentationFileLocation
					)
				):
				$streamContent = $this->processPublicXml(
					xmlFile: $this->outputRepresentationFileLocation
				);
				fclose(
					stream: $this->tempStream
				);
				break;
			case (
					$this->OUTPUT_REPRESENTATION === 'HTML'
					&& $this->outputRepresentationFileLocation !== Constant::$NULL
					&& file_exists(
						filename: $this->outputRepresentationFileLocation
					)
				):
				$streamContent = $this->processPublicXml(
					xmlFile: $this->outputRepresentationFileLocation
				);
				fclose(
					stream: $this->tempStream
				);
				break;
			case (
					$this->OUTPUT_REPRESENTATION === 'PHP'
					&& $this->outputRepresentationFileLocation !== Constant::$NULL
					&& file_exists(
						filename: $this->outputRepresentationFileLocation
					)
				):
				$finalArray = &$this->dataEncoderObject->finalArray;
				@ob_clean();
				include_once $this->outputRepresentationFileLocation;
				$streamContent = ob_get_clean();
				$this->tempStream = Constant::$NULL;
				break;
			default:
				rewind(
					stream: $this->tempStream
				);
				$streamContent = stream_get_contents(
					stream: $this->tempStream
				);
				fclose(
					stream: $this->tempStream
				);
				break;
		}

		return $streamContent;
	}

	/**
	 * Generate XML(XSLT)/HTML data
	 *
	 * @param string $xmlFile XML file location
	 *
	 * @return string
	 */
	private function processPublicXml(
		$xmlFile
	): string {
		rewind(
			stream: $this->tempStream
		);
		$xml = new \DOMDocument();
		$xml->loadXML(
			source: stream_get_contents(
				stream: $this->tempStream
			)
		);

		$xslt = new \XSLTProcessor();
		$XSL = new \DOMDocument();
		$XSL->load(
			filename: $this->xmlFile
		);
		$xslt->importStylesheet(
			stylesheet: $XSL
		);
		return $xslt->transformToXML(
			document: $xml
		);
	}
}
