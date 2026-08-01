<?php

/**
 * HTTP response
 * php version 8.3
 * 
 * @category  HTTP response
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App;

use Microservices\App\DataRepresentation\DataEncode;
use Microservices\App\Env;
use Microservices\App\Http;
use Microservices\App\HttpStatus;

/**
 * HTTP response
 * php version 8.3
 * 
 * @category  HTTP response
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class HttpResponse
{
	/**
	 * Output Representation
	 * 
	 * @var null|string
	 */
	public $outputRepresentation = null;

	/**
	 * Directory for HTML output format
	 * 
	 * @var null|string
	 */
	public $htmlDirectory = null;

	/**
	 * Directory for PHP output format
	 * 
	 * @var null|string
	 */
	public $phpDirectory = null;

	/**
	 * Directory for XML output format
	 * 
	 * @var null|string
	 */
	public $xsltDirectory = null;

	/**
	 * HTTP Status
	 * 
	 * @var int
	 */
	public $httpStatus;

	/**
	 * Data Encode object
	 * 
	 * @var null|DataEncode
	 */
	public $dataEncodeObject = null;

	/**
	 * HTTP object
	 * 
	 * @var null|Http
	 */
	private $httpObject = null;

	/**
	 * Constructor
	 * 
	 * @param Http $httpObject
	 */
	public function __construct(
		Http &$httpObject
	) {
		$this->httpObject = &$httpObject;
		$this->httpStatus = HttpStatus::$Ok;
		$this->outputRepresentation = Env::$outputRepresentation;
		$this->dataEncodeObject = new DataEncode(
			httpObject: $this->httpObject
		);
	}

	/**
	 * Initialize
	 * 
	 * @return bool
	 */
	public function init(): bool
	{
		$this->dataEncodeObject->init();

		return true;
	}
}
