<?php
namespace Microservices\App\DataRepresentation;

use Microservices\App\DataRepresentation\AbstractDataDecode;
use Microservices\App\DataRepresentation\Json\JsonDecode;
use Microservices\App\DataRepresentation\Xml\XmlDecode;
use Microservices\App\Env;

/**
 * Creates Data Representation Output
 *
 * @category   Data Decoder
 * @package    Microservices
 * @author     Ramesh Narayan Jangid
 * @copyright  Ramesh Narayan Jangid
 * @version    Release: @1.0.0@
 * @since      Class available since Release 1.0.0
 */
class DataDecode extends AbstractDataDecode
{
    /**
     * Json File Handle
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
     * @return void
     */
    public function __construct(&$dataFileHandle)
    {
        $this->dataFileHandle = &$dataFileHandle;

        if (Env::$inputDataRepresentation === 'Json') {
            $this->dataDecoder = new JsonDecode($this->dataFileHandle);
        } else {
            $this->dataDecoder = new XmlDecode($this->dataFileHandle);
        }
    }

    /**
     * Initialize
     *
     * @return void
     */
    public function init()
    {
        $this->dataDecoder->init();
    }

    /**
     * Validates data
     *
     * @return void
     */
    public function validate()
    {
        $this->dataDecoder->validate();
    }

    /**
     * Index data
     *
     * @return void
     */
    public function indexData()
    {
        $this->dataDecoder->indexData();
    }

    /**
     * Keys exist
     *
     * @param null|string $keys Keys exist (values seperated by colon)
     * @return boolean
     */
    public function isset($keys = null)
    {
        return $this->dataDecoder->isset($keys);
    }

    /**
     * Key exist
     *
     * @param null|string $keys Keys exist (values seperated by colon)
     * @return string Object/Array
     */
    public function dataType($keys = null)
    {
        return $this->dataDecoder->dataType($keys);
    }

    /**
     * Count of array element
     *
     * @param null|string $keys Key values seperated by colon
     * @return integer
     */
    public function count($keys = null)
    {
        return $this->dataDecoder->count($keys);
    }

    /**
     * Pass the keys and get whole raw data content belonging to keys
     *
     * @param string $keys Key values seperated by colon
     * @return bool|string
     */
    public function get($keys = '')
    {
        return $this->dataDecoder->get($keys);
    }

    /**
     * Get complete array for keys
     *
     * @param string $keys Key values seperated by colon
     * @return bool|array
     */
    public function getCompleteArray($keys = '')
    {
        return $this->dataDecoder->getCompleteArray($keys);
    }

    /**
     * Start processing the JSON string for a keys
     * Perform search inside keys of JSON like $json['data'][0]['data1']
     *
     * @param string $keys Key values seperated by colon
     * @return void
     * @throws \Exception
     */
    public function load($keys)
    {
        $this->dataDecoder->load($keys);
    }
}