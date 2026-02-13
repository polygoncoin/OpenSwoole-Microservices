<?php

/**
 * Handling PHP Raw Array details for Views
 * php version 8.3
 *
 * @category  DataEncode_PHP
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App\DataRepresentation\Encode;

use Microservices\App\DataRepresentation\Encode\DataEncodeInterface;
use Microservices\App\DataRepresentation\Encode\PhpEncoder\PhpEncoderObject;
use Microservices\App\HttpStatus;

/**
 * Generates PHP Array
 * php version 8.3
 *
 * @category  PHP_Encoder
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class PhpEncode implements DataEncodeInterface
{
    /**
     * Array
     *
     * @var null|array
     */
    public $finalArray = null;

    /**
     * Array of PhpEncoderObject objects
     *
     * @var PhpEncoderObject[]
     */
    private $objects = [];

    /**
     * Current PhpEncoderObject object
     *
     * @var null|PhpEncoderObject
     */
    private $currentObject = null;

    /**
     * Constructor
     *
     * @param resource $tempStream Temp stream Temporary stream
     * @param bool     $header     Append XML header flag
     */
    public function __construct(&$tempStream, $header = true)
    {
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
        if ($this->currentObject) {
            if ($this->currentObject->mode === 'Object') {
                if (is_array($data)) {
                    foreach ($data as $k => $v) {
                        $this->currentObject->returnArray[$k] = $this->escape(data: $v);
                    }
                }
            } else {
                if (is_array($data)) {
                    foreach ($data as $v) {
                        $this->currentObject->returnArray[] = $this->escape(data: $v);
                    }
                } else {
                    $this->currentObject->returnArray[] = $this->escape(data: $data);
                }
            }
        }
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
        $this->write(data: $data);
    }

    /**
     * Escape the json string key or value
     *
     * @param null|string|array $data Representation Data
     *
     * @return null|string|array
     */
    private function escape(&$data)
    {
        if ($data !== null) {
            if (is_array($data)) {
                foreach ($data as $k => $v) {
                    $data[$k] = $this->escape($v);
                }
            } else {
                $data = nl2br(htmlspecialchars($data));
            }
        }

        return $data;
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
        $this->write(data: $data);
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
            $this->write(data: [$key => $data]);
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
        $this->encode(data: [$key => $data]);
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
            array_push($this->objects, $this->currentObject);
        }
        $this->currentObject = new PhpEncoderObject(mode: 'Array');
        if ($key !== null) {
            $this->currentObject->key = $key;
        }
    }

    /**
     * End simple array
     *
     * @return void
     */
    public function endArray(): void
    {
        $key = $this->currentObject->key;
        $returnArray = &$this->currentObject->returnArray;
        $this->currentObject = null;
        if (count(value: $this->objects) > 0) {
            $this->currentObject = array_pop(array: $this->objects);
            if ($key !== '') {
                $this->currentObject->returnArray[$key] = &$returnArray;
            } else {
                $this->currentObject->returnArray[] = &$returnArray;
            }
        } else {
            $this->finalArray = &$returnArray;
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
            array_push($this->objects, $this->currentObject);
        }
        $this->currentObject = new PhpEncoderObject(mode: 'Object');
        if ($key !== null) {
            $this->currentObject->key = $key;
        }
    }

    /**
     * End associative array
     *
     * @return void
     */
    public function endObject(): void
    {
        $key = $this->currentObject->key;
        $returnArray = &$this->currentObject->returnArray;
        $this->currentObject = null;
        if (count(value: $this->objects) > 0) {
            $this->currentObject = array_pop(array: $this->objects);
            if ($key !== '') {
                $this->currentObject->returnArray[$key] = &$returnArray;
            } else {
                $this->currentObject->returnArray[] = &$returnArray;
            }
        } else {
            $this->finalArray = &$returnArray;
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
