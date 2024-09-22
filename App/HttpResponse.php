<?php
namespace Microservices\App;

use Microservices\App\Constants;
use Microservices\App\Common;
use Microservices\App\Env;
use Microservices\App\JsonEncode;

/**
 * HTTP Error Response
 *
 * This class is built to handle HTTP error response.
 *
 * @category   HttpError
 * @package    Microservices
 * @author     Ramesh Narayan Jangid
 * @copyright  Ramesh Narayan Jangid
 * @version    Release: @1.0.0@
 * @since      Class available since Release 1.0.0
 */
class HttpResponse
{
    /**
     * HTTP Status
     *
     * @var integer
     */
    public $httpStatus = 200;

    /**
     * Json Encode Object
     *
     * @var Microservices\App\JsonEncode
     */
    public $jsonEncode = null;

    /**
     * Microservices Request Details
     * 
     * @var array
     */
    public $inputs = null;

    /**
     * Constructor
     *
     * @param array $inputs
     */
    public function __construct(&$inputs)
    {
        $this->inputs = &$inputs;
    }

    /**
     * Initialize
     *
     * @return boolean
     */
    public function init()
    {
        $this->jsonEncode = new JsonEncode($this->inputs);
        $this->jsonEncode->init();
    }
}
