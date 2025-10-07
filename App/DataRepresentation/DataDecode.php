<?php

/**
 * Creates Data Representation Input
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

use Microservices\App\DataRepresentation\AbstractDataDecode;
use Microservices\App\DataRepresentation\Json\JsonDecode;
use Microservices\App\DataRepresentation\Xml\XmlDecode;
use Microservices\App\Env;

/**
 * Creates Data Representation Output
 * php version 8.3
 *
 * @category  DataDecoder
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class DataDecode extends AbstractDataDecode
{
    /**
     * JSON File Handle
     *
     * @var null|resource
     */
    private $dataFileHandle = null;

    /**
     * Temporary Stream
     *
     * @var null|AbstractDataDecode
     */
    private $dataDecoder = null;

    /**
     * JsonDecode constructor
     *
     * @param resource $dataFileHandle File handle
     */
    public function __construct(&$dataFileHandle)
    {
        $this->dataFileHandle = &$dataFileHandle;

        if (Env::$iRepresentation === 'JSON') {
            $this->dataDecoder = new JsonDecode(
                jsonFileHandle: $this->dataFileHandle
            );
        } else {
            $this->dataDecoder = new XmlDecode(
                jsonFileHandle: $this->dataFileHandle
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
        $this->dataDecoder->init();
    }

    /**
     * Validates data
     *
     * @return void
     */
    public function validate(): void
    {
        $this->dataDecoder->validate();
    }

    /**
     * Index data
     *
     * @return void
     */
    public function indexData(): void
    {
        $this->dataDecoder->indexData();
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
        return $this->dataDecoder->isset(keys: $keys);
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
        return $this->dataDecoder->dataType(keys: $keys);
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
        return $this->dataDecoder->count(keys: $keys);
    }

    /**
     * Pass the keys and get whole raw data content belonging to keys
     *
     * @param string $keys Key values separated by colon
     *
     * @return mixed
     */
    public function get($keys = ''): mixed
    {
        return $this->dataDecoder->get(keys: $keys);
    }

    /**
     * Get complete array for keys
     *
     * @param string $keys Key values separated by colon
     *
     * @return mixed
     */
    public function getCompleteArray($keys = ''): mixed
    {
        return $this->dataDecoder->getCompleteArray(keys: $keys);
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
        $this->dataDecoder->load(keys: $keys);
    }
}
