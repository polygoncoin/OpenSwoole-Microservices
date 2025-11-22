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
     * Constructor
     */
    public function __construct()
    {
        if (DbFunctions::$masterDb->database === Env::$gDbServerDatabase) {
            $this->v = new GlobalValidator();
        } else {
            $this->v = new ClientValidator();
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
            isset((Common::$req->s['necessary']))
            && count(value: Common::$req->s['necessary']) > 0
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
        if (!empty(Common::$req->s['necessary']['payload'])) {
            foreach (Common::$req->s['necessary']['payload'] as $column => &$arr) {
                if ($arr['necessary'] && !isset(Common::$req->s['payload'][$column])) {
                    $errors[] = 'Missing necessary payload: ' . $column;
                    $isValidData = false;
                }
            }
        }

        return [$isValidData, $errors];
    }
}
