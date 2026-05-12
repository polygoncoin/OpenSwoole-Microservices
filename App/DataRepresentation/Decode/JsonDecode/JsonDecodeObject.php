<?php

/**
 * Handling JSON formats
 * php version 8.3
 *
 * @category  DataDecode
 * @package   Openswoole_Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App\DataRepresentation\Decode\JsonDecode;

/**
 * JSON object
 * php version 8.3
 *
 * @category  JSON_Decode_Object
 * @package   Openswoole_Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
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
	 * Object / Array
	 *
	 * @var string
	 */
	public $mode = '';

	/**
	 * Object key for parant object
	 *
	 * @var null|string
	 */
	public $objectKey = null;

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
	public $objectValueArr = [];

	/**
	 * Array values
	 *
	 * @var array
	 */
	public $arrayValueArr = [];

	/**
	 * Constructor
	 *
	 * @param string $mode      Values can be one among Array
	 * @param string $objectKey Key for object
	 */
	public function __construct($mode, $objectKey = null)
	{
		$this->mode = $mode;

		$objectKey = $objectKey !== null ? trim(string: $objectKey) : $objectKey;
		$this->objectKey = !empty($objectKey) ? $objectKey : null;
	}
}
