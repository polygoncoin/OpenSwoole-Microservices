<?php
namespace Microservices\App;

use Microservices\App\Env;

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
class DatabaseOpenCacheKey
{
    static public $App = null;
    static public $Client = null;

    static public $Category = null;

    /**
     * Get Database Cache Key
     *
     * @param null|int $clientId
     * @param null|int $groupId
     * @param null|int $userId
     * @return string
     */
    static public function init($clientId)
    {
        self::$App = 'o:app' . Env::$outputDataRepresentation;
        self::$Client = ":c:{$clientId}";

        self::$Category = self::$App . ':category';
    }
}
