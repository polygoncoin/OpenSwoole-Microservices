<?php

/**
 * Handling XML Encode
 * php version 8.3
 *
 * @category  DataEncode_XML
 * @package   Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App\DataRepresentation\Xml;

use Microservices\App\DataRepresentation\Encode\DataEncodeInterface;
use Microservices\App\DataRepresentation\Encode\XmlEncoder\XmlEncoderObject;
use Microservices\App\HttpStatus;

/**
 * Generates XML
 * php version 8.3
 *
 * @category  Xml_Encoder
 * @package   Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Microservices
 * @since     Class available since Release 1.0.0
 */
class XmlEncode implements DataEncodeInterface
{
    /**
     * Temporary Stream
     *
     * @var null|resource
     */
    private $tempStream = null;

    /**
     * Array of XmlEncoderObject objects
     *
     * @var XmlEncoderObject[]
     */
    private $objects = [];

    /**
     * Current XmlEncoderObject object
     *
     * @var null|XmlEncoderObject
     */
    private $currentObject = null;

    /**
     * XmlEncode constructor
     *
     * @param resource $tempStream Temp stream Temporary stream
     * @param bool     $header     Append XML header flag
     */
    public function __construct(&$tempStream, $header = true)
    {
        $this->tempStream = &$tempStream;
        if ($header) {
            $xml = '<?xml version="1.0" encoding="UTF-8"?>';
            $this->write(data: $xml);
        }
    }

    /**
     * Initialize
     *
     * @param bool $header Append XML header flag
     *
     * @return void
     */
    public function init($header = true): void
    {
    }

    /**
     * Write to temporary stream
     *
     * @param string $data Representation Data
     *
     * @return void
     */
    private function write($data): void
    {
        fwrite(stream: $this->tempStream, data: $data);
    }

    /**
     * Encodes both simple and associative array to XML
     *
     * @param string|array $data Representation Data
     *
     * @return void
     */
    public function encode($data): void
    {
        if (is_array(value: $data)) {
            $isObject = (isset($data[0])) ? false : true;
            if (!$isObject) {
                $this->write(data: "<{$this->currentObject->key}>");
            }
            foreach ($data as $key => $value) {
                if (!is_array(value: $value)) {
                    $key = $this->escapeTag(key: $key);
                    $this->write(
                        data: "<{$key}>{$this->escape(data: $value)}</{$key}>"
                    );
                } else {
                    $this->addKeyData(key: $key, data: $value);
                }
            }
            if (!$isObject) {
                $this->write(data: "</{$this->currentObject->key}>");
            }
        } else {
            $this->write(data: $this->escape(data: $data));
        }
    }

    /**
     * Escape the XML string value
     *
     * @param null|string $data Representation Data
     *
     * @return string
     */
    private function escape($data): string
    {
        if ($data === null) {
            return 'null';
        }
        return htmlspecialchars(string: $data);
    }

    /**
     * Append raw XML string
     *
     * @param string $data Reference of Representation Data
     *
     * @return void
     */
    public function appendData(&$data): void
    {
        if ($this->currentObject) {
            $this->write(data: $data);
        }
    }

    /**
     * Append raw XML string
     *
     * @param string $key  Tag of associative array
     * @param string $data Reference of Representation Data
     *
     * @return void
     */
    public function appendKeyData($key, &$data): void
    {
        if ($this->currentObject && $this->currentObject->mode === 'Object') {
            $key = $this->escapeTag(key: $key);
            $this->write(data: "<{$key}>{$this->escape(data: $data)}</{$key}>");
        }
    }

    /**
     * Add simple array/value as in the XML format
     *
     * @param string|array $data Representation Data
     *
     * @return void
     * @throws \Exception
     */
    public function addArrayData($data): void
    {
        if ($this->currentObject->mode !== 'Array') {
            throw new \Exception(
                message: 'Mode should be Array',
                code: HttpStatus::$InternalServerError
            );
        }
        $this->encode(data: $data);
    }

    /**
     * Add simple array/value as in the XML format
     *
     * @param string       $key  Tag of associative array
     * @param string|array $data Representation Data
     *
     * @return void
     * @throws \Exception
     */
    public function addKeyData($key, $data): void
    {
        $this->startObject(key: $key);
        $this->encode(data: $data);
        $this->endObject();
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
        if ($key === null) {
            $key = 'Rows';
        }
        if ($this->currentObject) {
            array_push($this->objects, $this->currentObject);
        }
        $this->currentObject = new XmlEncoderObject(mode: 'Array', key: $key);
        $this->write(data: "<{$this->currentObject->key}>");
    }

    /**
     * End simple array
     *
     * @return void
     */
    public function endArray(): void
    {
        $this->write(data: "</{$this->currentObject->key}>");
        $this->currentObject = null;
        if (count(value: $this->objects) > 0) {
            $this->currentObject = array_pop($this->objects);
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
        if ($key === null) {
            $key = ($this->currentObject === null) ? 'Resultset' : 'Row';
        }
        if ($this->currentObject) {
            if ($this->currentObject->mode === 'Object' && ($key === null)) {
                throw new \Exception(
                    message: 'Object inside an Object should be supported with Key',
                    code: HttpStatus::$InternalServerError
                );
            }
            array_push($this->objects, $this->currentObject);
        }
        $this->currentObject = new XmlEncoderObject(mode: 'Object', key: $key);
        $this->write(data: "<{$this->currentObject->key}>");
    }

    /**
     * End associative array
     *
     * @return void
     */
    public function endObject(): void
    {
        $this->write(data: "</{$this->currentObject->key}>");
        $this->currentObject = null;
        if (count(value: $this->objects) > 0) {
            $this->currentObject = array_pop($this->objects);
        }
    }

    /**
     * Checks XML was properly closed
     *
     * @return void
     */
    public function end(): void
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
     * Checks XML was properly closed
     *
     * @param null|string $key Used while creating associative array inside an object
     *
     * @return array|string
     */
    private function escapeTag($key): array|string
    {
        return str_replace(search: ':', replace: '-', subject: $key);
    }
}
