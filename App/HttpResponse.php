<?php
namespace Microservices\App;

use Microservices\App\Constants;
use Microservices\App\Common;
use Microservices\App\DataRepresentation\AbstractDataEncode;
use Microservices\App\DataRepresentation\DataEncode;
use Microservices\App\Env;
use Microservices\App\HttpStatus;

/**
 * HTTP Error Response
 *
 * This class is built to handle HTTP error response
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
    public $httpStatus;

    /**
     * Json Encode Object
     *
     * @var null|AbstractDataEncode
     */
    public $dataEncode = null;

    /**
     * Microservices Request Details
     *
     * @var array
     */
    public $httpRequestDetails = null;

    /**
     * Constructor
     *
     * @param array $httpRequestDetails
     */
    public function __construct(&$httpRequestDetails)
    {
        $this->httpStatus = HttpStatus::$Ok;
        $this->httpRequestDetails = &$httpRequestDetails;
    }

    /**
     * Initialize
     *
     * @return boolean
     */
    public function init()
    {
        $this->dataEncode = new DataEncode($this->httpRequestDetails);
        $this->dataEncode->init();
    }
}
