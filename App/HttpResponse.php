<?php
namespace Microservices\App;

use Microservices\App\Constants;
use Microservices\App\Common;
use Microservices\App\Env;
use Microservices\App\JsonEncode;
use Microservices\App\Logs;

use OpenSwoole\Http\Request;
use OpenSwoole\Http\Response;

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
     * Quick HTTP Response
     *
     * @var string
     */
    public $output = null;

    /**
     * Json Encode Object
     *
     * @var Microservices\App\JsonEncode
     */
    public $jsonEncode = null;

    /**
     * OpenSwoole Http Request
     * 
     * @var OpenSwoole\Http\Request
     */
    private $request = null;

    /**
     * OpenSwoole Http Response
     * 
     * @var OpenSwoole\Http\Response
     */
    private $response = null;

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

        $this->jsonEncode = new JsonEncode();
        $this->jsonEncode->init($request, $response);
    }

    /**
     * Set Headers
     *
     * @return void
     */
    public function setHeaders()
    {
        $this->response->header('Content-Type', 'application/json;charset=utf-8');
        $this->response->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $this->response->header('Pragma', 'no-cache');
    }

    /**
     * Return 2xx response
     *
     * @param string $errorCode  Error code
     * @param string $errMessage Error message in 501 response
     * @return void
     */
    public function return2xx($errorCode, $errMessage)
    {
        $this->returnResponse(
            [
                'Status' => $errorCode,
                'Message' => $errMessage
            ]
        );
    }

    /**
     * Return 3xx response
     *
     * @param string $errorCode  Error code
     * @param string $errMessage Error message in 501 response
     * @return void
     */
    public function return3xx($errorCode, $errMessage)
    {
        $this->returnResponse(
            [
                'Status' => $errorCode,
                'Message' => $errMessage
            ]
        );
    }

    /**
     * Return 4xx response
     *
     * @param string  $errorCode  Error code
     * @param string  $errMessage Error message in 404 response
     * @return void
     */
    public function return4xx($errorCode, $errMessage)
    {
        $this->returnResponse(
            [
                'Status' => $errorCode,
                'Message' => $errMessage
            ]
        );
    }

    /**
     * Return 5xx response
     *
     * @param string $errorCode  Error code
     * @param string $errMessage Error message in 501 response
     * @return void
     */
    public function return5xx($errorCode, $errMessage)
    {
        $this->returnResponse(
            [
                'Status' => $errorCode,
                'Message' => $errMessage
            ]
        );
    }

    /**
     * Return HTTP response
     *
     * @param array $arr Array containing details of HTTP response
     * @return void
     */
    public function returnResponse($arr)
    {
        $this->output = json_encode($arr);
    }

    /**
     * Check HTTP Response to proceed ahead.
     *
     * @return boolean
     */
    public function isSuccess()
    {
        return is_null($this->output);
    }
}
