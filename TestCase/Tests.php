<?php

/**
 * Test Case
 * php version 8.3
 *
 * @category  Tests
 * @package   Openswoole_Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\TestCase;

/**
 * Tests
 * php version 8.3
 *
 * @category  Tests
 * @package   Openswoole_Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class Tests
{
	/**
	 * Process Auth based request
	 *
	 * @return array
	 */
	public function processTests(): array
	{
		$response = [];
		$response[] = $this->processAuth();
		$response[] = $this->processOpen();
		$response[] = $this->processXml();
		$response[] = $this->processSupplement();

		return $response;
	}

	/**
	 * Process Auth based request
	 *
	 * @return array
	 */
	public function processAuth(): array
	{
		return include __DIR__ . DIRECTORY_SEPARATOR . 'AuthTests.php';
	}

	/**
	 * Process Open to web api request
	 *
	 * @return array
	 */
	public function processOpen(): array
	{
		return include __DIR__ . DIRECTORY_SEPARATOR . 'OpenTests.php';
	}

	/**
	 * Process Open to web api request - Request/Response are in XML format
	 *
	 * @return array
	 */
	public function processXml(): array
	{
		return include __DIR__ . DIRECTORY_SEPARATOR . 'OpenTestsXml.php';
	}

	/**
	 * Process Auth based request
	 *
	 * @return array
	 */
	public function processSupplement(): array
	{
		return include __DIR__ . DIRECTORY_SEPARATOR . 'SupplementTest.php';
	}
}
