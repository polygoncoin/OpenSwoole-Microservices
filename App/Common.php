<?php
namespace Microservices\App;

use Microservices\App\Constants;
use Microservices\App\Common;
use Microservices\App\Env;
use Microservices\App\HttpRequest;
use Microservices\App\HttpResponse;

use OpenSwoole\Http\Request;
use OpenSwoole\Http\Response;

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
     * OpenSwoole Http Request
     * 
     * @var OpenSwoole\Http\Request
     */
    public $request = null;

    /**
     * OpenSwoole Http Response
     * 
     * @var OpenSwoole\Http\Response
     */
    public $response = null;

    /**
     * Initialize
     *
     * @param OpenSwoole\Http\Request  $request
     * @param OpenSwoole\Http\Response $response
     * @return boolean
     */
    public function init(Request &$request, Response &$response)
    {
        $this->request = $request;
        $this->response = $response;

        $this->httpResponse = new HttpResponse();
        $this->httpResponse->init($request, $response);

        $this->httpRequest = new HttpRequest($this->httpResponse);
        $this->httpRequest->init($request, $response);
    }
}
