<?php
namespace Microservices\App\DataRepresentation;

/**
 * Data Decode Abstract class
 *
 * @category   Abstract Data Decode Class
 * @package    Microservices
 * @author     Ramesh Narayan Jangid
 * @copyright  Ramesh Narayan Jangid
 * @version    Release: @1.0.0@
 * @since      Class available since Release 1.0.0
 */
abstract class AbstractDataDecode
{
    /**
     * Validates data
     *
     * @return void
     */
    abstract public function validate();

    /**
     * Index data
     *
     * @return void
     */
    abstract public function indexData();

    /**
     * Keys exist
     *
     * @param null|string $keys Keys exist (values seperated by colon)
     * @return boolean
     */
    abstract public function isset($keys = null);

    /**
     * Key exist
     *
     * @param null|string $keys Keys exist (values seperated by colon)
     * @return string Object/Array
     */
    abstract public function dataType($keys = null);

    /**
     * Count of array element
     *
     * @param null|string $keys Key values seperated by colon
     * @return integer
     */
    abstract public function count($keys = null);

    /**
     * Pass the keys and get whole data content belonging to keys
     *
     * @param string $keys Key values seperated by colon
     * @return bool|string
     */
    abstract public function get($keys = '');

    /**
     * Get complete Data for Kays
     *
     * @param string $keys Key values seperated by colon
     * @return bool|array
     */
    abstract public function getCompleteArray($keys = '');

    /**
     * Start processing the data string for a keys
     * Perform search inside keys of data like $data['data'][0]['data1']
     *
     * @param string $keys Key values seperated by colon
     * @return void
     * @throws \Exception
     */
    abstract public function load($keys);
}
