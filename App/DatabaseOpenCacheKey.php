<?php
/**
 * Server side Cache keys - Open to web
 * php version 8.3
 *
 * @category  CacheServerKeys
 * @package   OpenSwoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/OpenSwoole-Microservices
 * @since     Class available since Release 1.0.0
 */
namespace Microservices\App;

use Microservices\App\Env;

/**
 * Server side Cache keys - Open
 * php version 8.3
 *
 * @category  CacheServerKeys_Open
 * @package   OpenSwoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/OpenSwoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class DatabaseOpenCacheKey
{
    /**
     * App key
     *
     * @var null|string
     */
    public static $App = null;

    /**
     * Client key
     *
     * @var null|string
     */
    public static $Client = null;

    /**
     * Category key
     *
     * @var null|string
     */
    public static $Category = null;

    /**
     * Initialize
     *
     * @param null|int $clientId Client Id
     *
     * @return void
     */
    public static function init($clientId): void
    {
        self::$App = 'o:app' . Env::$outputRepresentation;
        self::$Client = ":c:{$clientId}";

        self::$Category = self::$App . ':category';
    }
}
