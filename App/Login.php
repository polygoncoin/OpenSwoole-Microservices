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

use Microservices\App\CacheKey;
use Microservices\App\Common;
use Microservices\App\Constants;
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

        if (Env::$enableRateLimitAtUsersPerIpLevel) {
            $rateLimiter = new RateLimiter();
            $result = $rateLimiter->check(
                prefix: Env::$rateLimitUsersPerIpPrefix,
                maxRequests: Env::$rateLimitUsersPerIpMaxUsers,
                secondsWindow: Env::$rateLimitUsersPerIpMaxUsersWindow,
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
        if (!$this->cacheExists(key: $clientUserKey)) {
            throw new \Exception(
                message: 'Invalid credentials',
                code: HttpStatus::$Unauthorized
            );
        }
        $this->uDetails = json_decode(
            json: $this->getCache(
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
            prefix: Env::$rateLimitUserLoginPrefix,
            maxRequests: Env::$rateLimitMaxUserLoginRequests,
            secondsWindow: Env::$rateLimitMaxUserLoginRequestsWindow,
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
                !$this->cacheExists(
                    key: CacheKey::token(token: $token)
                )
            ) {
                $this->setCache(
                    key: CacheKey::token(token: $token),
                    value: '{}',
                    expire: Constants::$TOKEN_EXPIRY_TIME
                );
                $userTokenKeyData = [
                    'token' => $token,
                    'timestamp' => Env::$timestamp
                ];
                break;
            }
        }
        return $userTokenKeyData;
    }

    /**
     * Outputs active/newly generated token details
     *
     * @return void
     */
    private function outputTokenDetails(): void
    {
        $uniqueHttpRequestHash = $this->api->http['hash'];

        $userTokenKey = CacheKey::userToken(
            uID: $this->uDetails['id']
        );

        $userTokenKeyExist = false;
        $userTokenKeyData = [];
        if ($this->cacheExists(key: $userTokenKey)) {
            $userTokenKeyExist = true;
            $userTokenKeyData = json_decode(
                json: $this->getCache(
                    key: $userTokenKey
                ),
                associative: true
            );
        }

        if (Env::$enableConcurrentLogins) {
            $userConcurrencyKey = CacheKey::userConcurrency(
                uID: $this->uDetails['id']
            );

            $userConcurrencyKeyExist = false;
            $userConcurrencyKeyData = '';
            if ($this->cacheExists(key: $userConcurrencyKey)) {
                $userConcurrencyKeyExist = true;
                $userConcurrencyKeyData = $this->getCache(
                    key: $userConcurrencyKey
                );
            }
        }

        $tokenFound = false;
        $tokenFoundData = [];
        if ($userTokenKeyExist) {
            if (count($userTokenKeyData) > 0) {
                foreach ($userTokenKeyData as $token => $tData) {
                    if ($this->cacheExists(key: CacheKey::token(token: $token))) {
                        if (Env::$enableConcurrentLogins) {
                            if (
                                $tData['uniqueHttpRequestHash'] === $uniqueHttpRequestHash
                                && $userConcurrencyKeyExist
                                && $userConcurrencyKeyData === $token
                            ) {
                                $timeLeft = Env::$timestamp - $tData['timestamp'];
                                if ((Constants::$TOKEN_EXPIRY_TIME - $timeLeft) > 0) {
                                    $tokenFoundData = $tData;
                                    $tokenFound = true;
                                    continue;
                                }
                            }
                        } else {
                            if (
                                $tData['uniqueHttpRequestHash'] === $uniqueHttpRequestHash
                                && $userConcurrencyKeyData === $token
                            ) {
                                $timeLeft = Env::$timestamp - $tData['timestamp'];
                                if ((Constants::$TOKEN_EXPIRY_TIME - $timeLeft) > 0) {
                                    $tokenFoundData = $tData;
                                    $tokenFound = true;
                                    continue;
                                }
                            }
                        }
                        $timeLeft = Env::$timestamp - $tData['timestamp'];
                        if ((Constants::$TOKEN_EXPIRY_TIME - $timeLeft) <= 0) {
                            $this->deleteCache(
                                key: CacheKey::token(
                                    token: $token
                                )
                            );
                            unset($userTokenKeyData[$token]);
                        }
                    } else {
                        unset($userTokenKeyData[$token]);
                    }
                }
                if (
                    Env::$enableConcurrentLogins
                    && count($userTokenKeyData) >= Env::$maxConcurrentLogins
                ) {
                    throw new \Exception(
                        message: 'Account already in use. '
                            . 'Please try after ' . Env::$concurrentAccessInterval . ' second(s)',
                        code: HttpStatus::$Conflict
                    );
                }
            } else {
                $this->deleteCache(key: $userTokenKey);
            }
        }

        if (!$tokenFound) {
            $newTokenData = $this->generateToken();
            $newTokenData['uniqueHttpRequestHash'] = $uniqueHttpRequestHash;

            unset($this->uDetails['password_hash']);
            foreach ($newTokenData as $k => $v) {
                $this->uDetails[$k] = $v;
            }

            $this->setCache(
                key: CacheKey::token(token: $newTokenData['token']),
                value: json_encode(
                    value: $this->uDetails
                ),
                expire: Constants::$TOKEN_EXPIRY_TIME
            );
            if (Env::$enableConcurrentLogins) {
                $userTokenKeyData[$newTokenData['token']] = $newTokenData;
            } else {
                $userTokenKeyData = [
                    $newTokenData['token'] => $newTokenData
                ];
            }
            $this->updateDB(userData: $userTokenKeyData);

            $tokenFoundData = &$newTokenData;
            $tokenFound = true;
        }

        if (!$tokenFound) {
            throw new \Exception(
                message: 'Unexpected error occured during login',
                code: HttpStatus::$InternalServerError
            );
        }

        $token = $tokenFoundData['token'];
        
        $this->setCache(
            key: $userTokenKey,
            value: json_encode(
                value: $userTokenKeyData
            ),
            expire: Constants::$TOKEN_EXPIRY_TIME
        );
        if (Env::$enableConcurrentLogins) {
            $this->setCache(
                key: $userConcurrencyKey,
                value: $token,
                expire: Env::$concurrentAccessInterval
            );
        }
        $time = Env::$timestamp - $tokenFoundData['timestamp'];
        $output = [
            'Token' => $tokenFoundData['token'],
            'Expires' => (Constants::$TOKEN_EXPIRY_TIME - $time)
        ];

        $this->api->initResponse();
        $this->api->res->dataEncode->startObject();
        $this->api->res->dataEncode->addKeyData(key: 'Results', data: $output);
    }

    /**
     * Update token details in DB for respective account
     *
     * @param array $userData Token Data
     *
     * @return void
     */
    private function updateDB(&$userData): void
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
                ':token' => json_encode($userData),
                ':id' => $this->uDetails['id']
            ]
        );
    }

    /**
     * Outputs active/newly generated session details
     *
     * @return void
     */
    private function startSession(): void
    {
        $uniqueHttpRequestHash = $this->api->http['hash'];

        $userSessionKey = CacheKey::userSessionId(
            uID: $this->uDetails['id']
        );

        $userSessionKeyExist = false;
        $userSessionKeyData = [];
        if ($this->cacheExists(key: $userSessionKey)) {
            $userSessionKeyExist = true;
            $userSessionKeyData = json_decode(
                json: $this->getCache(
                    key: $userSessionKey
                ),
                associative: true
            );
        }

        if (Env::$enableConcurrentLogins) {
            $userConcurrencyKey = CacheKey::userConcurrency(
                uID: $this->uDetails['id']
            );

            $userConcurrencyKeyExist = false;
            $userConcurrencyKeyData = '';
            if ($this->cacheExists(key: $userConcurrencyKey)) {
                $userConcurrencyKeyExist = true;
                $userConcurrencyKeyData = $this->getCache(
                    key: $userConcurrencyKey
                );
            }
        }

        $sessionFound = false;
        $sessionFoundData = [];
        if ($userSessionKeyExist) {
            if (count($userSessionKeyData) > 0) {
                foreach ($userSessionKeyData as $sessionId => $tData) {
                    if (Env::$enableConcurrentLogins) {
                        if (
                            $tData['uniqueHttpRequestHash'] === $uniqueHttpRequestHash
                            && $userConcurrencyKeyExist
                            && $userConcurrencyKeyData === $sessionId
                            && $sessionId === session_id()
                        ) {
                            $timeLeft = Env::$timestamp - $tData['sessionExpiryTimestamp'];
                            if ((Constants::$TOKEN_EXPIRY_TIME - $timeLeft) > 0) {
                                $sessionFoundData = $tData;
                                $sessionFound = true;
                                continue;
                            }
                        }
                    } else {
                        if (
                            $tData['uniqueHttpRequestHash'] === $uniqueHttpRequestHash
                            && $sessionId === session_id()
                        ) {
                            $timeLeft = Env::$timestamp - $tData['sessionExpiryTimestamp'];
                            if ((Constants::$TOKEN_EXPIRY_TIME - $timeLeft) > 0) {
                                $sessionFoundData = $tData;
                                $sessionFound = true;
                                continue;
                            }
                        }
                    }
                    if (isset($tData['sessionExpiryTimestamp'])) {
                        $timeLeft = Env::$timestamp - $tData['sessionExpiryTimestamp'];
                        if ((Constants::$TOKEN_EXPIRY_TIME - $timeLeft) <= 0) {
                            Session::deleteSession(sessionId: $sessionId);
                            unset($userSessionKeyData[$sessionId]);
                        }
                    }
                }
                if (Env::$enableConcurrentLogins) {
                    if (count($userSessionKeyData) >= Env::$maxConcurrentLogins) {
                        throw new \Exception(
                            message: 'Account already in use. '
                                . 'Please try after ' . Env::$concurrentAccessInterval . ' second(s)',
                            code: HttpStatus::$Conflict
                        );
                    }
                }
            } else {
                $this->deleteCache(key: $userSessionKey);
            }
        }

        if (!$sessionFound) {
            Session::sessionStartReadWrite();
            $newSessionData = [
                'sessionId' => session_id(),
                'timestamp' => Env::$timestamp,
                'uniqueHttpRequestHash' => $uniqueHttpRequestHash,
                'sessionExpiryTimestamp' => (Env::$timestamp + Constants::$TOKEN_EXPIRY_TIME)
            ];

            unset($this->uDetails['password_hash']);
            foreach ($newSessionData as $k => $v) {
                $this->uDetails[$k] = $v;
            }

            $_SESSION = $this->uDetails;

            if (Env::$enableConcurrentLogins) {
                $userSessionKeyData[$newSessionData['sessionId']] = $newSessionData;
            } else {
                $userSessionKeyData = [
                    $newSessionData['sessionId'] => $newSessionData
                ];
            }
            $this->updateDB(userData: $userSessionKeyData);

            $sessionFoundData = &$newSessionData;
            $sessionFound = true;
        }

        if (!$sessionFound) {
            throw new \Exception(
                message: 'Unexpected error occured during login',
                code: HttpStatus::$InternalServerError
            );
        }

        $sessionId = $sessionFoundData['sessionId'];
        
        $this->setCache(
            key: $userSessionKey,
            value: json_encode(
                value: $userSessionKeyData
            ),
            expire: Constants::$TOKEN_EXPIRY_TIME
        );
        if (Env::$enableConcurrentLogins) {
            $this->setCache(
                key: $userConcurrencyKey,
                value: $sessionId,
                expire: Env::$concurrentAccessInterval
            );
        }
        $time = Env::$timestamp - $sessionFoundData['sessionExpiryTimestamp'];
        $output = [
            'SessionId' => $sessionFoundData['sessionId'],
            'Expires' => (Constants::$TOKEN_EXPIRY_TIME - $time)
        ];

        $this->api->initResponse();
        $this->api->res->dataEncode->startObject();
        $this->api->res->dataEncode->addKeyData(key: 'Results', data: $output);
    }

    /**
     * Checks if cache key exist
     *
     * @param string $key Cache key
     *
     * @return mixed
     */
    private function cacheExists($key) {
        return DbFunctions::$gCacheServer->cacheExists(key: $key);
    }

    /**
     * Get cache on basis of key
     *
     * @param string $key Cache key
     *
     * @return mixed
     */
    private function getCache($key) {
        return DbFunctions::$gCacheServer->getCache(key: $key);
    }

    /**
     * Set cache on basis of key
     *
     * @param string $key    Cache key
     * @param string $value  Cache value
     * @param int    $expire Seconds to expire. Default 0 - doesn't expire
     *
     * @return mixed
     */
    private function setCache($key, $value, $expire = 0) {
        return DbFunctions::$gCacheServer->setCache(
            key: $key,
            value: $value,
            expire: $expire
        );
    }

    /**
     * Delete basis of key
     *
     * @param string $key Cache key
     *
     * @return mixed
     */
    private function deleteCache($key) {
        return DbFunctions::$gCacheServer->deleteCache(key: $key);
    }
}
