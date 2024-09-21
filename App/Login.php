<?php
namespace Microservices\App;

use Microservices\App\Constants;
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
     * User ID
     *
     * @var integer
     */
    private $userId;

    /**
     * Group ID
     *
     * @var integer
     */
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
        return $this->c->httpResponse->isSuccess();
    }

    /**
     * Process
     *
     * @return boolean
     */
    public function process()
    {
        if ($this->c->httpResponse->isSuccess()) $this->performBasicCheck();
        if ($this->c->httpResponse->isSuccess()) $this->loadUser();
        if ($this->c->httpResponse->isSuccess()) $this->validateRequestIp();
        if ($this->c->httpResponse->isSuccess()) $this->validatePassword();
        if ($this->c->httpResponse->isSuccess()) $this->outputTokenDetails();

        return $this->c->httpResponse->isSuccess();
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
            $this->c->httpResponse->return4xx(404, 'Invalid request method');
            return;
        }

        if ($this->c->httpRequest->REQUEST_METHOD === Constants::$POST) {
            $this->c->httpRequest->jsonDecode->validate();
            $this->c->httpRequest->jsonDecode->indexJSON();
            $this->payload = $this->c->httpRequest->jsonDecode->get();
        }

        // Check for required input variables
        foreach (array('username','password') as $value) {
            if (!isset($this->payload[$value]) || empty($this->payload[$value])) {
                $this->c->httpResponse->return4xx(404, 'Missing required parameters');
                return;
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
        // Redis - one can find the userID from username.
        if ($this->c->httpRequest->cache->cacheExists("user:{$this->payload['username']}")) {
            $this->userDetails = json_decode($this->c->httpRequest->cache->getCache("user:{$this->payload['username']}"), true);
            $this->userId = $this->userDetails['user_id'];
            $this->groupId = $this->userDetails['group_id'];
            if (empty($this->userId) || empty($this->groupId)) {
                $this->c->httpResponse->return4xx(404, 'Invalid credentials');
                return;
            }            
        } else {
            $this->c->httpResponse->return4xx(404, 'Invalid credentials.');
            return;
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
        if ($this->c->httpRequest->cache->cacheExists("cidr:{$this->groupId}")) {
            $cidrs = json_decode($this->c->httpRequest->cache->getCache("cidr:{$this->groupId}"), true);
            $ipNumber = ip2long($this->c->httpRequest->REMOTE_ADDR);
            $isValidIp = false;
            foreach ($cidrs as $cidr) {
                if ($cidr['start'] <= $ipNumber && $ipNumber <= $cidr['end']) {
                    $isValidIp = true;
                    break;
                }
            }
            if (!$isValidIp) {
                $this->c->httpResponse->return4xx(404, 'IP not supported');
                return;
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
            $this->c->httpResponse->return4xx(404, 'Invalid credentials');
            return;
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
            if (!$this->c->httpRequest->cache->cacheExists($token)) {
                $this->c->httpRequest->cache->setCache($token, '{}', Constants::$TOKEN_EXPIRY_TIME);
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

        if ($this->c->httpRequest->cache->cacheExists("usertoken:{$this->userId}")) {
            $tokenDetails = json_decode($this->c->httpRequest->cache->getCache("usertoken:{$this->userId}"), true);
            if ($this->c->httpRequest->cache->cacheExists($tokenDetails['token'])) {
                if ((Constants::$TOKEN_EXPIRY_TIME - ($this->timestamp - $tokenDetails['timestamp'])) > 0) {
                    $tokenFound = true;
                } else {
                    $this->c->httpRequest->cache->deleteCache($tokenDetails['token']);
                }
            }
        }

        if (!$tokenFound) {
            $tokenDetails = $this->generateToken();
            // We set this to have a check first if multiple request/attack occurs.
            $this->c->httpRequest->cache->setCache("usertoken:{$this->userId}", json_encode($tokenDetails), Constants::$TOKEN_EXPIRY_TIME);
            $this->c->httpRequest->cache->setCache($tokenDetails['token'], json_encode($this->userDetails), Constants::$TOKEN_EXPIRY_TIME);
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
        $userTable = Env::$users;

        $this->c->httpRequest->setDb(
            getenv('globalType'),
            getenv('globalHostname'),
            getenv('globalPort'),
            getenv('globalUsername'),
            getenv('globalPassword'),
            getenv('globalDatabase')
        );

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
