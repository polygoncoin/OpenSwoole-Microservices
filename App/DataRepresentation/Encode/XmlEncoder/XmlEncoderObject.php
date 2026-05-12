<?php

/**
 * Handling XML Encode
 * php version 8.3
 *
 * @category  DataEncode_XML
 * @package   Openswoole_Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
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
 * @package   Openswoole_Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class XmlEncoderObject
{
	public $mode = '';
	public $objectKey = '';

	/**
	 * Constructor
	 *
	 * @param string      $mode      Values can be one among Array/object
	 * @param null|string $objectKey Tag
	 */
	public function __construct($mode, $objectKey)
	{
		$this->mode = $mode;
		if ($objectKey !== null) {
			$this->objectKey = str_replace(search: ':', replace: '-', subject: $objectKey);
		} else {
			$this->objectKey = $objectKey;
		}
	}
}
