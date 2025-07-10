<?php
/**
 * Handling XML Encode
 * php version 8.3
 *
 * @category  DataEncode_XML
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
namespace Microservices\App\DataRepresentation\Xml;

use Microservices\App\DataRepresentation\AbstractDataEncode;
use Microservices\App\HttpStatus;

/**
 * Generates Xml
 * php version 8.3
 *
 * @category  Xml_Encoder
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class XmlEncode extends AbstractDataEncode
{
    /**
     * Temporary Stream
     *
     * @var null|resource
     */
    private $_tempStream = null;

    /**
     * Array of XmlEncoderObject objects
     *
     * @var XmlEncoderObject[]
     */
    private $_objects = [];

    /**
     * Current XmlEncoderObject object
     *
     * @var null|XmlEncoderObject
     */
    private $_currentObject = null;

    /**
     * XmlEncode constructor
     *
     * @param resource $tempStream Temp stream Temporary stream
     * @param bool     $header     Append XML header flag
     */
    public function __construct(&$tempStream, $header = true)
    {
        $this->_tempStream = &$tempStream;
        if ($header) {
            $xml = '<?xml version="1.0" encoding="UTF-8"?>';
            $this->_write(data: $xml);
        }
    }

    /**
     * Write to temporary stream
     *
     * @param string $data Representation Data
     *
     * @return void
     */
    private function _write($data): void
    {
        fwrite(stream: $this->_tempStream, data: $data);
    }

    /**
     * Encodes both simple and associative array to Xml
     *
     * @param string|array $data Representation Data
     *
     * @return void
     */
    public function encode($data): void
    {
        if (is_array(value: $data)) {
            $isAssoc = (isset($data[0])) ? false : true;
            if (!$isAssoc) {
                $this->_write(data: "<{$this->_currentObject->tag}>");
            }
            foreach ($data as $tag => $value) {
                if (!is_array(value: $value)) {
                    $tag = $this->_escapeTag(tag: $tag);
                    $this->_write(
                        data: "<{$tag}>{$this->_escape(data: $value)}</{$tag}>"
                    );
                } else {
                    $this->addKeyData(tag: $tag, data: $value);
                }
            }
            if (!$isAssoc) {
                $this->_write(data: "</{$this->_currentObject->tag}>");
            }
        } else {
            $this->_write(data: $this->_escape(data: $data));
        }
    }

    /**
     * Escape the Xml string value
     *
     * @param null|string $data Representation Data
     *
     * @return string
     */
    private function _escape($data): string
    {
        if (is_null(value: $data)) return 'null';
        return htmlspecialchars(string: $data);
    }

    /**
     * Append raw Xml string
     *
     * @param string $data Reference of Representation Data
     *
     * @return void
     */
    public function appendData(&$data): void
    {
        if ($this->_currentObject) {
            $this->_write(data: $data);
        }
    }

    /**
     * Append raw Xml string
     *
     * @param string $tag  Tag of associative array
     * @param string $data Reference of Representation Data
     *
     * @return void
     */
    public function appendKeyData($tag, &$data): void
    {
        if ($this->_currentObject && $this->_currentObject->mode === 'Object') {
            $tag = $this->_escapeTag(tag: $tag);
            $this->_write(data: "<{$tag}>{$this->_escape(data: $data)}</{$tag}>");
        }
    }

    /**
     * Add simple array/value as in the Xml format
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
     * Add simple array/value as in the Xml format
     *
     * @param string       $tag  Tag of associative array
     * @param string|array $data Representation Data
     *
     * @return void
     * @throws \Exception
     */
    public function addKeyData($tag, $data): void
    {
        $this->startObject(tag: $tag);
        $this->encode(data: $data);
        $this->endObject();
    }

    /**
     * Start simple array
     *
     * @param null|string $tag Used while creating simple array inside an object
     *
     * @return void
     */
    public function startArray($tag = null): void
    {
        if (is_null(value: $tag)) {
            $tag = 'Rows';
        }
        if ($this->_currentObject) {
            array_push($this->_objects, $this->_currentObject);
        }
        $this->_currentObject = new XmlEncoderObject(mode: 'Array', tag: $tag);
        $this->_write(data: "<{$this->_currentObject->tag}>");
    }

    /**
     * End simple array
     *
     * @return void
     */
    public function endArray(): void
    {
        $this->_write(data: "</{$this->_currentObject->tag}>");
        $this->_currentObject = null;
        if (count(value: $this->_objects)>0) {
            $this->_currentObject = array_pop($this->_objects);
        }
    }

    /**
     * Start simple array
     *
     * @param null|string $tag Used while creating associative array inside an object
     *
     * @return void
     * @throws \Exception
     */
    public function startObject($tag = null): void
    {
        if (is_null(value: $tag)) {
            $tag = (is_null(value: $this->_currentObject)) ? 'Resultset' : 'Row';
        }
        if ($this->_currentObject) {
            if ($this->_currentObject->mode === 'Object' && is_null(value: $tag)) {
                throw new \Exception(
                    message: 'Object inside an Object should be supported with Key',
                    code: HttpStatus::$InternalServerError
                );
            }
            array_push($this->_objects, $this->_currentObject);
        }
        $this->_currentObject = new XmlEncoderObject(mode: 'Object', tag: $tag);
        $this->_write(data: "<{$this->_currentObject->tag}>");
    }

    /**
     * End associative array
     *
     * @return void
     */
    public function endObject(): void
    {
        $this->_write(data: "</{$this->_currentObject->tag}>");
        $this->_currentObject = null;
        if (count(value: $this->_objects)>0) {
            $this->_currentObject = array_pop($this->_objects);
        }
    }

    /**
     * Checks Xml was properly closed
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

    /**
     * Checks Xml was properly closed
     *
     * @param null|string $tag Used while creating associative array inside an object
     *
     * @return array|string
     */
    private function _escapeTag($tag): array|string
    {
        return str_replace(search: ':', replace: '-', subject: $tag);
    }
}

/**
 * Xml Object
 *
 * This class is built to help maintain state of simple/associative array
 * php version 8.3
 *
 * @category  Xml_Encoder_Object
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class XmlEncoderObject
{
    public $mode = '';
    public $tag = '';

    /**
     * Constructor
     *
     * @param string      $mode Values can be one among Array/Object
     * @param null|string $tag  Tag
     */
    public function __construct($mode, $tag)
    {
        $this->mode = $mode;
        if (!is_null(value: $tag)) {
            $this->tag = str_replace(search: ':', replace: '-', subject: $tag);
        } else {
            $this->tag = $tag;
        }

    }
}
