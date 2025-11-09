<?php

/**
 * Data Encode
 * php version 8.3
 *
 * @category  DataEncode
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App\DataRepresentation\Encode;

/**
 * Data Encode Interface
 * php version 8.3
 *
 * @category  DataEncode_Interface
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
interface DataEncodeInterface
{
    /**
     * Initialize
     *
     * @param bool $header Append XML header flag
     *
     * @return void
     */
    public function init($header = true): void;

    /**
     * Start simple array
     *
     * @param null|string $key Used while creating simple array inside an object
     *
     * @return void
     */
    public function startArray($key = null): void;

    /**
     * Add simple array/value as in the data format
     *
     * @param string|array $data Representation Data
     *
     * @return void
     * @throws \Exception
     */
    public function addArrayData($data): void;

    /**
     * End simple array
     *
     * @return void
     */
    public function endArray(): void;

    /**
     * Start simple array
     *
     * @param null|string $key Used while creating associative array inside an object
     *
     * @return void
     * @throws \Exception
     */
    public function startObject($key = null): void;

    /**
     * Add simple array/value as in the data format
     *
     * @param string       $key  Key of associative array
     * @param string|array $data Representation Data
     *
     * @return void
     * @throws \Exception
     */
    public function addKeyData($key, $data): void;

    /**
     * End associative array
     *
     * @return void
     */
    public function endObject(): void;

    /**
     * Encodes both simple and associative array to json
     *
     * @param string|array $data Representation Data
     *
     * @return void
     */
    public function encode($data): void;

    /**
     * Append raw data string
     *
     * @param string $data Reference of Representation Data
     *
     * @return void
     */
    public function appendData(&$data): void;

    /**
     * Append raw data string
     *
     * @param string $key  key of associative array
     * @param string $data Reference of Representation Data
     *
     * @return void
     */
    public function appendKeyData($key, &$data): void;

    /**
     * Checks data was properly closed
     *
     * @return void
     */
    public function end(): void;
}
