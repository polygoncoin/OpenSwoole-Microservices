<?php
namespace Microservices\App;

use Microservices\App\AppTrait;
use Microservices\App\Constants;
use Microservices\App\Common;
use Microservices\App\Env;
use Microservices\App\Hook;
use Microservices\App\HttpStatus;
use Microservices\App\Validator;
use Microservices\App\Web;
use Microservices\App\Servers\Database\AbstractDatabase;

/**
 * Class to initialize DB Write operation
 *
 * This class process the POST/PUT/PATCH/DELETE api request
 *
 * @category   CRUD Write
 * @package    Microservices
 * @author     Ramesh Narayan Jangid
 * @copyright  Ramesh Narayan Jangid
 * @version    Release: @1.0.0@
 * @since      Class available since Release 1.0.0
 */
class Write
{
    use AppTrait;

    /**
     * Database Object
     *
     * @var null|AbstractDatabase
     */
    public $db = null;

    /**
     * Microservices Collection of Common Objects
     *
     * @var null|Common
     */
    private $c = null;

    /**
     * Trigger Web API Object
     *
     * @var null|Web
     */
    private $web = null;

    /**
     * Hook Object
     *
     * @var null|Hook
     */
    private $hook = null;

    /**
     * Operate DML As Transactions
     *
     * @var null|Web
     */
    private $operateAsTransaction = null;

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
        return true;
    }

    /**
     * Process
     *
     * @return boolean
     */
    public function process()
    {
        $Env = __NAMESPACE__ . '\Env';
        $session = &$this->c->httpRequest->session;

        // Load Queries
        $writeSqlConfig = include $this->c->httpRequest->__FILE__;

        // Rate Limiting request if configured for Route Queries.
        $this->rateLimitRoute($writeSqlConfig);

        // Lag Response
        $this->lagResponse($writeSqlConfig);

        // Operate as Transaction (BEGIN COMMIT else ROLLBACK on error)
        $this->operateAsTransaction = isset($writeSqlConfig['isTransaction']) ? $writeSqlConfig['isTransaction'] : false;

        // Set Server mode to execute query on - Read / Write Server
        $this->c->httpRequest->db = $this->c->httpRequest->setDbConnection('Master');
        $this->db = &$this->c->httpRequest->db;

        // Use results in where clause of sub queries recursively
        $useHierarchy = $this->getUseHierarchy($writeSqlConfig, 'useHierarchy');

        if (
            (Env::$allowConfigRequest && $this->c->httpRequest->isConfigRequest)
        ) {
            $this->processWriteConfig($writeSqlConfig, $useHierarchy);
        } else {
            $this->processWrite($writeSqlConfig, $useHierarchy);
        }

        if (isset($writeSqlConfig['affectedCacheKeys'])) {
            for ($i = 0, $iCount = count($writeSqlConfig['affectedCacheKeys']); $i < $iCount; $i++) {
                $this->c->httpRequest->delDmlCache($writeSqlConfig['affectedCacheKeys'][$i]);
            }
        }

        return true;
    }

    /**
     * Process write function for configuration
     *
     * @param array   $writeSqlConfig Config from file
     * @param boolean $useHierarchy   Use results in where clause of sub queries recursively
     * @return void
     */
    private function processWriteConfig(&$writeSqlConfig, $useHierarchy)
    {
        $this->c->httpResponse->dataEncode->startObject('Config');
        $this->c->httpResponse->dataEncode->addKeyData('Route', $this->c->httpRequest->configuredUri);
        $this->c->httpResponse->dataEncode->addKeyData('Payload', $this->getConfigParams($writeSqlConfig, $isFirstCall = true, $useHierarchy));
        $this->c->httpResponse->dataEncode->endObject();
    }

    /**
     * Process Function to insert/update
     *
     * @param array   $writeSqlConfig Config from file
     * @param boolean $useHierarchy   Use results in where clause of sub queries recursively
     * @return void
     * @throws \Exception
     */
    private function processWrite(&$writeSqlConfig, $useHierarchy)
    {
        // Check for payloadType
        if (isset($writeSqlConfig['__PAYLOAD-TYPE__'])) {
            if ($this->c->httpRequest->session['payloadType'] !== $writeSqlConfig['__PAYLOAD-TYPE__']) {
                throw new \Exception('Invalid paylaod type', HttpStatus::$BadRequest);
            }
            // Check for maximum number of objects supported when payloadType is Array
            if (
                $writeSqlConfig['__PAYLOAD-TYPE__'] === 'Array'
                && isset($writeSqlConfig['__MAX-PAYLOAD-OBJECTS__'])
                && ($this->c->httpRequest->jsonDecode->count() > $writeSqlConfig['__MAX-PAYLOAD-OBJECTS__'])
            ) {
                throw new \Exception('Maximum supported paylaod count is ' . $writeSqlConfig['__MAX-PAYLOAD-OBJECTS__'], HttpStatus::$BadRequest);
            }
        }

        // Set required fields
        $this->c->httpRequest->session['requiredArr'] = $this->getRequired($writeSqlConfig, $isFirstCall = true, $useHierarchy);

        if ($this->c->httpRequest->session['payloadType'] === 'Object') {
            $this->c->httpResponse->dataEncode->startObject('Results');
        } else {
            $this->c->httpResponse->dataEncode->startArray('Results');
        }

        // Perform action
        $i_count = $this->c->httpRequest->session['payloadType'] === 'Object' ? 1 : $this->c->httpRequest->jsonDecode->count();

        $configKeys = [];
        $payloadIndexes = [];
        for ($i=0; $i < $i_count; $i++) {
            $_configKeys = $configKeys;
            $_payloadIndexes = $payloadIndexes;
            if ($i === 0) {
                if ($this->c->httpRequest->session['payloadType'] === 'Object') {
                    $_payloadIndexes[] = '';
                } else {
                    $_payloadIndexes[] = "{$i}";
                }
            } else {
                $_payloadIndexes[] = "{$i}";
            }

            // Check for Idempotent Window
            list($idempotentWindow, $hashKey, $hashJson) = $this->checkIdempotent($writeSqlConfig, $_payloadIndexes);

            // Begin DML operation
            if (is_null($hashJson)) {
                if ($this->operateAsTransaction) {$this->db->begin();}
                $response = [];
                $this->writeDB($writeSqlConfig, $_payloadIndexes, $_configKeys, $useHierarchy, $response, $this->c->httpRequest->session['requiredArr']);
                if (!$this->operateAsTransaction || ($this->operateAsTransaction && $this->db->beganTransaction === true)) { // Success
                    if ($this->operateAsTransaction) {
                        $this->db->commit();
                    }
                    $arr = [
                        'Status' => HttpStatus::$Created,
                        'Payload' => $this->c->httpRequest->jsonDecode->getCompleteArray(implode(':', $_payloadIndexes)),
                        'Response' => &$response
                    ];
                    if ($idempotentWindow) {
                        $this->c->httpRequest->cache->setCache($hashKey, json_encode($arr), $idempotentWindow);
                    }
                } else { // Failure
                    $this->c->httpResponse->httpStatus = HttpStatus::$BadRequest;
                    $arr = [
                        'Status' => HttpStatus::$BadRequest,
                        'Payload' => $this->c->httpRequest->jsonDecode->getCompleteArray(implode(':', $_payloadIndexes)),
                        'Error' => &$response
                    ];
                }
            } else {
                $arr = json_decode($hashJson, true);
            }
            if (isset($_payloadIndexes[$i]) && $_payloadIndexes[$i] === '') {
                foreach ($arr as $k => $v) {
                    $this->c->httpResponse->dataEncode->addKeyData($k, $v);
                }
            } else {
                $this->c->httpResponse->dataEncode->encode($arr);
            }
        }

        if ($this->c->httpRequest->session['payloadType'] === 'Object') {
            $this->c->httpResponse->dataEncode->endObject();
        } else {
            $this->c->httpResponse->dataEncode->endArray();
        }
    }

    /**
     * Function to insert/update sub queries recursively
     *
     * @param array   $writeSqlConfig Config from file
     * @param array   $payloadIndexes Payload Indexes
     * @param array   $configKeys     Config Keys
     * @param boolean $useHierarchy   Use results in where clause of sub queries recursively
     * @param array   $response       Response by reference
     * @param array   $required       Required fields
     * @return void
     * @throws \Exception
     */
    private function writeDB(&$writeSqlConfig, $payloadIndexes, $configKeys, $useHierarchy, &$response, &$required)
    {
        if (isset($payloadIndexes[0]) && $payloadIndexes[0] === '') {
            $payloadIndexes = array_shift($payloadIndexes);
        }
        if (!is_array($payloadIndexes)) $payloadIndexes = [];

        $payloadIndex = is_array($payloadIndexes) ? implode(':', $payloadIndexes) : '';
        $isAssoc = $this->c->httpRequest->jsonDecode->jsonType($payloadIndex) === 'Object';
        $i_count = $isAssoc ? 1 : $this->c->httpRequest->jsonDecode->count($payloadIndex);

        $counter = 0;
        for ($i=0; $i < $i_count; $i++) {
            $_payloadIndexes = $payloadIndexes;
            if ($this->operateAsTransaction && !$this->db->beganTransaction) {
                $response['Error'] = 'Transaction rolled back';
                return;
            }

            if ($isAssoc && $i > 0) {
                return;
            }

            if (!$isAssoc && !$useHierarchy) {
                array_push($_payloadIndexes, $i);
            }
            $payloadIndex = is_array($_payloadIndexes) ? implode(':', $_payloadIndexes) : '';

            if (!$this->c->httpRequest->jsonDecode->isset($payloadIndex)) {
                throw new \Exception("Paylaod key '{$payloadIndex}' not set", HttpStatus::$NotFound);
            }

            $this->c->httpRequest->session['payload'] = $this->c->httpRequest->jsonDecode->get($payloadIndex);

            if (count($required)) {
                $this->c->httpRequest->session['required'] = $required;
            } else {
                $this->c->httpRequest->session['required'] = [];
            }

            // Validation
            if (!$this->isValidPayload($writeSqlConfig)) {
                continue;
            }

            // Execute Pre Sql Hooks
            if (isset($writeSqlConfig['__PRE-SQL-HOOKS__'])) {
                if (is_null($this->hook)) {
                    $this->hook = new Hook($this->c);
                }
                $this->hook->triggerHook($writeSqlConfig['__PRE-SQL-HOOKS__']);
            }

            // Get Sql and Params
            list($sql, $sqlParams, $errors) = $this->getSqlAndParams($writeSqlConfig);
            if (!empty($errors)) {
                $response['Error'] = $errors;
                $this->db->rollback();
                return;
            }

            // Execute Query
            $this->db->execDbQuery($sql, $sqlParams);
            if ($this->operateAsTransaction && !$this->db->beganTransaction) {
                $response['Error'] = 'Something went wrong';
                return;
            }
            if (!$isAssoc && !isset($response[$counter])) {
                $response[$counter] = [];
            }
            if (isset($writeSqlConfig['__INSERT-IDs__'])) {
                $insertId = $this->db->lastInsertId();
                if ($isAssoc) {
                    $response[$writeSqlConfig['__INSERT-IDs__']] = $insertId;
                } else {
                    $response[$counter][$writeSqlConfig['__INSERT-IDs__']] = $insertId;
                }
                $this->c->httpRequest->session['__INSERT-IDs__'][$writeSqlConfig['__INSERT-IDs__']] = $insertId;
            } else {
                $affectedRows = $this->db->affectedRows();
                if ($isAssoc) {
                    $response['affectedRows'] = $affectedRows;
                } else {
                    $response[$counter]['affectedRows'] = $affectedRows;
                }
            }
            $this->db->closeCursor();

            // triggers
            if (isset($writeSqlConfig['__TRIGGERS__'])) {
                if (is_null($this->web)) {
                    $this->web = new Web($this->c);
                }
                if ($isAssoc) {
                    $response['__TRIGGERS__'] = $this->web->triggerConfig($writeSqlConfig['__TRIGGERS__']);
                } else {
                    $response[$counter]['__TRIGGERS__'] = $this->web->triggerConfig($writeSqlConfig['__TRIGGERS__']);
                }
            }

            // Execute Post Sql Hooks
            if (isset($writeSqlConfig['__POST-SQL-HOOKS__'])) {
                if (is_null($this->hook)) {
                    $this->hook = new Hook($this->c);
                }
                $this->hook->triggerHook($writeSqlConfig['__POST-SQL-HOOKS__']);
            }

            // subQuery for payload
            if (isset($writeSqlConfig['__SUB-QUERY__'])) {
                $this->callWriteDB($isAssoc, $writeSqlConfig, $_payloadIndexes, $configKeys, $useHierarchy, $response, $required);
            }

            if (!$isAssoc) {
                $counter++;
            }
        }
    }

    /**
     * Validate and call writeDB
     *
     * @param boolean $isAssoc        Is Associative array
     * @param array   $writeSqlConfig Config from file
     * @param array   $payloadIndexes Payload Indexes
     * @param array   $configKeys     Config Keys
     * @param boolean $useHierarchy   Use results in where clause of sub queries recursively
     * @param array   $response       Response by reference
     * @param array   $required       Required fields
     * @return void
     */
    private function callWriteDB($isAssoc, &$writeSqlConfig, $payloadIndexes, $configKeys, $useHierarchy, &$response, &$required)
    {
        if ($useHierarchy) {
            $row = $this->c->httpRequest->session['payload'];
            $this->resetFetchData($dataPayloadType = 'sqlPayload', $configKeys, $row);
        }

        if (isset($payloadIndexes[0]) && $payloadIndexes[0] === '') {
            $payloadIndexes = array_shift($payloadIndexes);
        }
        if (!is_array($payloadIndexes)) $payloadIndexes = [];

        if (isset($writeSqlConfig['__SUB-QUERY__']) && $this->isAssoc($writeSqlConfig['__SUB-QUERY__'])) {
            foreach ($writeSqlConfig['__SUB-QUERY__'] as $module => &$_writeSqlConfig) {
                $_payloadIndexes = $payloadIndexes;
                $_configKeys = $configKeys;
                $modulePayloadKey = is_array($_payloadIndexes) ? implode(':', $_payloadIndexes) : '';
                if ($useHierarchy) { // use parent data of a payload
                    array_push($_payloadIndexes, $module);
                    array_push($_configKeys, $module);
                    if ($this->c->httpRequest->jsonDecode->isset($modulePayloadKey)) {
                        $_required = &$required[$module] ?? [];
                    } else {
                        throw new \Exception("Invalid payload: Module '{$module}' missing", HttpStatus::$NotFound);
                    }
                } else {
                    $_required = $required;
                }
                $_useHierarchy = $useHierarchy ?? $this->getUseHierarchy($_writeSqlConfig, 'useHierarchy');
                if ($isAssoc) {
                    $response[$module] = [];
                    $_response = &$response[$module];
                } else {
                    $response[$counter][$module] = [];
                    $_response = &$response[$counter][$module];
                }
                $this->writeDB($_writeSqlConfig, $_payloadIndexes, $_configKeys, $_useHierarchy, $_response, $_required);
            }
        }
    }

    /**
     * Checks if the payload is valid
     *
     * @param array $writeSqlConfig Config from file
     * @return boolean
     */
    private function isValidPayload($writeSqlConfig)
    {
        $return = true;
        $isValidData = true;
        if (isset($writeSqlConfig['__VALIDATE__'])) {
            list($isValidData, $errors) = $this->validate($writeSqlConfig['__VALIDATE__']);
            if ($isValidData !== true) {
                $this->c->httpResponse->httpStatus = HttpStatus::$BadRequest;
                $this->c->httpResponse->dataEncode->startObject();
                $this->c->httpResponse->dataEncode->addKeyData('Payload', $this->c->httpRequest->session['payload']);
                $this->c->httpResponse->dataEncode->addKeyData('Error', $errors);
                $this->c->httpResponse->dataEncode->endObject();
                $return = false;
            }
        }
        return $return;
    }
}
