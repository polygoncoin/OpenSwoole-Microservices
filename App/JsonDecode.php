<?php
namespace Microservices\App;

use Microservices\App\Constants;
use Microservices\App\Common;
use Microservices\App\Env;
use Microservices\App\Logs;

use OpenSwoole\Http\Request;
use OpenSwoole\Http\Response;

/**
 * Creates Objects from JSON String.
 *
 * This class is built to decode large json string or file
 * (which leads to memory limit issues for larger data set)
 * This class gives access to create obects from JSON string
 * in parts for what ever smallest part of data.
 * 
 *
 * @category   JSON Decoder
 * @package    Microservices
 * @author     Ramesh Narayan Jangid
 * @copyright  Ramesh Narayan Jangid
 * @version    Release: @1.0.0@
 * @since      Class available since Release 1.0.0
 */
class JsonDecode
{
    /**
     * Temporary Stream
     *
     * @var object
     */
    private $tempStream = null;

    /**
     * Array of JsonEncodeObject objects
     *
     * @var array
     */
    private $objects = [];

    /**
     * Parent JsonEncodeObject object
     *
     * @var Microservices\App\JsonDecoderObject
     */
    private $previousObjectIndex = null;

    /**
     * Current JsonEncodeObject object
     *
     * @var Microservices\App\JsonDecoderObject
     */
    private $currentObject = null;

    /**
     * Characters that are escaped while creating JSON.
     *
     * @var array
     */
    private $escapers = array("\\", "/", "\"", "\n", "\r", "\t", "\x08", "\x0c", ' ');

    /**
     * Characters that are escaped with for $escapers while creating JSON.
     *
     * @var array
     */
    private $replacements = array("\\\\", "\\/", "\\\"", "\\n", "\\r", "\\t", "\\f", "\\b", ' ');

    /**
     * JSON stream indexes.
     * Contains start and end positions for requested indexes.
     *
     * @var array
     */
    public $streamIndex = null;

    /**
     * JSON stream keys seperated by colon.
     *
     * @var string
     */
    private $keys = null;

    /**
     * JSON stream start position.
     *
     * @var integer
     */
    private $_s_ = null;

    /**
     * JSON stream end position.
     *
     * @var integer
     */
    private $_e_ = null;

    /**
     * JSON char counter.
     * Starts from $_s_ till $_e_
     *
     * @var integer
     */
    private $charCounter = null;

    /**
     * OpenSwoole Http Request
     * 
     * @var OpenSwoole\Http\Request
     */
    public $request = null;

    /**
     * OpenSwoole Http Response
     * 
     * @var OpenSwoole\Http\Response
     */
    public $response = null;

    /**
     * JsonEncode constructor
     * 
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Initialize
     *
     * @param OpenSwoole\Http\Request  $request
     * @param OpenSwoole\Http\Response $response
     * @return boolean
     */
    public function init(Request &$request, Response &$response)
    {
        $this->request = $request;
        $this->response = $response;

        if (isset($this->request->post['Payload'])) {
            $this->tempStream = fopen("php://memory", "rw+b");
            fwrite($this->tempStream, $this->request->post['Payload']);
        }
    }

    /**
     * Validates JSON
     * 
     * @return void
     */
    public function validate()
    {
        foreach($this->process() as $keyArr => $valueArr) {
            ;
        }
    }

    /**
     * Start processing the JSON string
     *
     * @param bool $index Index output.
     * @return void
     */
    public function process($index = false)
    {
        if (!$this->tempStream) {
            return;
        }

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
        fseek($this->tempStream, $this->charCounter, SEEK_SET);
        
        for(;
            (
                ($char = fgetc($this->tempStream)) !== false && 
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
                        // Start of Key or value inside quote.
                        case $char === '"':
                            $quote = true;
                            $nullStr = '';
                            break;

                        //Switch mode to value collection after colon.
                        case $char === ':':
                            $varMode = 'valueValue';
                            break;

                        // Start or End of Array or Object.
                        case in_array($char, ['[',']','{','}']):
                            $arr = $this->handleOpenClose($char, $keyValue, $nullStr, $index);
                            if ($arr !== false) {
                                yield $arr['key'] => $arr['value'];
                            }
                            $keyValue = $valueValue = '';
                            $varMode = 'keyValue';
                            break;
                    
                        // Check for null values.
                        case $char === ',' && !is_null($nullStr):
                            $nullStr = $this->checkNullStr($nullStr);
                            switch ($this->currentObject->mode) {
                                case 'Array':
                                    $this->currentObject->arrayValues[] = $nullStr;
                                    break;
                                case 'Object':
                                    if (!empty($keyValue)) {
                                        $this->currentObject->objectValues[$keyValue] = $nullStr;
                                    }
                                    break;
                            }
                            $nullStr = null;
                            $keyValue = $valueValue = '';
                            $varMode = 'keyValue';
                            break;

                        //Switch mode to value collection after colon.
                        case in_array($char, $this->escapers):
                            break;

                        // Append char to null string.
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

                        // Closing double quotes.
                        case $char === '"':
                            $quote = false;
                            switch (true) {
                                // Closing qoute of Key
                                case $varMode === 'keyValue':
                                    $varMode = 'valueValue';
                                    break;
                                
                                // Closing qoute of Value
                                case $varMode === 'valueValue':
                                    $this->currentObject->objectValues[$keyValue] = $valueValue;
                                    $keyValue = $valueValue = '';
                                    $varMode = 'keyValue';
                                    break;
                            }
                            break;

                        // Collect values for key or value.
                        default:
                            $$varMode .= $char;
                    }
                    break;
            }
        }
        $this->objects = [];
        $this->currentObject = null;
        $this->previousObjectIndex = null;
    }

    /**
     * Handles array / object open close char
     *
     * @param string $char     Character among any one "[" "]" "{" "}".
     * @param string $keyValue String value of key of an object.
     * @param string $nullStr  String present in JSON without double quotes.
     * @param bool   $index    Index output.
     * @return array
     */
    private function handleOpenClose($char, $keyValue, $nullStr, $index)
    {
        $arr = false;
        switch ($char) {
            case '[':
                if (!$index) {
                    $arr = [
                        'key' => $this->keys($index),
                        'value' => $this->getObjectValues()
                    ];
                }
                $this->startArray($keyValue);
                $this->increment();
                break;
            case '{':
                if (!$index) {
                    $arr = [
                        'key' => $this->keys($index),
                        'value' => $this->getObjectValues()
                    ];
                }
                $this->startObject($keyValue);
                $this->increment();
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
                        'key' => $this->keys($index),
                        'value' => [
                            '_s_' => $this->currentObject->_s_,
                            '_e_' => $this->charCounter
                        ]
                    ];
                } else {
                    if (!empty($this->currentObject->arrayValues)) {
                        $arr = [
                            'key' => $this->keys(),
                            'value' => $this->currentObject->arrayValues
                        ];    
                    }
                }
                $this->currentObject->arrayValues = [];
                $this->endArray();
                break;
            case '}':
                if (!empty($keyValue) && !empty($nullStr)) {
                    $nullStr = $this->checkNullStr($nullStr);
                    $this->currentObject->objectValues[$keyValue] = $nullStr;
                }
                if ($index) {
                    $arr = [
                        'key' => $this->keys($index),
                        'value' => [
                            '_s_' => $this->currentObject->_s_,
                            '_e_' => $this->charCounter
                        ]
                    ];
                } else {
                    if (!empty($this->currentObject->objectValues)) {
                        $arr = [
                            'key' => $this->keys(),
                            'value' => $this->currentObject->objectValues
                        ];    
                    }
                }
                $this->currentObject->objectValues = [];
                $this->endObject();
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
     * @param string $nullStr String present in JSON without double quotes.
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
     * @param string $key Used while creating simple array inside an objectiative array and $key is the key.
     * @return void
     */
    private function startArray($key = null)
    {
        $this->pushCurrentObject($key);
        $this->setPreviousObjectIndex();
        $this->currentObject = new JsonDecoderObject('Array', $key);
        $this->currentObject->_s_ = $this->charCounter;
    }

    /**
     * End of array
     *
     * @return void
     */
    private function endArray()
    {
        $this->popCurrentObject();
        $this->setPreviousObjectIndex();
    }

    /**
     * Start of object
     *
     * @param string $key Used while creating objectiative array inside an objectiative array and $key is the key.
     * @return void
     */
    private function startObject($key = null)
    {
        $this->pushCurrentObject($key);
        $this->setPreviousObjectIndex();
        $this->currentObject = new JsonDecoderObject('Object', $key);
        $this->currentObject->_s_ = $this->charCounter;
    }

    /**
     * End of object
     *
     * @return void
     */
    private function endObject()
    {
        $this->popCurrentObject();
        $this->setPreviousObjectIndex();
    }

    /**
     * Push current object
     *
     * @return void
     */
    private function pushCurrentObject($key)
    {
        if ($this->currentObject) {
            if ($this->currentObject->mode === 'Object' && empty(trim($key))) {
                $this->isBadJson($key);
            }
            if ($this->currentObject->mode === 'Array' && !empty(trim($key))) {
                $this->isBadJson($key);
            }
            array_push($this->objects, $this->currentObject);
            $this->currentObject = null;
        }
    }

    /**
     * Pop current object
     *
     * @return void
     */
    private function popCurrentObject()
    {
        if (count($this->objects) > 0) {
            $this->currentObject = array_pop($this->objects);
        } else {
            $this->currentObject = null;
        }
    }

    /**
     * Sets previous object index
     *
     * @return void
     */
    private function setPreviousObjectIndex()
    {
        if (count($this->objects) > 0) {
            $this->previousObjectIndex = count($this->objects) - 1;
        } else {
            $this->previousObjectIndex = null;
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
            !is_null($this->previousObjectIndex) &&
            $this->objects[$this->previousObjectIndex]->mode === 'Array'
        ) {
            if (is_null($this->objects[$this->previousObjectIndex]->arrayKey)) {
                $this->objects[$this->previousObjectIndex]->arrayKey = 0;
            } else {
                $this->objects[$this->previousObjectIndex]->arrayKey++;
            }
        }
    }

    /**
     * Returns extracted object values.
     *
     * @return array
     */
    private function getObjectValues()
    {
        $arr = false;
        if (
            !is_null($this->currentObject) && 
            $this->currentObject->mode === 'Object' && 
            count($this->currentObject->objectValues) > 0
        ) {
            $arr = $this->currentObject->objectValues;
            $this->currentObject->objectValues = [];
        }
        return $arr;
    }

    /**
     * Check for a valid JSON.
     * 
     * @return void
     */
    private function isBadJson($str)
    {
        $str = trim($str);
        if (!empty($str)) {
            $this->response->end('Invalid JSON: ' . $str);
            die;
        }
    }

    /**
     * Generated Array.
     * 
     * @param bool $index Index output.
     * @return array
     */
    private function keys($index = false)
    {
        $keys = [];
        $return = &$keys;
        if (!$index && !empty($this->keys)) {
            foreach(explode(':', $this->keys) as $key) {
                $keys[$key] = [];
                $keys = &$keys[$key];
            }
        }
        $objCount = count($this->objects);
        if ($objCount > 0) {
            for ($i=1; $i<$objCount; $i++) {
                switch ($this->objects[$i]->mode) {
                    case 'Object':
                        if (!is_null($this->objects[$i]->objectKey)) {
                            if ($index) {
                                $keys[] = $this->objects[$i]->objectKey;
                            } else {
                                $keys[$this->objects[$i]->objectKey] = [];
                                $keys = &$keys[$this->objects[$i]->objectKey];
                            }
                        }
                        break;
                    case 'Array':
                        if (!is_null($this->objects[$i]->objectKey)) {
                            if ($index) {
                                $keys[] = $this->objects[$i]->objectKey;
                            } else {
                                $keys[$this->objects[$i]->objectKey] = [];
                                $keys = &$keys[$this->objects[$i]->objectKey];
                            }
                        }
                        if (!is_null($this->objects[$i]->arrayKey)) {
                            if ($index) {
                                $keys[] = $this->objects[$i]->arrayKey;
                            } else {
                                $keys[$this->objects[$i]->arrayKey] = [];
                                $keys = &$keys[$this->objects[$i]->arrayKey];
                            }
                        }
                        break;
                }
            }
        }
        if ($this->currentObject) {
            switch ($this->currentObject->mode) {
                case 'Object':
                    if (!is_null($this->currentObject->objectKey)) {
                        if ($index) {
                            $keys[] = $this->currentObject->objectKey;
                        } else {
                            $keys[$this->currentObject->objectKey] = [];
                            $keys = &$keys[$this->currentObject->objectKey];
                        }
                    }
                    break;
                case 'Array':
                    if (!is_null($this->currentObject->objectKey)) {
                        if ($index) {
                            $keys[] = $this->currentObject->objectKey;
                        } else {
                            $keys[$this->currentObject->objectKey] = [];
                            $keys = &$keys[$this->currentObject->objectKey];
                        }
                    }
                    break;
            }
        }
        return $return;
    }

    /**
     * Validate JSON in stream
     *
     * @return void
     */
    public function indexJSON()
    {
        $this->streamIndex = null;
        foreach ($this->process(true) as $keys => $val) {
            if (
                isset($val['_s_']) &&
                isset($val['_e_'])
            ) {
                $streamIndex = &$this->streamIndex;
                for ($i=0, $iCount = count($keys); $i < $iCount; $i++) {
                    if (is_numeric($keys[$i]) && !isset($streamIndex[$keys[$i]])) {
                        $streamIndex[$keys[$i]] = [];
                        if (!isset($streamIndex['_c_'])) {
                            $streamIndex['_c_'] = 0;
                        }
                        if (is_numeric($keys[$i])) {
                            $streamIndex['_c_']++;
                        }
                    }
                    $streamIndex = &$streamIndex[$keys[$i]];
                }
                $streamIndex['_s_'] = $val['_s_'];
                $streamIndex['_e_'] = $val['_e_'];
            }
        }
    }

    /**
     * Key exist.
     *
     * @param string $keys Key values seperated by colon.
     * @return boolean
     */
    public function keysAreSet($keys)
    {
        $return = true;
        $streamIndex = &$this->streamIndex;
        foreach (explode(':', $keys) as $key) {
            if (isset($streamIndex[$key])) {
                $streamIndex = &$streamIndex[$key];
            } else {
                $return = false;
                break;
            }
        }
        return $return;
    }

    /**
     * Key exist.
     *
     * @param string $keys Key values seperated by colon.
     * @return boolean
     */
    public function keysType($keys = null)
    {
        $streamIndex = &$this->streamIndex;
        if (!is_null($keys)) {
            foreach (explode(':', $keys) as $key) {
                if (isset($streamIndex[$key])) {
                    $streamIndex = &$streamIndex[$key];
                } else {
                    $this->response->end("Invalid key {$key}");
                    return;
                }
            }    
        }
        $return = 'Object';
        if (
            isset($streamIndex['_c_']) &&
            $streamIndex['_c_'] > 1
        ) {
            $return = 'Array';
        }
        return $return;
    }

    /**
     * Get count of array element.
     *
     * @param string $keys Key values seperated by colon.
     * @return integer
     */
    public function getCount($keys = null)
    {
        $streamIndex = &$this->streamIndex;
        if (!is_null($keys)) {
            foreach (explode(':', $keys) as $key) {
                if (isset($streamIndex[$key])) {
                    $streamIndex = &$streamIndex[$key];
                } else {
                    $this->response->end("Invalid key {$key}");
                    return;
                }
            }    
        }
        if (!isset($streamIndex['_c_'])) {
            $streamIndex['_c_'] = 1;
        }
        return $streamIndex['_c_'];
    }

    /**
     * Pass the keys and get whole json content belonging to keys.
     *
     * @param string $keys Key values seperated by colon.
     * @return array
     */
    public function get($keys = null)
    {
        $streamIndex = &$this->streamIndex;
        if (!is_null($keys)) {
            foreach (explode(':', $keys) as $key) {
                if (isset($streamIndex[$key])) {
                    $streamIndex = &$streamIndex[$key];
                } else {
                    $this->response->end("Invalid key {$key}");
                    return;
                }
            }    
        }
        if (
            isset($streamIndex['_s_']) &&
            isset($streamIndex['_e_'])
        ) {
            $start = $streamIndex['_s_'];
            $end = $streamIndex['_e_'];
        } else {
            $this->response->end("Invalid keys '{$keys}'");
            die;
        }

        $length = $end - $start + 1;
        $json = stream_get_contents($this->tempStream, $length, $start);

        return json_decode($json, true);
    }

    /**
     * Start processing the JSON string for a keys
     * Perform search inside keys of JSON like $json['data'][0]['data1']
     *
     * @param string $keys Key values seperated by colon.
     * @return void
     */
    public function load($keys)
    {
        if (empty($keys)) {
            $this->_s_ = null;
            $this->_e_ = null;
            $this->keys = null;
            return;
        }
        $streamIndex = &$this->streamIndex;
        foreach (explode(':', $keys) as $key) {
            if (isset($streamIndex[$key])) {
                $streamIndex = &$streamIndex[$key];
            } else {
                $this->response->end("Invalid key {$key}");
                return;
            }
        }
        if (
            isset($streamIndex['_s_']) &&
            isset($streamIndex['_e_'])
        ) {
            $this->_s_ = $streamIndex['_s_'];
            $this->_e_ = $streamIndex['_e_'];
            $this->keys = $keys;
        } else {
            $this->response->end("Invalid keys '{$keys}'");
            die;
        }
    }
}

/**
 * JSON Object
 *
 * This class is built to help maintain state of an Array or Object of JSON.
 *
 * @category   Json Decoder Object
 * @package    Microservices
 * @author     Ramesh Narayan Jangid
 * @copyright  Ramesh Narayan Jangid
 * @version    Release: @1.0.0@
 * @since      Class available since Release 1.0.0
 */
class JsonDecoderObject
{
    /**
     * JSON stream start position.
     *
     * @var integer
     */
    public $_s_ = null;

    /**
     * JSON stream end position.
     *
     * @var integer
     */
    public $_e_ = null;
    
    /**
     * Object / Array.
     *
     * @var string
     */
    public $mode = '';

    /**
     * Object key for parant object.
     *
     * @var string
     */
    public $objectKey = null;
    
    /**
     * Array key for parant object.
     *
     * @var string
     */
    public $arrayKey = null;

    /**
     * Object values.
     *
     * @var array
     */
    public $objectValues = [];

    /**
     * Array values.
     *
     * @var array
     */
    public $arrayValues = [];

    /**
     * Constructor
     *
     * @param string $mode Values can be one among Array/Object
     */
    public function __construct($mode, $objectKey = null)
    {
        $this->mode = $mode;
        $objectKey = trim($objectKey);
        $this->objectKey = !empty($objectKey) ? $objectKey : null;
    }
}
