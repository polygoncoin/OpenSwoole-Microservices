<?php

/**
 * Validator
 * php version 8.3
 *
 * @category  Validator
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App;

use Microservices\App\Common;
use Microservices\App\DbFunctions;
use Microservices\App\Env;
use Microservices\Validation\ClientValidator;
use Microservices\Validation\GlobalValidator;
use Microservices\Validation\ValidatorInterface;

/**
 * Validator
 * php version 8.3
 *
 * @category  Validator
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class Validator
{
    /**
     * Validator object
     *
     * @var null|ValidatorInterface
     */
    private $v = null;

    /**
     * Api common Object
     *
     * @var null|Common
     */
    private $api = null;

    /**
     * Constructor
     *
     * @param Common $api
     */
    public function __construct(Common &$api)
    {
        $this->api = &$api;
        if (DbFunctions::$masterDb[$this->api->req->cId]->database === Env::$gDbServerDatabase) {
            $this->v = new GlobalValidator($this->api);
        } else {
            $this->v = new ClientValidator($this->api);
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
        if (
            isset(($this->api->req->s['necessary']))
            && count(value: $this->api->req->s['necessary']) > 0
        ) {
            if (
                ([$isValidData, $errors] = $this->validateRequired())
                && !$isValidData
            ) {
                return [$isValidData, $errors];
            }
        }

        return $this->v->validate(validationConfig: $validationConfig);
    }

    /**
     * Validate necessary payload
     *
     * @return array
     */
    private function validateRequired(): array
    {
        $isValidData = true;
        $errors = [];
        // Required fields payload validation
        if (!empty($this->api->req->s['necessary']['payload'])) {
            foreach ($this->api->req->s['necessary']['payload'] as $column => &$arr) {
                if ($arr['necessary'] && !isset($this->api->req->s['payload'][$column])) {
                    $errors[] = 'Missing necessary payload: ' . $column;
                    $isValidData = false;
                }
            }
        }

        return [$isValidData, $errors];
    }
}
