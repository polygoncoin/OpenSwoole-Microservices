<?php
namespace Microservices\App;

/**
 * Database Cache Key
 *
 * Generates Database Cache Key
 *
 * @category   Database Cache Key
 * @package    Microservices
 * @author     Ramesh Narayan Jangid
 * @copyright  Ramesh Narayan Jangid
 * @version    Release: @1.0.0@
 * @since      Class available since Release 1.0.0
 */
class DatabaseCacheKey
{
    static public $App = null;
    static public $Client = null;
    static public $Group = null;
    static public $User = null;

    static public $Category = null;
    static public $Category1 = null;

    static public $OpenCategory1 = null;

    /**
     * Get Database Cache Key
     *
     * @param null|int $clientId
     * @param null|int $groupId
     * @param null|int $userId
     * @return string
     */
    static public function init($clientId = null, $groupId = null, $userId = null)
    {
        self::$App = 'app';
        self::$Client = !is_null($clientId) ? ":c:{$clientId}" : '';
        self::$Group = !is_null($groupId) ? ":g:{$groupId}" : '';
        self::$User = !is_null($userId) ? ":u:{$userId}" : '';

        self::$Category = self::$App . self::$Client . self::$Group . ':category';
        self::$Category1 = self::$App . self::$Client . self::$Group . ':category:1';

        // 'open:' prepended by default for Open to world API's
        self::$OpenCategory1 = self::$App . self::$Client . ':category:1';
    }
}
