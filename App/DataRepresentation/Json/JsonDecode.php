<?php
/**
 * Handling JSON formats
 * php version 8.3
 *
 * @category  DataDecode
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
namespace Microservices\App\DataRepresentation\Json;

use Generator;
use Microservices\App\DataRepresentation\AbstractDataDecode;
use Microservices\App\HttpStatus;

/**
 * Creates Arrays from JSON string
 * php version 8.3
 *
 * @category  DataDecode_JSON
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class JsonDecode extends AbstractDataDecode
{
    /**
     * Json File Handle
     *
     * @var null|resource
     */
    private $_jsonFileHandle = null;

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
    private $_allowedPayloadLength = 100 * 1024 * 1024; // 100 MB

    /**
     * Json Decode Engine Object
     *
     * @var null|JsonDecodeEngine
     */
    private $_jsonDecodeEngine = null;

    /**
     * JsonDecode constructor
     *
     * @param resource $jsonFileHandle File handle
     */
    public function __construct(&$jsonFileHandle)
    {
        if (!$jsonFileHandle) {
            throw new \Exception(
                message: 'Invalid file',
                code: HttpStatus::$BadRequest
            );
        }
        $this->_jsonFileHandle = &$jsonFileHandle;

        // File Stats - Check for size
        $fileStats = fstat($this->_jsonFileHandle);
        if (isset($fileStats['size'])
            && $fileStats['size'] > $this->_allowedPayloadLength
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
     * @return void
     */
    public function init(): void
    {
        // Init Json Decode Engine
        $this->_jsonDecodeEngine = new JsonDecodeEngine(
            jsonFileHandle: $this->_jsonFileHandle
        );
    }

    /**
     * Validates JSON
     *
     * @return void
     */
    public function validate(): void
    {
        foreach ($this->_jsonDecodeEngine->process() as $keyArr => $valueArr) {
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
        $this->jsonFileIndex = null;
        foreach ($this->_jsonDecodeEngine->process(true) as $keys => $val) {
            if (isset($val['sIndex']) && isset($val['eIndex'])) {
                $jsonFileIndex = &$this->jsonFileIndex;
                for ($i=0, $iCount = count(value: $keys); $i < $iCount; $i++) {
                    if (is_numeric(value: $keys[$i])
                        && !isset($jsonFileIndex[$keys[$i]])
                    ) {
                        $jsonFileIndex[$keys[$i]] = [];
                        if (!isset($jsonFileIndex['_c_'])) {
                            $jsonFileIndex['_c_'] = 0;
                        }
                        if (is_numeric(value: $keys[$i])) {
                            $jsonFileIndex['_c_']++;
                        }
                    }
                    $jsonFileIndex = &$jsonFileIndex[$keys[$i]];
                }
                $jsonFileIndex['sIndex'] = $val['sIndex'];
                $jsonFileIndex['eIndex'] = $val['eIndex'];
            }
        }
    }

    /**
     * Keys exist
     *
     * @param null|string $keys Keys exist (values separated by colon)
     *
     * @return bool
     */
    public function isset($keys = null): bool
    {
        $return = true;
        $jsonFileIndex = &$this->jsonFileIndex;
        if (!is_null(value: $keys) && strlen(string: $keys) !== 0) {
            foreach (explode(separator: ':', string: $keys) as $key) {
                if (isset($jsonFileIndex[$key])) {
                    $jsonFileIndex = &$jsonFileIndex[$key];
                } else {
                    $return = false;
                    break;
                }
            }
        }
        return $return;
    }

    /**
     * Key exist
     *
     * @param null|string $keys Keys exist (values separated by colon)
     *
     * @return string Object/Array
     */
    public function dataType($keys = null): string
    {
        $jsonFileIndex = &$this->jsonFileIndex;
        if (!empty($keys) && strlen(string: $keys) > 0) {
            foreach (explode(separator: ':', string: $keys) as $key) {
                if (isset($jsonFileIndex[$key])) {
                    $jsonFileIndex = &$jsonFileIndex[$key];
                } else {
                    throw new \Exception(
                        message: "Invalid key {$key}",
                        code: HttpStatus::$BadRequest
                    );
                }
            }
        }
        $return = 'Object';
        if (isset($jsonFileIndex['sIndex']) && isset($jsonFileIndex['eIndex'])
            && isset($jsonFileIndex['_c_'])
        ) {
            $return = 'Array';
        }
        return $return;
    }

    /**
     * Count of array element
     *
     * @param null|string $keys Key values separated by colon
     *
     * @return int
     */
    public function count($keys = null): int
    {
        $jsonFileIndex = &$this->jsonFileIndex;
        if (!is_null(value: $keys) && strlen(string: $keys) !== 0) {
            foreach (explode(separator: ':', string: $keys) as $key) {
                if (isset($jsonFileIndex[$key])) {
                    $jsonFileIndex = &$jsonFileIndex[$key];
                } else {
                    throw new \Exception(
                        message: "Invalid key {$key}",
                        code: HttpStatus::$BadRequest
                    );
                }
            }
        }
        if (!(isset($jsonFileIndex['sIndex']) && isset($jsonFileIndex['eIndex'])
            && isset($jsonFileIndex['_c_']))
        ) {
            return 0;
        }
        return (int)$jsonFileIndex['_c_'];
    }

    /**
     * Pass the keys and get whole json content belonging to keys
     *
     * @param string $keys Key values separated by colon
     *
     * @return mixed
     */
    public function get($keys = ''): mixed
    {
        if (!$this->isset(keys: $keys)) {
            return false;
        }
        $valueArr = [];
        $this->load(keys: $keys);
        foreach ($this->_jsonDecodeEngine->process() as $valueArr) {
            break;
        }
        return $valueArr;
    }

    /**
     * Get complete JSON for Kays
     *
     * @param string $keys Key values separated by colon
     *
     * @return mixed
     */
    public function getCompleteArray($keys = ''): mixed
    {
        if (!$this->isset(keys: $keys)) {
            return false;
        }
        $this->load(keys: $keys);
        return json_decode(
            json: $this->_jsonDecodeEngine->getJsonString(),
            associative: true
        );
    }

    /**
     * Start processing the JSON string for a keys
     * Perform search inside keys of JSON like $json['data'][0]['data1']
     *
     * @param string $keys Key values separated by colon
     *
     * @return void
     * @throws \Exception
     */
    public function load($keys): void
    {
        if (empty($keys) && $keys != 0) {
            $this->_jsonDecodeEngine->sIndex = null;
            $this->_jsonDecodeEngine->eIndex = null;
            return;
        }
        $jsonFileIndex = &$this->jsonFileIndex;
        if (!is_null(value: $keys) && strlen(string: $keys) !== 0) {
            foreach (explode(separator: ':', string: $keys) as $key) {
                if (isset($jsonFileIndex[$key])) {
                    $jsonFileIndex = &$jsonFileIndex[$key];
                } else {
                    throw new \Exception(
                        message: "Invalid key {$key}",
                        code: HttpStatus::$BadRequest
                    );
                }
            }
        }
        if (isset($jsonFileIndex['sIndex']) && isset($jsonFileIndex['eIndex'])) {
            $this->_jsonDecodeEngine->sIndex = (INT)$jsonFileIndex['sIndex'];
            $this->_jsonDecodeEngine->eIndex = (INT)$jsonFileIndex['eIndex'];
        } else {
            throw new \Exception(
                message: "Invalid keys '{$keys}'",
                code: HttpStatus::$BadRequest
            );
        }
    }
}

/**
 * Creates Arrays from JSON String
 *
 * This class is built to decode large json string or file
 * (which leads to memory limit issues for larger data set)
 * This class gives access to create obects from JSON string
 * in parts for what ever smallest part of data
 * php version 8.3
 *
 * @category  JSON_Decode_Engine
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
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
    private $_jsonFileHandle = null;

    /**
     * Array of JsonDecodeObject objects
     *
     * @var JsonDecodeObject[]
     */
    private $_objects = [];

    /**
     * Current JsonDecodeObject object
     *
     * @var JsonDecodeObject
     */
    private $_currentObject = null;

    /**
     * Characters that are escaped while creating JSON
     *
     * @var string[]
     */
    private $_escapers = array(
        "\\", "\"", "\n", "\r", "\t", "\x08", "\x0c", ' '
    );

    /**
     * Characters that are escaped with for $escapers while creating JSON
     *
     * @var string[]
     */
    private $_replacements = array(
        "\\\\", "\\\"", "\\n", "\\r", "\\t", "\\f", "\\b", ' '
    );

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
    private $_charCounter = null;

    /**
     * JsonDecode constructor
     *
     * @param null|resource $jsonFileHandle JSON file handle
     */
    public function __construct(&$jsonFileHandle)
    {
        $this->_jsonFileHandle = &$jsonFileHandle;
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

        $this->_charCounter = $this->sIndex !== null ? $this->sIndex : 0;
        fseek(
            stream: $this->_jsonFileHandle,
            offset: $this->_charCounter,
            whence: SEEK_SET
        );

        for (; (
                ($char = fgetc(stream: $this->_jsonFileHandle)) !== false &&
                (
                    ($this->eIndex === null) ||
                    ($this->eIndex !== null && $this->_charCounter <= $this->eIndex)
                )
            ); $this->_charCounter++) {
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
                    $arr = $this->_handleOpenClose(
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
                case $char === ', ' && !is_null(value: $nullStr):
                    $nullStr = $this->_checkNullStr(nullStr: $nullStr);
                    switch ($this->_currentObject->mode) {
                    case 'Array':
                        $this->_currentObject->arrayValues[] = $nullStr;
                        break;
                    case 'Assoc':
                        if (!empty($keyValue)) {
                            $this->_currentObject->assocValues[$keyValue] = $nullStr;
                        }
                        break;
                    }
                    $nullStr = null;
                    $keyValue = $valueValue = '';
                    $varMode = 'keyValue';
                    break;

                //Switch mode to value collection after colon
                case in_array(needle: $char, haystack: $this->_escapers):
                    break;

                // Append char to null string
                case !in_array(needle: $char, haystack: $this->_escapers):
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
                                haystack: $this->_replacements
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
                        haystack: $this->_replacements
                    ):
                    $$varMode .= str_replace(search: $this->_replacements, replace: $this->_escapers, subject: $strToEscape . $char);
                    $strToEscape = '';
                    $prevIsEscape = false;
                    break;

                // Escape value without char
                case $varMode === 'valueValue'
                    && $prevIsEscape === true
                    && in_array(
                        needle: $strToEscape,
                        haystack: $this->_replacements
                    ):
                    $$varMode .= str_replace(search: $this->_replacements, replace: $this->_escapers, subject: $strToEscape) . $char;
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
                        if (!isset($this->_currentObject)) {
                            $this->_startObject();
                        }
                        $this->_currentObject->assocValues[$keyValue] = $valueValue;
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
        $this->_objects = [];
        $this->_currentObject = null;
    }

    /**
     * Get JSON string
     *
     * @return bool|string
     */
    public function getJsonString(): bool|string
    {
        if (is_null(value: $this->sIndex) && is_null(value: $this->eIndex)) {
            rewind(stream: $this->_jsonFileHandle);
            return stream_get_contents(stream: $this->_jsonFileHandle);
        } else {
            $offset = $this->sIndex !== null ? $this->sIndex : 0;
            $length = $this->eIndex - $offset + 1;
            return stream_get_contents(
                stream: $this->_jsonFileHandle,
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
    private function _handleOpenClose($char, $keyValue, $nullStr, $index): array|bool
    {
        $arr = false;
        switch ($char) {
        case '[':
            if (!$index) {
                $arr = [
                    'key' => $this->_getKeys(),
                    'value' => $this->_getObjectValues()
                ];
            }
            $this->_increment();
            $this->_startArray(key: $keyValue);
            break;
        case '{':
            if (!$index) {
                $arr = [
                    'key' => $this->_getKeys(),
                    'value' => $this->_getObjectValues()
                ];
            }
            $this->_increment();
            $this->_startObject(key: $keyValue);
            break;
        case ']':
            if (!empty($keyValue)) {
                $this->_currentObject->arrayValues[] = $keyValue;
                if (is_null(value: $this->_currentObject->arrayKey)) {
                    $this->_currentObject->arrayKey = 0;
                } else {
                    $this->_currentObject->arrayKey++;
                }
            }
            if ($index) {
                $arr = [
                    'key' => $this->_getKeys(),
                    'value' => [
                        'sIndex' => $this->_currentObject->sIndex,
                        'eIndex' => $this->_charCounter
                    ]
                ];
            } else {
                if (!empty($this->_currentObject->arrayValues)) {
                    $arr = [
                        'key' => $this->_getKeys(),
                        'value' => $this->_currentObject->arrayValues
                    ];
                }
            }
            $this->_currentObject = null;
            $this->_popPreviousObject();
            break;
        case '}':
            if (!empty($keyValue) && !empty($nullStr)) {
                $nullStr = $this->_checkNullStr(nullStr: $nullStr);
                $this->_currentObject->assocValues[$keyValue] = $nullStr;
            }
            if ($index) {
                $arr = [
                    'key' => $this->_getKeys(),
                    'value' => [
                        'sIndex' => $this->_currentObject->sIndex,
                        'eIndex' => $this->_charCounter
                    ]
                ];
            } else {
                if (!empty($this->_currentObject->assocValues)) {
                    $arr = [
                        'key' => $this->_getKeys(),
                        'value' => $this->_currentObject->assocValues
                    ];
                }
            }
            $this->_currentObject = null;
            $this->_popPreviousObject();
            break;
        }
        if ($arr !== false && !empty($arr) && isset($arr['value'])
            && $arr['value'] !== false && count(value: $arr['value']) > 0
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
    private function _checkNullStr($nullStr): bool|int|null
    {
        $return = false;
        if ($nullStr === 'null') {
            $return = null;
        } elseif (is_numeric(value: $nullStr)) {
            $return = (int)$nullStr;
        }
        if ($return === false) {
            $this->_isBadJson(str: $nullStr);
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
    private function _startArray($key = null): void
    {
        $this->_pushCurrentObject(key: $key);
        $this->_currentObject = new JsonDecodeObject(mode: 'Array', assocKey: $key);
        $this->_currentObject->sIndex = $this->_charCounter;
    }

    /**
     * Start of object
     *
     * @param null|string $key Used while creating object inside an object
     *
     * @return void
     */
    private function _startObject($key = null): void
    {
        $this->_pushCurrentObject(key: $key);
        $this->_currentObject = new JsonDecodeObject(mode: 'Assoc', assocKey: $key);
        $this->_currentObject->sIndex = $this->_charCounter;
    }

    /**
     * Push current object
     *
     * @param null|string $key Used while creating object inside an object
     *
     * @return void
     */
    private function _pushCurrentObject($key): void
    {
        if ($this->_currentObject) {
            if ($this->_currentObject->mode === 'Assoc'
                && (is_null(value: $key) || empty(trim(string: $key)))
            ) {
                $this->_isBadJson(str: $key);
            }
            if ($this->_currentObject->mode === 'Array'
                && (is_null(value: $key) || empty(trim(string: $key)))
            ) {
                $this->_isBadJson(str: $key);
            }
            array_push($this->_objects, $this->_currentObject);
        }
    }

    /**
     * Pop Previous object
     *
     * @return void
     */
    private function _popPreviousObject(): void
    {
        if (count(value: $this->_objects) > 0) {
            $this->_currentObject = array_pop($this->_objects);
        } else {
            $this->_currentObject = null;
        }
    }

    /**
     * Increment arrayKey counter for array of objects or arrays
     *
     * @return void
     */
    private function _increment(): void
    {
        if (!is_null(value: $this->_currentObject)
            && $this->_currentObject->mode === 'Array'
        ) {
            if (is_null(value: $this->_currentObject->arrayKey)) {
                $this->_currentObject->arrayKey = 0;
            } else {
                $this->_currentObject->arrayKey++;
            }
        }
    }

    /**
     * Returns extracted object values
     *
     * @return array|bool
     */
    private function _getObjectValues(): array|bool
    {
        $arr = false;
        if (!is_null(value: $this->_currentObject)
            && $this->_currentObject->mode === 'Assoc'
            && count(value: $this->_currentObject->assocValues) > 0
        ) {
            $arr = $this->_currentObject->assocValues;
            $this->_currentObject->assocValues = [];
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
    private function _isBadJson($str): void
    {
        $str =  !is_null(value: $str) ? trim(string: $str) : $str;
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
    private function _getKeys(): array
    {
        $keys = [];
        $return = &$keys;
        $objCount = count(value: $this->_objects);
        if ($objCount > 0) {
            for ($i=0; $i<$objCount; $i++) {
                switch ($this->_objects[$i]->mode) {
                case 'Assoc':
                    if (!is_null(value: $this->_objects[$i]->assocKey)) {
                        $keys[] = $this->_objects[$i]->assocKey;
                    }
                    break;
                case 'Array':
                    if (!is_null(value: $this->_objects[$i]->assocKey)) {
                        $keys[] = $this->_objects[$i]->assocKey;
                    }
                    if (!is_null(value: $this->_objects[$i]->arrayKey)) {
                        $keys[] = $this->_objects[$i]->arrayKey;
                    }
                    break;
                }
            }
        }
        if ($this->_currentObject) {
            switch ($this->_currentObject->mode) {
            case 'Assoc':
                if (!is_null(value: $this->_currentObject->assocKey)) {
                    $keys[] = $this->_currentObject->assocKey;
                }
                break;
            case 'Array':
                if (!is_null(value: $this->_currentObject->assocKey)) {
                    $keys[] = $this->_currentObject->assocKey;
                }
                break;
            }
        }
        return $return;
    }
}

/**
 * JSON Object
 * php version 8.3
 *
 * @category  JSON_Decode_Object
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class JsonDecodeObject
{
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
     * Assoc / Array
     *
     * @var string
     */
    public $mode = '';

    /**
     * Assoc key for parant object
     *
     * @var null|string
     */
    public $assocKey = null;

    /**
     * Array key for parant object
     *
     * @var null|string
     */
    public $arrayKey = null;

    /**
     * Object values
     *
     * @var array
     */
    public $assocValues = [];

    /**
     * Array values
     *
     * @var array
     */
    public $arrayValues = [];

    /**
     * Constructor
     *
     * @param string $mode     Values can be one among Array
     * @param string $assocKey Key for Object
     */
    public function __construct($mode, $assocKey = null)
    {
        $this->mode = $mode;

        $assocKey = !is_null(value: $assocKey) ? trim(string: $assocKey) : $assocKey;
        $this->assocKey = !empty($assocKey) ? $assocKey : null;
    }
}
