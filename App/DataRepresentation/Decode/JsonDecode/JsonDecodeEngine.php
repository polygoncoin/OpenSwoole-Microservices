<?php

/**
 * Handling JSON formats
 * php version 8.3
 *
 * @category  DataDecode
 * @package   Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Microservices
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
 * This class gives access to create objects from JSON string
 * in parts for what ever smallest part of data
 * php version 8.3
 *
 * @category  JSON_Decode_Engine
 * @package   Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Microservices
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
     * Array of JsonDecodeObject objects
     *
     * @var JsonDecodeObject[]
     */
    private $objects = [];

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
    private $escapers = [
        "\\", "\"", "\n", "\r", "\t", "\x08", "\x0c", ' '
    ];

    /**
     * Characters that are escaped with for $escapers while creating JSON
     *
     * @var string[]
     */
    private $replacements = [
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
     * JsonDecode constructor
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
                ($char = fgetc(stream: $this->jsonFileHandle)) !== false &&
                (
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
                        case $char === ',' && ($nullStr !== null):
                            $nullStr = $this->checkNullStr(nullStr: $nullStr);
                            switch ($this->currentObject->mode) {
                                case 'Array':
                                    $this->currentObject->arrayValues[] = $nullStr;
                                    break;
                                case 'Object':
                                    if (!empty($keyValue)) {
                                        $this->currentObject->assocValues[$keyValue] = $nullStr;
                                    }
                                    break;
                            }
                            $nullStr = null;
                            $keyValue = $valueValue = '';
                            $varMode = 'keyValue';
                            break;

                        //Switch mode to value collection after colon
                        case in_array(needle: $char, haystack: $this->escapers):
                            break;

                        // Append char to null string
                        case !in_array(needle: $char, haystack: $this->escapers):
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
                                        haystack: $this->replacements
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
                                haystack: $this->replacements
                            ):
                            $$varMode .= str_replace(
                                search: $this->replacements,
                                replace: $this->escapers,
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
                                haystack: $this->replacements
                            ):
                            $$varMode .= str_replace(
                                search: $this->replacements,
                                replace: $this->escapers,
                                subject: $strToEscape
                            ) . $char;
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
                                    $this->currentObject->assocValues[$keyValue] = $valueValue;
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
        $this->objects = [];
        $this->currentObject = null;
    }

    /**
     * Get JSON string
     *
     * @return bool|string
     */
    public function getJsonString(): bool|string
    {
        if (($this->sIndex === null) && ($this->eIndex === null)) {
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
                        'key' => $this->getKeys(),
                        'value' => $this->getObjectValues()
                    ];
                }
                $this->increment();
                $this->startArray(key: $keyValue);
                break;
            case '{':
                if (!$index) {
                    $arr = [
                        'key' => $this->getKeys(),
                        'value' => $this->getObjectValues()
                    ];
                }
                $this->increment();
                $this->startObject(key: $keyValue);
                break;
            case ']':
                if (!empty($keyValue)) {
                    $this->currentObject->arrayValues[] = $keyValue;
                    if ($this->currentObject->arrayKey === null) {
                        $this->currentObject->arrayKey = 0;
                    } else {
                        $this->currentObject->arrayKey++;
                    }
                }
                if ($index) {
                    $arr = [
                        'key' => $this->getKeys(),
                        'value' => [
                            'sIndex' => $this->currentObject->sIndex,
                            'eIndex' => $this->charCounter
                        ]
                    ];
                } else {
                    if (!empty($this->currentObject->arrayValues)) {
                        $arr = [
                            'key' => $this->getKeys(),
                            'value' => $this->currentObject->arrayValues
                        ];
                    }
                }
                $this->currentObject = null;
                $this->popPreviousObject();
                break;
            case '}':
                if (!empty($keyValue) && !empty($nullStr)) {
                    $nullStr = $this->checkNullStr(nullStr: $nullStr);
                    $this->currentObject->assocValues[$keyValue] = $nullStr;
                }
                if ($index) {
                    $arr = [
                        'key' => $this->getKeys(),
                        'value' => [
                            'sIndex' => $this->currentObject->sIndex,
                            'eIndex' => $this->charCounter
                        ]
                    ];
                } else {
                    if (!empty($this->currentObject->assocValues)) {
                        $arr = [
                            'key' => $this->getKeys(),
                            'value' => $this->currentObject->assocValues
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
     * @param null|string $key Used while creating simple array inside an object
     *
     * @return void
     */
    private function startArray($key = null): void
    {
        $this->pushCurrentObject(key: $key);
        $this->currentObject = new JsonDecodeObject(mode: 'Array', assocKey: $key);
        $this->currentObject->sIndex = $this->charCounter;
    }

    /**
     * Start of object
     *
     * @param null|string $key Used while creating object inside an object
     *
     * @return void
     */
    private function startObject($key = null): void
    {
        $this->pushCurrentObject(key: $key);
        $this->currentObject = new JsonDecodeObject(mode: 'Object', assocKey: $key);
        $this->currentObject->sIndex = $this->charCounter;
    }

    /**
     * Push current object
     *
     * @param null|string $key Used while creating object inside an object
     *
     * @return void
     */
    private function pushCurrentObject($key): void
    {
        if ($this->currentObject) {
            if (
                $this->currentObject->mode === 'Object'
                && (($key === null) || empty(trim(string: $key)))
            ) {
                $this->isBadJson(str: $key);
            }
            if (
                $this->currentObject->mode === 'Array'
                && (($key === null) || empty(trim(string: $key)))
            ) {
                $this->isBadJson(str: $key);
            }
            array_push($this->objects, $this->currentObject);
        }
    }

    /**
     * Pop Previous object
     *
     * @return void
     */
    private function popPreviousObject(): void
    {
        if (count(value: $this->objects) > 0) {
            $this->currentObject = array_pop($this->objects);
        } else {
            $this->currentObject = null;
        }
    }

    /**
     * Increment arrayKey counter for array of objects or arrays
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
            && count(value: $this->currentObject->assocValues) > 0
        ) {
            $arr = $this->currentObject->assocValues;
            $this->currentObject->assocValues = [];
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
    private function getKeys(): array
    {
        $keys = [];
        $return = &$keys;
        $objCount = count(value: $this->objects);
        if ($objCount > 0) {
            for ($i = 0; $i < $objCount; $i++) {
                switch ($this->objects[$i]->mode) {
                    case 'Object':
                        if ($this->objects[$i]->assocKey !== null) {
                            $keys[] = $this->objects[$i]->assocKey;
                        }
                        break;
                    case 'Array':
                        if ($this->objects[$i]->assocKey !== null) {
                            $keys[] = $this->objects[$i]->assocKey;
                        }
                        if ($this->objects[$i]->arrayKey !== null) {
                            $keys[] = $this->objects[$i]->arrayKey;
                        }
                        break;
                }
            }
        }
        if ($this->currentObject) {
            switch ($this->currentObject->mode) {
                case 'Object':
                    if ($this->currentObject->assocKey !== null) {
                        $keys[] = $this->currentObject->assocKey;
                    }
                    break;
                case 'Array':
                    if ($this->currentObject->assocKey !== null) {
                        $keys[] = $this->currentObject->assocKey;
                    }
                    break;
            }
        }
        return $return;
    }
}
