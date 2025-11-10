<?php

/**
 * Autoload
 * php version 8.3
 *
 * @category  Autoload
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices;

/**
 * Autoload
 * php version 8.3
 *
 * @category  Autoload
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class Autoload
{
    /**
     * Autoload Register function
     *
     * @param string $className Class name
     *
     * @return void
     */
    public static function register($className): void
    {
        $className = substr(
            string: $className,
            offset: strlen(string: __NAMESPACE__)
        );
        $className = str_replace(
            search: "\\",
            replace: DIRECTORY_SEPARATOR,
            subject: $className
        );
        $file = __DIR__ . $className . '.php';
        if (!file_exists(filename: $file)) {
            echo PHP_EOL . "File '{$file}' missing" . PHP_EOL;
        } else {
            include_once $file;
        }
    }
}
