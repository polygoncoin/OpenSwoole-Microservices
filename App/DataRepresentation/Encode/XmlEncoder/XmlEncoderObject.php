<?php

/**
 * Handling XML Encode
 * php version 8.3
 *
 * @category  DataEncode_XML
 * @package   Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App\DataRepresentation\Encode\XmlEncoder;

/**
 * XML object
 *
 * This class is built to help maintain state of simple/associative array
 * php version 8.3
 *
 * @category  Xml_Encoder_Object
 * @package   Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Microservices
 * @since     Class available since Release 1.0.0
 */
class XmlEncoderObject
{
    public $mode = '';
    public $key = '';

    /**
     * Constructor
     *
     * @param string      $mode Values can be one among Array/object
     * @param null|string $key  Tag
     */
    public function __construct($mode, $key)
    {
        $this->mode = $mode;
        if ($key !== null) {
            $this->key = str_replace(search: ':', replace: '-', subject: $key);
        } else {
            $this->key = $key;
        }
    }
}
