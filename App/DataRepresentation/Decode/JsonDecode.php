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

namespace Microservices\App\DataRepresentation\Decode;

use Generator;
use Microservices\App\DataRepresentation\Decode\DataDecodeInterface;
use Microservices\App\DataRepresentation\Decode\JsonDecode\JsonDecodeEngine;
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
class JsonDecode implements DataDecodeInterface
{
    /**
     * JSON File Handle
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
     * Allowed Payload length
     *
     * @var int
     */
    private $allowedPayloadLength = 100 * 1024 * 1024; // 100 MB

    /**
     * JSON Decode Engine object
     *
     * @var null|JsonDecodeEngine
     */
    private $jsonDecodeEngine = null;

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
        $this->jsonFileHandle = &$jsonFileHandle;

        // File Stats - Check for size
        $fileStats = fstat(stream: $this->jsonFileHandle);
        if (
            isset($fileStats['size'])
            && $fileStats['size'] > $this->allowedPayloadLength
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
        // Init JSON Decode Engine
        $this->jsonDecodeEngine = new JsonDecodeEngine(
            jsonFileHandle: $this->jsonFileHandle
        );
    }

    /**
     * Validates JSON
     *
     * @return void
     */
    public function validate(): void
    {
        foreach ($this->jsonDecodeEngine->process() as $keyArr => $valueArr) {
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
        foreach ($this->jsonDecodeEngine->process(index: true) as $keys => $val) {
            if (isset($val['sIndex']) && isset($val['eIndex'])) {
                $jsonFileIndex = &$this->jsonFileIndex;
                for ($i = 0, $iCount = count(value: $keys); $i < $iCount; $i++) {
                    if (
                        is_numeric(value: $keys[$i])
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
        if (($keys !== null) && strlen(string: $keys) !== 0) {
            $jsonFileIndex = &$this->jsonFileIndex;
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
        if (($keys !== null) && strlen(string: $keys) > 0) {
            foreach (explode(separator: ':', string: $keys) as $key) {
                if (isset($jsonFileIndex[$key])) {
                    $jsonFileIndex = &$jsonFileIndex[$key];
                } else {
                    throw new \Exception(
                        message: "Key '{$key}' not found",
                        code: HttpStatus::$BadRequest
                    );
                }
            }
        }

        $return = 'Object';
        if (isset($jsonFileIndex['_c_'])) {
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
        if (($keys !== null) && strlen(string: $keys) !== 0) {
            foreach (explode(separator: ':', string: $keys) as $key) {
                if (isset($jsonFileIndex[$key])) {
                    $jsonFileIndex = &$jsonFileIndex[$key];
                } else {
                    throw new \Exception(
                        message: "Key '{$key}' not found",
                        code: HttpStatus::$BadRequest
                    );
                }
            }
        }

        $count = 0;
        if (isset($jsonFileIndex['sIndex']) && isset($jsonFileIndex['eIndex'])) {
            $count = 1;
        }
        if (isset($jsonFileIndex['_c_'])) {
            $count = (int)$jsonFileIndex['_c_'];
        }
        return $count;
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
        foreach ($this->jsonDecodeEngine->process() as $valueArr) {
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
            json: $this->jsonDecodeEngine->getJsonString(),
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
        if (in_array(needle: $keys, haystack: [null, ''])) {
            $this->jsonDecodeEngine->sIndex = null;
            $this->jsonDecodeEngine->eIndex = null;
            return;
        }
        $jsonFileIndex = &$this->jsonFileIndex;
        if (($keys !== null) && strlen(string: $keys) !== 0) {
            foreach (explode(separator: ':', string: $keys) as $key) {
                if (isset($jsonFileIndex[$key])) {
                    $jsonFileIndex = &$jsonFileIndex[$key];
                } else {
                    throw new \Exception(
                        message: "Key '{$key}' not found",
                        code: HttpStatus::$BadRequest
                    );
                }
            }
        }
        if (isset($jsonFileIndex['sIndex']) && isset($jsonFileIndex['eIndex'])) {
            $this->jsonDecodeEngine->sIndex = (int)$jsonFileIndex['sIndex'];
            $this->jsonDecodeEngine->eIndex = (int)$jsonFileIndex['eIndex'];
        } else {
            throw new \Exception(
                message: "Invalid keys '{$keys}'",
                code: HttpStatus::$BadRequest
            );
        }
    }
}
