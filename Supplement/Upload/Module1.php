<?php

/**
 * UploadAPI
 * php version 8.3
 *
 * @category  UploadAPI
 * @package   Openswoole_Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\Supplement\Upload;

use Microservices\App\Constant;
use Microservices\App\DbCommonFunction;
use Microservices\App\Http;
use Microservices\Supplement\Upload\UploadInterface;
use Microservices\Supplement\Upload\UploadTrait;

/**
 * UploadAPI Example
 * php version 8.3
 *
 * @category  UploadAPI_Example
 * @package   Openswoole_Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class Module1 implements UploadInterface
{
	use UploadTrait;

	/**
	 * Http Object
	 *
	 * @var null|Http
	 */
	private $http = null;

	/**
	 * Constructor
	 *
	 * @param Http $http
	 */
	public function __construct(Http &$http)
	{
		$this->http = &$http;
		DbCommonFunction::setDbConnection($this->http->req, fetchFrom: 'Master');
	}

	/**
	 * Initialize
	 *
	 * @return bool
	 */
	public function init(): bool
	{
		return true;
	}

	/**
	 * Process
	 *
	 * @param array $payload Payload
	 *
	 * @return array
	 */
	public function process(array $payload = []): array
	{
		$absFilePath = $this->getLocation();
		$this->saveFile(absFilePath: $absFilePath);

		return [true];
	}

	/**
	 * Function to get filename with location depending upon $sess
	 *
	 * @return string
	 */
	private function getLocation(): string
	{
		return Constant::$DROP_BOX_DIR
			. DIRECTORY_SEPARATOR . $this->http->req->cId
			. DIRECTORY_SEPARATOR . 'test.png';
	}
}
