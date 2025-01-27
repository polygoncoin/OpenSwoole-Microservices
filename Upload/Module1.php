<?php
namespace Microservices\Upload;

use Microservices\App\Constants;
use Microservices\App\Common;
use Microservices\App\Env;
use Microservices\Upload\UploadInterface;
use Microservices\Upload\UploadTrait;

/**
 * Class is used for file uploads
 *
 * This class supports POST & PUT HTTP request
 *
 * @category   Upload Module 1
 * @package    Microservices
 * @author     Ramesh Narayan Jangid
 * @copyright  Ramesh Narayan Jangid
 * @version    Release: @1.0.0@
 * @since      Class available since Release 1.0.0
 */
class Module1 implements UploadInterface
{
    use UploadTrait;

    /**
     * Microservices Collection of Common Objects
     *
     * @var null|Common
     */
    private $c = null;

    /**
     * Constructor
     *
     * @param Common $common
     */
    public function __construct(&$common)
    {
        $this->c = &$common;
        $this->c->httpRequest->setConnection($fetchFrom = 'Master');
    }

    /**
     * Initialize
     *
     * @return boolean
     */
    public function init()
    {
        return true;
    }

    /**
     * Process
     *
     * @return boolean
     */
    public function process()
    {
        $srcFilePath = $this->c->httpRequestDetails['files']['input_key']['tmp_name'];
        $destFilePath = $this->getLocation();
        $this->saveFile($srcFilePath, $destFilePath);

        return true;
    }

    /**
     * Function to get filename with location depending uplon $session
     *
     * @return string
     */
    private function getLocation()
    {
        return Constants::$DOC_ROOT . '/Dropbox/' . 'test.png';
    }
}
