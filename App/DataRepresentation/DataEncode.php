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

use Microservices\App\DataRepresentation\Encode\PhpEncode;
use Microservices\App\DataRepresentation\Encode\JsonEncode;
use Microservices\App\DataRepresentation\Encode\XmlEncode;
use Microservices\App\Common;

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
     * @var null|resource|array
     */
    private $tempStream = null;

    /**
     * Api common Object
     *
     * @var null|Common
     */
    private $api = null;

    /**
     * Temporary Stream
     *
     * @var null|Object
     */
    private $dataEncoder = null;

    /**
     * XSLT file
     *
     * @var null|string
     */
    public $xsltFile = null;

    /**
     * HTML file
     *
     * @var null|string
     */
    public $htmlFile = null;

    /**
     * PHP file
     *
     * @var null|string
     */
    public $phpFile = null;

    /**
     * Constructor
     *
     * @param Common $api
     */
    public function __construct(Common &$api)
    {
        $this->api = &$api;
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
        if ($this->api->http['server']['method'] === 'GET') {
            if ($this->api->res->oRepresentation === 'PHP') {
                $this->tempStream = [];    
            } else {
                $this->tempStream = fopen(filename: "php://temp", mode: "rw+b");
            }
        } else {
            if ($this->api->res->oRepresentation === 'PHP') {
                $this->tempStream = [];    
            } else {
                $this->tempStream = fopen(filename: "php://memory", mode: "rw+b");
            }
        }
        switch ($this->api->res->oRepresentation) {
            case 'JSON':
                $this->dataEncoder = new JsonEncode(
                    tempStream: $this->tempStream,
                    header: $header
                );
                break;
            case 'PHP':
                $this->dataEncoder = new PhpEncode(
                    tempStream: $this->tempStream,
                    header: $header
                );
                break;
            case 'XML':
            case 'XSLT':
            case 'HTML':
                $this->dataEncoder = new XmlEncode(
                    tempStream: $this->tempStream,
                    header: $header
                );
                break;
            default:
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

        switch (true) {
            case (
                    $this->api->res->oRepresentation === 'XSLT'
                    && $this->xsltFile !== null
                    && file_exists(filename: $this->xsltFile)
                ):
                echo $this->processXml($this->xsltFile);
                fclose(stream: $this->tempStream);
                break;
            case (
                    $this->api->res->oRepresentation === 'HTML'
                    && $this->htmlFile !== null
                    && file_exists(filename: $this->htmlFile)
                ):
                echo $this->processXml($this->htmlFile);
                fclose(stream: $this->tempStream);
                break;
            case (
                    $this->api->res->oRepresentation === 'PHP'
                    && $this->phpFile !== null
                    && file_exists(filename: $this->phpFile)
                ):
                $finalArray = &$this->tempStream->finalArray;
                include_once $this->phpFile;
                $this->tempStream = null;
                break;
            default:
                rewind(stream: $this->tempStream);
                $outputStream = fopen(filename: 'php://output', mode: 'wb');
                stream_copy_to_stream(from: $this->tempStream, to: $outputStream);
                fclose(stream: $outputStream);
                fclose(stream: $this->tempStream);
                break;
        }
    }

    /**
     * Return JSON String
     *
     * @return bool|string
     */
    public function getData(): bool|string
    {
        $this->end();

        switch (true) {
            case (
                    $this->api->res->oRepresentation === 'XSLT'
                    && $this->xsltFile !== null
                    && file_exists(filename: $this->xsltFile)
                ):
                $streamContent = $this->processXml($this->xsltFile);
                fclose(stream: $this->tempStream);
                break;
            case (
                    $this->api->res->oRepresentation === 'HTML'
                    && $this->htmlFile !== null
                    && file_exists(filename: $this->htmlFile)
                ):
                $streamContent = $this->processXml($this->htmlFile);
                fclose(stream: $this->tempStream);
                break;
            case (
                    $this->api->res->oRepresentation === 'PHP'
                    && $this->phpFile !== null
                    && file_exists(filename: $this->phpFile)
                ):
                $finalArray = &$this->dataEncoder->finalArray;
                ob_clean();
                include_once $this->phpFile;
                $streamContent = ob_get_clean();
                $this->tempStream = null;
                break;
            default:
                rewind(stream: $this->tempStream);
                $streamContent = stream_get_contents(stream: $this->tempStream);
                fclose(stream: $this->tempStream);
                break;
        }

        return $streamContent;
    }

    /**
     * Generate XML(XSLT)/HTML data
     *
     * @param string $xmlFile XML file location
     *
     * @return string
     */
    private function processXml($xmlFile)
    {
        rewind(stream: $this->tempStream);
        $xml = new \DOMDocument();
        $xml->loadXML(source: stream_get_contents(stream: $this->tempStream));

        $xslt = new \XSLTProcessor();
        $XSL = new \DOMDocument();
        $XSL->load(filename: $this->xmlFile);
        $xslt->importStylesheet(stylesheet: $XSL);
        return $xslt->transformToXML(document: $xml);
    }
}
