<?php

/**
 * Environment
 * php version 8.3
 *
 * @category  Environment
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App;

use Microservices\App\Constant;
use Microservices\App\HttpStatus;

/**
 * Environment
 * php version 8.3
 *
 * @category  Environment
 * @package   Openswoole-Microservices
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class Environment
{
    public $OUTPUT_PERFORMANCE_STATS = null;
    public $DISABLE_REQUESTS_VIA_PROXIES = null;

    public $SECRET = null;
    public $PAYLOAD_IN_RESPONSE = null;

    public $ENABLE_RELOAD_CACHE = null;
    public $RELOAD_REQUEST_KEYWORD = null;
    public $RELOAD_CACHE_CIDR = null;

    public $MAX_CONCURRENT_LOGIN = null;
    public $MAX_CONCURRENT_LOGIN_WINDOW = null;

    public $CACHE_MODE = null;
    public $CACHE_HOST = null;
    public $CACHE_PORT = null;
    public $CACHE_USER = null;
    public $CACHE_PASSWORD = null;
    public $CACHE_DB = null;
    public $CACHE_TABLE = null;

    public $MASTER_DB_MODE = null;
    public $MASTER_DB_HOST = null;
    public $MASTER_DB_PORT = null;
    public $MASTER_DB_USER = null;
    public $MASTER_DB_PASSWORD = null;
    public $MASTER_DB_NAME = null;
    public $MASTER_DB_PLACEHOLDER = null;

    public $SLAVE_DB_MODE = null;
    public $SLAVE_DB_HOST = null;
    public $SLAVE_DB_PORT = null;
    public $SLAVE_DB_USER = null;
    public $SLAVE_DB_PASSWORD = null;
    public $SLAVE_DB_NAME = null;
    public $SLAVE_DB_PLACEHOLDER = null;

    public $QUERY_CACHE_MODE = null;
    public $QUERY_CACHE_HOST = null;
    public $QUERY_CACHE_PORT = null;
    public $QUERY_CACHE_USER = null;
    public $QUERY_CACHE_PASSWORD = null;
    public $QUERY_CACHE_DB = null;
    public $QUERY_CACHE_TABLE = null;

    public $SESSION_STORE_MODE = null;

    public $SESSION_LIFETIME = null;
    public $SESSION_STORE_PATH = null;
    public $SESSION_COOKIE_NAME = null;
    public $SESSION_COOKIE_PATH = null;
    public $SESSION_COOKIE_DOMAIN = null;
    public $SESSION_COOKIE_SECURE = null;
    public $SESSION_COOKIE_HTTPONLY = null;
    public $SESSION_COOKIE_SAMESITE = null;

    public $SESSION_ENCRYPTION_PASS_PHRASE = null;
    public $SESSION_ENCRYPTION_IV = null;

    public $SESSION_MYSQL_HOST = null;
    public $SESSION_MYSQL_PORT = null;
    public $SESSION_MYSQL_USER = null;
    public $SESSION_MYSQL_PASSWORD = null;
    public $SESSION_MYSQL_DB = null;
    public $SESSION_MYSQL_TABLE = null;

    public $SESSION_PGSQL_HOST = null;
    public $SESSION_PGSQL_PORT = null;
    public $SESSION_PGSQL_USER = null;
    public $SESSION_PGSQL_PASSWORD = null;
    public $SESSION_PGSQL_DB = null;
    public $SESSION_PGSQL_TABLE = null;

    public $SESSION_MONGO_HOST = null;
    public $SESSION_MONGO_PORT = null;
    public $SESSION_MONGO_USER = null;
    public $SESSION_MONGO_PASSWORD = null;
    public $SESSION_MONGO_DB = null;
    public $SESSION_MONGO_TABLE = null;

    public $SESSION_REDIS_HOST = null;
    public $SESSION_REDIS_PORT = null;
    public $SESSION_REDIS_USER = null;
    public $SESSION_REDIS_PASSWORD = null;
    public $SESSION_REDIS_DB = null;

    public $SESSION_MEMCACHE_HOST = null;
    public $SESSION_MEMCACHE_PORT = null;

    public $SESSION_DATA_COOKIE_NAME = null;

    public $INPUT_REPRESENTATION = null;
    public $OUTPUT_REPRESENTATION = null;

    public $DEFAULT_PER_PAGE_COUNT = null;
    public $MAX_PER_PAGE_COUNT = null;

    public $APPEND_SUPPLEMENT_FUNCTION_KEYWORD = null;

    public $EXPLAIN_REQUEST_KEYWORD = null;
    public $IMPORT_REQUEST_KEYWORD = null;
    public $IMPORT_SAMPLE_REQUEST_KEYWORD = null;

    public $DROPBOX_REQUEST_KEYWORD = null;
    public $CRON_REQUEST_KEYWORD = null;
    public $CUSTOM_REQUEST_KEYWORD = null;
    public $THIRD_PARTY_REQUEST_KEYWORD = null;
    public $UPLOAD_REQUEST_KEYWORD = null;

    public $MYSQL_CLIENT_LOCATION = null;

    public $RATE_LIMIT_IP_PREFIX = null;
    public $RATE_LIMIT_ROUTE_PREFIX = null;
    public $RATE_LIMIT_IP_ROUTE_PREFIX = null;
    public $RATE_LIMIT_GROUP_PREFIX = null;
    public $RATE_LIMIT_IP_GROUP_PREFIX = null;
    public $RATE_LIMIT_USER_PREFIX = null;
    public $RATE_LIMIT_IP_USER_PREFIX = null;
    public $RATE_LIMIT_USER_LOGIN_PREFIX = null;

	/**
	 * Constructor
	 *
	 * @param string $envFile Enviroment file name
	 */
	public function __construct($envFile)
	{
        $envDataArray = parse_ini_file(
            filename: ROOT . DIRECTORY_SEPARATOR . $envFile
        );
        foreach ($envDataArray as $envVarName => $envVarValue) {
            if ($envVarName === 'OUTPUT_REPRESENTATION') {
                $this->OUTPUT_REPRESENTATION = [
                    'OUTPUT_REPRESENTATION' => $envVarValue,
                    'OUTPUT_REPRESENTATION_FILE' => Constant::$FALSE
                ];
            } else {
                $this->$envVarName = $envVarValue;
            }
        }
	}
}
