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
    // ;
    // ; Global DB level config (represented by zero))
    // ;

    // ;; Domain enable settings
    public $ENABLE_PUBLIC_DOMAIN = null;
    public $ENABLE_PRIVATE_WEB_DOMAIN = null;
    public $ENABLE_PRIVATE_API_DOMAIN = null;

    // ;; Domain name settings
    public $PUBLIC_DOMAIN_NAME = null;
    public $PRIVATE_WEB_DOMAIN_NAME = null;
    public $PRIVATE_API_DOMAIN_NAME = null;

    // ;; Customer config directory
    public $CUSTOMER_CONFIG_DIRECTORY = null;

    // ;; Enable
    public $OUTPUT_PERFORMANCE_STATS = null;
    public $DISABLE_PROXY_REQUEST = null;

    // ;; Data representation
    // ; Supported: JSON/XML
    public $INPUT_REPRESENTATION = null;
    // ; Supported: JSON/XML/XSLT/HTML/PHP
    public $OUTPUT_REPRESENTATION = null;

    // ;; Enable - Data representation in query string
    public $ENABLE_INPUT_REPRESENTATION_IN_QUERY_STRING = null;

    // ;; Encryption secret setting
    public $SECRET = null;

    // ;; Concurrent logins
    public $ENABLE_CONCURRENT_LOGIN = null;
    public $CONCURRENT_LOGIN_COUNT = null;

    // ;; Payload settings
    public $ENABLE_PAYLOAD_IN_RESPONSE = null;
    public $PAYLOAD_KEY_IN_RESPONSE = null;

    // ;; Default per page (records per page)
    public $DEFAULT_PER_PAGE_COUNT = null;
    public $MAX_PER_PAGE_COUNT = null;

    // ;; Customer level private domain login lifetime
    public $TOKEN_LIFETIME = null;
    public $SESSION_LIFETIME = null;


    // ;
    // ; Route related config (to allow / now to allow) settings
    // ;

    // ;; Exact keyword routes
    public $ENABLE_RELOAD = null;
    public $ENABLE_ROUTES = null;
    // ; Keywords
    public $RELOAD_KEYWORD = null;
    public $ROUTES_KEYWORD = null;

    // ;; Supplement feature prefixed to route with slash
    public $ENABLE_CRON = null;
    public $ENABLE_CUSTOM = null;
    public $ENABLE_DROPBOX = null;
    public $ENABLE_THIRDPARTY = null;
    public $ENABLE_UPLOAD = null;
    // ; Supplement feature Keywords
    public $CRON_KEYWORD = null;
    public $CUSTOM_KEYWORD = null;
    public $DROPBOX_KEYWORD = null;
    public $THIRDPARTY_KEYWORD = null;
    public $UPLOAD_KEYWORD = null;
    // ; Supplement functions keyword to append
    public $APPEND_SUPPLEMENT_FUNCTION_KEYWORD = null;

    // ;; Keyword to append to all route with slash
    public $ENABLE_EXPLAIN = null;
    public $ENABLE_IMPORT = null;
    public $ENABLE_IMPORT_SAMPLE = null;
    // ; Keywords
    public $EXPLAIN_KEYWORD = null;
    public $IMPORT_CSV_KEYWORD = null;
    public $IMPORT_SAMPLE_KEYWORD = null;

    // ;; Download client location for export CSV
    public $ENABLE_EXPORT = null;
    public $EXPORT_KEYWORD = null;
    public $EXPORT_CLIENT_LOCATION = null;


    // ;
    // ; Rate limiting config
    // ;

    public $ENABLE_RATE_LIMITING = null;

    // ;; Requests
    public $ENABLE_RATE_LIMITING_CUSTOMER = null;
    public $ENABLE_RATE_LIMITING_ROUTE = null;
    public $ENABLE_RATE_LIMITING_GROUP = null;
    public $ENABLE_RATE_LIMITING_GROUP_ROUTE = null;
    public $ENABLE_RATE_LIMITING_USER = null;
    public $ENABLE_RATE_LIMITING_USER_ROUTE = null;
    // ;; IP requests
    public $ENABLE_RATE_LIMITING_CUSTOMER_IP = null;
    public $ENABLE_RATE_LIMITING_ROUTE_IP = null;
    public $ENABLE_RATE_LIMITING_GROUP_IP = null;
    public $ENABLE_RATE_LIMITING_GROUP_ROUTE_IP = null;
    public $ENABLE_RATE_LIMITING_USER_IP = null;
    public $ENABLE_RATE_LIMITING_USER_ROUTE_IP = null;
    // ;; Login requests
    public $ENABLE_RATE_LIMITING_LOGIN = null;
    public $ENABLE_RATE_LIMITING_LOGIN_IP = null;
    public $ENABLE_RATE_LIMITING_EACH_USER_LOGIN = null;
    public $ENABLE_RATE_LIMITING_EACH_USER_LOGIN_IP = null;


    // ;
    // ; --- Containers detail -------
    // ;

    // ;; Cache container - Redis / Memcached / MongoDb
    public $CACHE_MODE = null;
    public $CACHE_HOST = null;
    public $CACHE_PORT = null;
    public $CACHE_USER = null;
    public $CACHE_PASSWORD = null;
    public $CACHE_DB = null;
    public $CACHE_TABLE = null; // ; For MongoDb

    // ;; Database container - MySql / PostgreSql
    // ; Master database
    public $MASTER_DB_MODE = null;
    public $MASTER_DB_HOST = null;
    public $MASTER_DB_PORT = null;
    public $MASTER_DB_USER = null;
    public $MASTER_DB_PASSWORD = null;
    public $MASTER_DB_NAME = null;
    public $MASTER_DB_PLACEHOLDER = null; // ; Named(:param) / Unnamed(?)
    // ; Slave database
    public $SLAVE_DB_MODE = null;
    public $SLAVE_DB_HOST = null;
    public $SLAVE_DB_PORT = null;
    public $SLAVE_DB_USER = null;
    public $SLAVE_DB_PASSWORD = null;
    public $SLAVE_DB_NAME = null;
    public $SLAVE_DB_PLACEHOLDER = null; // ; Named(:param) / Unnamed(?)

    // ;; Query cache
    public $ENABLE_QUERY_CACHE_FOR_PUBLIC = null;
    public $ENABLE_QUERY_CACHE_FOR_PRIVATE_WEB = null;
    public $ENABLE_QUERY_CACHE_FOR_PRIVATE_API = null;
    // ; Query cache container - Redis / Memcached / MongoDb
    public $QUERY_CACHE_MODE = null;
    public $QUERY_CACHE_HOST = null;
    public $QUERY_CACHE_PORT = null;
    public $QUERY_CACHE_USER = null;
    public $QUERY_CACHE_PASSWORD = null;
    public $QUERY_CACHE_DB = null;
    public $QUERY_CACHE_TABLE = null; // ; For MongoDb


    // ;
    // ; PHP session related configs
    // ;

    // ;; Session mode - 'File', 'MySql', 'PostgreSql', 'MongoDb', 'Redis', 'Memcached', 'Cookie'
    public $SESSION_STORE_MODE = null;

    // ;; Session storage - 'File'
    public $SESSION_STORE_PATH = null;

    // ;; Session storage - 'Cookie'
    public $SESSION_DATA_COOKIE_NAME = null;

    // ;; Session encryption - 'File', 'MySql', 'PostgreSql', 'MongoDb', 'Redis', 'Memcached', 'Cookie'
    // ; Value=base64_encode(openssl_random_pseudo_bytes(32))
    public $SESSION_ENCRYPTION_PASS_PHRASE = null;
    // ; Value=base64_encode(openssl_random_pseudo_bytes(16))
    public $SESSION_ENCRYPTION_IV = null;

    // ; Cookie settings - 'File', 'MySql', 'PostgreSql', 'MongoDb', 'Redis', 'Memcached', 'Cookie'
    public $SESSION_COOKIE_NAME = null;
    public $SESSION_COOKIE_PATH = null;
    public $SESSION_COOKIE_SECURE = null;
    public $SESSION_COOKIE_HTTPONLY = null;
    public $SESSION_COOKIE_SAMESITE = null;

    // ; Session container - 'MySql', 'PostgreSql', 'MongoDb', 'Redis', 'Memcached'
    public $SESSION_HOST = null;
    public $SESSION_PORT = null;
    public $SESSION_USER = null;
    public $SESSION_PASSWORD = null;
    public $SESSION_DB = null;
    public $SESSION_TABLE = null;


    // ;
    // ; CIDR config
    // ;

    public $ENABLE_CIDR = null;

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
