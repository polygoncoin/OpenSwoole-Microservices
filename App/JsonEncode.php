<?php
namespace Microservices\App;

use Microservices\App\Constants;
use Microservices\App\Common;
use Microservices\App\Env;
use Microservices\App\HttpStatus;
use Microservices\App\Logs;

/**
 * Creates JSON
 *
 * This class is built to avoid creation of large array objects
 * (which leads to memory limit issues for larger data set)
 * which are then converted to JSON. This class gives access to
 * create JSON in parts for what ever smallest part of data
 * we have of the large data set which are yet to be fetched
 *
 * @category   JSON Encoder
 * @package    Microservices
 * @author     Ramesh Narayan Jangid
 * @copyright  Ramesh Narayan Jangid
 * @version    Release: @1.0.0@
 * @since      Class available since Release 1.0.0
 */
class JsonEncode
{
    /**
     * Temporary Stream
     *
     * @var null|resource
     */
    private $tempStream = null;

    /**
     * Array of JsonEncoderObject objects
     *
     * @var JsonEncoderObject[]
     */
    private $objects = [];

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
    private $escapers = array("\\", "\"", "\n", "\r", "\t", "\x08", "\x0c", ' ');

    /**
     * Characters that are escaped with for $escapers while creating JSON
     *
     * @var string[]
     */
    private $replacements = array("\\\\", "\\\"", "\\n", "\\r", "\\t", "\\f", "\\b", ' ');

    /**
     * Microservices Request Details
     *
     * @var null|array
     */
    public $httpRequestDetails = null;

    /**
     * JsonEncode constructor
     *
     * @param array $httpRequestDetails
     */
    public function __construct(&$httpRequestDetails)
    {
        $this->httpRequestDetails = &$httpRequestDetails;
    }

    /**
     * Initialize
     *
     * @return boolean
     */
    public function init()
    {
        if ($this->httpRequestDetails['server']['request_method'] === 'GET') {
            $this->tempStream = fopen("php://temp", "rw+b");
        } else {
            $this->tempStream = fopen("php://memory", "rw+b");
        }
    }

    /**
     * Write to temporary stream
     *
     * @return void
     */
    public function write($str)
    {
        fwrite($this->tempStream, $str);
    }

    /**
     * Escape the json string key or value
     *
     * @param string $str json key or value string
     * @return string
     */
    private function escape($str)
    {
        if (is_null($str)) return 'null';
        $str = str_replace($this->escapers, $this->replacements, $str);
        return "\"{$str}\"";
    }

    /**
     * Encodes both simple and associative array to json
     *
     * @param $arr string value escaped and array value json_encode function is applied
     * @return void
     */
    public function encode($arr)
    {
        if ($this->currentObject) {
            $this->write($this->currentObject->comma);
        }
        if (is_array($arr)) {
            $this->write(json_encode($arr));
        } else {
            $this->write($this->escape($arr));
        }
        if ($this->currentObject) {
            $this->currentObject->comma = ',';
        }
    }

    /**
     * Add simple array/value as in the json format
     *
     * @param $value data type is string/array. This is used to add value/array in the current Array
     * @return void
     * @throws \Exception
     */
    public function addValue($value)
    {
        if ($this->currentObject->mode !== 'Array') {
            throw new \Exception('Mode should be Array', HttpStatus::$InternalServerError);
        }
        $this->encode($value);
    }

    /**
     * Add simple array/value as in the json format
     *
     * @param string $key   key of associative array
     * @param        $value data type is string/array. This is used to add value/array in the current Array
     * @return void
     * @throws \Exception
     */
    public function addKeyValue($key, $value)
    {
        if ($this->currentObject->mode !== 'Object') {
            throw new \Exception('Mode should be Object', HttpStatus::$InternalServerError);
        }
        $this->write($this->currentObject->comma);
        $this->write($this->escape($key) . ':');
        $this->currentObject->comma = '';
        $this->encode($value);
    }

    /**
     * Start simple array
     *
     * @param string $key Used while creating simple array inside an associative array and $key is the key
     * @return void
     */
    public function startArray($key = null)
    {
        if ($this->currentObject) {
            $this->write($this->currentObject->comma);
            array_push($this->objects, $this->currentObject);
        }
        $this->currentObject = new JsonEncoderObject('Array');
        if (!is_null($key)) {
            $this->write($this->escape($key) . ':');
        }
        $this->write('[');
    }

    /**
     * End simple array
     *
     * @return void
     */
    public function endArray()
    {
        $this->write(']');
        $this->currentObject = null;
        if (count($this->objects)>0) {
            $this->currentObject = array_pop($this->objects);
            $this->currentObject->comma = ',';
        }
    }

    /**
     * Start simple array
     *
     * @param string $key Used while creating associative array inside an associative array and $key is the key
     * @return void
     * @throws \Exception
     */
    public function startObject($key = null)
    {
        if ($this->currentObject) {
            if ($this->currentObject->mode === 'Object' && is_null($key)) {
                throw new \Exception('Object inside an Object should be supported with a Key', HttpStatus::$InternalServerError);
            }
            $this->write($this->currentObject->comma);
            array_push($this->objects, $this->currentObject);
        }
        $this->currentObject = new JsonEncoderObject('Object');
        if (!is_null($key)) {
            $this->write($this->escape($key) . ':');
        }
        $this->write('{');
    }

    /**
     * End associative array
     *
     * @return void
     */
    public function endObject()
    {
        $this->write('}');
        $this->currentObject = null;
        if (count($this->objects)>0) {
            $this->currentObject = array_pop($this->objects);
            $this->currentObject->comma = ',';
        }
    }

    /**
     * Checks json was properly closed
     *
     * @return void
     */
    public function end()
    {
        while ($this->currentObject && $this->currentObject->mode) {
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

    /**
     * Stream Json String
     *
     * @return void
     */
    public function streamJson()
    {
        // Log request details
        rewind($this->tempStream);
        $log = [
            'datetime' => date('Y-m-d H:i:s'),
            'GET' => $_GET,
            'php:input' => @file_get_contents('php://input'),
            'php:output' => stream_get_contents($this->tempStream)
        ];
        (new Logs)->log('info', json_encode($log));

        // Stream JSON
        rewind($this->tempStream);
        $json = stream_get_contents($this->tempStream);
        fclose($this->tempStream);
        return $json;
    }

    /**
     * destruct functipn
     */
    public function __destruct()
    {
    }
}

/**
 * JSON Object
 *
 * This class is built to help maintain state of simple/associative array
 *
 * @category   Json Encoder Object
 * @package    Microservices
 * @author     Ramesh Narayan Jangid
 * @copyright  Ramesh Narayan Jangid
 * @version    Release: @1.0.0@
 * @since      Class available since Release 1.0.0
 */
class JsonEncoderObject
{
    public $mode = '';
    public $comma = '';

    /**
     * Constructor
     *
     * @param string $mode Values can be one among Array/Object
     */
    public function __construct($mode)
    {
        $this->mode = $mode;
    }
}