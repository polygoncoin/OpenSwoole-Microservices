<?php
namespace Microservices\App;

use Microservices\App\Constants;
use Microservices\App\Common;
use Microservices\App\Env;
use Microservices\Validation\ClientValidator;
use Microservices\Validation\GlobalValidator;
use Microservices\Validation\ValidatorInterface;

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
     * @var null|ValidatorInterface
     */
    private $v = null;

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

        if ($this->c->httpRequest->db->database === Env::$globalDatabase) {
            $this->v = new GlobalValidator($this->c);
        } else {
            $this->v = new ClientValidator($this->c);
        }
    }

    /**
     * Validate payload
     *
     * @param array $session            Inputs
     * @param array $validationConfig Validation configuration
     * @return array
     */
    public function validate($session, $validationConfig)
    {
        if (isset(($session['required'])) && count($session['required']) > 0) {
            if ((list($isValidData, $errors) = $this->validateRequired($session)) && !$isValidData) {
                return [$isValidData, $errors];
            }
        }

        return $this->v->validate($session, $validationConfig);
    }

    /**
     * Validate required payload
     *
     * @param array $session Inputs
     * @return array
     */
    private function validateRequired($session)
    {
        $isValidData = true;
        $errors = [];
        // Required fields payload validation
        foreach ($session['required']['payload'] as $column => &$arr) {
            if ($arr['require'] && !isset($session['payload'][$column])) {
                $errors[] = 'Missing required payload: '.$column;
                $isValidData = false;
            }
        }

        return [$isValidData, $errors];
    }
}
