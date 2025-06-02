<?php
namespace Microservices\App\DataRepresentation\Json;

use Microservices\App\Constants;
use Microservices\App\Common;
use Microservices\App\DataRepresentation\AbstractDataEncode;
use Microservices\App\Env;
use Microservices\App\HttpStatus;

/**
 * Generates Json
 *
 * This class is built to avoid creation of large array objects
 * (which leads to memory limit issues for larger data set)
 * which are then converted to JSON. This class gives access to
 * create JSON in parts for what ever smallest part of data
 * we have of the large data set which are yet to be fetched
 *
 * @category   Json Encoder
 * @package    Microservices
 * @author     Ramesh Narayan Jangid
 * @copyright  Ramesh Narayan Jangid
 * @version    Release: @1.0.0@
 * @since      Class available since Release 1.0.0
 */
class JsonEncode extends AbstractDataEncode
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
     * JsonEncode constructor
     *
     * @param resource $tempStream
     */
    public function __construct(&$tempStream)
    {
        $this->tempStream = &$tempStream;
    }

    /**
     * Write to temporary stream
     *
     * @param string $data Representation Data
     * @return void
     */
    private function write($data)
    {
        fwrite($this->tempStream, $data);
    }

    /**
     * Encodes both simple and associative array to json
     *
     * @param string|array $data Representation Data
     * @return void
     */
    public function encode($data)
    {
        if ($this->currentObject) {
            $this->write($this->currentObject->comma);
        }
        if (is_array($data)) {
            $this->write(json_encode($data));
        } else {
            $this->write($this->escape($data));
        }
        if ($this->currentObject) {
            $this->currentObject->comma = ',';
        }
    }

    /**
     * Escape the json string key or value
     *
     * @param null|string $data Representation Data
     * @return string
     */
    private function escape($data)
    {
        if (is_null($data)) return 'null';
        $data = str_replace($this->escapers, $this->replacements, $data);
        return "\"{$data}\"";
    }

    /**
     * Append raw json string
     *
     * @param string $data Reference of Representation Data
     * @return void
     */
    public function appendData(&$data)
    {
        if ($this->currentObject) {
            $this->write($this->currentObject->comma);
            $this->write($data);
            $this->currentObject->comma = ',';
        }
    }

    /**
     * Append raw json string
     *
     * @param string $key  key of associative array
     * @param string $data Reference of Representation Data
     * @return void
     */
    public function appendKeyData($key, &$data)
    {
        if ($this->currentObject && $this->currentObject->mode === 'Object') {
            $this->write($this->currentObject->comma);
            $this->write($this->escape($key) . ':' . $data);
            $this->currentObject->comma = ',';
        }
    }

    /**
     * Add simple array/value as in the json format
     *
     * @param string|array $data Representation Data
     * @return void
     * @throws \Exception
     */
    public function addArrayData($data)
    {
        if ($this->currentObject->mode !== 'Array') {
            throw new \Exception('Mode should be Array', HttpStatus::$InternalServerError);
        }
        $this->encode($data);
    }

    /**
     * Add simple array/value as in the json format
     *
     * @param string       $key  Key of associative array
     * @param string|array $data Representation Data
     * @return void
     * @throws \Exception
     */
    public function addKeyData($key, $data)
    {
        if ($this->currentObject->mode !== 'Object') {
            throw new \Exception('Mode should be Object', HttpStatus::$InternalServerError);
        }
        $this->write($this->currentObject->comma);
        $this->write($this->escape($key) . ':');
        $this->currentObject->comma = '';
        $this->encode($data);
    }

    /**
     * Start simple array
     *
     * @param null|string $key Used while creating simple array inside an associative array and $key is the key
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
     * @param null|string $key Used while creating associative array inside an associative array and $key is the key
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
