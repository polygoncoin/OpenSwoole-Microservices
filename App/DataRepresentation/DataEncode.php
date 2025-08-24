<?php
/**
 * Creates Data Representation Output
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
namespace Microservices\App\DataRepresentation;

use Microservices\App\DataRepresentation\AbstractDataEncode;
use Microservices\App\DataRepresentation\Json\JsonEncode;
use Microservices\App\DataRepresentation\Xml\XmlEncode;
use Microservices\App\Env;

/**
 * Creates Data Representation Output
 * php version 8.3
 *
 * @category  DataEncoder
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class DataEncode extends AbstractDataEncode
{
    /**
     * Temporary Stream
     *
     * @var null|resource
     */
    private $_tempStream = null;

    /**
     * Microservices Request Details
     *
     * @var null|array
     */
    public $http = null;

    /**
     * Temporary Stream
     *
     * @var null|AbstractDataEncode
     */
    private $_dataEncoder = null;

    /**
     * XSLT
     *
     * @var null|string
     */
    public $XSLT = null;

    /**
     * DataEncode constructor
     *
     * @param array $http HTTP request details
     */
    public function __construct(&$http)
    {
        $this->http = &$http;
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
        if ($this->http['server']['request_method'] === 'GET') {
            $this->_tempStream = fopen(filename: "php://temp", mode: "rw+b");
        } else {
            $this->_tempStream = fopen(filename: "php://memory", mode: "rw+b");
        }
        switch (Env::$outputRepresentation) {
        case 'XML':
            $this->_dataEncoder = new XmlEncode(
                tempStream: $this->_tempStream,
                header: $header
            );
            break;
        case 'JSON':
            $this->_dataEncoder = new JsonEncode(
                tempStream: $this->_tempStream,
                header: $header
            );
            break;
        }
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
        $this->_dataEncoder->startArray(key: $key);
    }

    /**
     * Add simple array/value as in the data format
     *
     * @param string|array $data Representation Data
     *
     * @return void
     * @throws \Exception
     */
    public function addArrayData($data): void
    {
        $this->_dataEncoder->addArrayData(data: $data);
    }

    /**
     * End simple array
     *
     * @return void
     */
    public function endArray(): void
    {
        $this->_dataEncoder->endArray();
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
        $this->_dataEncoder->startObject(key: $key);
    }

    /**
     * Add simple array/value as in the data format
     *
     * @param string       $key  Key of associative array
     * @param string|array $data Representation Data
     *
     * @return void
     * @throws \Exception
     */
    public function addKeyData($key, $data): void
    {
        $this->_dataEncoder->addKeyData(key: $key, data: $data);
    }

    /**
     * End associative array
     *
     * @return void
     */
    public function endObject(): void
    {
        $this->_dataEncoder->endObject();
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
        $this->_dataEncoder->encode(data: $data);
    }

    /**
     * Append raw data string
     *
     * @param string $data Representation Data
     *
     * @return void
     */
    public function appendData(&$data): void
    {
        $this->_dataEncoder->appendData(data: $data);
    }

    /**
     * Append raw data string
     *
     * @param string $key  key of associative array
     * @param string $data Representation Data
     *
     * @return void
     */
    public function appendKeyData($key, &$data): void
    {
        $this->_dataEncoder->appendKeyData(key: $key, data: $data);
    }

    /**
     * Checks data was properly closed
     *
     * @return void
     */
    public function end(): void
    {
        $this->_dataEncoder->end();
    }

    /**
     * Stream Data String
     *
     * @return bool|string
     */
    public function streamData(): bool|string
    {
        return $this->getData();
    }

    /**
     * Return Json String
     *
     * @return bool|string
     */
    public function getData(): bool|string
    {
        $this->end();

        rewind(stream: $this->_tempStream);
        $json = stream_get_contents(stream: $this->_tempStream);
        fclose(stream: $this->_tempStream);

        return $json;
    }
}
