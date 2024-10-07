<?php
namespace Microservices\Upload;

use Microservices\App\Constants;
use Microservices\App\Common;
use Microservices\App\Env;
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
class Module1
{
    use UploadTrait;

    /**
     * Microservices Collection of Common Objects
     * 
     * @var Microservices\App\Common
     */
    private $c = null;

    /**
     * Constructor
     * 
     * @param Microservices\App\Common $common
     */
    public function __construct(Common &$common)
    {
        $this->c = &$common;
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
        $absFilePath = $this->getLocation();
        $this->saveFile($absFilePath);

        return true;
    }

    /**
     * Function to get filename with location depending uplon $conditions
     *
     * @return string
     */
    private function getLocation()
    {
        return Constants::$DOC_ROOT . '/Dropbox/' . 'test.png';
    }
}
