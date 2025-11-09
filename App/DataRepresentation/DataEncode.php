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

use Microservices\App\DataRepresentation\Encode\JsonEncode;
use Microservices\App\DataRepresentation\Encode\XmlEncode;
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
class DataEncode
{
    /**
     * Temporary Stream
     *
     * @var null|resource
     */
    private $tempStream = null;

    /**
     * Microservices Request Details
     *
     * @var null|array
     */
    public $http = null;

    /**
     * Temporary Stream
     *
     * @var null|Object
     */
    private $dataEncoder = null;

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
        if ($this->http['server']['method'] === 'GET') {
            $this->tempStream = fopen(filename: "php://temp", mode: "rw+b");
        } else {
            $this->tempStream = fopen(filename: "php://memory", mode: "rw+b");
        }
        switch (Env::$oRepresentation) {
            case 'XML':
            case 'HTML':
                $this->dataEncoder = new XmlEncode(
                    tempStream: $this->tempStream,
                    header: $header
                );
                break;
            case 'JSON':
                $this->dataEncoder = new JsonEncode(
                    tempStream: $this->tempStream,
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
        $this->dataEncoder->startArray(key: $key);
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
        $this->dataEncoder->addArrayData(data: $data);
    }

    /**
     * End simple array
     *
     * @return void
     */
    public function endArray(): void
    {
        $this->dataEncoder->endArray();
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
        $this->dataEncoder->startObject(key: $key);
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
        $this->dataEncoder->addKeyData(key: $key, data: $data);
    }

    /**
     * End associative array
     *
     * @return void
     */
    public function endObject(): void
    {
        $this->dataEncoder->endObject();
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
        $this->dataEncoder->encode(data: $data);
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
        $this->dataEncoder->appendData(data: $data);
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
        $this->dataEncoder->appendKeyData(key: $key, data: $data);
    }

    /**
     * Checks data was properly closed
     *
     * @return void
     */
    public function end(): void
    {
        $this->dataEncoder->end();
    }

    /**
     * Stream Data String
     *
     * @return void
     */
    public function streamData(): void
    {
        $this->end();
        rewind(stream: $this->tempStream);

        if (
            in_array(Env::$oRepresentation, ['XML', 'HTML'])
            && ($this->XSLT !== null)
            && file_exists(filename: $this->XSLT)
        ) {
            $xml = new \DOMDocument();
            $xml->loadXML(source: stream_get_contents(stream: $this->tempStream));

            $xslt = new \XSLTProcessor();
            $XSL = new \DOMDocument();
            $XSL->load(filename: $this->XSLT);
            $xslt->importStylesheet(stylesheet: $XSL);
            echo $xslt->transformToXML(document: $xml);
        } else {
            $outputStream = fopen(filename: 'php://output', mode: 'wb');
            stream_copy_to_stream(from: $this->tempStream, to: $outputStream);
            fclose(stream: $outputStream);
        }
        fclose(stream: $this->tempStream);
    }

    /**
     * Return JSON String
     *
     * @return bool|string
     */
    public function getData(): bool|string
    {
        $this->end();

        rewind(stream: $this->tempStream);
        $streamContent = stream_get_contents(stream: $this->tempStream);
        fclose(stream: $this->tempStream);

        return $streamContent;
    }
}
