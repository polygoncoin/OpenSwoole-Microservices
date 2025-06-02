<?php
namespace Microservices\App\DataRepresentation;

/**
 * Loading database server
 *
 * This abstract class is built to handle Data Representation Encoding
 *
 * @category   Abstract Data Encode Class
 * @package    Microservices
 * @author     Ramesh Narayan Jangid
 * @copyright  Ramesh Narayan Jangid
 * @version    Release: @1.0.0@
 * @since      Class available since Release 1.0.0
 */
abstract class AbstractDataEncode
{
    /**
     * Start simple array
     *
     * @param null|string $key Used while creating simple array inside an associative array and $key is the key
     * @return void
     */
    abstract public function startArray($key = null);

    /**
     * Add simple array/value as in the data format
     *
     * @param string|array $data Representation Data
     * @return void
     * @throws \Exception
     */
    abstract public function addArrayData($data);

    /**
     * End simple array
     *
     * @return void
     */
    abstract public function endArray();

    /**
     * Start simple array
     *
     * @param null|string $key Used while creating associative array inside an associative array and $key is the key
     * @return void
     * @throws \Exception
     */
    abstract public function startObject($key = null);

    /**
     * Add simple array/value as in the data format
     *
     * @param string       $key  Key of associative array
     * @param string|array $data Representation Data
     * @return void
     * @throws \Exception
     */
    abstract public function addKeyData($key, $data);

    /**
     * End associative array
     *
     * @return void
     */
    abstract public function endObject();

    /**
     * Encodes both simple and associative array to json
     *
     * @param string|array $data Representation Data
     * @return void
     */
    abstract public function encode($data);

    /**
     * Append raw data string
     *
     * @param string $data Reference of Representation Data
     * @return void
     */
    abstract public function appendData(&$data);

    /**
     * Append raw data string
     *
     * @param string $key  key of associative array
     * @param string $data Reference of Representation Data
     * @return void
     */
    abstract public function appendKeyData($key, &$data);

    /**
     * Checks data was properly closed
     *
     * @return void
     */
    abstract public function end();
}
