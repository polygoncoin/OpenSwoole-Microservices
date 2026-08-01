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

namespace Microservices\App\DataRepresentation\Decode\JsonDecode;

use Generator;
use Microservices\App\Constant;
use Microservices\App\DataRepresentation\Decode\JsonDecode\JsonDecodeObject;
use Microservices\App\HttpStatus;

/**
 * Creates Arrays from JSON String
 * 
 * This class is built to decode large json string or file
 * (which leads to memory limit issues for larger data set)
 * This class gives access to create object's from JSON string
 * in parts for what ever smallest part of data
 * php version 8.3
 * 
 * @category  JSON_Decode_Engine
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class JsonDecodeEngine
{
	/**
	 * File Handle
	 * 
	 * @var null|resource
	 */
	private $jsonFileHandle = null;

	/**
	 * Array of JsonDecodeObject object's
	 * 
	 * @var JsonDecodeObject[]
	 */
	private $jsonDecodeObjectObjectArray = [];

	/**
	 * Current JsonDecodeObject object
	 * 
	 * @var JsonDecodeObject
	 */
	private $jsonDecodeObjectObject = null;

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
	 * JSON file start position
	 * 
	 * @var null|int
	 */
	public $startIndex = null;

	/**
	 * JSON file end position
	 * 
	 * @var null|int
	 */
	public $endIndex = null;

	/**
	 * JSON char counter
	 * Starts from $startIndex till $endIndex
	 * 
	 * @var null|int
	 */
	private $charCounter = null;

	/**
	 * Constructor
	 * 
	 * @param null|resource $jsonFileHandle JSON file handle
	 */
	public function __construct(
		&$jsonFileHandle
	) {
		$this->jsonFileHandle = &$jsonFileHandle;
	}

	/**
	 * Start processing the JSON string
	 * 
	 * @param bool $index Index output
	 * 
	 * @return Generator
	 */
	public function process(
		$index = false
	): Generator {
		// Flags Variable
		$quote = false;

		// Values inside Quotes
		$keyValue = '';
		$valueValue = '';

		// Values without Quotes
		$nullStr = null;

		// Variable mode - key/value;
		$varMode = 'keyValue';

		$strToEscape  = '';
		$prevIsEscape = false;

		$this->charCounter = $this->startIndex !== Constant::$NULL ? $this->startIndex : 0;
		fseek(
			stream: $this->jsonFileHandle,
			offset: $this->charCounter,
			whence: SEEK_SET
		);

		for (
			;
			(
				(
					$char = fgetc(
						stream: $this->jsonFileHandle
					)
				) !== Constant::$FALSE
				&& (
					($this->endIndex === Constant::$NULL)
					|| (
						($this->endIndex !== Constant::$NULL)
						&& $this->charCounter <= $this->endIndex
					)
				)
			);
			$this->charCounter++
		) {
			switch (true) {
				case $quote === Constant::$FALSE:
					switch (true) {
						// Start of Key or value inside quote
						case $char === '"':
							$quote = true;
							$nullStr = '';
							break;

						//Switch mode to value collection after colon
						case $char === ':':
							$varMode = 'valueValue';
							break;

						// Start or End of Array
						case in_array(
							needle: $char,
							haystack: ['[', ']', '{', '}'],
							strict: Constant::$TRUE
						):
							$arr = $this->handleOpenClose(
								char: $char,
								keyValue: $keyValue,
								nullStr: $nullStr,
								index: $index
							);
							if ($arr !== Constant::$FALSE) {
								yield $arr['key'] => $arr['value'];
							}
							$keyValue = $valueValue = '';
							$varMode = 'keyValue';
							break;

						// Check for null values
						case (
							$char === ','
							&& ($nullStr !== Constant::$NULL)
						):
							$nullStr = $this->checkNullStr(
								nullStr: $nullStr
							);
							switch ($this->jsonDecodeObjectObject->mode) {
								case 'Array':
									$this->jsonDecodeObjectObject->arrayValueArray[] = $nullStr;
									break;
								case 'Object':
									if (!empty($keyValue)) {
										$this->jsonDecodeObjectObject->objectValueArray[$keyValue] = $nullStr;
									}
									break;
							}
							$nullStr = null;
							$keyValue = $valueValue = '';
							$varMode = 'keyValue';
							break;

						//Switch mode to value collection after colon
						case in_array(
							needle: $char,
							haystack: $this->escapeArray,
							strict: Constant::$TRUE
						):
							break;

						// Append char to null string
						case !in_array(
							needle: $char,
							haystack: $this->escapeArray,
							strict: Constant::$TRUE
						):
							$nullStr .= $char;
							break;
					}
					break;

				case $quote === Constant::$TRUE:
					switch (true) {
						// Collect string to be escaped
						case $varMode === 'valueValue'
							&& ($char === '\\'
								|| ($prevIsEscape
									&& in_array(
										needle: $strToEscape . $char,
										haystack: $this->replaceArray,
										strict: Constant::$TRUE
									)
								)
							):
							$strToEscape .= $char;
							$prevIsEscape = true;
							break;

						// Escape value with char
						case $varMode === 'valueValue'
							&& $prevIsEscape === Constant::$TRUE
							&& in_array(
								needle: $strToEscape . $char,
								haystack: $this->replaceArray,
								strict: Constant::$TRUE
							):
							$$varMode .= str_replace(
								search: $this->replaceArray,
								replace: $this->escapeArray,
								subject: $strToEscape . $char
							);
							$strToEscape = '';
							$prevIsEscape = false;
							break;

						// Escape value without char
						case $varMode === 'valueValue'
							&& $prevIsEscape === Constant::$TRUE
							&& in_array(
								needle: $strToEscape,
								haystack: $this->replaceArray,
								strict: Constant::$TRUE
							):
							$$varMode .= str_replace(
								search: $this->replaceArray,
								replace: $this->escapeArray,
								subject: $strToEscape . $char
							);
							$strToEscape = '';
							$prevIsEscape = false;
							break;

						// Closing double quotes
						case $char === '"':
							$quote = false;
							switch (true) {
								// Closing qoute of Key
								case $varMode === 'keyValue':
									$varMode = 'valueValue';
									break;

								// Closing qoute of Value
								case $varMode === 'valueValue':
									if (!isset($this->jsonDecodeObjectObject)) {
										$this->startObject();
									}
									$this->jsonDecodeObjectObject->objectValueArray[$keyValue] = $valueValue;
									$keyValue = $valueValue = '';
									$varMode = 'keyValue';
									break;
							}
							break;

						// Collect values for key or value
						default:
							$$varMode .= $char;
					}
					break;
			}
		}
		$this->jsonDecodeObjectObjectArray = [];
		$this->jsonDecodeObjectObject = null;
	}

	/**
	 * Get JSON string
	 * 
	 * @return bool|string
	 */
	public function getJsonString(): bool|string
	{
		if (
			($this->startIndex === Constant::$NULL)
			&& ($this->endIndex === Constant::$NULL)
		) {
			rewind(
				stream: $this->jsonFileHandle
			);
			return stream_get_contents(
				stream: $this->jsonFileHandle
			);
		} else {
			$offset = $this->startIndex !== Constant::$NULL ? $this->startIndex : 0;
			$length = $this->endIndex - $offset + 1;
			return stream_get_contents(
				stream: $this->jsonFileHandle,
				length: $length,
				offset: $offset
			);
		}
	}

	/**
	 * Handles array / object open close char
	 * 
	 * @param string $char     Character among any one "[" "]" "{" "}"
	 * @param string $keyValue String value of key of an object
	 * @param string $nullStr  String present in JSON without double quotes
	 * @param bool   $index    Index output
	 * 
	 * @return array|bool
	 */
	private function handleOpenClose(
		$char,
		$keyValue,
		$nullStr,
		$index
	): array|bool {
		$arr = false;
		switch ($char) {
			case '[':
				if (!$index) {
					$arr = [
						'key' => $this->getKey(),
						'value' => $this->getObjectValues()
					];
				}
				$this->increment();
				$this->startArray(
					objectKey: $keyValue
				);
				break;
			case '{':
				if (!$index) {
					$arr = [
						'key' => $this->getKey(),
						'value' => $this->getObjectValues()
					];
				}
				$this->increment();
				$this->startObject(
					objectKey: $keyValue
				);
				break;
			case ']':
				if (!empty($keyValue)) {
					$this->jsonDecodeObjectObject->arrayValueArray[] = $keyValue;
					if ($this->jsonDecodeObjectObject->arrayKey === Constant::$NULL) {
						$this->jsonDecodeObjectObject->arrayKey = 0;
					} else {
						$this->jsonDecodeObjectObject->arrayKey++;
					}
				}
				if ($index) {
					$arr = [
						'key' => $this->getKey(),
						'value' => [
							'startIndex' => $this->jsonDecodeObjectObject->startIndex,
							'endIndex' => $this->charCounter
						]
					];
				} else {
					if (!empty($this->jsonDecodeObjectObject->arrayValueArray)) {
						$arr = [
							'key' => $this->getKey(),
							'value' => $this->jsonDecodeObjectObject->arrayValueArray
						];
					}
				}
				$this->jsonDecodeObjectObject = null;
				$this->popPreviousObject();
				break;
			case '}':
				if (
					!empty($keyValue)
					&& !empty($nullStr)
				) {
					$nullStr = $this->checkNullStr(
						nullStr: $nullStr
					);
					$this->jsonDecodeObjectObject->objectValueArray[$keyValue] = $nullStr;
				}
				if ($index) {
					$arr = [
						'key' => $this->getKey(),
						'value' => [
							'startIndex' => $this->jsonDecodeObjectObject->startIndex,
							'endIndex' => $this->charCounter
						]
					];
				} else {
					if (!empty($this->jsonDecodeObjectObject->objectValueArray)) {
						$arr = [
							'key' => $this->getKey(),
							'value' => $this->jsonDecodeObjectObject->objectValueArray
						];
					}
				}
				$this->jsonDecodeObjectObject = null;
				$this->popPreviousObject();
				break;
		}
		if (
			$arr !== Constant::$FALSE
			&& !empty($arr)
			&& isset($arr['value'])
			&& $arr['value'] !== Constant::$FALSE
			&& count(
				value: $arr['value']
			) > 0
		) {
			return $arr;
		}
		return false;
	}

	/**
	 * Check String present in JSON without double quotes for null or int
	 * 
	 * @param string $nullStr String present in JSON without double quotes
	 * 
	 * @return bool|int|null
	 */
	private function checkNullStr(
		$nullStr
	): bool|int|null {
		$return = false;
		if ($nullStr === 'null') {
			$return = null;
		} elseif (
			is_numeric(
				value: $nullStr
			)
		) {
			$return = (int)$nullStr;
		}
		if ($return === Constant::$FALSE) {
			$this->isBadJson(
				str: $nullStr
			);
		}
		return $return;
	}

	/**
	 * Start of array
	 * 
	 * @param null|string $objectKey Used while creating simple array inside an object
	 * 
	 * @return void
	 */
	private function startArray(
		$objectKey = null
	): void {
		$this->pushCurrentObject(
			objectKey: $objectKey
		);
		$this->jsonDecodeObjectObject = new JsonDecodeObject(
			mode: 'Array',
			objectKey: $objectKey
		);
		$this->jsonDecodeObjectObject->startIndex = $this->charCounter;
	}

	/**
	 * Start of object
	 * 
	 * @param null|string $objectKey Used while creating object inside an object
	 * 
	 * @return void
	 */
	private function startObject(
		$objectKey = null
	): void {
		$this->pushCurrentObject(
			objectKey: $objectKey
		);
		$this->jsonDecodeObjectObject = new JsonDecodeObject(
			mode: 'Object',
			objectKey: $objectKey
		);
		$this->jsonDecodeObjectObject->startIndex = $this->charCounter;
	}

	/**
	 * Push current object
	 * 
	 * @param null|string $objectKey Used while creating object inside an object
	 * 
	 * @return void
	 */
	private function pushCurrentObject(
		$objectKey
	): void {
		if ($this->jsonDecodeObjectObject) {
			if (
				$this->jsonDecodeObjectObject->mode === 'Object'
				&& (
					($objectKey === Constant::$NULL)
					|| empty(
						trim(
							string: $objectKey
						)
					)
				)
			) {
				$this->isBadJson(
					str: $objectKey
				);
			}
			if (
				$this->jsonDecodeObjectObject->mode === 'Array'
				&& (
					($objectKey === Constant::$NULL)
					|| empty(
						trim(
							string: $objectKey
						)
					)
				)
			) {
				$this->isBadJson(
					str: $objectKey
				);
			}
			array_push(
				$this->jsonDecodeObjectObjectArray,
				$this->jsonDecodeObjectObject
			);
		}
	}

	/**
	 * Pop Previous object
	 * 
	 * @return void
	 */
	private function popPreviousObject(): void
	{
		if (
			count(
				value: $this->jsonDecodeObjectObjectArray
			) > 0
		) {
			$this->jsonDecodeObjectObject = array_pop($this->jsonDecodeObjectObjectArray);
		} else {
			$this->jsonDecodeObjectObject = null;
		}
	}

	/**
	 * Increment arrayKey counter for array of object's or arrays
	 * 
	 * @return void
	 */
	private function increment(): void
	{
		if (
			($this->jsonDecodeObjectObject !== Constant::$NULL)
			&& $this->jsonDecodeObjectObject->mode === 'Array'
		) {
			if ($this->jsonDecodeObjectObject->arrayKey === Constant::$NULL) {
				$this->jsonDecodeObjectObject->arrayKey = 0;
			} else {
				$this->jsonDecodeObjectObject->arrayKey++;
			}
		}
	}

	/**
	 * Returns extracted object values
	 * 
	 * @return array|bool
	 */
	private function getObjectValues(): array|bool
	{
		$arr = false;
		if (
			$this->jsonDecodeObjectObject !== Constant::$NULL
			&& $this->jsonDecodeObjectObject->mode === 'Object'
			&& count(
				value: $this->jsonDecodeObjectObject->objectValueArray
			) > 0
		) {
			$arr = $this->jsonDecodeObjectObject->objectValueArray;
			$this->jsonDecodeObjectObject->objectValueArray = [];
		}
		return $arr;
	}

	/**
	 * Check for a valid JSON
	 * 
	 * @param null|string $str Bad JSON string
	 * 
	 * @return void
	 */
	private function isBadJson(
		$str
	): void {
		$str =  $str !== Constant::$NULL ? trim(
			string: $str
		) : $str;
		if (!empty($str)) {
			throw new \Exception(
				message: "Invalid JSON: {$str}",
				code: HttpStatus::$BadRequest
			);
		}
	}

	/**
	 * Generated Array
	 * 
	 * @return array
	 */
	private function getKey(): array
	{
		$keyArray = [];
		$return = &$keyArray;
		$objCount = count(
			value: $this->jsonDecodeObjectObjectArray
		);
		if ($objCount > 0) {
			for ($index = 0; $index < $objCount; $index++) {
				switch ($this->jsonDecodeObjectObjectArray[$index]->mode) {
					case 'Object':
						if ($this->jsonDecodeObjectObjectArray[$index]->objectKey !== Constant::$NULL) {
							$keyArray[] = $this->jsonDecodeObjectObjectArray[$index]->objectKey;
						}
						break;
					case 'Array':
						if ($this->jsonDecodeObjectObjectArray[$index]->objectKey !== Constant::$NULL) {
							$keyArray[] = $this->jsonDecodeObjectObjectArray[$index]->objectKey;
						}
						if ($this->jsonDecodeObjectObjectArray[$index]->arrayKey !== Constant::$NULL) {
							$keyArray[] = $this->jsonDecodeObjectObjectArray[$index]->arrayKey;
						}
						break;
				}
			}
		}
		if ($this->jsonDecodeObjectObject) {
			switch ($this->jsonDecodeObjectObject->mode) {
				case 'Object':
					if ($this->jsonDecodeObjectObject->objectKey !== Constant::$NULL) {
						$keyArray[] = $this->jsonDecodeObjectObject->objectKey;
					}
					break;
				case 'Array':
					if ($this->jsonDecodeObjectObject->objectKey !== Constant::$NULL) {
						$keyArray[] = $this->jsonDecodeObjectObject->objectKey;
					}
					break;
			}
		}
		return $return;
	}
}
