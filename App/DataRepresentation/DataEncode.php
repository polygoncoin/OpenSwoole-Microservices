<?php
namespace Microservices\App\DataRepresentation;

use Microservices\App\DataRepresentation\AbstractDataEncode;
use Microservices\App\DataRepresentation\Json\JsonEncode;
use Microservices\App\DataRepresentation\Xml\XmlEncode;
use Microservices\App\Env;

/**
 * Creates Data Representation Output
 *
 * @category   Data Encoder
 * @package    Microservices
 * @author     Ramesh Narayan Jangid
 * @copyright  Ramesh Narayan Jangid
 * @version    Release: @1.0.0@
 * @since      Class available since Release 1.0.0
 */
class DataEncode extends AbstractDataEncode
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
    public $httpRequestDetails = null;

    /**
     * Temporary Stream
     *
     * @var null|AbstractDataEncode
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
     * @param array $httpRequestDetails
     */
    public function __construct(&$httpRequestDetails)
    {
        $this->httpRequestDetails = &$httpRequestDetails;
    }

    /**
     * Initialize
     *
     * @return boolean
     */
    public function init($header = true)
    {
        if ($this->httpRequestDetails['server']['request_method'] === 'GET') {
            $this->tempStream = fopen("php://temp", "rw+b");
        } else {
            $this->tempStream = fopen("php://memory", "rw+b");
        }
        switch (Env::$outputDataRepresentation) {
            case 'Xml':
                $this->dataEncoder = new XmlEncode($this->tempStream, $header);
                break;
            case 'Json':
                $this->dataEncoder = new JsonEncode($this->tempStream, $header);
                break;
            default:
                $this->dataEncoder = new JsonEncode($this->tempStream, $header);
                break;
        }
    }

    /**
     * Start simple array
     *
     * @param null|string $key Used while creating simple array inside an associative array and $key is the key
     * @return void
     */
    public function startArray($key = null)
    {
        $this->dataEncoder->startArray($key);
    }

    /**
     * Add simple array/value as in the data format
     *
     * @param string|array $data Representation Data
     * @return void
     * @throws \Exception
     */
    public function addArrayData($data)
    {
        $this->dataEncoder->addArrayData($data);
    }

    /**
     * End simple array
     *
     * @return void
     */
    public function endArray()
    {
        $this->dataEncoder->endArray();
    }

    /**
     * Start simple array
     *
     * @param null|string $key Used while creating associative array inside an associative array and $key is the key
     * @return void
     * @throws \Exception
     */
    public function startObject($key = null)
    {
        $this->dataEncoder->startObject($key);
    }

    /**
     * Add simple array/value as in the data format
     *
     * @param string       $key  Key of associative array
     * @param string|array $data Representation Data
     * @return void
     * @throws \Exception
     */
    public function addKeyData($key, $data)
    {
        $this->dataEncoder->addKeyData($key, $data);
    }

    /**
     * End associative array
     *
     * @return void
     */
    public function endObject()
    {
        $this->dataEncoder->endObject();
    }

    /**
     * Encodes both simple and associative array to json
     *
     * @param string|array $data Representation Data
     * @return void
     */
    public function encode($data)
    {
        $this->dataEncoder->encode($data);
    }

    /**
     * Append raw data string
     *
     * @param string $data Representation Data
     * @return void
     */
    public function appendData(&$data)
    {
        $this->dataEncoder->appendData($data);
    }

    /**
     * Append raw data string
     *
     * @param string $key  key of associative array
     * @param string $data Representation Data
     * @return void
     */
    public function appendKeyData($key, &$data)
    {
        $this->dataEncoder->appendKeyData($key, $data);
    }

    /**
     * Checks data was properly closed
     *
     * @return void
     */
    public function end()
    {
        $this->dataEncoder->end();
    }

    /**
     * Stream Data String
     *
     * @return void
     */
    public function streamData()
    {
        return $this->getData();
    }

    /**
     * Return Json String
     *
     * @return void
     */
    public function getData()
    {
        $this->end();

        rewind($this->tempStream);
        $json = stream_get_contents($this->tempStream);
        fclose($this->tempStream);

        return $json;
    }
}
