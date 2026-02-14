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
use Microservices\App\Functions;
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
     * Api common Object
     *
     * @var null|Common
     */
    private $api = null;

    /**
     * Constructor
     *
     * @param Common $api
     */
    public function __construct(Common &$api)
    {
        $this->api = &$api;
    }

    /**
     * Initialize
     *
     * @return bool
     */
    public function init(): bool
    {
        $this->api->req->loadClientDetails();

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
        if ($this->api->req->METHOD !== Constants::$POST) {
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
                secondsWindow: getenv('rateLimitUsersPerIpMaxUsersWindow'),
                key: $this->api->req->IP
            );
            if ($result['allowed']) {
                // Process the request
            } else {
                // Return 429 Too Many Requests
                throw new \Exception(
                    message: $result['resetAt'] - Env::$timestamp,
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
        if ($this->api->req->METHOD !== Constants::$POST) {
            throw new \Exception(
                message: 'Invalid request method',
                code: HttpStatus::$NotFound
            );
        }

        $this->api->req->loadPayload();
        $this->payload = $this->api->req->dataDecode->get();

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
        $cID = $this->api->req->s['cDetails']['id'];
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
        $ipNumber = ip2long(ip: $this->api->req->IP);

        $cCidrKey = CacheKey::cCidr(
            cID: $this->api->req->s['cDetails']['id']
        );
        $gCidrKey = CacheKey::gCidr(
            gID: $this->uDetails['group_id']
        );
        $uCidrKey = CacheKey::uCidr(
            cID: $this->api->req->s['cDetails']['id'],
            uID: $this->uDetails['id']
        );
        $cidrChecked = false;
        foreach ([$cCidrKey, $gCidrKey, $uCidrKey] as $key) {
            if (!$cidrChecked) {
                $cidrChecked = Functions::checkCacheCidr(
                    IP: $this->api->req->IP,
                    againstCacheKey: $key
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
    private function validatePassword(): void
    {
        $rateLimiter = new RateLimiter();
        $result = $rateLimiter->check(
            prefix: getenv('rateLimitUserLoginPrefix'),
            maxRequests: getenv('rateLimitMaxUserLoginRequests'),
            secondsWindow: getenv('rateLimitMaxUserLoginRequestsWindow'),
            key: $this->api->req->IP . $this->username
        );
        if ($result['allowed']) {
            // Process the request
        } else {
            // Return 429 Too Many Requests
            throw new \Exception(
                message: $result['resetAt'] - Env::$timestamp,
                code: HttpStatus::$TooManyRequests
            );
        }
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
                    'timestamp' => Env::$timestamp
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
        $tokenDetails = [];
        $tokenFound = false;
        $foundTokenDetails = [];
        $uniqueHttpRequestHash = $this->api->http['hash'];

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

            if (count($tokenDetails) > 0) {
                foreach ($tokenDetails as $token => $tDetails) {
                    if (
                        DbFunctions::$gCacheServer->cacheExists(
                            key: CacheKey::token(
                                token: $token
                            )
                        )
                    ) {
                        if ($tDetails['uniqueHttpRequestHash'] === $uniqueHttpRequestHash) {
                            $time = Env::$timestamp - $tDetails['timestamp'];
                            if ((Constants::$TOKEN_EXPIRY_TIME - $time) > 0) {
                                $foundTokenDetails = $tDetails;
                                $tokenFound = true;
                            } else {
                                DbFunctions::$gCacheServer->deleteCache(
                                    key: CacheKey::token(
                                        token: $token
                                    )
                                );
                                unset($tokenDetails[$token]);
                            }
                        }
                    } else {
                        unset($tokenDetails[$token]);
                    }
                }
            }
        }

        if (!$tokenFound) {
            if (count($tokenDetails) >= Env::$maxConcurrentLogins) {
                throw new \Exception(
                    message: 'There are ' . Env::$maxConcurrentLogins . ' (max allowed) concurrent logins with your account',
                    code: HttpStatus::$NotFound
                );
            }
            $newTokenDetails = $this->generateToken();
            $newTokenDetails['uniqueHttpRequestHash'] = $uniqueHttpRequestHash;
            $tokenDetails[$newTokenDetails['token']] = $newTokenDetails;

            unset($this->uDetails['password_hash']);
            $this->uDetails['uniqueHttpRequestHash'] = $uniqueHttpRequestHash;
            DbFunctions::$gCacheServer->setCache(
                key: CacheKey::token(token: $newTokenDetails['token']),
                value: json_encode(
                    value: $this->uDetails
                ),
                expire: Constants::$TOKEN_EXPIRY_TIME
            );
            $foundTokenDetails = $newTokenDetails;
            $this->updateDB(tokenDetails: $tokenDetails);
        }
        // We set this to have a check first if multiple request/attack occurs
        DbFunctions::$gCacheServer->setCache(
            key: $userTokenKey,
            value: json_encode(
                value: $tokenDetails
            ),
            expire: Constants::$TOKEN_EXPIRY_TIME
        );

        $time = Env::$timestamp - $foundTokenDetails['timestamp'];
        $output = [
            'Token' => $foundTokenDetails['token'],
            'Expires' => (Constants::$TOKEN_EXPIRY_TIME - $time)
        ];

        $this->api->initResponse();
        $this->api->res->dataEncode->startObject();
        $this->api->res->dataEncode->addKeyData(key: 'Results', data: $output);
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
        DbFunctions::setDbConnection($this->api->req, fetchFrom: 'Master');

        $usersTable = $this->api->req->usersTable;
        DbFunctions::$masterDb[$this->api->req->cId]->execDbQuery(
            sql: "
                UPDATE
                    `{$usersTable}`
                SET
                    `token` = :token
                WHERE
                    id = :id",
            params: [
                ':token' => json_encode($tokenDetails),
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
        if (
            isset($_SESSION['id'])
            && $_SESSION['sessionExpiryTimestamp'] > Env::$timestamp
        ) {
            $isLoggedIn = true;
        }

        if (!$isLoggedIn) {
            $sessionDetails = [];
            $userSessionIdKey = CacheKey::userSessionId(
                uID: $this->uDetails['id']
            );
            if (DbFunctions::$gCacheServer->cacheExists(key: $userSessionIdKey)) {
                $userSessionIdKeyData = json_decode(
                    json: DbFunctions::$gCacheServer->getCache(
                        key: $userSessionIdKey
                    ),
                    associative: true
                );
                foreach ($userSessionIdKeyData as $sessId => $sessData) {
                    if ($sessData['sessionExpiryTimestamp'] <= Env::$timestamp) {
                        Session::deleteSession(sessionId: $sessId);
                    } else {
                        $sessionDetails[$sessId] = $sessData;
                    }
                }
            }
            if (count($sessionDetails) >= Env::$maxConcurrentLogins) {
                throw new \Exception(
                    message: 'There are ' . Env::$maxConcurrentLogins . ' (max allowed) concurrent logins with your account',
                    code: HttpStatus::$NotFound
                );
            }
            $uniqueHttpRequestHash = $this->api->http['hash'];
            $timestamp = Env::$timestamp;
            Session::sessionStartReadWrite();
            $sessionId = session_id();
            $newSessionDetails = [
                'sessionId' => $sessionId,
                'timestamp' => $timestamp,
                'uniqueHttpRequestHash' => $uniqueHttpRequestHash,
                'sessionExpiryTimestamp' => (Env::$timestamp + Constants::$TOKEN_EXPIRY_TIME),
            ];
            $sessionDetails[$sessionId] = $newSessionDetails;

            unset($this->uDetails['password_hash']);
            $this->uDetails['sessionId'] = $sessionId;
            $this->uDetails['timestamp'] = $timestamp;
            $this->uDetails['uniqueHttpRequestHash'] = $uniqueHttpRequestHash;
            $this->uDetails['sessionExpiryTimestamp'] = (Env::$timestamp + Constants::$TOKEN_EXPIRY_TIME);
            $_SESSION = $this->uDetails;

            DbFunctions::$gCacheServer->setCache(
                key: $userSessionIdKey,
                value: json_encode($sessionDetails),
                expire: $expire
            );

            $isLoggedIn = true;
        }

        $output = [
            'Session' => 'Active',
            'Expires' => ($_SESSION['sessionExpiryTimestamp'] - Env::$timestamp)
        ];

        $this->api->initResponse();
        $this->api->res->dataEncode->startObject();
        $this->api->res->dataEncode->addKeyData(key: 'Results', data: $output);
    }
}
