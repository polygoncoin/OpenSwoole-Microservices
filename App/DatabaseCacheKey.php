<?php
/**
 * Server side Cache keys - Auth based
 * php version 8.3
 *
 * @category  CacheServerKeys
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
namespace Microservices\App;

/**
 * Server side Cache keys - Auth
 * php version 8.3
 *
 * @category  CacheServerKeys_Auth
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class DatabaseCacheKey
{
    /**
     * App key
     *
     * @var null|string $App
     */
    public static $App = null;

    /**
     * Client key
     *
     * @var null|string $Client
     */
    public static $Client = null;

    /**
     * Group key
     *
     * @var null|string $Group
     */
    public static $Group = null;

    /**
     * User key
     *
     * @var null|string $User
     */
    public static $User = null;

    /**
     * Category key
     *
     * @var null|string $Category
     */
    public static $Category = null;

    /**
     * Category1 key
     *
     * @var null|string $Category1
     */
    public static $Category1 = null;

    /**
     * Initialize
     *
     * @param null|int $cID Client Id
     * @param null|int $gID  Group Id
     * @param null|int $uID   User Id
     *
     * @return void
     */
    public static function init(
        $cID = null,
        $gID = null,
        $uID = null
    ): void {
        self::$App = 'app';
        self::$Client = $cID !== null ? ":c:{$cID}" : '';
        self::$Group = $gID !== null ? ":g:{$gID}" : '';
        self::$User = $uID !== null ? ":u:{$uID}" : '';

        self::$Category = self::$App . self::$Client . self::$Group . ':category';
        self::$Category1 = self::$App . self::$Client . self::$Group . ':category:1';
    }
}
