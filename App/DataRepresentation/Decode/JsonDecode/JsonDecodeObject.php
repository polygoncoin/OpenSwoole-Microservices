<?php

/**
 * Handling JSON formats
 * php version 8.3
 *
 * @category  DataDecode
 * @package   Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App\DataRepresentation\Decode\JsonDecode;

/**
 * JSON object
 * php version 8.3
 *
 * @category  JSON_Decode_Object
 * @package   Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Microservices
 * @since     Class available since Release 1.0.0
 */
class JsonDecodeObject
{
    /**
     * JSON file start position
     *
     * @var null|int
     */
    public $sIndex = null;

    /**
     * JSON file end position
     *
     * @var null|int
     */
    public $eIndex = null;

    /**
     * Assoc / Array
     *
     * @var string
     */
    public $mode = '';

    /**
     * Assoc key for parant object
     *
     * @var null|string
     */
    public $assocKey = null;

    /**
     * Array key for parant object
     *
     * @var null|string
     */
    public $arrayKey = null;

    /**
     * Object values
     *
     * @var array
     */
    public $assocValues = [];

    /**
     * Array values
     *
     * @var array
     */
    public $arrayValues = [];

    /**
     * Constructor
     *
     * @param string $mode     Values can be one among Array
     * @param string $assocKey Key for object
     */
    public function __construct($mode, $assocKey = null)
    {
        $this->mode = $mode;

        $assocKey = $assocKey !== null ? trim(string: $assocKey) : $assocKey;
        $this->assocKey = !empty($assocKey) ? $assocKey : null;
    }
}
