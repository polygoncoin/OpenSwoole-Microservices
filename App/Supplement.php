<?php

/**
 * Supplement APIs
 * php version 8.3
 *
 * @category  Supplement
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App;

use Microservices\App\AppTrait;
use Microservices\App\Common;
use Microservices\App\DataRepresentation\AbstractDataEncode;
use Microservices\App\Env;
use Microservices\App\Hook;
use Microservices\App\HttpStatus;
use Microservices\App\Web;
use Microservices\App\Servers\Database\AbstractDatabase;

/**
 * Supplement APIs
 * php version 8.3
 *
 * @category  Supplement
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class Supplement
{
    use AppTrait;

    /**
     * Database object
     *
     * @var null|AbstractDatabase
     */
    public $db = null;

    /**
     * Common object
     *
     * @var null|Common
     */
    private $c = null;

    /**
     * Session variable
     *
     * @var null|array
     */
    private $s = null;

    /**
     * Trigger Web API object
     *
     * @var null|Web
     */
    private $web = null;

    /**
     * Hook object
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
     * JSON Encode object
     *
     * @var null|AbstractDataEncode
     */
    public $dataEncode = null;

    /**
     * Supplement Class object
     *
     * @var null|object
     */
    public $supplementObj = null;

    /**
     * Constructor
     *
     * @param Common $common Common object
     */
    public function __construct(Common &$common)
    {
        $this->c = &$common;
        $this->s = &$this->c->req->s;
        $this->dataEncode = &$this->c->res->dataEncode;
    }

    /**
     * Initialize
     *
     * @param object $supplementObj Supplement API object
     *
     * @return bool
     */
    public function init(&$supplementObj): bool
    {
        $this->supplementObj = &$supplementObj;
        return true;
    }

    /**
     * Process
     *
     * @return bool
     */
    public function process(): bool
    {
        $Env = __NAMESPACE__ . '\Env';

        // Load Queries
        $sSqlConfig = include $this->c->req->rParser->sqlConfigFile;

        // Rate Limiting request if configured for Route Queries.
        $this->rateLimitRoute(sqlConfig: $sSqlConfig);

        $this->dataEncode->XSLT = $sSqlConfig['XSLT'] ?? null;

        // Lag Response
        $this->lagResponse(sqlConfig: $sSqlConfig);

        // Operate as Transaction (BEGIN COMMIT else ROLLBACK on error)
        $this->operateAsTransaction = isset($sSqlConfig['isTransaction']) ?
            $sSqlConfig['isTransaction'] : false;

        // Set Server mode to execute query on - Read / Write Server
        $this->c->req->db = $this->c->req->setDbConnection(fetchFrom: 'Master');
        $this->db = &$this->c->req->db;

        // Use results in where clause of sub queries recursively
        $useHierarchy = $this->getUseHierarchy(
            sqlConfig: $sSqlConfig,
            keyword: 'useHierarchy'
        );

        if (Env::$allowConfigRequest && $this->c->req->rParser->isConfigRequest) {
            $this->processSupplementConfig(
                sSqlConfig: $sSqlConfig,
                useHierarchy: $useHierarchy
            );
        } else {
            $this->processSupplement(
                sSqlConfig: $sSqlConfig,
                useHierarchy: $useHierarchy
            );
            if (isset($sSqlConfig['affectedCacheKeys'])) {
                for (
                    $i = 0, $iCount = count(value: $sSqlConfig['affectedCacheKeys']);
                    $i < $iCount;
                    $i++
                ) {
                    $this->c->req->delDmlCache(
                        cacheKey: $sSqlConfig['affectedCacheKeys'][$i]
                    );
                }
            }
        }

        return true;
    }

    /**
     * Process write function for configuration
     *
     * @param array $sSqlConfig   Config from file
     * @param bool  $useHierarchy Use results in where clause of sub queries
     *
     * @return void
     */
    private function processSupplementConfig(&$sSqlConfig, $useHierarchy): void
    {
        $this->dataEncode->startObject(key: 'Config');
        $this->dataEncode->addKeyData(
            key: 'Route',
            data: $this->c->req->rParser->configuredUri
        );
        $this->dataEncode->addKeyData(
            key: 'Payload',
            data: $this->getConfigParams(
                sqlConfig: $sSqlConfig,
                isFirstCall: true,
                flag: $useHierarchy
            )
        );
        $this->dataEncode->endObject();
    }

    /**
     * Process Function to insert/update
     *
     * @param array $sSqlConfig   Config from file
     * @param bool  $useHierarchy Use results in where clause of sub queries
     *
     * @return void
     * @throws \Exception
     */
    private function processSupplement(&$sSqlConfig, $useHierarchy): void
    {
        // Check for payloadType
        if (isset($sSqlConfig['__PAYLOAD-TYPE__'])) {
            $payloadType = $this->s['payloadType'];
            if ($payloadType !== $sSqlConfig['__PAYLOAD-TYPE__']) {
                throw new \Exception(
                    message: 'Invalid payload type',
                    code: HttpStatus::$BadRequest
                );
            }
            // Check for maximum objects supported when payloadType is Array
            if (
                $sSqlConfig['__PAYLOAD-TYPE__'] === 'Array'
                && isset($sSqlConfig['__MAX-PAYLOAD-OBJECTS__'])
                && ($objCount = $this->c->req->dataDecode->count())
                && ($objCount > $sSqlConfig['__MAX-PAYLOAD-OBJECTS__'])
            ) {
                throw new \Exception(
                    message: 'Maximum supported payload count is ' .
                        $sSqlConfig['__MAX-PAYLOAD-OBJECTS__'],
                    code: HttpStatus::$BadRequest
                );
            }
        }

        // Set necessary fields
        $this->s['necessaryArr'] = $this->getRequired(
            sqlConfig: $sSqlConfig,
            isFirstCall: true,
            flag: $useHierarchy
        );

        if ($this->s['payloadType'] === 'Object') {
            $this->dataEncode->startObject(key: 'Results');
        } else {
            $this->dataEncode->startObject(key: 'Results');
            if (Env::$oRepresentation === 'XML') {
                $this->dataEncode->startArray(key: 'Rows');
            }
        }

        // Perform action
        $iCount = $this->s['payloadType'] === 'Object' ?
            1 : $this->c->req->dataDecode->count();

        for ($i = 0; $i < $iCount; $i++) {
            $configKeys = [];
            $payloadIndexes = [];
            if ($i === 0) {
                if ($this->s['payloadType'] === 'Object') {
                    $payloadIndexes[] = '';
                } else {
                    $payloadIndexes[] = "{$i}";
                }
            } else {
                $payloadIndexes[] = "{$i}";
            }

            // Check for Idempotent Window
            [$idempotentWindow, $hashKey, $hashJson] = $this->checkIdempotent(
                sqlConfig: $sSqlConfig,
                payloadIndexes: $payloadIndexes
            );

            // Begin DML operation
            if ($hashJson === null) {
                if ($this->operateAsTransaction) {
                    $this->db->begin();
                }
                $response = [];
                $this->execSupplement(
                    sSqlConfig: $sSqlConfig,
                    payloadIndexes: $payloadIndexes,
                    configKeys: $configKeys,
                    useHierarchy: $useHierarchy,
                    response: $response,
                    necessary: $this->s['necessaryArr']
                );
                $bool = $this->operateAsTransaction
                    && ($this->db->beganTransaction === true);
                if (!$this->operateAsTransaction || $bool) {
                    if ($this->operateAsTransaction) {
                        $this->db->commit();
                    }
                    $arr = [
                        'Status' => HttpStatus::$Created,
                        'Payload' => $this->c->req->dataDecode->getCompleteArray(
                            keys: implode(
                                separator: ':',
                                array: $payloadIndexes
                            )
                        ),
                        'Response' => $response
                    ];
                    if ($idempotentWindow) {
                        $this->c->req->cache->setCache(
                            key: $hashKey,
                            value: json_encode(value: $arr),
                            expire: $idempotentWindow
                        );
                    }
                } else { // Failure
                    $this->c->res->httpStatus = HttpStatus::$BadRequest;
                    $arr = [
                        'Status' => HttpStatus::$BadRequest,
                        'Payload' => $this->c->req->dataDecode->getCompleteArray(
                            keys: implode(
                                separator: ':',
                                array: $payloadIndexes
                            )
                        ),
                        'Error' => $response
                    ];
                }
            } else {
                $arr = json_decode(json: $hashJson, associative: true);
            }

            if ($payloadIndexes[0] === '') {
                foreach ($arr as $k => $v) {
                    $this->dataEncode->addKeyData(key: $k, data: $v);
                }
            } else {
                if (Env::$oRepresentation === 'XML') {
                    $this->dataEncode->startObject(key: 'Row');
                    foreach ($arr as $k => $v) {
                        $this->dataEncode->addKeyData(key: $k, data: $v);
                    }
                    $this->dataEncode->endObject();
                } else {
                    $this->dataEncode->addKeyData(key: $i, data: $arr);
                }
            }
        }

        if ($this->s['payloadType'] === 'Object') {
            $this->dataEncode->endObject();
        } else {
            if (Env::$oRepresentation === 'XML') {
                $this->dataEncode->endArray();
            }
            $this->dataEncode->endObject();
        }
    }

    /**
     * Function to execute supplement recursively
     *
     * @param array $sSqlConfig     Config from file
     * @param array $payloadIndexes Payload Indexes
     * @param array $configKeys     Config Keys
     * @param bool  $useHierarchy   Use results in where clause of sub queries
     * @param array $response       Response by reference
     * @param array $necessary      Required fields
     *
     * @return void
     * @throws \Exception
     */
    private function execSupplement(
        &$sSqlConfig,
        $payloadIndexes,
        $configKeys,
        $useHierarchy,
        &$response,
        &$necessary
    ): void {
        // Return if function is not set
        if (!isset($sSqlConfig['__FUNCTION__'])) {
            return;
        }

        $payloadIndex = is_array(value: $payloadIndexes) ?
            trim(
                string: implode(
                    separator: ':',
                    array: $payloadIndexes
                ),
                characters: ':'
            ) : '';

        $isObject = $this->c->req->dataDecode->dataType(
            keys: $payloadIndex
        ) === 'Object';

        $iCount = $isObject ?
            1 : $this->c->req->dataDecode->count(keys: $payloadIndex);

        $counter = -1;
        for ($i = 0; $i < $iCount; $i++) {
            $payloadIndexes = $payloadIndexes;
            if ($this->operateAsTransaction && !$this->db->beganTransaction) {
                $response['Error'] = 'Transaction rolled back';
                return;
            }

            if ($isObject && $i > 0) {
                return;
            }

            if (!$isObject && !$useHierarchy) {
                array_push($payloadIndexes, $i);
            }

            $payloadIndex = is_array(value: $payloadIndexes) ?
                implode(separator: ':', array: $payloadIndexes) : '';

            if (!$this->c->req->dataDecode->isset(keys: $payloadIndex)) {
                if ($useHierarchy) {
                    throw new \Exception(
                        message: "Payload key '{$payloadIndex}' not set",
                        code: HttpStatus::$NotFound
                    );
                } else {
                    continue;
                }
            }

            $this->s['payload'] = $this->c->req->dataDecode->get(
                keys: $payloadIndex
            );

            if (count(value: $necessary)) {
                $this->s['necessary'] = $necessary;
            } else {
                $this->s['necessary'] = [];
            }

            // Validation
            if (!$this->isValidPayload(sSqlConfig: $sSqlConfig)) {
                continue;
            }

            // Execute Pre Sql Hooks
            if (isset($sSqlConfig['__PRE-SQL-HOOKS__'])) {
                if ($this->hook === null) {
                    $this->hook = new Hook(common: $this->c);
                }
                $this->hook->triggerHook(
                    hookConfig: $sSqlConfig['__PRE-SQL-HOOKS__']
                );
            }

            // Execute function
            $results = $this->supplementObj->process(
                $sSqlConfig['__FUNCTION__'],
                $this->s['payload']
            );

            if ($this->operateAsTransaction && !$this->db->beganTransaction) {
                $response['Error'] = 'Something went wrong';
                return;
            }

            if ($isObject) {
                $counter = 0;
            } else {
                $response[++$counter] = [];
                $response = &$response[$counter];
            }
            $response = $results;

            $this->db->closeCursor();

            // triggers
            if (isset($sSqlConfig['__TRIGGERS__'])) {
                if ($this->web === null) {
                    $this->web = new Web(common: $this->c);
                }
                $response['__TRIGGERS__'] = $this->web->triggerConfig(
                    triggerConfig: $sSqlConfig['__TRIGGERS__']
                );
            }

            // Execute Post Sql Hooks
            if (isset($sSqlConfig['__POST-SQL-HOOKS__'])) {
                if ($this->hook === null) {
                    $this->hook = new Hook(common: $this->c);
                }
                $this->hook->triggerHook(
                    hookConfig: $sSqlConfig['__POST-SQL-HOOKS__']
                );
            }

            // subQuery for payload
            if (isset($sSqlConfig['__SUB-PAYLOAD__'])) {
                $this->callExecSupplement(
                    sSqlConfig: $sSqlConfig,
                    payloadIndexes: $payloadIndexes,
                    configKeys: $configKeys,
                    useHierarchy: $useHierarchy,
                    response: $response,
                    necessary: $necessary
                );
            }
        }
    }

    /**
     * Validate and call _writeDB
     *
     * @param array $sSqlConfig     Config from file
     * @param array $payloadIndexes Payload Indexes
     * @param array $configKeys     Config Keys
     * @param bool  $useHierarchy   Use results in where clause of sub queries
     * @param array $response       Response by reference
     * @param array $necessary      Required fields
     *
     * @return void
     */
    private function callExecSupplement(
        &$sSqlConfig,
        $payloadIndexes,
        $configKeys,
        $useHierarchy,
        &$response,
        &$necessary
    ): void {
        if ($useHierarchy) {
            $row = $this->s['payload'];
            $this->resetFetchData(
                fetchFrom: 'sqlPayload',
                keys: $configKeys,
                row: $row
            );
        }

        if (isset($payloadIndexes[0]) && $payloadIndexes[0] === '') {
            $payloadIndexes = array_shift($payloadIndexes);
        }
        if (!is_array(value: $payloadIndexes)) {
             $payloadIndexes = [];
        }

        if (
            isset($sSqlConfig['__SUB-PAYLOAD__'])
            && $this->isObject(arr: $sSqlConfig['__SUB-PAYLOAD__'])
        ) {
            foreach ($sSqlConfig['__SUB-PAYLOAD__'] as $module => &$sSqlConfig) {
                $dataExists = false;
                $payloadIndexes = $payloadIndexes;
                $configKeys = $configKeys;
                array_push($payloadIndexes, $module);
                array_push($configKeys, $module);
                $modulePayloadKey = is_array(value: $payloadIndexes) ?
                    implode(separator: ':', array: $payloadIndexes) : '';
                $dataExists = $this->c->req->dataDecode->isset(
                    keys: $modulePayloadKey
                );
                if ($useHierarchy && !$dataExists) { // use parent data of a payload
                    throw new \Exception(
                        message: "Invalid payload: Module '{$module}' missing",
                        code: HttpStatus::$NotFound
                    );
                }
                if ($dataExists) {
                    $necessary = $necessary[$module] ?? $necessary;
                    $useHierarchy = $useHierarchy ?? $this->getUseHierarchy(
                        sqlConfig: $sSqlConfig,
                        keyword: 'useHierarchy'
                    );
                    $response[$module] = [];
                    $response = &$response[$module];
                    $this->execSupplement(
                        sSqlConfig: $sSqlConfig,
                        payloadIndexes: $payloadIndexes,
                        configKeys: $configKeys,
                        useHierarchy: $useHierarchy,
                        response: $response,
                        necessary: $necessary
                    );
                }
            }
        }
    }

    /**
     * Checks if the payload is valid
     *
     * @param array $sSqlConfig Config from file
     *
     * @return bool
     */
    private function isValidPayload($sSqlConfig): bool
    {
        $return = true;
        $isValidData = true;
        if (isset($sSqlConfig['__VALIDATE__'])) {
            [$isValidData, $errors] = $this->validate(
                validationConfig: $sSqlConfig['__VALIDATE__']
            );
            if ($isValidData !== true) {
                $this->c->res->httpStatus = HttpStatus::$BadRequest;
                $this->dataEncode->startObject();
                $this->dataEncode->addKeyData(
                    key: 'Payload',
                    data: $this->s['payload']
                );
                $this->dataEncode->addKeyData(key: 'Error', data: $errors);
                $this->dataEncode->endObject();
                $return = false;
            }
        }
        return $return;
    }
}
