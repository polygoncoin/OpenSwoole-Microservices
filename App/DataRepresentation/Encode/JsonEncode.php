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

namespace Microservices\App\DataRepresentation\Encode;

use Microservices\App\DataRepresentation\Encode\DataEncodeInterface;
use Microservices\App\DataRepresentation\Encode\JsonEncode\JsonEncodeObject;
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
class JsonEncode implements DataEncodeInterface
{
    /**
     * Temporary Stream
     *
     * @var null|resource
     */
    private $tempStream = null;

    /**
     * Array of JsonEncodeObject objects
     *
     * @var JsonEncodeObject[]
     */
    private $objects = [];

    /**
     * Current JsonEncodeObject object
     *
     * @var null|JsonEncodeObject
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
     * JsonEncode constructor
     *
     * @param resource $tempStream Temp stream Temporary stream
     * @param bool     $header     Append XML header flag
     */
    public function __construct(&$tempStream, $header = true)
    {
        $this->tempStream = &$tempStream;
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
     * Encodes both simple and associative array to json
     *
     * @param string|array $data Representation Data
     *
     * @return void
     */
    public function encode($data): void
    {
        if ($this->currentObject) {
            $this->write(data: $this->currentObject->comma);
        }
        if (is_array(value: $data)) {
            $this->write(data: json_encode(value: $data));
        } else {
            $this->write(data: $this->escape(data: $data));
        }
        if ($this->currentObject) {
            $this->currentObject->comma = ', ';
        }
    }

    /**
     * Escape the json string key or value
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
        $data = str_replace(
            search: $this->escapers,
            replace: $this->replacements,
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
        if ($this->currentObject) {
            $this->write(data: $this->currentObject->comma);
            $this->write(data: $data);
            $this->currentObject->comma = ', ';
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
        if ($this->currentObject && $this->currentObject->mode === 'Object') {
            $this->write(data: $this->currentObject->comma);
            $this->write(data: $this->escape(data: $key) . ':' . $data);
            $this->currentObject->comma = ', ';
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
        if ($this->currentObject->mode !== 'Array') {
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
        if ($this->currentObject->mode !== 'Object') {
            throw new \Exception(
                message: 'Mode should be Object',
                code: HttpStatus::$InternalServerError
            );
        }
        $this->write(data: $this->currentObject->comma);
        $this->write(data: $this->escape(data: $key) . ':');
        $this->currentObject->comma = '';
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
        if ($this->currentObject) {
            $this->write(data: $this->currentObject->comma);
            array_push($this->objects, $this->currentObject);
        }
        $this->currentObject = new JsonEncodeObject(mode: 'Array');
        if ($key !== null) {
            $this->write(data: $this->escape(data: $key) . ':');
        }
        $this->write(data: '[');
    }

    /**
     * End simple array
     *
     * @return void
     */
    public function endArray(): void
    {
        $this->write(data: ']');
        $this->currentObject = null;
        if (count(value: $this->objects) > 0) {
            $this->currentObject = array_pop(array: $this->objects);
            $this->currentObject->comma = ', ';
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
        if ($this->currentObject) {
            if ($this->currentObject->mode === 'Object' && ($key === null)) {
                throw new \Exception(
                    message: 'Object inside an Object should be supported with Key',
                    code: HttpStatus::$InternalServerError
                );
            }
            $this->write(data: $this->currentObject->comma);
            array_push($this->objects, $this->currentObject);
        }
        $this->currentObject = new JsonEncodeObject(mode: 'Object');
        if ($key !== null) {
            $this->write(data: $this->escape(data: $key) . ':');
        }
        $this->write(data: '{');
    }

    /**
     * End associative array
     *
     * @return void
     */
    public function endObject(): void
    {
        $this->write(data: '}');
        $this->currentObject = null;
        if (count(value: $this->objects) > 0) {
            $this->currentObject = array_pop(array: $this->objects);
            $this->currentObject->comma = ', ';
        }
    }

    /**
     * Checks json was properly closed
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
}
