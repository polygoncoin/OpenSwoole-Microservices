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
