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

use Microservices\App\Constant;
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
	private $jsonEncoderObjectObjectArray = [];

	/**
	 * Current JsonEncoderObject object
	 * 
	 * @var null|JsonEncoderObject
	 */
	private $jsonEncoderObjectObject = null;

	/**
	 * Characters that are escaped while creating JSON
	 * 
	 * @var string[]
	 */
	private $escapeArray = [
		"\\", "\"", "\n", "\r", "\t", "\x08", "\x0c", ' '
	];

	/**
	 * Characters that are escaped with for $escapeArray while creating JSON
	 * 
	 * @var string[]
	 */
	private $replaceArray = [
		"\\\\", "\\\"", "\\n", "\\r", "\\t", "\\f", "\\b", ' '
	];

	/**
	 * Constructor
	 * 
	 * @param resource $tempStream Temp stream Temporary stream
	 * @param bool     $header     Append XML header flag
	 */
	public function __construct(
		&$tempStream,
		$header = true
	) {
		$this->tempStream = &$tempStream;
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
	}

	/**
	 * Write to temporary stream
	 * 
	 * @param string $data Representation Data
	 * 
	 * @return void
	 */
	private function write(
		$data
	): void {
		fwrite(
			stream: $this->tempStream,
			data: $data
		);
	}

	/**
	 * Encodes both simple and associative array to json
	 * 
	 * @param string|array $data Representation Data
	 * 
	 * @return void
	 */
	public function encode(
		$data
	): void {
		if ($this->jsonEncoderObjectObject) {
			$this->write(
				data: $this->jsonEncoderObjectObject->comma
			);
		}
		if (
			is_array(
				value: $data
			)
		) {
			$this->write(
				data: json_encode(
					value: $data
				)
			);
		} else {
			$this->write(
				data: $this->escape(
					data: $data
				)
			);
		}
		if ($this->jsonEncoderObjectObject) {
			$this->jsonEncoderObjectObject->comma = ', ';
		}
	}

	/**
	 * Escape the json string key or value
	 * 
	 * @param null|string $data Representation Data
	 * 
	 * @return string
	 */
	private function escape(
		$data
	): string {
		if ($data === Constant::$NULL) {
			return 'null';
		}
		$data = str_replace(
			search: $this->escapeArray,
			replace: $this->replaceArray,
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
	public function appendData(
		&$data
	): void {
		if ($this->jsonEncoderObjectObject) {
			$this->write(
				data: $this->jsonEncoderObjectObject->comma
			);
			$this->write(
				data: $data
			);
			$this->jsonEncoderObjectObject->comma = ', ';
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
	public function appendKeyData(
		$objectKey,
		&$data
	): void {
		if (
			$this->jsonEncoderObjectObject
			&& $this->jsonEncoderObjectObject->mode === 'Object'
		) {
			$this->write(
				data: $this->jsonEncoderObjectObject->comma
			);
			$this->write(
				data: $this->escape(
					data: $objectKey
				) . ':' . $data
			);
			$this->jsonEncoderObjectObject->comma = ', ';
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
	public function addArrayData(
		$data
	): void {
		if ($this->jsonEncoderObjectObject->mode !== 'Array') {
			throw new \Exception(
				message: 'Mode should be Array',
				code: HttpStatus::$InternalServerError
			);
		}
		$this->encode(
			data: $data
		);
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
	public function addKeyData(
		$objectKey,
		$data
	): void {
		if ($this->jsonEncoderObjectObject->mode !== 'Object') {
			throw new \Exception(
				message: 'Mode should be Object',
				code: HttpStatus::$InternalServerError
			);
		}
		$this->write(
			data: $this->jsonEncoderObjectObject->comma
		);
		$this->write(
			data: $this->escape(
				data: $objectKey
			) . ':'
		);
		$this->jsonEncoderObjectObject->comma = '';
		$this->encode(
			data: $data
		);
	}

	/**
	 * Start simple array
	 * 
	 * @param null|string $objectKey Used while creating simple array inside an object
	 * 
	 * @return void
	 */
	public function startArray(
		$objectKey = null
	): void {
		if ($this->jsonEncoderObjectObject) {
			$this->write(
				data: $this->jsonEncoderObjectObject->comma
			);
			array_push(
				$this->jsonEncoderObjectObjectArray,
				$this->jsonEncoderObjectObject
			);
		}
		$this->jsonEncoderObjectObject = new JsonEncoderObject(
			mode: 'Array'
		);
		if ($objectKey !== Constant::$NULL) {
			$this->write(
				data: $this->escape(
					data: $objectKey
				) . ':'
			);
		}
		$this->write(
			data: '['
		);
	}

	/**
	 * End simple array
	 * 
	 * @return void
	 */
	public function endArray(): void
	{
		$this->write(
			data: ']'
		);
		$this->jsonEncoderObjectObject = null;
		if (
			count(
				value: $this->jsonEncoderObjectObjectArray
			) > 0
		) {
			$this->jsonEncoderObjectObject = array_pop(
				array: $this->jsonEncoderObjectObjectArray
			);
			$this->jsonEncoderObjectObject->comma = ', ';
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
	public function startObject(
		$objectKey = null
	): void {
		if ($this->jsonEncoderObjectObject) {
			if (
				$this->jsonEncoderObjectObject->mode === 'Object'
				&& ($objectKey === Constant::$NULL)
			) {
				throw new \Exception(
					message: 'Object inside an Object should be supported with key',
					code: HttpStatus::$InternalServerError
				);
			}
			$this->write(
				data: $this->jsonEncoderObjectObject->comma
			);
			array_push(
				$this->jsonEncoderObjectObjectArray,
				$this->jsonEncoderObjectObject
			);
		}
		$this->jsonEncoderObjectObject = new JsonEncoderObject(
			mode: 'Object'
		);
		if ($objectKey !== Constant::$NULL) {
			$this->write(
				data: $this->escape(
					data: $objectKey
				) . ':'
			);
		}
		$this->write(
			data: '{'
		);
	}

	/**
	 * End associative array
	 * 
	 * @return void
	 */
	public function endObject(): void
	{
		$this->write(
			data: '}'
		);
		$this->jsonEncoderObjectObject = null;
		if (
			count(
				value: $this->jsonEncoderObjectObjectArray
			) > 0
		) {
			$this->jsonEncoderObjectObject = array_pop(
				array: $this->jsonEncoderObjectObjectArray
			);
			$this->jsonEncoderObjectObject->comma = ', ';
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
			$this->jsonEncoderObjectObject
			&& $this->jsonEncoderObjectObject->mode
		) {
			switch ($this->jsonEncoderObjectObject->mode) {
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
