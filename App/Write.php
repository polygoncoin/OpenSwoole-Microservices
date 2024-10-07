<?php
namespace Microservices\App;

use Microservices\App\AppTrait;
use Microservices\App\Constants;
use Microservices\App\Common;
use Microservices\App\Env;
use Microservices\App\Validator;

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
     * Validator class object
     *
     * @var object
     */
    public $validator = null;

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
        $Constants = __NAMESPACE__ . '\Constants';
        $Env = __NAMESPACE__ . '\Env';

        // Load Queries
        $writeSqlConfig = include $this->c->httpRequest->__file__;

        // Set Server mode to execute query on - Read / Write Server.
        $this->c->httpRequest->setConnection('Master');

        // Use results in where clause of sub queries recursively.
        $useHierarchy = $this->getUseHierarchy($writeSqlConfig);

        if (
            Env::$allowConfigRequest &&
            Env::$isConfigRequest
        ) {
            $this->processWriteConfig($writeSqlConfig, $useHierarchy);
        } else {
            $this->processWrite($writeSqlConfig, $useHierarchy);
        }

        return true;
    }

    /**
     * Process write function for configuration.
     *
     * @param array   $writeSqlConfig Config from file
     * @param boolean $useHierarchy   Use results in where clause of sub queries recursively.
     * @return boolean
     */
    private function processWriteConfig(&$writeSqlConfig, $useHierarchy)
    {
        $response = [];
        $response['Route'] = $this->c->httpRequest->configuredUri;
        $response['Payload'] = $this->getConfigParams($writeSqlConfig, true, $useHierarchy);

        $this->c->httpResponse->jsonEncode->startObject('Config');
        $this->c->httpResponse->jsonEncode->addKeyValue('Route', $response['Route']);
        $this->c->httpResponse->jsonEncode->addKeyValue('Payload', $response['Payload']);
        $this->c->httpResponse->jsonEncode->endObject();

        return true;
    }    

    /**
     * Process Function to insert/update.
     *
     * @param array   $writeSqlConfig Config from file
     * @param boolean $useHierarchy   Use results in where clause of sub queries recursively.
     * @return boolean
     */
    private function processWrite(&$writeSqlConfig, $useHierarchy)
    {
        // Set required fields.
        $this->c->httpRequest->input['requiredArr'] = $this->getRequired($writeSqlConfig, true, $useHierarchy);

        if ($this->c->httpRequest->input['payloadType'] === 'Object') {
            $this->c->httpResponse->jsonEncode->startObject('Results');
        } else {
            $this->c->httpResponse->jsonEncode->startArray('Results');
        }

        // Perform action
        $i_count = $this->c->httpRequest->input['payloadType'] === 'Object' ? 1 : $this->c->httpRequest->jsonDecode->count();

        for ($i=0; $i < $i_count; $i++) {
            if ($i === 0) {
                if ($this->c->httpRequest->input['payloadType'] === 'Object') {
                    $payloadKey = '';
                } else {
                    $payloadKey = "{$i}";
                }
            } else {
                $payloadKey = "{$i}";
            }

            // Begin DML operation
            $this->c->httpRequest->db->begin();
            $response = [];
            $this->writeDB($writeSqlConfig, $payloadKey, $useHierarchy, $response, $this->c->httpRequest->input['requiredArr']);
            if ($this->c->httpRequest->input['payloadType'] === 'Array') {
                $this->c->httpResponse->jsonEncode->startObject();
            }
            if ($this->c->httpRequest->db->beganTransaction === true) {
                $this->c->httpRequest->db->commit();
                $this->c->httpResponse->jsonEncode->addKeyValue('Status', 200);
            } else {
                $this->c->httpResponse->httpStatus = 400;
                $this->c->httpResponse->jsonEncode->addKeyValue('Status', 400);
            }
            $this->c->httpResponse->jsonEncode->addKeyValue('Response', $response);
            if ($this->c->httpRequest->input['payloadType'] === 'Array') {
                $this->c->httpResponse->jsonEncode->endObject();
            }
        }

        if ($this->c->httpRequest->input['payloadType'] === 'Object') {
            $this->c->httpResponse->jsonEncode->endObject();
        } else {
            $this->c->httpResponse->jsonEncode->endArray();
        }

        return true;
    }    

    /**
     * Function to insert/update sub queries recursively.
     *
     * @param array   $writeSqlConfig Config from file
     * @param boolean $payloadKey     Payload key.
     * @param boolean $useHierarchy   Use results in where clause of sub queries recursively.
     * @param array   $response       Response by reference.
     * @param array   $required       Required fields.
     * @return boolean
     */
    private function writeDB(&$writeSqlConfig, $payloadKey, $useHierarchy, &$response, &$required)
    {
        $isAssoc = $this->c->httpRequest->jsonDecode->jsonType($payloadKey) === 'Object';
        $i_count = $isAssoc ? 1 : $this->c->httpRequest->jsonDecode->count($payloadKey);

        $counter = 0;
        for ($i=0; $i < $i_count; $i++) {
            if (!$this->c->httpRequest->db->beganTransaction) {
                $response['Error'] = 'Transaction rolled back';
                return;
            }
            
            if ($isAssoc && $i > 0) {
                    return;
            }

            if ($isAssoc) {
                $payloadKey = $payloadKey;
            } else {
                $payloadKey = (strlen($payloadKey) === 0) ? $i : "{$payloadKey}:{$i}";
            }

            if (!$this->c->httpRequest->jsonDecode->isset($payloadKey)) {
                throw new \Exception("Paylaod key '{$payloadKey}' not set", 404);
            }

            $this->c->httpRequest->input['payload'] = $this->c->httpRequest->jsonDecode->get($payloadKey);
            if (isset($required['__required__'])) {
                $this->c->httpRequest->input['required'] = $required['__required__'];
            } else {
                $this->c->httpRequest->input['required'] = [];
            }
    
            // Validation
            if (!$this->isValidPayload($writeSqlConfig)) {
                continue;
            }

            // Get Sql and Params
            list($sql, $sqlParams, $errors) = $this->getSqlAndParams($writeSqlConfig);
            if (!empty($errors)) {
                $response['Error'] = $errors;
                $this->c->httpRequest->db->rollback();
                return;
            }

            // Execute Query
            $this->c->httpRequest->db->execDbQuery($sql, $sqlParams);
            if (!$this->c->httpRequest->db->beganTransaction) {
                $response['Error'] = 'Something went wrong';
                return;
            }
            if (!$isAssoc && !isset($response[$counter])) {
                $response[$counter] = [];
            }
            if (isset($writeSqlConfig['insertId'])) {
                $insertId = $this->c->httpRequest->db->lastInsertId();
                if ($isAssoc) {
                    $response[$writeSqlConfig['insertId']] = $insertId;
                } else {
                    $response[$counter][$writeSqlConfig['insertId']] = $insertId;
                }
                $this->c->httpRequest->input['insertIdParams'][$writeSqlConfig['insertId']] = $insertId;
            } else {
                $affectedRows = $this->c->httpRequest->db->affectedRows();
                if ($isAssoc) {
                    $response['affectedRows'] = $affectedRows;
                } else {
                    $response[$counter]['affectedRows'] = $affectedRows;
                }
            }
            $this->c->httpRequest->db->closeCursor();

            // subQuery for payload.
            if (isset($writeSqlConfig['subQuery'])) {
                foreach ($writeSqlConfig['subQuery'] as $module => &$_writeSqlConfig) {
                    $modulePayloadKey = (strlen($payloadKey) === 0) ? $module : "{$payloadKey}:{$module}";
                    if ($useHierarchy) { // use parent data of a payload.
                        if ($this->c->httpRequest->jsonDecode->isset($modulePayloadKey)) {
                            $_payloadKey = $modulePayloadKey;
                            $_required = &$required[$module] ?? [];
                        } else {
                            throw new \Exception("Invalid payload: Module '{$module}' missing", 404);
                        }
                    } else {
                        $_payloadKey = $modulePayloadKey;
                        $_required = [];
                    }
                    $_useHierarchy = $useHierarchy ?? $this->getUseHierarchy($_writeSqlConfig);
                    if ($isAssoc) {
                        $response[$module] = [];
                        $_response = &$response[$module];
                    } else {
                        $response[$counter][$module] = [];
                        $_response = &$response[$counter][$module];
                    }
                    $this->writeDB($_writeSqlConfig, $_payloadKey, $_useHierarchy, $_response, $_required);
                }
            }

            if (!$isAssoc) {
                $counter++;
            }
        }

        return true;
    }

    /**
     * Checks if the payload is valid
     *
     * @param array   $writeSqlConfig Config from file
     * @return boolean
     */
    private function isValidPayload($writeSqlConfig)
    {
        $return = true;
        $isValidData = true;
        if (isset($writeSqlConfig['validate'])) {
            list($isValidData, $errors) = $this->validate($writeSqlConfig['validate']);
            if ($isValidData !== true) {
                $this->c->httpResponse->httpStatus = 400;
                $this->c->httpResponse->jsonEncode->startObject();
                $this->c->httpResponse->jsonEncode->addKeyValue('Payload', $this->c->httpRequest->input['payload']);
                $this->c->httpResponse->jsonEncode->addKeyValue('Error', $errors);
                $this->c->httpResponse->jsonEncode->endObject();
                $return = false;
            }
        }
        return $return;
    }
}
