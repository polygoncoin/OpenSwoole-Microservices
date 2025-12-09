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
use Microservices\App\DbFunctions;
use Microservices\App\Env;
use Microservices\App\HttpStatus;
use Microservices\App\RateLimiter;
use Microservices\App\SessionHandlers\Session;

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
    private $uDetails;

    /**
     * Payload
     *
     * @var array
     */
    private $payload = [];

    /**
     * Constructor
     */
    public function __construct()
    {
    }

    /**
     * Initialize
     *
     * @return bool
     */
    public function init(): bool
    {
        Common::$req->loadClientDetails();

        return true;
    }

    /**
     * Process
     *
     * @return bool
     * @throws \Exception
     */
    public function process(): bool
    {
        // Check request method is POST
        if (Common::$req->METHOD !== Constants::$POST) {
            throw new \Exception(
                message: 'Invalid request method',
                code: HttpStatus::$NotFound
            );
        }

        $this->loadPayload();
        $this->loadUserDetails();
        $this->validateRequestIp();
        $this->validatePassword();

        if (((int)getenv(name: 'enableRateLimitAtUsersPerIpLevel')) === 1) {
            $rateLimiter = new RateLimiter();
            $result = $rateLimiter->check(
                prefix: getenv('rateLimitUsersPerIpPrefix'),
                maxRequests: getenv('rateLimitUsersPerIpMaxUsers'),
                secondsWindow: getenv('rateLimitUsersPerIpSecondsWindow'),
                key: Common::$req->IP
            );
            if ($result['allowed']) {
                // Process the request
            } else {
                // Return 429 Too Many Requests
                throw new \Exception(
                    message: $result['resetAt'] - Common::$timestamp,
                    code: HttpStatus::$TooManyRequests
                );
            }
        }

        switch (Env::$authMode) {
            case 'Token':
                $this->outputTokenDetails();
                break;
            case 'Session':
                $this->startSession();
                break;
        }

        return true;
    }

    /**
     * Function to load Payload
     *
     * @return void
     * @throws \Exception
     */
    private function loadPayload(): void
    {
        // Check request method is POST
        if (Common::$req->METHOD !== Constants::$POST) {
            throw new \Exception(
                message: 'Invalid request method',
                code: HttpStatus::$NotFound
            );
        }

        Common::$req->loadPayload();
        $this->payload = Common::$req->dataDecode->get();

        // Check for necessary conditions variables
        foreach (['username', 'password'] as $value) {
            if (!isset($this->payload[$value]) || empty($this->payload[$value])) {
                throw new \Exception(
                    message: 'Missing necessary parameters',
                    code: HttpStatus::$NotFound
                );
            } else {
                $this->$value = $this->payload[$value];
            }
        }
    }

    /**
     * Function to load user details from cache
     *
     * @return void
     * @throws \Exception
     */
    private function loadUserDetails(): void
    {
        $cID = Common::$req->s['cDetails']['id'];
        $clientUserKey = CacheKey::clientUser(
            cID: $cID,
            username: $this->payload['username']
        );
        // Redis - one can find the userID from client username
        if (!DbFunctions::$gCacheServer->cacheExists(key: $clientUserKey)) {
            throw new \Exception(
                message: 'Invalid credentials',
                code: HttpStatus::$Unauthorized
            );
        }
        $this->uDetails = json_decode(
            json: DbFunctions::$gCacheServer->getCache(
                key: $clientUserKey
            ),
            associative: true
        );
        if (
            empty($this->uDetails['id'])
            || empty($this->uDetails['id'])
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
    private function validateRequestIp(): void
    {
        $ipNumber = ip2long(ip: Common::$req->IP);

        $cCidrKey = CacheKey::cCidr(
            cID: Common::$req->s['cDetails']['id']
        );
        $gCidrKey = CacheKey::gCidr(
            gID: $this->uDetails['group_id']
        );
        $uCidrKey = CacheKey::uCidr(
            uID: $this->uDetails['id']
        );
        foreach ([$cCidrKey, $gCidrKey, $uCidrKey] as $key) {
            if (DbFunctions::$gCacheServer->cacheExists(key: $key)) {
                $cidrs = json_decode(
                    json: DbFunctions::$gCacheServer->getCache(
                        key: $key
                    ),
                    associative: true
                );
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
                        code: HttpStatus::$BadRequest
                    );
                }
            }
        }
    }

    /**
     * Validates password from its hash present in cache
     *
     * @return void
     * @throws \Exception
     */
    private function validatePassword(): void
    {
        // get hash from cache and compares with password
        if (
            !password_verify(
                password: $this->password,
                hash: $this->uDetails['password_hash']
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
    private function generateToken(): array
    {
        //generates a crypto-secure 64 characters long
        while (true) {
            $token = bin2hex(string: random_bytes(length: 32));

            if (
                !DbFunctions::$gCacheServer->cacheExists(
                    key: CacheKey::token(token: $token)
                )
            ) {
                DbFunctions::$gCacheServer->setCache(
                    key: CacheKey::token(token: $token),
                    value: '{}',
                    expire: Constants::$TOKEN_EXPIRY_TIME
                );
                $tokenDetails = [
                    'token' => $token,
                    'timestamp' => Common::$timestamp
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
    private function outputTokenDetails(): void
    {
        $tokenFound = false;

        $userTokenKey = CacheKey::userToken(
            uID: $this->uDetails['id']
        );
        if (DbFunctions::$gCacheServer->cacheExists(key: $userTokenKey)) {
            $tokenDetails = json_decode(
                json: DbFunctions::$gCacheServer->getCache(
                    key: $userTokenKey
                ),
                associative: true
            );

            if (
                DbFunctions::$gCacheServer->cacheExists(
                    key: CacheKey::token(
                        token: $tokenDetails['token']
                    )
                )
            ) {
                $time = Common::$timestamp - $tokenDetails['timestamp'];
                if ((Constants::$TOKEN_EXPIRY_TIME - $time) > 0) {
                    $tokenFound = true;
                } else {
                    DbFunctions::$gCacheServer->deleteCache(
                        key: CacheKey::token(
                            token: $tokenDetails['token']
                        )
                    );
                }
            }
        }

        if (!$tokenFound) {
            $tokenDetails = $this->generateToken();
            // We set this to have a check first if multiple request/attack occurs
            DbFunctions::$gCacheServer->setCache(
                key: $userTokenKey,
                value: json_encode(
                    value: $tokenDetails
                ),
                expire: Constants::$TOKEN_EXPIRY_TIME
            );
            unset($this->uDetails['password_hash']);
            DbFunctions::$gCacheServer->setCache(
                key: CacheKey::token(token: $tokenDetails['token']),
                value: json_encode(
                    value: $this->uDetails
                ),
                expire: Constants::$TOKEN_EXPIRY_TIME
            );
            $this->updateDB(tokenDetails: $tokenDetails);
        }

        $time = Common::$timestamp - $tokenDetails['timestamp'];
        $output = [
            'Token' => $tokenDetails['token'],
            'Expires' => (Constants::$TOKEN_EXPIRY_TIME - $time)
        ];

        Common::initResponse();
        Common::$res->dataEncode->startObject();
        Common::$res->dataEncode->addKeyData(key: 'Results', data: $output);
    }

    /**
     * Update token details in DB for respective account
     *
     * @param array $tokenDetails Token Details
     *
     * @return void
     */
    private function updateDB(&$tokenDetails): void
    {
        DbFunctions::setDbConnection(fetchFrom: 'Master');

        $userTable = Env::$clientUsers;
        DbFunctions::$masterDb->execDbQuery(
            sql: "
                UPDATE
                    `{$userTable}`
                SET
                    `token` = :token,
                    `token_ts` = :token_ts
                WHERE
                    id = :id",
            params: [
                ':token' => $tokenDetails['token'],
                ':token_ts' => $tokenDetails['timestamp'],
                ':id' => $this->uDetails['id']
            ]
        );
    }

    /**
     * Outputs active/newly generated token details
     *
     * @return void
     */
    private function startSession(): void
    {
        $isLoggedIn = false;
        if (isset($_SESSION['id'])) {
            $isLoggedIn = true;
        }

        if (!$isLoggedIn) {
            $userSessionIdKey = CacheKey::userSessionId(
                uID: $this->uDetails['id']
            );
            $expire = Constants::$TOKEN_EXPIRY_TIME;
            $timestamp = Common::$timestamp;
            if (DbFunctions::$gCacheServer->cacheExists(key: $userSessionIdKey)) {
                $userSessionIdKeyData = json_decode(
                    json: DbFunctions::$gCacheServer->getCache(
                        key: $userSessionIdKey
                    ),
                    associative: true
                );
                DbFunctions::$gCacheServer->deleteCache(
                    key: $userSessionIdKey
                );
                Session::deleteSession(sessionId: $userSessionIdKeyData['sessionId']);
                $expire = Common::$timestamp - $userSessionIdKeyData['timestamp'];
                $expire = ($expire > Constants::$TOKEN_EXPIRY_TIME)
                    ? Constants::$TOKEN_EXPIRY_TIME : $expire;
                $timestamp = $userSessionIdKeyData['timestamp'];
            }
            unset($this->uDetails['password_hash']);
            $this->uDetails['timestamp'] = $timestamp;

            // Start session in normal (read/write) mode.
            // Use once client is authorized and want to make changes in $_SESSION
            Session::sessionStartReadWrite();
            $_SESSION = $this->uDetails;

            DbFunctions::$gCacheServer->setCache(
                key: $userSessionIdKey,
                value: json_encode(
                    value: [
                        'timestamp' => $timestamp,
                        'sessionId' => session_id()
                    ]
                ),
                expire: $expire
            );

            $isLoggedIn = true;
        }

        $time = Common::$timestamp - $_SESSION['timestamp'];
        $output = [
            'Session' => 'Active',
            'Expires' => (Constants::$TOKEN_EXPIRY_TIME - $time)
        ];

        Common::initResponse();
        Common::$res->dataEncode->startObject();
        Common::$res->dataEncode->addKeyData(key: 'Results', data: $output);
    }
}
