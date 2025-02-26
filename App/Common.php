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
 * Common objects class
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
     * @var null|HttpRequest
     */
    public $httpRequest = null;

    /**
     * Microservices HTTP Response
     *
     * @var null|HttpResponse
     */
    public $httpResponse = null;

    /**
     * Microservices Request Details
     *
     * @var null|array
     */
    public $httpRequestDetails = null;

    /**
     * Constructor
     *
     * @param array $httpRequestDetails
     */
    public function __construct(&$httpRequestDetails)
    {
        $this->httpRequestDetails = &$httpRequestDetails;
    }

    /**
     * Initialize
     *
     * @return boolean
     */
    public function init()
    {
        $this->httpRequest = new HttpRequest($this->httpRequestDetails);
        $this->httpRequest->init();

        $this->httpResponse = new HttpResponse($this->httpRequestDetails);
        $this->httpResponse->init();
    }
}
