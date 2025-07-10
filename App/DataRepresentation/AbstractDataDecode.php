<?php
/**
 * Data Decode
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
namespace Microservices\App\DataRepresentation;

/**
 * Data Decode Abstract class
 * php version 8.3
 *
 * @category  DataDecode_Abstract_Class
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
abstract class AbstractDataDecode
{
    /**
     * Validates data
     *
     * @return void
     */
    abstract public function validate(): void;

    /**
     * Index data
     *
     * @return void
     */
    abstract public function indexData(): void;

    /**
     * Keys exist
     *
     * @param null|string $keys Keys exist (values separated by colon)
     *
     * @return bool
     */
    abstract public function isset($keys = null): bool;

    /**
     * Key exist
     *
     * @param null|string $keys Keys exist (values separated by colon)
     *
     * @return string Object/Array
     */
    abstract public function dataType($keys = null): string;

    /**
     * Count of array element
     *
     * @param null|string $keys Key values separated by colon
     *
     * @return int
     */
    abstract public function count($keys = null): int;

    /**
     * Pass the keys and get whole data content belonging to keys
     *
     * @param string $keys Key values separated by colon
     *
     * @return mixed
     */
    abstract public function get($keys = ''): mixed;

    /**
     * Get complete Data for Kays
     *
     * @param string $keys Key values separated by colon
     *
     * @return mixed
     */
    abstract public function getCompleteArray($keys = ''): mixed;

    /**
     * Start processing the data string for a keys
     * Perform search inside keys of data like $data['data'][0]['data1']
     *
     * @param string $keys Key values separated by colon
     *
     * @return void
     * @throws \Exception
     */
    abstract public function load($keys): void;
}
