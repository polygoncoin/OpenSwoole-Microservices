<?php
/**
 * UploadAPI
 * php version 8.3
 *
 * @category  UploadAPI
 * @package   OpenSwoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/OpenSwoole-Microservices
 * @since     Class available since Release 1.0.0
 */
namespace Microservices\Supplement\Upload;

use Microservices\App\Constants;
use Microservices\App\Common;
use Microservices\Supplement\Upload\UploadInterface;
use Microservices\Supplement\Upload\UploadTrait;

/**
 * UploadAPI Example
 * php version 8.3
 *
 * @category  UploadAPI_Example
 * @package   OpenSwoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/OpenSwoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class Module1 implements UploadInterface
{
    use UploadTrait;

    /**
     * Common Object
     *
     * @var null|Common
     */
    private $_c = null;

    /**
     * Constructor
     *
     * @param Common $common Common object
     */
    public function __construct(Common &$common)
    {
        $this->_c = &$common;
        $this->_c->req->db = $this->_c->req->setDbConnection(fetchFrom: 'Master');
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
     * @return bool
     */
    public function process(): bool
    {
        $absFilePath = $this->_getLocation();
        $this->_saveFile(absFilePath: $absFilePath);

        return true;
    }

    /**
     * Function to get filename with location depending upon $sess
     *
     * @return string
     */
    private function _getLocation(): string
    {
        return Constants::$DOC_ROOT .
            DIRECTORY_SEPARATOR . 'Dropbox' .
            DIRECTORY_SEPARATOR . 'test.png';
    }
}
