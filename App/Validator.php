<?php
/**
 * Validator
 * php version 8.3
 *
 * @category  Validator
 * @package   OpenSwoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/OpenSwoole-Microservices
 * @since     Class available since Release 1.0.0
 */
namespace Microservices\App;

use Microservices\App\Common;
use Microservices\App\Env;
use Microservices\Validation\ClientValidator;
use Microservices\Validation\GlobalValidator;
use Microservices\Validation\ValidatorInterface;

/**
 * Validator
 * php version 8.3
 *
 * @category  Validator
 * @package   OpenSwoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/OpenSwoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class Validator
{
    /**
     * Validator Object
     * 
     * @var null|ValidatorInterface
     */
    private $_v = null;

    /**
     * Common Object
     *
     * @var null|Common
     */
    private $_c = null;

    /**
     * Constructor
     *
     * @param Common $common Common object
     */
    public function __construct(&$common)
    {
        $this->_c = &$common;

        if ($this->_c->req->db->database === Env::$globalDatabase) {
            $this->_v = new GlobalValidator(common: $this->_c);
        } else {
            $this->_v = new ClientValidator(common: $this->_c);
        }
    }

    /**
     * Validate payload
     *
     * @param array $validationConfig Validation configuration
     *
     * @return array
     */
    public function validate(&$validationConfig): array
    {
        $sess = &$this->_c->req->sess;
        if (isset(($sess['necessary'])) && count(value: $sess['necessary']) > 0) {
            if (([$isValidData, $errors] = $this->_validateRequired()) 
                && !$isValidData
            ) {
                return [$isValidData, $errors];
            }
        }

        return $this->_v->validate(validationConfig: $validationConfig);
    }

    /**
     * Validate necessary payload
     *
     * @return array
     */
    private function _validateRequired(): array
    {
        $isValidData = true;
        $errors = [];
        $sess = &$this->_c->req->sess;
        // Required fields payload validation
        if (!empty($sess['necessary']['payload'])) {
            foreach ($sess['necessary']['payload'] as $column => &$arr) {
                if ($arr['nec'] && !isset($sess['payload'][$column])) {
                    $errors[] = 'Missing necessary payload: '.$column;
                    $isValidData = false;
                }
            }
        }

        return [$isValidData, $errors];
    }
}
