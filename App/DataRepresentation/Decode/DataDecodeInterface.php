<?php

/**
 * Data Decode
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

namespace Microservices\App\DataRepresentation\Decode;

/**
 * Data Decode Interface
 * php version 8.3
 *
 * @category  DataDecode_Interface
 * @package   Openswoole_Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
interface DataDecodeInterface
{
	/**
	 * Initialize
	 *
	 * @return void
	 */
	public function init(): void;

	/**
	 * Validates data
	 *
	 * @return void
	 */
	public function validate(): void;

	/**
	 * Index data
	 *
	 * @return void
	 */
	public function indexData(): void;

	/**
	 * Result exist as per $keyString
	 *
	 * @param null|string $keyString Key's exist (values separated by colon)
	 *
	 * @return bool
	 */
	public function isset($keyString = null): bool;

	/**
	 * Datatype of result as per $keyString
	 *
	 * @param null|string $keyString Key's exist (values separated by colon)
	 *
	 * @return string Object/Array
	 */
	public function dataType($keyString = null): string;

	/**
	 * Count of result as per $keyString
	 *
	 * @param null|string $keyString Key values separated by colon
	 *
	 * @return int
	 */
	public function count($keyString = null): int;

	/**
	 * Get result as per $keyString
	 *
	 * @param string $keyString Key values separated by colon
	 *
	 * @return mixed
	 */
	public function get($keyString = ''): mixed;

	/**
	 * Get complete result as per $keyString
	 *
	 * @param string $keyString Key values separated by colon
	 *
	 * @return mixed
	 */
	public function getCompleteArray($keyString = ''): mixed;

	/**
	 * Load result as per $keyString
	 * Start processing the data string for a key's
	 * Perform search inside key's of data like $data['data'][0]['data1']
	 *
	 * @param string $keyString Key values separated by colon
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function load($keyString): void;
}
