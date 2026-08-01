<?php

/**
 * Handling PHP Raw Array detail for Views
 * php version 8.3
 * 
 * @category  DataEncode_PHP
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
use Microservices\App\DataRepresentation\Encode\PhpEncoder\PhpEncoderObject;
use Microservices\App\HttpStatus;

/**
 * Generates PHP Array
 * php version 8.3
 * 
 * @category  PHP_Encoder
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class PhpEncode implements DataEncodeInterface
{
	/**
	 * Array
	 * 
	 * @var null|array
	 */
	public $finalArray = null;

	/**
	 * Array of PhpEncoderObject object's
	 * 
	 * @var PhpEncoderObject[]
	 */
	private $jsonEncoderObjectObjectArray = [];

	/**
	 * Current PhpEncoderObject object
	 * 
	 * @var null|PhpEncoderObject
	 */
	private $jsonEncoderObjectObject = null;

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
		if ($this->jsonEncoderObjectObject) {
			if ($this->jsonEncoderObjectObject->mode === 'Object') {
				if (
					is_array(
						value: $data
					)
				) {
					foreach ($data as $k => $v) {
						$this->jsonEncoderObjectObject->returnArray[$k] = $this->escape(
							data: $v
						);
					}
				}
			} else {
				if (
					is_array(
						value: $data
					)
				) {
					foreach ($data as $v) {
						$this->jsonEncoderObjectObject->returnArray[] = $this->escape(
							data: $v
						);
					}
				} else {
					$this->jsonEncoderObjectObject->returnArray[] = $this->escape(
						data: $data
					);
				}
			}
		}
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
		$this->write(
			data: $data
		);
	}

	/**
	 * Escape the json string key or value
	 * 
	 * @param mixed $data Representation Data
	 * 
	 * @return mixed
	 */
	private function escape(
		&$data
	): mixed {
		if ($data !== Constant::$NULL) {
			if (
				is_array(
					value: $data
				)
			) {
				foreach ($data as $k => $v) {
					$data[$k] = $this->escape($v);
				}
			} else {
				$data = nl2br(
					htmlspecialchars(
						$data
					)
				);
			}
		}

		return $data;
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
		$this->write(
			data: $data
		);
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
				data: [$objectKey => $data]
			);
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
		$this->encode(
			data: [$objectKey => $data]
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
			array_push(
				$this->jsonEncoderObjectObjectArray,
				$this->jsonEncoderObjectObject
			);
		}
		$this->jsonEncoderObjectObject = new PhpEncoderObject(
			mode: 'Array'
		);
		if ($objectKey !== Constant::$NULL) {
			$this->jsonEncoderObjectObject->objectKey = $objectKey;
		}
	}

	/**
	 * End simple array
	 * 
	 * @return void
	 */
	public function endArray(): void
	{
		$objectKey = $this->jsonEncoderObjectObject->objectKey;
		$returnArray = &$this->jsonEncoderObjectObject->returnArray;
		$this->jsonEncoderObjectObject = null;
		if (
			count(
				value: $this->jsonEncoderObjectObjectArray
			) > 0
		) {
			$this->jsonEncoderObjectObject = array_pop(
				array: $this->jsonEncoderObjectObjectArray
			);
			if ($objectKey !== '') {
				$this->jsonEncoderObjectObject->returnArray[$objectKey] = &$returnArray;
			} else {
				$this->jsonEncoderObjectObject->returnArray[] = &$returnArray;
			}
		} else {
			$this->finalArray = &$returnArray;
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
			array_push(
				$this->jsonEncoderObjectObjectArray,
				$this->jsonEncoderObjectObject
			);
		}
		$this->jsonEncoderObjectObject = new PhpEncoderObject(
			mode: 'Object'
		);
		if ($objectKey !== Constant::$NULL) {
			$this->jsonEncoderObjectObject->objectKey = $objectKey;
		}
	}

	/**
	 * End associative array
	 * 
	 * @return void
	 */
	public function endObject(): void
	{
		$objectKey = $this->jsonEncoderObjectObject->objectKey;
		$returnArray = &$this->jsonEncoderObjectObject->returnArray;
		$this->jsonEncoderObjectObject = null;
		if (
			count(
				value: $this->jsonEncoderObjectObjectArray
			) > 0
		) {
			$this->jsonEncoderObjectObject = array_pop(
				array: $this->jsonEncoderObjectObjectArray
			);
			if ($objectKey !== '') {
				$this->jsonEncoderObjectObject->returnArray[$objectKey] = &$returnArray;
			} else {
				$this->jsonEncoderObjectObject->returnArray[] = &$returnArray;
			}
		} else {
			$this->finalArray = &$returnArray;
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
