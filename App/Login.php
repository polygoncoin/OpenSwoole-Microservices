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
     * Database object
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
    private $userDetails;

    /**
     * Current timestamp
     *
     * @var int
     */
    private $timestamp;

    /**
     * Payload
     *
     * @var array
     */
    private $payload = [];

    /**
     * Common object
     *
     * @var null|Common
     */
    private $c = null;

    /**
     * Constructor
     *
     * @param Common $common Common object
     */
    public function __construct(Common &$common)
    {
        $this->c = &$common;
    }

    /**
     * Initialize
     *
     * @return bool
     */
    public function init(): bool
    {
        $this->c->req->loadClientDetails();

        return true;
    }

    /**
     * Process
     *
     * @return bool
     */
    public function process(): bool
    {
        // Check request method is POST
        if ($this->c->req->METHOD !== Constants::$POST) {
            throw new \Exception(
                message: 'Invalid request method',
                code: HttpStatus::$NotFound
            );
        }

        $this->loadPayload();
        $this->loadUserDetails();
        $this->validateRequestIp();
        $this->validatePassword();
        $this->outputTokenDetails();

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
        if ($this->c->req->METHOD !== Constants::$POST) {
            throw new \Exception(
                message: 'Invalid request method',
                code: HttpStatus::$NotFound
            );
        }

        $this->c->req->loadPayload();
        $this->payload = $this->c->req->dataDecode->get();

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
        $cID = $this->c->req->s['cDetails']['id'];
        $clientUserKey = CacheKey::clientUser(
            cID: $cID,
            username: $this->payload['username']
        );
        // Redis - one can find the userID from client username
        if (!$this->c->req->cache->cacheExists(key: $clientUserKey)) {
            throw new \Exception(
                message: 'Invalid credentials',
                code: HttpStatus::$Unauthorized
            );
        }
        $this->userDetails = json_decode(
            json: $this->c->req->cache->getCache(
                key: $clientUserKey
            ),
            associative: true
        );
        if (
            empty($this->userDetails['id'])
            || empty($this->userDetails['id'])
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
        // Redis - one can find the userID from username
        $cidrKey = CacheKey::cidr(gID: $this->userDetails['group_id']);
        if ($this->c->req->cache->cacheExists(key: $cidrKey)) {
            $cidrs = json_decode(
                json: $this->c->req->cache->getCache(
                    key: $cidrKey
                ),
                associative: true
            );
            $ipNumber = ip2long(ip: $this->c->req->IP);
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
    private function validatePassword(): void
    {
        // get hash from cache and compares with password
        if (
            !password_verify(
                password: $this->password,
                hash: $this->userDetails['password_hash']
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
                !$this->c->req->cache->cacheExists(
                    key: CacheKey::token(token: $token)
                )
            ) {
                $this->c->req->cache->setCache(
                    key: CacheKey::token(token: $token),
                    value: '{}',
                    expire: Constants::$TOKEN_EXPIRY_TIME
                );
                $tokenDetails = [
                    'token' => $token,
                    'timestamp' => $this->timestamp
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
        $this->timestamp = time();
        $tokenFound = false;

        $userTokenKey = CacheKey::userToken(
            uID: $this->userDetails['id']
        );
        if ($this->c->req->cache->cacheExists(key: $userTokenKey)) {
            $tokenDetails = json_decode(
                json: $this->c->req->cache->getCache(
                    key: $userTokenKey
                ),
                associative: true
            );

            if (
                $this->c->req->cache->cacheExists(
                    key: CacheKey::token(
                        token: $tokenDetails['token']
                    )
                )
            ) {
                $time = $this->timestamp - $tokenDetails['timestamp'];
                if ((Constants::$TOKEN_EXPIRY_TIME - $time) > 0) {
                    $tokenFound = true;
                } else {
                    $this->c->req->cache->deleteCache(
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
            $this->c->req->cache->setCache(
                key: $userTokenKey,
                value: json_encode(
                    value: $tokenDetails
                ),
                expire: Constants::$TOKEN_EXPIRY_TIME
            );
            unset($this->userDetails['password_hash']);
            $this->c->req->cache->setCache(
                key: CacheKey::token(token: $tokenDetails['token']),
                value: json_encode(
                    value: $this->userDetails
                ),
                expire: Constants::$TOKEN_EXPIRY_TIME
            );
            $this->updateDB(tokenDetails: $tokenDetails);
        }

        $time = $this->timestamp - $tokenDetails['timestamp'];
        $output = [
            'Token' => $tokenDetails['token'],
            'Expires' => (Constants::$TOKEN_EXPIRY_TIME - $time)
        ];

        $this->c->initResponse();
        $this->c->res->dataEncode->startObject();
        $this->c->res->dataEncode->addKeyData(key: 'Results', data: $output);
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
        $this->c->req->db = $this->c->req->setDbConnection(fetchFrom: 'Master');
        $this->db = &$this->c->req->db;

        $userTable = Env::$client_users;
        $this->db->execDbQuery(
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
                ':id' => $this->userDetails['id']
            ]
        );
    }
}
