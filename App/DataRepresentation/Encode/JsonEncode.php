<?php

/**
 * Handling JSON Encode
 * php version 8.3
 *
 * @category  DataEncode_JSON
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App\DataRepresentation\Encode;

use Microservices\App\DataRepresentation\Encode\DataEncodeInterface;
use Microservices\App\DataRepresentation\Encode\JsonEncoder\JsonEncoderObject;
use Microservices\App\HttpStatus;

/**
 * Creates JSON string
 * php version 8.3
 *
 * @category  JSON_Encoder
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class JsonEncode implements DataEncodeInterface
{
	/**
	 * Temporary Stream
	 *
	 * @var null|resource|array
	 */
	private $tempStream = null;

	/**
	 * Array of JsonEncoderObject object's
	 *
	 * @var JsonEncoderObject[]
	 */
	private $objectArr = [];

	/**
	 * Current JsonEncoderObject object
	 *
	 * @var null|JsonEncoderObject
	 */
	private $currentObject = null;

	/**
	 * Characters that are escaped while creating JSON
	 *
	 * @var string[]
	 */
	private $escapeArr = [
		"\\", "\"", "\n", "\r", "\t", "\x08", "\x0c", ' '
	];

	/**
	 * Characters that are escaped with for $escapeArr while creating JSON
	 *
	 * @var string[]
	 */
	private $replaceArr = [
		"\\\\", "\\\"", "\\n", "\\r", "\\t", "\\f", "\\b", ' '
	];

	/**
	 * Constructor
	 *
	 * @param resource $tempStream Temp stream Temporary stream
	 * @param bool     $header     Append XML header flag
	 */
	public function __construct(&$tempStream, $header = true)
	{
		$this->tempStream = &$tempStream;
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
	 * Encodes both simple and associative array to json
	 *
	 * @param string|array $data Representation Data
	 *
	 * @return void
	 */
	public function encode($data): void
	{
		if ($this->currentObject) {
			$this->write(data: $this->currentObject->comma);
		}
		if (is_array(value: $data)) {
			$this->write(data: json_encode(value: $data));
		} else {
			$this->write(data: $this->escape(data: $data));
		}
		if ($this->currentObject) {
			$this->currentObject->comma = ', ';
		}
	}

	/**
	 * Escape the json string key or value
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
		$data = str_replace(
			search: $this->escapeArr,
			replace: $this->replaceArr,
			subject: $data
		);
		return "\"{$data}\"";
	}

	/**
	 * Append raw json string
	 *
	 * @param string $data Reference of Representation Data
	 *
	 * @return void
	 */
	public function appendData(&$data): void
	{
		if ($this->currentObject) {
			$this->write(data: $this->currentObject->comma);
			$this->write(data: $data);
			$this->currentObject->comma = ', ';
		}
	}

	/**
	 * Append raw json string
	 *
	 * @param string $objectKey Key of associative array
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
			$this->write(data: $this->currentObject->comma);
			$this->write(data: $this->escape(data: $objectKey) . ':' . $data);
			$this->currentObject->comma = ', ';
		}
	}

	/**
	 * Add simple array/value as in the json format
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
	 * Add simple array/value as in the json format
	 *
	 * @param string       $objectKey Key of associative array
	 * @param string|array $data      Representation Data
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function addKeyData($objectKey, $data): void
	{
		if ($this->currentObject->mode !== 'Object') {
			throw new \Exception(
				message: 'Mode should be Object',
				code: HttpStatus::$InternalServerError
			);
		}
		$this->write(data: $this->currentObject->comma);
		$this->write(data: $this->escape(data: $objectKey) . ':');
		$this->currentObject->comma = '';
		$this->encode(data: $data);
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
		if ($this->currentObject) {
			$this->write(data: $this->currentObject->comma);
			array_push($this->objectArr, $this->currentObject);
		}
		$this->currentObject = new JsonEncoderObject(mode: 'Array');
		if ($objectKey !== null) {
			$this->write(data: $this->escape(data: $objectKey) . ':');
		}
		$this->write(data: '[');
	}

	/**
	 * End simple array
	 *
	 * @return void
	 */
	public function endArray(): void
	{
		$this->write(data: ']');
		$this->currentObject = null;
		if (count(value: $this->objectArr) > 0) {
			$this->currentObject = array_pop(array: $this->objectArr);
			$this->currentObject->comma = ', ';
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
		if ($this->currentObject) {
			if (
				$this->currentObject->mode === 'Object'
				&& ($objectKey === null)
			) {
				throw new \Exception(
					message: 'Object inside an Object should be supported with key',
					code: HttpStatus::$InternalServerError
				);
			}
			$this->write(data: $this->currentObject->comma);
			array_push($this->objectArr, $this->currentObject);
		}
		$this->currentObject = new JsonEncoderObject(mode: 'Object');
		if ($objectKey !== null) {
			$this->write(data: $this->escape(data: $objectKey) . ':');
		}
		$this->write(data: '{');
	}

	/**
	 * End associative array
	 *
	 * @return void
	 */
	public function endObject(): void
	{
		$this->write(data: '}');
		$this->currentObject = null;
		if (count(value: $this->objectArr) > 0) {
			$this->currentObject = array_pop(array: $this->objectArr);
			$this->currentObject->comma = ', ';
		}
	}

	/**
	 * Checks json was properly closed
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
}
