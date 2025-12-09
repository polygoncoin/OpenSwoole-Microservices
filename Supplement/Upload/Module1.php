<?php

/**
 * UploadAPI
 * php version 8.3
 *
 * @category  UploadAPI
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\Supplement\Upload;

use Microservices\App\Common;
use Microservices\App\Constants;
use Microservices\App\DbFunctions;
use Microservices\Supplement\Upload\UploadInterface;
use Microservices\Supplement\Upload\UploadTrait;

/**
 * UploadAPI Example
 * php version 8.3
 *
 * @category  UploadAPI_Example
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class Module1 implements UploadInterface
{
    use UploadTrait;

    /**
     * Api common Object
     *
     * @var null|Common
     */
    private $api = null;

    /**
     * Constructor
     *
     * @param Common $api
     */
    public function __construct(Common &$api)
    {
        $this->api = &$api;
        DbFunctions::setDbConnection($this->api->req, fetchFrom: 'Master');
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
        return Constants::$DROP_BOX_DIR .
            DIRECTORY_SEPARATOR . 'test.png';
    }
}
