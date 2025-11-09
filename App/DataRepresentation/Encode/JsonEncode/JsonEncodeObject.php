<?php

/**
 * Handling JSON Encode
 * php version 8.3
 *
 * @category  DataEncode_JSON
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App\DataRepresentation\Encode\JsonEncode;

/**
 * JSON object
 *
 * This class is built to help maintain state of simple/associative array
 * php version 8.3
 *
 * @category  Json_Encoder_Object
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class JsonEncodeObject
{
    public $mode = '';
    public $comma = '';

    /**
     * Constructor
     *
     * @param string $mode Values can be one among Array/object
     */
    public function __construct($mode)
    {
        $this->mode = $mode;
    }
}
