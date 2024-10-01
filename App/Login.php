<?php
namespace Microservices\App;

use Microservices\App\Constants;
use Microservices\App\CacheKey;
use Microservices\App\Common;
use Microservices\App\Env;

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
     * @var string
     */
    private $username = null;

    /**
     * Password for login
     *
     * @var string
     */
    private $password = null;

    /**
     * Details pertaining to user.
     *
     * @var array
     */
    private $userDetails;
    
    /**
     * IDs
     */
    private $userId;
    private $groupId;

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
     * @var Microservices\App\Common
     */
    private $c = null;

    /**
     * Cache Keys
     */
    private $cu_key = null;
    private $t_key = null;
    private $ut_key = null;
    private $cidr_key = null;

    /**
     * Constructor
     * 
     * @param Microservices\App\Common $common
     */
    public function __construct(Common &$common)
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
        $this->c->httpRequest->checkHost();

        return true;
    }

    /**
     * Process
     *
     * @return boolean
     */
    public function process()
    {
        $this->performBasicCheck();
        $this->loadUser();
        $this->validateRequestIp();
        $this->validatePassword();
        $this->outputTokenDetails();

        return true;
    }

    /**
     * Function to perform basic checks
     *
     * @return void
     */
    private function performBasicCheck()
    {
        // Check request method is POST.
        if ($this->c->httpRequest->REQUEST_METHOD !== Constants::$POST) {
            throw new \Exception('Invalid request method', 404);
        }

        $this->c->httpRequest->jsonDecode->validate();
        $this->c->httpRequest->jsonDecode->indexJSON();
        $this->payload = $this->c->httpRequest->jsonDecode->get();

        // Check for required input variables
        foreach (array('username','password') as $value) {
            if (!isset($this->payload[$value]) || empty($this->payload[$value])) {
                throw new \Exception('Missing required parameters', 404);
            } else {
                $this->$value = $this->payload[$value];
            }
        }
    }

    /**
     * Function to load user details from cache
     *
     * @return void
     */
    private function loadUser()
    {
        $clientId = $this->c->httpRequest->clientInfo['client_id'];
        $this->cu_key = CacheKey::ClientUser($clientId,$this->payload['username']);
        // Redis - one can find the userID from username.
        if ($this->c->httpRequest->cache->cacheExists($this->cu_key)) {
            $this->userDetails = json_decode($this->c->httpRequest->cache->getCache($this->cu_key), true);
            $this->userId = $this->userDetails['user_id'];
            $this->groupId = $this->userDetails['group_id'];
            if (empty($this->userId) || empty($this->groupId)) {
                throw new \Exception('Invalid credentials', 401);
            }            
        } else {
            throw new \Exception('Invalid credentials', 401);
        }
    }

    /**
     * Function to validate source ip.
     *
     * @return void
     */
    private function validateRequestIp()
    {
        // Redis - one can find the userID from username.
        $this->cidr_key = CacheKey::CIDR($this->groupId);
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
                throw new \Exception('IP not supported', 401);
            }
        }
    }

    /**
     * Validates password from its hash present in cache
     *
     * @return void
     */
    private function validatePassword()
    {
        // get hash from cache and compares with password
        if (!password_verify($this->password, $this->userDetails['password_hash'])) {
            throw new \Exception('Invalid credentials', 401);
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
            $this->t_key = CacheKey::Token($token);
            if (!$this->c->httpRequest->cache->cacheExists($this->t_key)) {
                $this->c->httpRequest->cache->setCache($this->t_key, '{}', Constants::$TOKEN_EXPIRY_TIME);
                $tokenDetails = ['token' => $token, 'timestamp' => $this->timestamp];
                break;
            }
        }
        return $tokenDetails;
    }

    /**
     * Outputs active/newly generated token details.
     *
     * @return void
     */
    private function outputTokenDetails()
    {
        $this->timestamp = time();
        $tokenFound = false;

        $this->ut_key = CacheKey::UserToken($this->userId);
        if ($this->c->httpRequest->cache->cacheExists($this->ut_key)) {
            $tokenDetails = json_decode($this->c->httpRequest->cache->getCache($this->ut_key), true);
            $this->t_key = CacheKey::Token($tokenDetails['token']);
            if ($this->c->httpRequest->cache->cacheExists($this->t_key)) {
                if ((Constants::$TOKEN_EXPIRY_TIME - ($this->timestamp - $tokenDetails['timestamp'])) > 0) {
                    $tokenFound = true;
                } else {
                    $this->c->httpRequest->cache->deleteCache($this->t_key);
                }
            }
        }

        if (!$tokenFound) {
            $tokenDetails = $this->generateToken();
            // We set this to have a check first if multiple request/attack occurs.
            $this->c->httpRequest->cache->setCache($this->ut_key, json_encode($tokenDetails), Constants::$TOKEN_EXPIRY_TIME);
            $this->t_key = CacheKey::Token($tokenDetails['token']);
            $this->c->httpRequest->cache->setCache($this->t_key, json_encode($this->userDetails), Constants::$TOKEN_EXPIRY_TIME);
            $this->updateDB($tokenDetails);
        }

        $output = [
            'Token' => $tokenDetails['token'],
            'Expires' => (Constants::$TOKEN_EXPIRY_TIME - ($this->timestamp - $tokenDetails['timestamp']))
        ];

        $this->c->httpResponse->jsonEncode->addKeyValue('Results', $output);
    }

    private function updateDB($tokenDetails)
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
            ':user_id' => $this->userId
        ]);
    }
}
