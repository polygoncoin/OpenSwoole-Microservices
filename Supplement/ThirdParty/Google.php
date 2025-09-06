<?php
/**
 * ThirdPartyAPI
 * php version 8.3
 *
 * @category  ThirdPartyAPI_Interface
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
namespace Microservices\Supplement\ThirdParty;

use Microservices\App\Common;
use Microservices\App\HttpStatus;
use Microservices\Supplement\ThirdParty\ThirdPartyInterface;
use Microservices\Supplement\ThirdParty\ThirdPartyTrait;

/**
 * ThirdPartyAPI Example
 * php version 8.3
 *
 * @category  ThirdPartyAPI_Example
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

class Google implements ThirdPartyInterface
{
    use ThirdPartyTrait;

    /**
     * Common object
     *
     * @var null|Common
     */
    private $_c = null;

    /**
     * Constructor
     *
     * @param Common $common Common object
     */
    public function __construct(Common &$common)
    {
        $this->_c = &$common;
        $this->_c->req->db = $this->_c->req->setDbConnection(fetchFrom: 'Slave');
    }

    /**
     * Initialize
     *
     * @return bool
     */
    public function init(): bool
    {
        return true;
    }

    /**
     * Process
     *
     * @return bool
     */
    public function process(): bool
    {
        // Create and call functions to manage third party cURL calls here

        $curl_handle=curl_init();
        curl_setopt(
            handle: $curl_handle,
            option: CURLOPT_URL,
            value: 'https://api.ipify.org?format=json'
        );
        curl_setopt(handle: $curl_handle, option: CURLOPT_CONNECTTIMEOUT, value: 2);
        curl_setopt(handle: $curl_handle, option: CURLOPT_RETURNTRANSFER, value: 1);
        $output = curl_exec(handle: $curl_handle);
        curl_close(handle: $curl_handle);
        if (empty($output)) {
            $output = ['Error' => 'Nothing returned by ipify'];
            $this->_c->res->httpStatus = HttpStatus::$InternalServerError;
        } else {
            $output = json_decode(json: $output, associative: true);
        }
        // End the calls with json response with dataEncode object
        $this->_endProcess(output: $output);

        return true;
    }

    /**
     * Function to end process which outputs the results
     *
     * @param string $output Output
     *
     * @return void
     */
    private function _endProcess($output): void
    {
        $this->_c->res->dataEncode->addKeyData(key: 'Results', data: $output);
    }
}
