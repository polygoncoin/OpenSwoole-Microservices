<?php

/**
 * Creates Data Representation Input
 * php version 8.3
 *
 * @category  DataDecode
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App\DataRepresentation;

use Microservices\App\DataRepresentation\Decode\JsonDecode;
use Microservices\App\DataRepresentation\Decode\XmlDecode;

/**
 * Creates Data Representation Output
 * php version 8.3
 *
 * @category  DataDecoder
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class DataDecode
{
	/**
	 * JSON File Handle
	 *
	 * @var null|resource
	 */
	private $dataFileHandle = null;

	/**
	 * Temporary Stream
	 *
	 * @var null|Object
	 */
	private $dataDecoder = null;

	/**
	 * Constructor
	 *
	 * @param string   $iRepresentation Input Representation
	 * @param resource $dataFileHandle  File handle
	 */
	public function __construct($iRepresentation, &$dataFileHandle)
	{
		$this->dataFileHandle = &$dataFileHandle;

		if ($iRepresentation === 'JSON') {
			$this->dataDecoder = new JsonDecode(
				jsonFileHandle: $this->dataFileHandle
			);
		} else {
			$this->dataDecoder = new XmlDecode(
				jsonFileHandle: $this->dataFileHandle
			);
		}
	}

	/**
	 * Initialize
	 *
	 * @return bool
	 */
	public function init(): bool
	{
		return $this->dataDecoder->init();
	}

	/**
	 * Validates data
	 *
	 * @return void
	 */
	public function validate(): void
	{
		$this->dataDecoder->validate();
	}

	/**
	 * Index data
	 *
	 * @return void
	 */
	public function indexData(): void
	{
		$this->dataDecoder->indexData();
	}

	/**
	 * Result exist as per $keyString
	 *
	 * @param null|string $keyString Key's exist (values separated by colon)
	 *
	 * @return bool
	 */
	public function isset($keyString = null): bool
	{
		return $this->dataDecoder->isset(keyString: $keyString);
	}

	/**
	 * Datatype of result as per $keyString
	 *
	 * @param null|string $keyString Key's exist (values separated by colon)
	 *
	 * @return string Object/Array
	 */
	public function dataType($keyString = null): string
	{
		return $this->dataDecoder->dataType(keyString: $keyString);
	}

	/**
	 * Count of result as per $keyString
	 *
	 * @param null|string $keyString Key values separated by colon
	 *
	 * @return int
	 */
	public function count($keyString = null): int
	{
		return $this->dataDecoder->count(keyString: $keyString);
	}

	/**
	 * Get result as per $keyString
	 *
	 * @param string $keyString Key values separated by colon
	 *
	 * @return mixed
	 */
	public function get($keyString = ''): mixed
	{
		return $this->dataDecoder->get(keyString: $keyString);
	}

	/**
	 * Get complete result as per $keyString
	 *
	 * @param string $keyString Key values separated by colon
	 *
	 * @return mixed
	 */
	public function getCompleteArray($keyString = ''): mixed
	{
		return $this->dataDecoder->getCompleteArray(keyString: $keyString);
	}

	/**
	 * Load result as per $keyString
	 * Start processing the JSON string for a key's
	 * Perform search inside key's of JSON like $json['data'][0]['data1']
	 *
	 * @param string $keyString Key values separated by colon
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function load($keyString): void
	{
		$this->dataDecoder->load(keyString: $keyString);
	}
}
