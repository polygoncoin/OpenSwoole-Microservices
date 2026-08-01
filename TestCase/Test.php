<?php

/**
 * Test Case
 * php version 8.3
 * 
 * @category  Test
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\TestCase;

use Microservices\App\Constant;
use Microservices\App\Web;

/**
 * Test
 * php version 8.3
 * 
 * @category  Test
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class Test
{
	/**
	 * HTTP request data
	 * 
	 * @var null|array
	 */
	public $httpReqData = null;

	/**
	 * Constructor
	 * 
	 * @param array $httpReqData
	 */
	public function __construct(
		&$httpReqData
	) {
		$this->httpReqData = &$httpReqData;
	}

	/**
	 * Process all request
	 * 
	 * @return array
	 */
	public function processAllTest(): array
	{
		$response = [];

		$response[] = $this->processPrivate();
		$response[] = $this->processPublic();
		$response[] = $this->processPublicXml();
		$response[] = $this->processPrivateSupplement();

		return $response;
	}

	/**
	 * Process auth based request
	 * 
	 * @return array
	 */
	public function processPrivate(): array
	{
		$httpReqData = &$this->httpReqData;
		return include __DIR__ . DIRECTORY_SEPARATOR . 'PrivateTest.php';
	}

	/**
	 * Process open to web request
	 * 
	 * @return array
	 */
	public function processPublic(): array
	{
		$httpReqData = &$this->httpReqData;
		return include __DIR__ . DIRECTORY_SEPARATOR . 'PublicTest.php';
	}

	/**
	 * Process open to web xml request
	 * Request/Response are in XML format
	 * 
	 * @return array
	 */
	public function processPublicXml(): array
	{
		$httpReqData = &$this->httpReqData;
		return include __DIR__ . DIRECTORY_SEPARATOR . 'PublicTestXml.php';
	}

	/**
	 * Process supplement request
	 * 
	 * @return array
	 */
	public function processPrivateSupplement(): array
	{
		$httpReqData = &$this->httpReqData;
		return include __DIR__ . DIRECTORY_SEPARATOR . 'PrivateSupplementTest.php';
	}
}
