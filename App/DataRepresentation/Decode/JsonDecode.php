<?php

/**
 * Handling JSON formats
 * php version 8.3
 * 
 * @category  DataDecode
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App\DataRepresentation\Decode;

use Generator;
use Microservices\App\CommonFunction;
use Microservices\App\Constant;
use Microservices\App\DataRepresentation\Decode\DataDecodeInterface;
use Microservices\App\DataRepresentation\Decode\JsonDecode\JsonDecodeEngine;
use Microservices\App\HttpStatus;

/**
 * Creates Arrays from JSON string
 * php version 8.3
 * 
 * @category  DataDecode_JSON
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class JsonDecode implements DataDecodeInterface
{
	/**
	 * JSON File Handle
	 * 
	 * @var null|resource
	 */
	private $jsonFileHandle = null;

	/**
	 * JSON file indexes
	 * Contains start and end positions for requested indexes
	 * 
	 * @var null|array
	 */
	public $jsonFileIndex = null;

	/**
	 * Allowed Payload length
	 * 
	 * @var int
	 */
	private $allowedPayloadLength = 100 * 1024 * 1024; // 100 MB

	/**
	 * JSON Decode Engine object
	 * 
	 * @var null|JsonDecodeEngine
	 */
	private $jsonDecodeEngineObject = null;

	/**
	 * Constructor
	 * 
	 * @param resource $jsonFileHandle File handle
	 */
	public function __construct(
		&$jsonFileHandle
	) {
		if (!$jsonFileHandle) {
			throw new \Exception(
				message: 'Invalid file',
				code: HttpStatus::$BadRequest
			);
		}
		$this->jsonFileHandle = &$jsonFileHandle;

		// File Stats - Check for size
		$fileStats = fstat(
			stream: $this->jsonFileHandle
		);
		if (
			isset($fileStats['size'])
			&& $fileStats['size'] > $this->allowedPayloadLength
		) {
			throw new \Exception(
				message: 'File size greater than allowed size',
				code: HttpStatus::$BadRequest
			);
		}
	}

	/**
	 * Initialize
	 * 
	 * @return bool
	 */
	public function init(): bool
	{
		// Init JSON Decode Engine
		$this->jsonDecodeEngineObject = new JsonDecodeEngine(
			jsonFileHandle: $this->jsonFileHandle
		);

		return Constant::$TRUE;
	}

	/**
	 * Validates JSON
	 * 
	 * @return void
	 */
	public function validate(): void
	{
		foreach ($this->jsonDecodeEngineObject->process() as $keyArray => $valueArray) {
			;
		}
	}

	/**
	 * Index file JSON
	 * 
	 * @return void
	 */
	public function indexData(): void
	{
		$this->jsonFileIndex = Constant::$NULL;
		foreach (
			$this->jsonDecodeEngineObject->process(
				index: Constant::$TRUE
			) as $keyArray => $val
		) {
			if (
				isset($val['startIndex'])
				&& isset($val['endIndex'])
			) {
				$jsonFileIndex = &$this->jsonFileIndex;
				$indexCount = count(
					value: $keyArray
				);
				for ($index = 0; $index < $indexCount; $index++) {
					if (
						is_numeric(
							value: $keyArray[$index]
						)
						&& !isset($jsonFileIndex[$keyArray[$index]])
					) {
						$jsonFileIndex[$keyArray[$index]] = [];
						if (!isset($jsonFileIndex['_c_'])) {
							$jsonFileIndex['_c_'] = 0;
						}
						if (
							is_numeric(
								value: $keyArray[$index]
							)
						) {
							$jsonFileIndex['_c_']++;
						}
					}
					$jsonFileIndex = &$jsonFileIndex[$keyArray[$index]];
				}
				$jsonFileIndex['startIndex'] = $val['startIndex'];
				$jsonFileIndex['endIndex'] = $val['endIndex'];
			}
		}
	}

	/**
	 * Result exist as per $keyString
	 * 
	 * @param null|string $keyString Key's exist (values separated by colon)
	 * 
	 * @return bool
	 */
	public function isset(
		$keyString = null
	): bool {
		$return = Constant::$TRUE;
		if (
			($keyString !== Constant::$NULL)
			&& strlen(
				string: $keyString
			) !== 0
		) {
			$jsonFileIndex = &$this->jsonFileIndex;
			foreach (
				explode(
					separator: ':',
					string: $keyString
				) as $objectKey
			) {
				if (isset($jsonFileIndex[$objectKey])) {
					$jsonFileIndex = &$jsonFileIndex[$objectKey];
				} else {
					$return = Constant::$FALSE;
					break;
				}
			}
		}
		return $return;
	}

	/**
	 * Datatype of result as per $keyString
	 * 
	 * @param null|string $keyString Key's exist (values separated by colon)
	 * 
	 * @return string Object/Array
	 */
	public function dataType(
		$keyString = null
	): string {
		$jsonFileIndex = &$this->jsonFileIndex;
		if (
			($keyString !== Constant::$NULL)
			&& strlen(
				string: $keyString
			) > 0
		) {
			foreach (
				explode(
					separator: ':',
					string: $keyString
				) as $objectKey
			) {
				if (isset($jsonFileIndex[$objectKey])) {
					$jsonFileIndex = &$jsonFileIndex[$objectKey];
				} else {
					throw new \Exception(
						message: "Key '{$objectKey}' not found",
						code: HttpStatus::$BadRequest
					);
				}
			}
		}

		$return = 'Object';
		if (isset($jsonFileIndex['_c_'])) {
			$return = 'Array';
		}
		return $return;
	}

	/**
	 * Count of result as per $keyString
	 * 
	 * @param null|string $keyString Key values separated by colon
	 * 
	 * @return int
	 */
	public function count(
		$keyString = null
	): int {
		$jsonFileIndex = &$this->jsonFileIndex;
		if (
			($keyString !== Constant::$NULL)
			&& strlen(
				string: $keyString
			) !== 0
		) {
			foreach (
				explode(
					separator: ':',
					string: $keyString
				) as $objectKey
			) {
				if (isset($jsonFileIndex[$objectKey])) {
					$jsonFileIndex = &$jsonFileIndex[$objectKey];
				} else {
					throw new \Exception(
						message: "Key '{$objectKey}' not found",
						code: HttpStatus::$BadRequest
					);
				}
			}
		}

		$count = 0;
		if (
			isset($jsonFileIndex['startIndex'])
			&& isset($jsonFileIndex['endIndex'])
		) {
			$count = 1;
		}
		if (isset($jsonFileIndex['_c_'])) {
			$count = (int)$jsonFileIndex['_c_'];
		}
		return $count;
	}

	/**
	 * Get result as per $keyString
	 * 
	 * @param string $keyString Key values separated by colon
	 * 
	 * @return mixed
	 */
	public function get(
		$keyString = ''
	): mixed {
		if (
			!$this->isset(
				keyString: $keyString
			)
		) {
			return Constant::$FALSE;
		}
		$valueArray = [];
		$this->load(
			keyString: $keyString
		);
		foreach ($this->jsonDecodeEngineObject->process() as $valueArray) {
			break;
		}
		return $valueArray;
	}

	/**
	 * Get Object as per $keyString (Ignores Sub Objects/Array)
	 * 
	 * @param string $keyString Key values separated by colon
	 * 
	 * @return mixed
	 */
	public function getObject(
		$keyString = ''
	): mixed {
		$valueArray = $this->get(
			keyString: $keyString
		);

		foreach($valueArray as $key => $value) {
			if (is_array($value)) {
				unset($valueArray[$key]);
			}
		}

		return $valueArray;
	}

	/**
	 * Get complete result as per $keyString
	 * 
	 * @param string $keyString Key values separated by colon
	 * 
	 * @return mixed
	 */
	public function getCompleteArray(
		$keyString = ''
	): mixed {
		if (
			!$this->isset(
				keyString: $keyString
			)
		) {
			return Constant::$FALSE;
		}
		$this->load(
			keyString: $keyString
		);
		return CommonFunction::jsonDecode(
			value: $this->jsonDecodeEngineObject->getJsonString()
		);
	}

	/**
	 * Load result as per $keyString
	 * Start processing the JSON string for a key's
	 * Perform search inside key's of JSON like $json['data'][0]['data1']
	 * 
	 * @param string $keyString Key values separated by colon
	 * 
	 * @return void
	 * @throws \Exception
	 */
	public function load(
		$keyString
	): void {
		if (
			in_array(
				needle: $keyString,
				haystack: [Constant::$NULL, ''],
				strict: Constant::$TRUE
			)
		) {
			$this->jsonDecodeEngineObject->startIndex = Constant::$NULL;
			$this->jsonDecodeEngineObject->endIndex = Constant::$NULL;
			return;
		}
		$jsonFileIndex = &$this->jsonFileIndex;
		if (
			($keyString !== Constant::$NULL)
			&& strlen(
				string: $keyString
			) !== 0
		) {
			foreach (
				explode(
					separator: ':',
					string: $keyString
				) as $objectKey
			) {
				if (isset($jsonFileIndex[$objectKey])) {
					$jsonFileIndex = &$jsonFileIndex[$objectKey];
				} else {
					throw new \Exception(
						message: "Key '{$objectKey}' not found",
						code: HttpStatus::$BadRequest
					);
				}
			}
		}
		if (
			isset($jsonFileIndex['startIndex'])
			&& isset($jsonFileIndex['endIndex'])
		) {
			$this->jsonDecodeEngineObject->startIndex = (int)$jsonFileIndex['startIndex'];
			$this->jsonDecodeEngineObject->endIndex = (int)$jsonFileIndex['endIndex'];
		} else {
			throw new \Exception(
				message: "Invalid key's '{$keyString}'",
				code: HttpStatus::$BadRequest
			);
		}
	}
}
