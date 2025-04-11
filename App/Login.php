<?php
namespace Microservices\App;

use Microservices\App\Constants;
use Microservices\App\CacheKey;
use Microservices\App\Common;
use Microservices\App\Env;
use Microservices\App\HttpStatus;

/**
 * Login
 *
 * This class is used for login and generates token for user
 *
 * @category   Login
 * @package    Microservices
 * @author     Ramesh Narayan Jangid
 * @copyright  Ramesh Narayan Jangid
 * @version    Release: @1.0.0@
 * @since      Class available since Release 1.0.0
 */
class Login
{
    /**
     * Username for login
     *
     * @var null|string
     */
    private $username = null;

    /**
     * Password for login
     *
     * @var null|string
     */
    private $password = null;

    /**
     * Details pertaining to user
     *
     * @var array
     */
    private $userDetails;

    /**
     * Current timestamp
     *
     * @var integer
     */
    private $timestamp;

    /**
     * Payload
     *
     * @var array
     */
    private $payload = [];

    /**
     * Microservices Collection of Common Objects
     *
     * @var null|Common
     */
    private $c = null;

    /**
     * Cache Keys
     */
    private $clientUserKey = null;
    private $tokenKey = null;
    private $userTokenKey = null;
    private $cidr_key = null;

    /**
     * Constructor
     *
     * @param Common $common
     */
    public function __construct(&$common)
    {
        $this->c = &$common;
    }

    /**
     * Initialize
     *
     * @return boolean
     */
    public function init()
    {
        $this->c->httpRequest->loadClientDetails();

        return true;
    }

    /**
     * Process
     *
     * @return boolean
     */
    public function process()
    {
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
    private function loadPayload()
    {
        // Check request method is POST
        if ($this->c->httpRequest->REQUEST_METHOD !== Constants::$POST) {
            throw new \Exception('Invalid request method', HttpStatus::$NotFound);
        }

        $this->c->httpRequest->jsonDecode->validate();
        $this->c->httpRequest->jsonDecode->indexJSON();
        $this->payload = $this->c->httpRequest->jsonDecode->get();

        // Check for required conditions variables
        foreach (array('username','password') as $value) {
            if (!isset($this->payload[$value]) || empty($this->payload[$value])) {
                throw new \Exception('Missing required parameters', HttpStatus::$NotFound);
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
    private function loadUserDetails()
    {
        $clientId = $this->c->httpRequest->session['clientDetails']['client_id'];
        $this->clientUserKey = CacheKey::ClientUser($clientId, $this->payload['username']);
        // Redis - one can find the userID from username
        if ($this->c->httpRequest->cache->cacheExists($this->clientUserKey)) {
            $this->userDetails = json_decode($this->c->httpRequest->cache->getCache($this->clientUserKey), true);
            if (empty($this->userDetails['user_id']) || empty($this->userDetails['group_id'])) {
                throw new \Exception('Invalid credentials', HttpStatus::$Unauthorized);
            }
        } else {
            throw new \Exception('Invalid credentials', HttpStatus::$Unauthorized);
        }
    }

    /**
     * Function to validate source ip
     *
     * @return void
     * @throws \Exception
     */
    private function validateRequestIp()
    {
        // Redis - one can find the userID from username
        $this->cidr_key = CacheKey::CIDR($this->userDetails['group_id']);
        if ($this->c->httpRequest->cache->cacheExists($this->cidr_key)) {
            $cidrs = json_decode($this->c->httpRequest->cache->getCache($this->cidr_key), true);
            $ipNumber = ip2long($this->c->httpRequest->REMOTE_ADDR);
            $isValidIp = false;
            foreach ($cidrs as $cidr) {
                if ($cidr['start'] <= $ipNumber && $ipNumber <= $cidr['end']) {
                    $isValidIp = true;
                    break;
                }
            }
            if (!$isValidIp) {
                throw new \Exception('IP not supported', HttpStatus::$Unauthorized);
            }
        }
    }

    /**
     * Validates password from its hash present in cache
     *
     * @return void
     * @throws \Exception
     */
    private function validatePassword()
    {
        // get hash from cache and compares with password
        if (!password_verify($this->password, $this->userDetails['password_hash'])) {
            throw new \Exception('Invalid credentials', HttpStatus::$Unauthorized);
        }
    }

    /**
     * Generates token
     *
     * @return array
     */
    private function generateToken()
    {
        //generates a crypto-secure 64 characters long
        while (true) {
            $token = bin2hex(random_bytes(32));
            $this->tokenKey = CacheKey::Token($token);
            if (!$this->c->httpRequest->cache->cacheExists($this->tokenKey)) {
                $this->c->httpRequest->cache->setCache($this->tokenKey, '{}', Constants::$TOKEN_EXPIRY_TIME);
                $tokenDetails = ['token' => $token, 'timestamp' => $this->timestamp];
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
    private function outputTokenDetails()
    {
        $this->timestamp = time();
        $tokenFound = false;

        $this->userTokenKey = CacheKey::UserToken($this->userDetails['user_id']);
        if ($this->c->httpRequest->cache->cacheExists($this->userTokenKey)) {
            $tokenDetails = json_decode($this->c->httpRequest->cache->getCache($this->userTokenKey), true);
            $this->tokenKey = CacheKey::Token($tokenDetails['token']);
            if ($this->c->httpRequest->cache->cacheExists($this->tokenKey)) {
                if ((Constants::$TOKEN_EXPIRY_TIME - ($this->timestamp - $tokenDetails['timestamp'])) > 0) {
                    $tokenFound = true;
                } else {
                    $this->c->httpRequest->cache->deleteCache($this->tokenKey);
                }
            }
        }

        if (!$tokenFound) {
            $tokenDetails = $this->generateToken();
            // We set this to have a check first if multiple request/attack occurs
            $this->c->httpRequest->cache->setCache($this->userTokenKey, json_encode($tokenDetails), Constants::$TOKEN_EXPIRY_TIME);
            $this->tokenKey = CacheKey::Token($tokenDetails['token']);
            $this->c->httpRequest->cache->setCache($this->tokenKey, json_encode($this->userDetails), Constants::$TOKEN_EXPIRY_TIME);
            $this->updateDB($tokenDetails);
        }

        $output = [
            'Token' => $tokenDetails['token'],
            'Expires' => (Constants::$TOKEN_EXPIRY_TIME - ($this->timestamp - $tokenDetails['timestamp']))
        ];

        $this->c->httpResponse->jsonEncode->addKeyValue('Results', $output);
    }

    /**
     * Update token details in DB for respective account
     *
     * @param array $tokenDetails
     * @return void
     */
    private function updateDB(&$tokenDetails)
    {
        $this->c->httpRequest->setConnection('Master');

        $userTable = Env::$client_users;
        $this->c->httpRequest->db->execDbQuery("
        UPDATE
            `{$userTable}`
        SET
            `token` = :token,
            `token_ts` = :token_ts
        WHERE
            user_id = :user_id",
        [
            ':token' => $tokenDetails['token'],
            ':token_ts' => $tokenDetails['timestamp'],
            ':user_id' => $this->userDetails['user_id']
        ]);
    }
}
