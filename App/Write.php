<?php
namespace Microservices\App;

use Microservices\App\AppTrait;
use Microservices\App\Constants;
use Microservices\App\Common;
use Microservices\App\Env;
use Microservices\App\HttpStatus;
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

        // Load Queries
        $writeSqlConfig = include $this->c->httpRequest->__file__;

        // Set Server mode to execute query on - Read / Write Server.
        $this->c->httpRequest->setConnection('Master');

        // Use results in where clause of sub queries recursively.
        $useHierarchy = $this->getUseHierarchy($writeSqlConfig);

        if (
            (Env::$allowConfigRequest && Env::$isConfigRequest)
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
        $this->c->httpResponse->jsonEncode->startObject('Config');
        $this->c->httpResponse->jsonEncode->addKeyValue('Route', $this->c->httpRequest->configuredUri);
        $this->c->httpResponse->jsonEncode->addKeyValue('Payload', $this->getConfigParams($writeSqlConfig, true, $useHierarchy));
        $this->c->httpResponse->jsonEncode->endObject();
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
        $this->c->httpRequest->session['requiredArr'] = $this->getRequired($writeSqlConfig, true, $useHierarchy);

        if ($this->c->httpRequest->session['payloadType'] === 'Object') {
            $this->c->httpResponse->jsonEncode->startObject('Results');
        } else {
            $this->c->httpResponse->jsonEncode->startArray('Results');
        }

        // Perform action
        $i_count = $this->c->httpRequest->session['payloadType'] === 'Object' ? 1 : $this->c->httpRequest->jsonDecode->count();

        for ($i=0; $i < $i_count; $i++) {
            if ($i === 0) {
                if ($this->c->httpRequest->session['payloadType'] === 'Object') {
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
            $this->writeDB($writeSqlConfig, $payloadKey, $useHierarchy, $response, $this->c->httpRequest->session['requiredArr']);
            if ($this->c->httpRequest->db->beganTransaction === true) {
                $this->c->httpRequest->db->commit();
                $arr = [
                    'Status' => HttpStatus::$Created,
                    'Payload' => $this->c->httpRequest->jsonDecode->getCompleteArray($payloadKey),
                    'Response' => &$response
                ];
            } else {
                $this->c->httpResponse->httpStatus = HttpStatus::$BadRequest;
                $arr = [
                    'Status' => HttpStatus::$BadRequest,
                    'Payload' => $this->c->httpRequest->jsonDecode->getCompleteArray($payloadKey),
                    'Error' => &$response
                ];
            }
            $this->c->httpResponse->jsonEncode->encode($arr);
        }

        if ($this->c->httpRequest->session['payloadType'] === 'Object') {
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
     * @throws \Exception
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
                throw new \Exception("Paylaod key '{$payloadKey}' not set", HttpStatus::$NotFound);
            }

            $this->c->httpRequest->session['payload'] = $this->c->httpRequest->jsonDecode->get($payloadKey);


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
                $this->c->httpRequest->session['insertIdParams'][$writeSqlConfig['insertId']] = $insertId;
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
                            throw new \Exception("Invalid payload: Module '{$module}' missing", HttpStatus::$NotFound);
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
