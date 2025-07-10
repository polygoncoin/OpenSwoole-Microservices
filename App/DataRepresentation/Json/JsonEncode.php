<?php
/**
 * Handling JSON Encode
 * php version 8.3
 *
 * @category  DataEncode_JSON
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
namespace Microservices\App\DataRepresentation\Json;

use Microservices\App\DataRepresentation\AbstractDataEncode;
use Microservices\App\HttpStatus;

/**
 * Creates JSON string
 * php version 8.3
 *
 * @category  JSON_Encoder
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class JsonEncode extends AbstractDataEncode
{
    /**
     * Temporary Stream
     *
     * @var null|resource
     */
    private $_tempStream = null;

    /**
     * Array of JsonEncoderObject objects
     *
     * @var JsonEncoderObject[]
     */
    private $_objects = [];

    /**
     * Current JsonEncoderObject object
     *
     * @var null|JsonEncoderObject
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
     * JsonEncode constructor
     *
     * @param resource $tempStream Temp stream Temporary stream
     * @param bool     $header     Append XML header flag
     */
    public function __construct(&$tempStream, $header = true)
    {
        $this->_tempStream = &$tempStream;
    }

    /**
     * Write to temporary stream
     *
     * @param string $data Representation Data
     *
     * @return void
     */
    private function _write($data)
    {
        fwrite(stream: $this->_tempStream, data: $data);
    }

    /**
     * Encodes both simple and associative array to json
     *
     * @param string|array $data Representation Data
     *
     * @return void
     */
    public function encode($data): void
    {
        if ($this->_currentObject) {
            $this->_write(data: $this->_currentObject->comma);
        }
        if (is_array(value: $data)) {
            $this->_write(data: json_encode(value: $data));
        } else {
            $this->_write(data: $this->_escape(data: $data));
        }
        if ($this->_currentObject) {
            $this->_currentObject->comma = ', ';
        }
    }

    /**
     * Escape the json string key or value
     *
     * @param null|string $data Representation Data
     *
     * @return string
     */
    private function _escape($data): string
    {
        if (is_null(value: $data)) return 'null';
        $data = str_replace(
            search: $this->_escapers,
            replace: $this->_replacements,
            subject: $data
        );
        return "\"{$data}\"";
    }

    /**
     * Append raw json string
     *
     * @param string $data Reference of Representation Data
     *
     * @return void
     */
    public function appendData(&$data): void
    {
        if ($this->_currentObject) {
            $this->_write(data: $this->_currentObject->comma);
            $this->_write(data: $data);
            $this->_currentObject->comma = ', ';
        }
    }

    /**
     * Append raw json string
     *
     * @param string $key  key of associative array
     * @param string $data Reference of Representation Data
     *
     * @return void
     */
    public function appendKeyData($key, &$data): void
    {
        if ($this->_currentObject && $this->_currentObject->mode === 'Object') {
            $this->_write(data: $this->_currentObject->comma);
            $this->_write(data: $this->_escape(data: $key) . ':' . $data);
            $this->_currentObject->comma = ', ';
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
    public function addArrayData($data): void
    {
        if ($this->_currentObject->mode !== 'Array') {
            throw new \Exception(
                message: 'Mode should be Array',
                code: HttpStatus::$InternalServerError
            );
        }
        $this->encode(data: $data);
    }

    /**
     * Add simple array/value as in the json format
     *
     * @param string       $key  Key of associative array
     * @param string|array $data Representation Data
     *
     * @return void
     * @throws \Exception
     */
    public function addKeyData($key, $data): void
    {
        if ($this->_currentObject->mode !== 'Object') {
            throw new \Exception(
                message: 'Mode should be Object',
                code: HttpStatus::$InternalServerError
            );
        }
        $this->_write(data: $this->_currentObject->comma);
        $this->_write(data: $this->_escape(data: $key) . ':');
        $this->_currentObject->comma = '';
        $this->encode(data: $data);
    }

    /**
     * Start simple array
     *
     * @param null|string $key Used while creating simple array inside an object
     *
     * @return void
     */
    public function startArray($key = null): void
    {
        if ($this->_currentObject) {
            $this->_write(data: $this->_currentObject->comma);
            array_push($this->_objects, $this->_currentObject);
        }
        $this->_currentObject = new JsonEncoderObject(mode: 'Array');
        if (!is_null(value: $key)) {
            $this->_write(data: $this->_escape(data: $key) . ':');
        }
        $this->_write(data: '[');
    }

    /**
     * End simple array
     *
     * @return void
     */
    public function endArray(): void
    {
        $this->_write(data: ']');
        $this->_currentObject = null;
        if (count(value: $this->_objects)>0) {
            $this->_currentObject = array_pop(array: $this->_objects);
            $this->_currentObject->comma = ', ';
        }
    }

    /**
     * Start simple array
     *
     * @param null|string $key Used while creating associative array inside an object
     *
     * @return void
     * @throws \Exception
     */
    public function startObject($key = null): void
    {
        if ($this->_currentObject) {
            if ($this->_currentObject->mode === 'Object' && is_null(value: $key)) {
                throw new \Exception(
                    message: 'Object inside an Object should be supported with Key',
                    code: HttpStatus::$InternalServerError
                );
            }
            $this->_write(data: $this->_currentObject->comma);
            array_push($this->_objects, $this->_currentObject);
        }
        $this->_currentObject = new JsonEncoderObject(mode: 'Object');
        if (!is_null(value: $key)) {
            $this->_write(data: $this->_escape(data: $key) . ':');
        }
        $this->_write(data: '{');
    }

    /**
     * End associative array
     *
     * @return void
     */
    public function endObject(): void
    {
        $this->_write('}');
        $this->_currentObject = null;
        if (count(value: $this->_objects)>0) {
            $this->_currentObject = array_pop(array: $this->_objects);
            $this->_currentObject->comma = ', ';
        }
    }

    /**
     * Checks json was properly closed
     *
     * @return void
     */
    public function end(): void
    {
        while ($this->_currentObject && $this->_currentObject->mode) {
            switch ($this->_currentObject->mode) {
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
 * php version 8.3
 *
 * @category  Json_Encoder_Object
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
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
