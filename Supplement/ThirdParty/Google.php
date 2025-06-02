<?php
namespace Microservices\Supplement\ThirdParty;

use Microservices\App\Constants;
use Microservices\App\Common;
use Microservices\App\Env;
use Microservices\App\HttpStatus;
use Microservices\Supplement\ThirdParty\ThirdPartyInterface;
use Microservices\Supplement\ThirdParty\ThirdPartyTrait;


/**
 * Class for third party - Google
 *
 * This class perform third party - Google operations
 * One can initiate third party calls via access to URL
 * https://domain.tld/client/thirdParty/className?queryString
 * All HTTP methods are supported
 *
 * @category   Third party sample
 * @package    Microservices
 * @author     Ramesh Narayan Jangid
 * @copyright  Ramesh Narayan Jangid
 * @version    Release: @1.0.0@
 * @since      Class available since Release 1.0.0
 */
class Google implements ThirdPartyInterface
{
    use ThirdPartyTrait;

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
        $this->c->httpRequest->db = $this->c->httpRequest->setDbConnection($fetchFrom = 'Slave');
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
        // Create and call functions to manage third party cURL calls here

        $curl_handle=curl_init();
        curl_setopt($curl_handle,CURLOPT_URL,'https://api.ipify.org?format=json');
        curl_setopt($curl_handle,CURLOPT_CONNECTTIMEOUT,2);
        curl_setopt($curl_handle,CURLOPT_RETURNTRANSFER,1);
        $output = curl_exec($curl_handle);
        curl_close($curl_handle);
        if (empty($output)){
            $output = ['Error' => 'Nothing returned by ipify'];
            $this->c->httpResponse->httpStatus = HttpStatus::$InternalServerError;
        } else {
            $output = json_decode($output, true);
        }
        // End the calls with json response with dataEncode Object
        $this->endProcess($output);

        return true;
    }

    /**
     * Function to end process which outputs the results
     *
     * @param string $output
     * @return void
     */
    private function endProcess($output)
    {
        $this->c->httpResponse->dataEncode->addKeyData('Results', $output);
    }
}
