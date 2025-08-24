<?php
/**
 * Login
 * php version 8.3
 *
 * @category  Login
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
namespace Microservices\App;

use Microservices\App\Constants;
use Microservices\App\CacheKey;
use Microservices\App\Common;
use Microservices\App\Env;
use Microservices\App\HttpStatus;
use Microservices\App\Servers\Database\AbstractDatabase;

/**
 * Login
 * php version 8.3
 *
 * @category  Login
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class Login
{
    /**
     * Database Object
     *
     * @var null|AbstractDatabase
     */
    public $db = null;

    /**
     * Username for login
     *
     * @var null|string
     */
    public $username = null;

    /**
     * Password for login
     *
     * @var null|string
     */
    public $password = null;

    /**
     * Details pertaining to user
     *
     * @var array
     */
    private $_userDetails;

    /**
     * Current timestamp
     *
     * @var int
     */
    private $_timestamp;

    /**
     * Payload
     *
     * @var array
     */
    private $_payload = [];

    /**
     * Common Object
     *
     * @var null|Common
     */
    private $_c = null;

    /**
     * Cache Keys
     */
    private $_clientUserKey = null;
    private $_tokenKey = null;
    private $_userTokenKey = null;
    private $_cidrKey = null;

    /**
     * Constructor
     *
     * @param Common $common Common object
     */
    public function __construct(Common &$common)
    {
        $this->_c = &$common;
    }

    /**
     * Initialize
     *
     * @return bool
     */
    public function init(): bool
    {
        $this->_c->req->loadClientDetails();

        return true;
    }

    /**
     * Process
     *
     * @return bool
     */
    public function process(): bool
    {
        $this->_loadPayload();
        $this->_loadUserDetails();
        $this->_validateRequestIp();
        $this->_validatePassword();
        $this->_outputTokenDetails();

        return true;
    }

    /**
     * Function to load Payload
     *
     * @return void
     * @throws \Exception
     */
    private function _loadPayload(): void
    {
        // Check request method is POST
        if ($this->_c->req->REQUEST_METHOD !== Constants::$POST) {
            throw new \Exception(
                message: 'Invalid request method',
                code: HttpStatus::$NotFound
            );
        }

        $this->_c->req->loadPayload();
        $this->_payload = $this->_c->req->dataDecode->get();

        // Check for necessary conditions variables
        foreach (array('username', 'password') as $value) {
            if (!isset($this->_payload[$value]) || empty($this->_payload[$value])) {
                throw new \Exception(
                    message: 'Missing necessary parameters',
                    code: HttpStatus::$NotFound
                );
            } else {
                $this->$value = $this->_payload[$value];
            }
        }
    }

    /**
     * Function to load user details from cache
     *
     * @return void
     * @throws \Exception
     */
    private function _loadUserDetails(): void
    {
        $clientId = $this->_c->req->session['clientDetails']['client_id'];
        $this->_clientUserKey = CacheKey::clientUser(
            clientId: $clientId,
            username: $this->_payload['username']
        );
        // Redis - one can find the userID from client username
        if (!$this->_c->req->cache->cacheExists(key: $this->_clientUserKey)) {
            throw new \Exception(
                message: 'Invalid credentials',
                code: HttpStatus::$Unauthorized
            );
        }
        $this->_userDetails = json_decode(
            json: $this->_c->req->cache->getCache(
                key: $this->_clientUserKey
            ),
            associative: true
        );
        if (empty($this->_userDetails['user_id'])
            || empty($this->_userDetails['group_id'])
        ) {
            throw new \Exception(
                message: 'Invalid credentials',
                code: HttpStatus::$Unauthorized
            );
        }
    }

    /**
     * Function to validate source ip
     *
     * @return void
     * @throws \Exception
     */
    private function _validateRequestIp(): void
    {
        // Redis - one can find the userID from username
        $this->_cidrKey = CacheKey::cidr(groupId: $this->_userDetails['group_id']);
        if ($this->_c->req->cache->cacheExists(key: $this->_cidrKey)) {
            $cidrs = json_decode(
                json: $this->_c->req->cache->getCache(
                    key: $this->_cidrKey
                ),
                associative: true
            );
            $ipNumber = ip2long(ip: $this->_c->req->REMOTE_ADDR);
            $isValidIp = false;
            foreach ($cidrs as $cidr) {
                if ($cidr['start'] <= $ipNumber && $ipNumber <= $cidr['end']) {
                    $isValidIp = true;
                    break;
                }
            }
            if (!$isValidIp) {
                throw new \Exception(
                    message: 'IP not supported',
                    code: HttpStatus::$Unauthorized
                );
            }
        }
    }

    /**
     * Validates password from its hash present in cache
     *
     * @return void
     * @throws \Exception
     */
    private function _validatePassword(): void
    {
        // get hash from cache and compares with password
        if (!password_verify(
            password: $this->password,
            hash: $this->_userDetails['password_hash']
        )
        ) {
            throw new \Exception(
                message: 'Invalid credentials',
                code: HttpStatus::$Unauthorized
            );
        }
    }

    /**
     * Generates token
     *
     * @return array
     */
    private function _generateToken(): array
    {
        //generates a crypto-secure 64 characters long
        while (true) {
            $token = bin2hex(string: random_bytes(length: 32));
            $this->_tokenKey = CacheKey::token(token: $token);
            if (!$this->_c->req->cache->cacheExists(key: $this->_tokenKey)) {
                $this->_c->req->cache->setCache(
                    key: $this->_tokenKey,
                    value: '{}',
                    expire: Constants::$TOKEN_EXPIRY_TIME
                );
                $tokenDetails = [
                    'token' => $token,
                    'timestamp' => $this->_timestamp
                ];
                break;
            }
        }
        return $tokenDetails;
    }

    /**
     * Outputs active/newly generated token details
     *
     * @return void
     */
    private function _outputTokenDetails(): void
    {
        $this->_timestamp = time();
        $tokenFound = false;

        $this->_userTokenKey = CacheKey::userToken(
            userId: $this->_userDetails['user_id']
        );
        if ($this->_c->req->cache->cacheExists(key: $this->_userTokenKey)) {
            $tokenDetails = json_decode(
                json: $this->_c->req->cache->getCache(
                    key: $this->_userTokenKey
                ),
                associative: true
            );
            $this->_tokenKey = CacheKey::token(token: $tokenDetails['token']);
            if ($this->_c->req->cache->cacheExists(key: $this->_tokenKey)) {
                $time = $this->_timestamp - $tokenDetails['timestamp'];
                if ((Constants::$TOKEN_EXPIRY_TIME - $time) > 0) {
                    $tokenFound = true;
                } else {
                    $this->_c->req->cache->deleteCache(key: $this->_tokenKey);
                }
            }
        }

        if (!$tokenFound) {
            $tokenDetails = $this->_generateToken();
            // We set this to have a check first if multiple request/attack occurs
            $this->_c->req->cache->setCache(
                key: $this->_userTokenKey,
                value: json_encode(
                    value: $tokenDetails
                ),
                expire: Constants::$TOKEN_EXPIRY_TIME
            );
            $this->_tokenKey = CacheKey::token(token: $tokenDetails['token']);
            unset($this->_userDetails['password_hash']);
            $this->_c->req->cache->setCache(
                key: $this->_tokenKey,
                value: json_encode(
                    value: $this->_userDetails
                ),
                expire: Constants::$TOKEN_EXPIRY_TIME
            );
            $this->_updateDB(tokenDetails: $tokenDetails);
        }

        $time = $this->_timestamp - $tokenDetails['timestamp'];
        $output = [
            'Token' => $tokenDetails['token'],
            'Expires' => (Constants::$TOKEN_EXPIRY_TIME - $time)
        ];

        $this->_c->initResponse();
        $this->_c->res->dataEncode->startObject();
        $this->_c->res->dataEncode->addKeyData(key: 'Results', data: $output);
    }

    /**
     * Update token details in DB for respective account
     *
     * @param array $tokenDetails Token Details
     *
     * @return void
     */
    private function _updateDB(&$tokenDetails): void
    {
        $this->_c->req->db = $this->_c->req->setDbConnection(fetchFrom: 'Master');
        $this->db = &$this->_c->req->db;

        $userTable = Env::$client_users;
        $this->db->execDbQuery(
            sql: "
                UPDATE
                    `{$userTable}`
                SET
                    `token` = :token,
                    `token_ts` = :token_ts
                WHERE
                    user_id = :user_id",
            params: [
                ':token' => $tokenDetails['token'],
                ':token_ts' => $tokenDetails['timestamp'],
                ':user_id' => $this->_userDetails['user_id']
            ]
        );
    }
}
