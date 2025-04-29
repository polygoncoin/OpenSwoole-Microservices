<?php
namespace Microservices\App;

use Microservices\App\AppTrait;
use Microservices\App\Constants;
use Microservices\App\Common;
use Microservices\App\Env;
use Microservices\App\HttpStatus;
use Microservices\App\Validator;
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
     * Idempotent Window (Default 0 - Disabled)
     *
     * @var integer
     */
    private $idempotentWindow = 0;

    /**
     * Microservices Collection of Common Objects
     *
     * @var null|Common
     */
    private $c = null;

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
        $writeSqlConfig = include $this->c->httpRequest->__file__;

        // Check for Idempotent Window
        if (
            isset($writeSqlConfig['idempotentWindow'])
            && is_numeric($writeSqlConfig['idempotentWindow'])
            && $writeSqlConfig['idempotentWindow'] > 0
        ) {
            $this->idempotentWindow = (int)$writeSqlConfig['idempotentWindow'];
        }

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
        $this->c->httpResponse->jsonEncode->startObject('Config');
        $this->c->httpResponse->jsonEncode->addKeyValue('Route', $this->c->httpRequest->configuredUri);
        $this->c->httpResponse->jsonEncode->addKeyValue('Payload', $this->getConfigParams($writeSqlConfig, $isFirstCall = true, $useHierarchy));
        $this->c->httpResponse->jsonEncode->endObject();
    }

    /**
     * Process Function to insert/update
     *
     * @param array   $writeSqlConfig Config from file
     * @param boolean $useHierarchy   Use results in where clause of sub queries recursively
     * @return void
     */
    private function processWrite(&$writeSqlConfig, $useHierarchy)
    {
        // Set required fields
        $this->c->httpRequest->session['requiredArr'] = $this->getRequired($writeSqlConfig, $isFirstCall = true, $useHierarchy);

        if ($this->c->httpRequest->session['payloadType'] === 'Object') {
            $this->c->httpResponse->jsonEncode->startObject('Results');
        } else {
            $this->c->httpResponse->jsonEncode->startArray('Results');
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

            $hashJson = null;
            if ($this->idempotentWindow) {
                $payloadSignature = [
                    'IdempotentSecret' => getenv('IdempotentSecret'),
                    'idempotentWindow' => $this->idempotentWindow,
                    'httpMethod' => $this->REQUEST_METHOD,
                    '$_GET' => $this->httpRequestDetails['get'],
                    'clientId' => $this->c->httpRequest->clientId,
                    'groupId' => $this->c->httpRequest->groupId,
                    'userId' => $this->c->httpRequest->userId,
                    'payload' => $this->c->httpRequest->jsonDecode->get(implode(':', $_payloadIndexes))
                ];
                $hash = hash_hmac('sha256', json_encode($payloadSignature), getenv('IdempotentSecret'));
                $hashKey = md5($hash);
                if ($this->cache->cacheExists($hashKey)) {
                    $hashJson = str_replace('JSON', $this->cache->getCache($hashKey), '{"Idempotent": JSON, "Status": 200}');
                }
            }

            if (is_null($hashJson)) {
                // Begin DML operation
                $this->db->begin();
                $response = [];
                $this->writeDB($writeSqlConfig, $_payloadIndexes, $_configKeys, $useHierarchy, $response, $this->c->httpRequest->session['requiredArr']);
                if ($this->db->beganTransaction === true) {
                    $this->db->commit();
                    $arr = [
                        'Status' => HttpStatus::$Created,
                        'Payload' => $this->c->httpRequest->jsonDecode->getCompleteArray(implode(':', $_payloadIndexes)),
                        'Response' => &$response
                    ];
                    if ($this->idempotentWindow) {
                        $this->c->httpRequest->cache->setCache($hashKey, json_encode($arr), $this->idempotentWindow);
                    }
                } else {
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
            $this->c->httpResponse->jsonEncode->encode($arr);
        }

        if ($this->c->httpRequest->session['payloadType'] === 'Object') {
            $this->c->httpResponse->jsonEncode->endObject();
        } else {
            $this->c->httpResponse->jsonEncode->endArray();
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
        $payloadIndex = implode(':', $payloadIndexes);
        $isAssoc = $this->c->httpRequest->jsonDecode->jsonType($payloadIndex) === 'Object';
        $i_count = $isAssoc ? 1 : $this->c->httpRequest->jsonDecode->count($payloadIndex);

        $counter = 0;
        for ($i=0; $i < $i_count; $i++) {
            $_payloadIndexes = $payloadIndexes;
            if (!$this->db->beganTransaction) {
                $response['Error'] = 'Transaction rolled back';
                return;
            }

            if ($isAssoc && $i > 0) {
                return;
            }

            if (!$isAssoc && !$useHierarchy) {
                array_push($_payloadIndexes, $i);
            }
            $payloadIndex = implode(':', $_payloadIndexes);

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

            // Get Sql and Params
            list($sql, $sqlParams, $errors) = $this->getSqlAndParams($writeSqlConfig);
            if (!empty($errors)) {
                $response['Error'] = $errors;
                $this->db->rollback();
                return;
            }

            // Execute Query
            $this->db->execDbQuery($sql, $sqlParams);
            if (!$this->db->beganTransaction) {
                $response['Error'] = 'Something went wrong';
                return;
            }
            if (!$isAssoc && !isset($response[$counter])) {
                $response[$counter] = [];
            }
            if (isset($writeSqlConfig['insertId'])) {
                $insertId = $this->db->lastInsertId();
                if ($isAssoc) {
                    $response[$writeSqlConfig['insertId']] = $insertId;
                } else {
                    $response[$counter][$writeSqlConfig['insertId']] = $insertId;
                }
                $this->c->httpRequest->session['insertIdParams'][$writeSqlConfig['insertId']] = $insertId;
            } else {
                $affectedRows = $this->db->affectedRows();
                if ($isAssoc) {
                    $response['affectedRows'] = $affectedRows;
                } else {
                    $response[$counter]['affectedRows'] = $affectedRows;
                }
            }
            $this->db->closeCursor();

            // subQuery for payload
            if (isset($writeSqlConfig['subQuery'])) {
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
            $this->resetFetchData($configKeys, $row, $useHierarchy);
        }

        if (isset($writeSqlConfig['subQuery']) && $this->isAssoc($writeSqlConfig['subQuery'])) {
            foreach ($writeSqlConfig['subQuery'] as $module => &$_writeSqlConfig) {
                $_payloadIndexes = $payloadIndexes;
                $_configKeys = $configKeys;
                $modulePayloadKey = implode(':', $_payloadIndexes);
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
        if (isset($writeSqlConfig['validate'])) {
            list($isValidData, $errors) = $this->validate($writeSqlConfig['validate']);
            if ($isValidData !== true) {
                $this->c->httpResponse->httpStatus = HttpStatus::$BadRequest;
                $this->c->httpResponse->jsonEncode->startObject();
                $this->c->httpResponse->jsonEncode->addKeyValue('Payload', $this->c->httpRequest->session['payload']);
                $this->c->httpResponse->jsonEncode->addKeyValue('Error', $errors);
                $this->c->httpResponse->jsonEncode->endObject();
                $return = false;
            }
        }
        return $return;
    }
}
