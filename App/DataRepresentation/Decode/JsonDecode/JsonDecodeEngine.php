<?php

/**
 * Handling JSON formats
 * php version 8.3
 *
 * @category  DataDecode
 * @package   Openswoole_Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App\DataRepresentation\Decode\JsonDecode;

use Generator;
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
 * @package   Openswoole_Microservices
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
	private $objectArr = [];

	/**
	 * Current JsonDecodeObject object
	 *
	 * @var JsonDecodeObject
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
	 * JSON file start position
	 *
	 * @var null|int
	 */
	public $sIndex = null;

	/**
	 * JSON file end position
	 *
	 * @var null|int
	 */
	public $eIndex = null;

	/**
	 * JSON char counter
	 * Starts from $sIndex till $eIndex
	 *
	 * @var null|int
	 */
	private $charCounter = null;

	/**
	 * Constructor
	 *
	 * @param null|resource $jsonFileHandle JSON file handle
	 */
	public function __construct(&$jsonFileHandle)
	{
		$this->jsonFileHandle = &$jsonFileHandle;
	}

	/**
	 * Start processing the JSON string
	 *
	 * @param bool $index Index output
	 *
	 * @return Generator
	 */
	public function process($index = false): Generator
	{
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

		$this->charCounter = $this->sIndex !== null ? $this->sIndex : 0;
		fseek(
			stream: $this->jsonFileHandle,
			offset: $this->charCounter,
			whence: SEEK_SET
		);

		for (
			;
			(
				($char = fgetc(stream: $this->jsonFileHandle)) !== false
				&& (
					($this->eIndex === null)
					|| (
						($this->eIndex !== null)
						&& $this->charCounter <= $this->eIndex
					)
				)
			);
			$this->charCounter++
		) {
			switch (true) {
				case $quote === false:
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
						case in_array(needle: $char, haystack: ['[', ']', '{', '}']):
							$arr = $this->handleOpenClose(
								char: $char,
								keyValue: $keyValue,
								nullStr: $nullStr,
								index: $index
							);
							if ($arr !== false) {
								yield $arr['key'] => $arr['value'];
							}
							$keyValue = $valueValue = '';
							$varMode = 'keyValue';
							break;

						// Check for null values
						case (
							$char === ','
							&& ($nullStr !== null)
						):
							$nullStr = $this->checkNullStr(nullStr: $nullStr);
							switch ($this->currentObject->mode) {
								case 'Array':
									$this->currentObject->arrayValueArr[] = $nullStr;
									break;
								case 'Object':
									if (!empty($keyValue)) {
										$this->currentObject->objectValueArr[$keyValue] = $nullStr;
									}
									break;
							}
							$nullStr = null;
							$keyValue = $valueValue = '';
							$varMode = 'keyValue';
							break;

						//Switch mode to value collection after colon
						case in_array(needle: $char, haystack: $this->escapeArr):
							break;

						// Append char to null string
						case !in_array(needle: $char, haystack: $this->escapeArr):
							$nullStr .= $char;
							break;
					}
					break;

				case $quote === true:
					switch (true) {
						// Collect string to be escaped
						case $varMode === 'valueValue'
							&& ($char === '\\'
								|| ($prevIsEscape
									&& in_array(
										needle: $strToEscape . $char,
										haystack: $this->replaceArr
									)
								)
							):
							$strToEscape .= $char;
							$prevIsEscape = true;
							break;

						// Escape value with char
						case $varMode === 'valueValue'
							&& $prevIsEscape === true
							&& in_array(
								needle: $strToEscape . $char,
								haystack: $this->replaceArr
							):
							$$varMode .= str_replace(
								search: $this->replaceArr,
								replace: $this->escapeArr,
								subject: $strToEscape . $char
							);
							$strToEscape = '';
							$prevIsEscape = false;
							break;

						// Escape value without char
						case $varMode === 'valueValue'
							&& $prevIsEscape === true
							&& in_array(
								needle: $strToEscape,
								haystack: $this->replaceArr
							):
							$$varMode .= str_replace(
								search: $this->replaceArr,
								replace: $this->escapeArr,
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
									if (!isset($this->currentObject)) {
										$this->startObject();
									}
									$this->currentObject->objectValueArr[$keyValue] = $valueValue;
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
		$this->objectArr = [];
		$this->currentObject = null;
	}

	/**
	 * Get JSON string
	 *
	 * @return bool|string
	 */
	public function getJsonString(): bool|string
	{
		if (
			($this->sIndex === null)
			&& ($this->eIndex === null)
		) {
			rewind(stream: $this->jsonFileHandle);
			return stream_get_contents(stream: $this->jsonFileHandle);
		} else {
			$offset = $this->sIndex !== null ? $this->sIndex : 0;
			$length = $this->eIndex - $offset + 1;
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
	private function handleOpenClose($char, $keyValue, $nullStr, $index): array|bool
	{
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
				$this->startArray(objectKey: $keyValue);
				break;
			case '{':
				if (!$index) {
					$arr = [
						'key' => $this->getKey(),
						'value' => $this->getObjectValues()
					];
				}
				$this->increment();
				$this->startObject(objectKey: $keyValue);
				break;
			case ']':
				if (!empty($keyValue)) {
					$this->currentObject->arrayValueArr[] = $keyValue;
					if ($this->currentObject->arrayKey === null) {
						$this->currentObject->arrayKey = 0;
					} else {
						$this->currentObject->arrayKey++;
					}
				}
				if ($index) {
					$arr = [
						'key' => $this->getKey(),
						'value' => [
							'sIndex' => $this->currentObject->sIndex,
							'eIndex' => $this->charCounter
						]
					];
				} else {
					if (!empty($this->currentObject->arrayValueArr)) {
						$arr = [
							'key' => $this->getKey(),
							'value' => $this->currentObject->arrayValueArr
						];
					}
				}
				$this->currentObject = null;
				$this->popPreviousObject();
				break;
			case '}':
				if (
					!empty($keyValue)
					&& !empty($nullStr)
				) {
					$nullStr = $this->checkNullStr(nullStr: $nullStr);
					$this->currentObject->objectValueArr[$keyValue] = $nullStr;
				}
				if ($index) {
					$arr = [
						'key' => $this->getKey(),
						'value' => [
							'sIndex' => $this->currentObject->sIndex,
							'eIndex' => $this->charCounter
						]
					];
				} else {
					if (!empty($this->currentObject->objectValueArr)) {
						$arr = [
							'key' => $this->getKey(),
							'value' => $this->currentObject->objectValueArr
						];
					}
				}
				$this->currentObject = null;
				$this->popPreviousObject();
				break;
		}
		if (
			$arr !== false
			&& !empty($arr)
			&& isset($arr['value'])
			&& $arr['value'] !== false
			&& count(value: $arr['value']) > 0
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
	private function checkNullStr($nullStr): bool|int|null
	{
		$return = false;
		if ($nullStr === 'null') {
			$return = null;
		} elseif (is_numeric(value: $nullStr)) {
			$return = (int)$nullStr;
		}
		if ($return === false) {
			$this->isBadJson(str: $nullStr);
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
	private function startArray($objectKey = null): void
	{
		$this->pushCurrentObject(objectKey: $objectKey);
		$this->currentObject = new JsonDecodeObject(mode: 'Array', objectKey: $objectKey);
		$this->currentObject->sIndex = $this->charCounter;
	}

	/**
	 * Start of object
	 *
	 * @param null|string $objectKey Used while creating object inside an object
	 *
	 * @return void
	 */
	private function startObject($objectKey = null): void
	{
		$this->pushCurrentObject(objectKey: $objectKey);
		$this->currentObject = new JsonDecodeObject(mode: 'Object', objectKey: $objectKey);
		$this->currentObject->sIndex = $this->charCounter;
	}

	/**
	 * Push current object
	 *
	 * @param null|string $objectKey Used while creating object inside an object
	 *
	 * @return void
	 */
	private function pushCurrentObject($objectKey): void
	{
		if ($this->currentObject) {
			if (
				$this->currentObject->mode === 'Object'
				&& (
					($objectKey === null)
					|| empty(trim(string: $objectKey))
				)
			) {
				$this->isBadJson(str: $objectKey);
			}
			if (
				$this->currentObject->mode === 'Array'
				&& (
					($objectKey === null)
					|| empty(trim(string: $objectKey))
				)
			) {
				$this->isBadJson(str: $objectKey);
			}
			array_push($this->objectArr, $this->currentObject);
		}
	}

	/**
	 * Pop Previous object
	 *
	 * @return void
	 */
	private function popPreviousObject(): void
	{
		if (count(value: $this->objectArr) > 0) {
			$this->currentObject = array_pop($this->objectArr);
		} else {
			$this->currentObject = null;
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
			($this->currentObject !== null)
			&& $this->currentObject->mode === 'Array'
		) {
			if ($this->currentObject->arrayKey === null) {
				$this->currentObject->arrayKey = 0;
			} else {
				$this->currentObject->arrayKey++;
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
			$this->currentObject !== null
			&& $this->currentObject->mode === 'Object'
			&& count(value: $this->currentObject->objectValueArr) > 0
		) {
			$arr = $this->currentObject->objectValueArr;
			$this->currentObject->objectValueArr = [];
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
	private function isBadJson($str): void
	{
		$str =  $str !== null ? trim(string: $str) : $str;
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
		$keyArr = [];
		$return = &$keyArr;
		$objCount = count(value: $this->objectArr);
		if ($objCount > 0) {
			for ($i = 0; $i < $objCount; $i++) {
				switch ($this->objectArr[$i]->mode) {
					case 'Object':
						if ($this->objectArr[$i]->objectKey !== null) {
							$keyArr[] = $this->objectArr[$i]->objectKey;
						}
						break;
					case 'Array':
						if ($this->objectArr[$i]->objectKey !== null) {
							$keyArr[] = $this->objectArr[$i]->objectKey;
						}
						if ($this->objectArr[$i]->arrayKey !== null) {
							$keyArr[] = $this->objectArr[$i]->arrayKey;
						}
						break;
				}
			}
		}
		if ($this->currentObject) {
			switch ($this->currentObject->mode) {
				case 'Object':
					if ($this->currentObject->objectKey !== null) {
						$keyArr[] = $this->currentObject->objectKey;
					}
					break;
				case 'Array':
					if ($this->currentObject->objectKey !== null) {
						$keyArr[] = $this->currentObject->objectKey;
					}
					break;
			}
		}
		return $return;
	}
}
