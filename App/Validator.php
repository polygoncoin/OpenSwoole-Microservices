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
     * Common object
     *
     * @var null|Common
     */
    private $c = null;

    /**
     * Constructor
     *
     * @param Common $common Common object
     */
    public function __construct(Common &$common)
    {
        $this->c = &$common;

        if ($this->c->req->db->database === Env::$globalDbDatabase) {
            $this->v = new GlobalValidator(common: $this->c);
        } else {
            $this->v = new ClientValidator(common: $this->c);
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
            isset(($this->c->req->s['necessary']))
            && count(value: $this->c->req->s['necessary']) > 0
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
        if (!empty($this->c->req->s['necessary']['payload'])) {
            foreach ($this->c->req->s['necessary']['payload'] as $column => &$arr) {
                if ($arr['nec'] && !isset($this->c->req->s['payload'][$column])) {
                    $errors[] = 'Missing necessary payload: ' . $column;
                    $isValidData = false;
                }
            }
        }

        return [$isValidData, $errors];
    }
}
