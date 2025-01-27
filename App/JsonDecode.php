<?php
namespace Microservices\App;

use Microservices\App\HttpStatus;

/**
 * Creates Arrays from JSON String
 *
 * This class is built to decode large json string or file
 * (which leads to memory limit issues for larger data set)
 * This class gives access to create obects from JSON string
 * in parts for what ever smallest part of data
 *
 * @category   JSON
 * @package    JSON Decoder
 * @author     Ramesh Narayan Jangid
 * @copyright  Ramesh Narayan Jangid
 * @version    Release: @1.0.0@
 * @since      Class available since Release 1.0.0
 */
class JsonDecode
{
    /**
     * Json File Handle
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
     * Allowed Paylaod length
     *
     * @var integer
     */
    private $allowedPayloadLength = 100 * 1024 * 1024; // 100 MB

    /**
     * Json Decode Engine Object
     *
     * @var null|JsonDecodeEngine
     */
    private $jsonDecodeEngine = null;

    /**
     * JsonEncode constructor
     *
     * @param resource $jsonFileHandle File handle
     * @return void
     */
    public function __construct(&$jsonFileHandle)
    {
        if (!$jsonFileHandle) {
            die('Invalid file');
        }
        $this->jsonFileHandle = &$jsonFileHandle;

        // File Stats - Check for size
        $fileStats = fstat($this->jsonFileHandle);
        if (isset($fileStats['size']) && $fileStats['size'] > $this->allowedPayloadLength) {
            die('File size greater than allowed size');
        }
    }

    /**
     * Initialize
     *
     * @return boolean
     */
    public function init()
    {
        // Init Json Decode Engine
        $this->jsonDecodeEngine = new JsonDecodeEngine($this->jsonFileHandle);
    }
    /**
     * Validates JSON
     *
     * @return void
     */
    public function validate()
    {
        foreach($this->jsonDecodeEngine->process() as $keyArr => $valueArr) {
            ;
        }
    }

    /**
     * Index file JSON
     *
     * @return void
     */
    public function indexJson()
    {
        $this->jsonFileIndex = null;
        foreach ($this->jsonDecodeEngine->process(true) as $keys => $val) {
            if (
                isset($val['_s_']) &&
                isset($val['_e_'])
            ) {
                $jsonFileIndex = &$this->jsonFileIndex;
                for ($i=0, $iCount = count($keys); $i < $iCount; $i++) {
                    if (is_numeric($keys[$i]) && !isset($jsonFileIndex[$keys[$i]])) {
                        $jsonFileIndex[$keys[$i]] = [];
                        if (!isset($jsonFileIndex['_c_'])) {
                            $jsonFileIndex['_c_'] = 0;
                        }
                        if (is_numeric($keys[$i])) {
                            $jsonFileIndex['_c_']++;
                        }
                    }
                    $jsonFileIndex = &$jsonFileIndex[$keys[$i]];
                }
                $jsonFileIndex['_s_'] = $val['_s_'];
                $jsonFileIndex['_e_'] = $val['_e_'];
            }
        }
    }

    /**
     * Keys exist
     *
     * @param string $keys Keys exist (values seperated by colon)
     * @return boolean
     */
    public function isset($keys = null)
    {
        $return = true;
        $jsonFileIndex = &$this->jsonFileIndex;
        if (!is_null($keys) && strlen($keys) !== 0) {
            foreach (explode(':', $keys) as $key) {
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
     * @param string $keys Key values seperated by colon
     * @return string
     */
    public function jsonType($keys = null)
    {
        $jsonFileIndex = &$this->jsonFileIndex;
        if (!is_null($keys) && strlen($keys) !== 0) {
            foreach (explode(':', $keys) as $key) {
                if (isset($jsonFileIndex[$key])) {
                    $jsonFileIndex = &$jsonFileIndex[$key];
                } else {
                    die("Invalid key {$key}");
                }
            }
        }
        $return = 'Object';
        if (
            (
                isset($jsonFileIndex['_s_']) &&
                isset($jsonFileIndex['_e_']) &&
                isset($jsonFileIndex['_c_'])
            )
        ) {
            $return = 'Array';
        }
        return $return;
    }

    /**
     * Count of array element
     *
     * @param string $keys Key values seperated by colon
     * @return integer
     */
    public function count($keys = null)
    {
        $jsonFileIndex = &$this->jsonFileIndex;
        if (!is_null($keys) && strlen($keys) !== 0) {
            foreach (explode(':', $keys) as $key) {
                if (isset($jsonFileIndex[$key])) {
                    $jsonFileIndex = &$jsonFileIndex[$key];
                } else {
                    die("Invalid key {$key}");
                }
            }
        }
        if (
            !(
                isset($jsonFileIndex['_s_']) &&
                isset($jsonFileIndex['_e_']) &&
                isset($jsonFileIndex['_c_'])
            )
        ) {
            return 0;
        }
        return $jsonFileIndex['_c_'];
    }

    /**
     * Pass the keys and get whole json content belonging to keys
     *
     * @param string $keys Key values seperated by colon
     * @return array
     */
    public function get($keys = '')
    {
        if (!$this->isset($keys)) {
            return false;
        }
        $valueArr = [];
        $this->load($keys);
        foreach ($this->jsonDecodeEngine->process() as $keyArr => $valueArr) {
            break;
        }
        return $valueArr;
    }

    /**
     * Get complete JSON for Kays
     *
     * @param string $keys Key values seperated by colon
     * @return array
     */
    public function getCompleteArray($keys = '')
    {
        if (!$this->isset($keys)) {
            return false;
        }
        $this->load($keys);
        return json_decode($this->jsonDecodeEngine->getJsonString(), true);
    }

    /**
     * Start processing the JSON string for a keys
     * Perform search inside keys of JSON like $json['data'][0]['data1']
     *
     * @param string $keys Key values seperated by colon.
     * @return void
     * @throws \Exception
     */
    public function load($keys)
    {
        if (empty($keys) && $keys != 0) {
            $this->jsonDecodeEngine->_s_ = null;
            $this->jsonDecodeEngine->_e_ = null;
            return;
        }
        $jsonFileIndex = &$this->jsonFileIndex;
        if (!is_null($keys) && strlen($keys) !== 0) {
            foreach (explode(':', $keys) as $key) {
                if (isset($jsonFileIndex[$key])) {
                    $jsonFileIndex = &$jsonFileIndex[$key];
                } else {
                    die("Invalid key {$key}");
                }
            }
        }
        if (
            isset($jsonFileIndex['_s_']) &&
            isset($jsonFileIndex['_e_'])
        ) {
            $this->jsonDecodeEngine->_s_ = $jsonFileIndex['_s_'];
            $this->jsonDecodeEngine->_e_ = $jsonFileIndex['_e_'];
        } else {
            throw new \Exception("Invalid keys '{$keys}'", HttpStatus::$BadRequest);
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
 *
 * @category   JSON
 * @package    JSON Decode Engine
 * @author     Ramesh Narayan Jangid
 * @copyright  Ramesh Narayan Jangid
 * @version    Release: @1.0.0@
 * @since      Class available since Release 1.0.0
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
     * Array of JsonEncodeObject objects
     *
     * @var JsonDecodeObject[]
     */
    private $objects = [];

    /**
     * Current JsonEncodeObject object
     *
     * @var JsonDecodeObject
     */
    private $currentObject = null;

    /**
     * Characters that are escaped while creating JSON
     *
     * @var string[]
     */
    private $escapers = array("\\", "/", "\"", "\n", "\r", "\t", "\x08", "\x0c", ' ');

    /**
     * Characters that are escaped with for $escapers while creating JSON
     *
     * @var string[]
     */
    private $replacements = array("\\\\", "\\/", "\\\"", "\\n", "\\r", "\\t", "\\f", "\\b", ' ');

    /**
     * JSON file start position
     *
     * @var null|integer
     */
    public $_s_ = null;

    /**
     * JSON file end position
     *
     * @var null|integer
     */
    public $_e_ = null;

    /**
     * JSON char counter
     * Starts from $_s_ till $_e_
     *
     * @var null|integer
     */
    private $charCounter = null;

    /**
     * JsonEncode constructor
     *
     * @param null|resource $jsonFileHandle
     * @return void
     */
    public function __construct(&$jsonFileHandle)
    {
        $this->jsonFileHandle = &$jsonFileHandle;
    }

    /**
     * Start processing the JSON string
     *
     * @param boolean $index Index output
     * @return void
     */
    public function process($index = false)
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

        $this->charCounter = $this->_s_ !== null ? $this->_s_ : 0;
        fseek($this->jsonFileHandle, $this->charCounter, SEEK_SET);

        for(;
            (
                ($char = fgetc($this->jsonFileHandle)) !== false &&
                (
                    ($this->_e_ === null) ||
                    ($this->_e_ !== null && $this->charCounter <= $this->_e_)
                )
            )
            ;$this->charCounter++
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
                        case in_array($char, ['[',']','{','}']):
                            $arr = $this->handleOpenClose($char, $keyValue, $nullStr, $index);
                            if ($arr !== false) {
                                yield $arr['key'] => $arr['value'];
                            }
                            $keyValue = $valueValue = '';
                            $varMode = 'keyValue';
                            break;

                        // Check for null values
                        case $char === ',' && !is_null($nullStr):
                            $nullStr = $this->checkNullStr($nullStr);
                            switch ($this->currentObject->mode) {
                                case 'Array':
                                    $this->currentObject->arrayValues[] = $nullStr;
                                    break;
                                case 'Assoc':
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
                        case in_array($char, $this->escapers):
                            break;

                        // Append char to null string
                        case !in_array($char, $this->escapers):
                            $nullStr .= $char;
                            break;
                    }
                    break;

                case $quote === true:
                    switch (true) {
                        // Collect string to be escaped
                        case $varMode === 'valueValue' && ($char === '\\' || ($prevIsEscape && in_array($strToEscape . $char , $this->replacements))):
                            $strToEscape .= $char;
                            $prevIsEscape = true;
                            break;

                        // Escape value with char
                        case $varMode === 'valueValue' && $prevIsEscape === true && in_array($strToEscape . $char , $this->replacements):
                            $$varMode .= str_replace($this->replacements, $this->escapers, $strToEscape . $char);
                            $strToEscape = '';
                            $prevIsEscape = false;
                            break;

                        // Escape value without char
                        case $varMode === 'valueValue' && $prevIsEscape === true && in_array($strToEscape , $this->replacements):
                            $$varMode .= str_replace($this->replacements, $this->escapers, $strToEscape) . $char;
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
     * @param boolean $index Index output
     * @return string
     */
    public function getJsonString()
    {
        $offset = $this->_s_ !== null ? $this->_s_ : 0;
        $length = $this->_e_ - $offset + 1;

        return stream_get_contents($this->jsonFileHandle, $length, $offset);
    }

    /**
     * Handles array / object open close char
     *
     * @param string $char     Character among any one "[" "]" "{" "}"
     * @param string $keyValue String value of key of an object
     * @param string $nullStr  String present in JSON without double quotes
     * @param boolean   $index    Index output
     * @return array
     */
    private function handleOpenClose($char, $keyValue, $nullStr, $index)
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
                $this->startArray($keyValue);
                break;
            case '{':
                if (!$index) {
                    $arr = [
                        'key' => $this->getKeys(),
                        'value' => $this->getObjectValues()
                    ];
                }
                $this->increment();
                $this->startObject($keyValue);
                break;
            case ']':
                if (!empty($keyValue)) {
                    $this->currentObject->arrayValues[] = $keyValue;
                    if (is_null($this->currentObject->arrayKey)) {
                        $this->currentObject->arrayKey = 0;
                    } else {
                        $this->currentObject->arrayKey++;
                    }
                }
                if ($index) {
                    $arr = [
                        'key' => $this->getKeys(),
                        'value' => [
                            '_s_' => $this->currentObject->_s_,
                            '_e_' => $this->charCounter
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
                    $nullStr = $this->checkNullStr($nullStr);
                    $this->currentObject->assocValues[$keyValue] = $nullStr;
                }
                if ($index) {
                    $arr = [
                        'key' => $this->getKeys(),
                        'value' => [
                            '_s_' => $this->currentObject->_s_,
                            '_e_' => $this->charCounter
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
            $arr !== false &&
            !empty($arr) &&
            isset($arr['value']) &&
            $arr['value'] !== false &&
            count($arr['value']) > 0
        ) {
            return $arr;
        }
        return false;
    }

    /**
     * Check String present in JSON without double quotes for null or integer
     *
     * @param string $nullStr String present in JSON without double quotes
     * @return mixed
     */
    private function checkNullStr($nullStr)
    {
        $return = false;
        if ($nullStr === 'null') {
            $return = null;
        } elseif (is_numeric($nullStr)) {
            $return = (int)$nullStr;
        }
        if ($return === false) {
            $this->isBadJson($nullStr);
        }
        return $return;
    }

    /**
     * Start of array
     *
     * @param string $key Used while creating simple array inside an objectiative array and $key is the key
     * @return void
     */
    private function startArray($key = null)
    {
        $this->pushCurrentObject($key);
        $this->currentObject = new JsonDecodeObject('Array', $key);
        $this->currentObject->_s_ = $this->charCounter;
    }

    /**
     * Start of object
     *
     * @param string $key Used while creating objectiative array inside an objectiative array and $key is the key
     * @return void-
     */
    private function startObject($key = null)
    {
        $this->pushCurrentObject($key);
        $this->currentObject = new JsonDecodeObject('Assoc', $key);
        $this->currentObject->_s_ = $this->charCounter;
    }

    /**
     * Push current object
     *
     * @return void
     */
    private function pushCurrentObject($key)
    {
        if ($this->currentObject) {
            if ($this->currentObject->mode === 'Assoc' && (is_null($key) || empty(trim($key)))) {
                $this->isBadJson($key);
            }
            if ($this->currentObject->mode === 'Array' && (is_null($key) || empty(trim($key)))) {
                $this->isBadJson($key);
            }
            array_push($this->objects, $this->currentObject);
        }
    }

    /**
     * Pop Previous object
     *
     * @return void
     */
    private function popPreviousObject()
    {
        if (count($this->objects) > 0) {
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
    private function increment()
    {
        if (
            !is_null($this->currentObject) &&
            $this->currentObject->mode === 'Array'
        ) {
            if (is_null($this->currentObject->arrayKey)) {
                $this->currentObject->arrayKey = 0;
            } else {
                $this->currentObject->arrayKey++;
            }
        }
    }

    /**
     * Returns extracted object values
     *
     * @return array
     */
    private function getObjectValues()
    {
        $arr = false;
        if (
            !is_null($this->currentObject) &&
            $this->currentObject->mode === 'Assoc' &&
            count($this->currentObject->assocValues) > 0
        ) {
            $arr = $this->currentObject->assocValues;
            $this->currentObject->assocValues = [];
        }
        return $arr;
    }

    /**
     * Check for a valid JSON
     *
     * @return void
     */
    private function isBadJson($str)
    {
        $str =  !is_null($str) ? trim($str) : $str;
        if (!empty($str)) {
            die("Invalid JSON: {$str}");
        }
    }

    /**
     * Generated Array
     *
     * @param boolean $index true for normal array / false for associative array
     * @return array
     */
    private function getKeys()
    {
        $keys = [];
        $return = &$keys;
        $objCount = count($this->objects);
        if ($objCount > 0) {
            for ($i=0; $i<$objCount; $i++) {
                switch ($this->objects[$i]->mode) {
                    case 'Assoc':
                        if (!is_null($this->objects[$i]->assocKey)) {
                            $keys[] = $this->objects[$i]->assocKey;
                        }
                        break;
                    case 'Array':
                        if (!is_null($this->objects[$i]->assocKey)) {
                            $keys[] = $this->objects[$i]->assocKey;
                        }
                        if (!is_null($this->objects[$i]->arrayKey)) {
                            $keys[] = $this->objects[$i]->arrayKey;
                        }
                        break;
                }
            }
        }
        if ($this->currentObject) {
            switch ($this->currentObject->mode) {
                case 'Assoc':
                    if (!is_null($this->currentObject->assocKey)) {
                        $keys[] = $this->currentObject->assocKey;
                    }
                    break;
                case 'Array':
                    if (!is_null($this->currentObject->assocKey)) {
                        $keys[] = $this->currentObject->assocKey;
                    }
                    break;
            }
        }
        return $return;
    }

    /**
     * Generated Assoc Array
     *
     * @return array
     */
    private function getAssocKeys()
    {
        $keys = [];
        $return = &$keys;
        $objCount = count($this->objects);
        if ($objCount > 0) {
            for ($i=0; $i<$objCount; $i++) {
                switch ($this->objects[$i]->mode) {
                    case 'Assoc':
                        if (!is_null($this->objects[$i]->assocKey)) {
                            $keys[$this->objects[$i]->assocKey] = [];
                            $keys = &$keys[$this->objects[$i]->assocKey];
                        }
                        break;
                    case 'Array':
                        if (!is_null($this->objects[$i]->assocKey)) {
                            $keys[$this->objects[$i]->assocKey] = [];
                            $keys = &$keys[$this->objects[$i]->assocKey];
                        }
                        if (!is_null($this->objects[$i]->arrayKey)) {
                            $keys[$this->objects[$i]->arrayKey] = [];
                            $keys = &$keys[$this->objects[$i]->arrayKey];
                        }
                        break;
                }
            }
        }
        if ($this->currentObject) {
            switch ($this->currentObject->mode) {
                case 'Assoc':
                    if (!is_null($this->currentObject->assocKey)) {
                        $keys[$this->currentObject->assocKey] = [];
                        $keys = &$keys[$this->currentObject->assocKey];
                    }
                    break;
                case 'Array':
                    if (!is_null($this->currentObject->assocKey)) {
                        $keys[$this->currentObject->assocKey] = [];
                        $keys = &$keys[$this->currentObject->assocKey];
                    }
                    break;
            }
        }
        return $return;
    }
}

/**
 * JSON Object
 *
 * This class is built to help maintain state of an Array or Object of JSON
 *
 * @category   JSON
 * @package    Json Decoder
 * @author     Ramesh Narayan Jangid
 * @copyright  Ramesh Narayan Jangid
 * @version    Release: @1.0.0@
 * @since      Class available since Release 1.0.0
 */
class JsonDecodeObject
{
    /**
     * JSON file start position
     *
     * @var null|integer
     */
    public $_s_ = null;

    /**
     * JSON file end position
     *
     * @var null|integer
     */
    public $_e_ = null;

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
     * @param string $mode Values can be one among Array
     */
    public function __construct($mode, $assocKey = null)
    {
        $this->mode = $mode;

        $assocKey = !is_null($assocKey) ? trim($assocKey) : $assocKey;
        $this->assocKey = !empty($assocKey) ? $assocKey : null;
    }
}
