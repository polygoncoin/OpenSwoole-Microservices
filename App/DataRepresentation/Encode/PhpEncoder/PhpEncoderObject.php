<?php

/**
 * Handling PHP Encode
 * php version 8.3
 *
 * @category  DataEncode_PHP
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App\DataRepresentation\Encode\PhpEncoder;

/**
 * PHP object
 *
 * This class is built to help maintain state of simple/associative array
 * php version 8.3
 *
 * @category  Php_Encoder_Object
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class PhpEncoderObject
{
    public $mode = '';
    public $key = '';
    public $returnArray = [];

    /**
     * Constructor
     *
     * @param string      $mode Values can be one among Array/object
     * @param null|string $key  Tag
     */
    public function __construct($mode, $key = '')
    {
        $this->mode = $mode;
        $this->key = $key;
    }
}
