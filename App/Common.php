<?php
namespace Microservices\App;

use Microservices\App\Constants;
use Microservices\App\Common;
use Microservices\App\Env;
use Microservices\App\HttpRequest;
use Microservices\App\HttpResponse;

/**
 * Common Class
 *
 * Common objects class.
 *
 * @category   Common
 * @package    Microservices
 * @author     Ramesh Narayan Jangid
 * @copyright  Ramesh Narayan Jangid
 * @version    Release: @1.0.0@
 * @since      Class available since Release 1.0.0
 */
class Common
{
    /**
     * Microservices HTTP Request
     * 
     * @var Microservices\App\HttpRequest
     */
    public $httpRequest = null;

    /**
     * Microservices HTTP Response
     * 
     * @var Microservices\App\HttpResponse
     */
    public $httpResponse = null;

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
        $this->httpRequest = new HttpRequest($this->inputs);
        $this->httpRequest->init();

        $this->httpResponse = new HttpResponse($this->inputs);
        $this->httpResponse->init();
    }
}
