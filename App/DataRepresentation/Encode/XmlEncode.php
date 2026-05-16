<?php

/**
 * Handling XML Encode
 * php version 8.3
 *
 * @category  DataEncode_XML
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App\DataRepresentation\Encode;

use Microservices\App\DataRepresentation\Encode\DataEncodeInterface;
use Microservices\App\DataRepresentation\Encode\XmlEncoder\XmlEncoderObject;
use Microservices\App\HttpStatus;

/**
 * Generates XML
 * php version 8.3
 *
 * @category  Xml_Encoder
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class XmlEncode implements DataEncodeInterface
{
	/**
	 * Temporary Stream
	 *
	 * @var null|resource|array
	 */
	private $tempStream = null;

	/**
	 * Array of XmlEncoderObject object's
	 *
	 * @var XmlEncoderObject[]
	 */
	private $objectArr = [];

	/**
	 * Current XmlEncoderObject object
	 *
	 * @var null|XmlEncoderObject
	 */
	private $currentObject = null;

	/**
	 * Constructor
	 *
	 * @param resource $tempStream Temp stream Temporary stream
	 * @param bool     $header     Append XML header flag
	 */
	public function __construct(&$tempStream, $header = true)
	{
		$this->tempStream = &$tempStream;
		if ($header) {
			$xml = '<?xml version="1.0" encoding="UTF-8"?>';
			$this->write(data: $xml);
		}
	}

	/**
	 * Initialize
	 *
	 * @param bool $header Append XML header flag
	 *
	 * @return void
	 */
	public function init($header = true): void
	{
	}

	/**
	 * Write to temporary stream
	 *
	 * @param string $data Representation Data
	 *
	 * @return void
	 */
	private function write($data): void
	{
		fwrite(stream: $this->tempStream, data: $data);
	}

	/**
	 * Encodes both simple and associative array to XML
	 *
	 * @param string|array $data Representation Data
	 *
	 * @return void
	 */
	public function encode($data): void
	{
		if (is_array(value: $data)) {
			$isObject = (isset($data[0])) ? false : true;
			if (!$isObject) {
				$this->write(data: "<{$this->currentObject->objectKey}>");
			}
			foreach ($data as $objectKey => $value) {
				if (!is_array(value: $value)) {
					$objectKey = $this->escapeTag(objectKey: $objectKey);
					$this->write(
						data: "<{$objectKey}>{$this->escape(data: $value)}</{$objectKey}>"
					);
				} else {
					$this->addKeyData(objectKey: $objectKey, data: $value);
				}
			}
			if (!$isObject) {
				$this->write(data: "</{$this->currentObject->objectKey}>");
			}
		} else {
			$this->write(data: $this->escape(data: $data));
		}
	}

	/**
	 * Escape the XML string value
	 *
	 * @param null|string $data Representation Data
	 *
	 * @return string
	 */
	private function escape($data): string
	{
		if ($data === null) {
			return 'null';
		}
		return htmlspecialchars(string: $data);
	}

	/**
	 * Append raw XML string
	 *
	 * @param string $data Reference of Representation Data
	 *
	 * @return void
	 */
	public function appendData(&$data): void
	{
		if ($this->currentObject) {
			$this->write(data: $data);
		}
	}

	/**
	 * Append raw XML string
	 *
	 * @param string $objectKey Tag of associative array
	 * @param string $data      Reference of Representation Data
	 *
	 * @return void
	 */
	public function appendKeyData($objectKey, &$data): void
	{
		if (
			$this->currentObject
			&& $this->currentObject->mode === 'Object'
		) {
			$objectKey = $this->escapeTag(objectKey: $objectKey);
			$this->write(data: "<{$objectKey}>{$this->escape(data: $data)}</{$objectKey}>");
		}
	}

	/**
	 * Add simple array/value as in the XML format
	 *
	 * @param string|array $data Representation Data
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function addArrayData($data): void
	{
		if ($this->currentObject->mode !== 'Array') {
			throw new \Exception(
				message: 'Mode should be Array',
				code: HttpStatus::$InternalServerError
			);
		}
		$this->encode(data: $data);
	}

	/**
	 * Add simple array/value as in the XML format
	 *
	 * @param string       $objectKey Tag of associative array
	 * @param string|array $data      Representation Data
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function addKeyData($objectKey, $data): void
	{
		$this->startObject(objectKey: $objectKey);
		$this->encode(data: $data);
		$this->endObject();
	}

	/**
	 * Start simple array
	 *
	 * @param null|string $objectKey Used while creating simple array inside an object
	 *
	 * @return void
	 */
	public function startArray($objectKey = null): void
	{
		if ($objectKey === null) {
			$objectKey = 'Rows';
		}
		if ($this->currentObject) {
			array_push($this->objectArr, $this->currentObject);
		}
		$this->currentObject = new XmlEncoderObject(mode: 'Array', objectKey: $objectKey);
		$this->write(data: "<{$this->currentObject->objectKey}>");
	}

	/**
	 * End simple array
	 *
	 * @return void
	 */
	public function endArray(): void
	{
		$this->write(data: "</{$this->currentObject->objectKey}>");
		$this->currentObject = null;
		if (count(value: $this->objectArr) > 0) {
			$this->currentObject = array_pop($this->objectArr);
		}
	}

	/**
	 * Start simple array
	 *
	 * @param null|string $objectKey Used while creating associative array inside an object
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function startObject($objectKey = null): void
	{
		if ($objectKey === null) {
			$objectKey = ($this->currentObject === null) ? 'Resultset' : 'Row';
		}
		if ($this->currentObject) {
			if (
				$this->currentObject->mode === 'Object'
				&& ($objectKey === null)
			) {
				throw new \Exception(
					message: 'Object inside an Object should be supported with Key',
					code: HttpStatus::$InternalServerError
				);
			}
			array_push($this->objectArr, $this->currentObject);
		}
		$this->currentObject = new XmlEncoderObject(mode: 'Object', objectKey: $objectKey);
		$this->write(data: "<{$this->currentObject->objectKey}>");
	}

	/**
	 * End associative array
	 *
	 * @return void
	 */
	public function endObject(): void
	{
		$this->write(data: "</{$this->currentObject->objectKey}>");
		$this->currentObject = null;
		if (count(value: $this->objectArr) > 0) {
			$this->currentObject = array_pop($this->objectArr);
		}
	}

	/**
	 * Checks XML was properly closed
	 *
	 * @return void
	 */
	public function end(): void
	{
		while (
			$this->currentObject
			&& $this->currentObject->mode
		) {
			switch ($this->currentObject->mode) {
				case 'Array':
					$this->endArray();
					break;
				case 'Object':
					$this->endObject();
					break;
			}
		}
	}

	/**
	 * Checks XML was properly closed
	 *
	 * @param null|string $objectKey Used while creating associative array inside an object
	 *
	 * @return array|string
	 */
	private function escapeTag($objectKey): array|string
	{
		return str_replace(search: ':', replace: '-', subject: $objectKey);
	}
}
