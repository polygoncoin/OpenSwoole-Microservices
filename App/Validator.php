<?php
namespace Microservices\App;

use Microservices\App\Constants;
use Microservices\App\Common;
use Microservices\App\Env;
use Microservices\Validation\ClientValidator;
use Microservices\Validation\GlobalValidator;

/**
 * Validator
 *
 * This class is meant for validation
 *
 * @category   Validator
 * @package    Microservices
 * @author     Ramesh Narayan Jangid
 * @copyright  Ramesh Narayan Jangid
 * @version    Release: @1.0.0@
 * @since      Class available since Release 1.0.0
 */
class Validator
{
    /**
     * Validator object
     */
    private $v = null;

    /**
     * Microservices Collection of Common Objects
     * 
     * @var Microservices\App\Common
     */
    private $c = null;

    /**
     * Constructor
     * 
     * @param Microservices\App\Common $common
     */
    public function __construct(Common &$common)
    {
        $this->c = &$common;

        if ($this->c->httpRequest->db->database === Env::$globalDatabase) {
            $this->v = new GlobalValidator($this->c);
        } else {
            $this->v = new ClientValidator($this->c);
        }
    }

    /**
     * Validate payload
     *
     * @param array $conditions            Inputs
     * @param array $validationConfig Validation configuration.
     * @return array
     */
    public function validate($conditions, $validationConfig)
    {
        if (isset(($conditions['required'])) && count($conditions['required']) > 0) {
            if ((list($isValidData, $errors) = $this->validateRequired($conditions)) && !$isValidData) {
                return [$isValidData, $errors];
            }
        }

        return $this->v->validate($conditions, $validationConfig);
    }

    /**
     * Validate required payload
     *
     * @param array $conditions Inputs
     * @return array
     */
    private function validateRequired($conditions)
    {
        $isValidData = true;
        $errors = [];
        // Required fields payload validation
        $payload = $conditions['payload'];
        $required = $conditions['required'];
        if (count($payload) >= count($required)) {
            foreach ($required as $column) {
                if (!isset($payload[$column])) {
                    $errors[] = 'Invalid payload: '.$column;
                    $isValidData = false;
                }
            }
        } else {
            $errors[] = 'Invalid payload';
            $isValidData = false;
        }

        return [$isValidData, $errors];
    }
}
