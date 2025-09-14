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
    private $_c = null;

    /**
     * Session variable
     *
     * @var null|array
     */
    private $_s = null;

    /**
     * Trigger Web API object
     *
     * @var null|Web
     */
    private $_web = null;

    /**
     * Hook object
     *
     * @var null|Hook
     */
    private $_hook = null;

    /**
     * Operate DML As Transactions
     *
     * @var null|Web
     */
    private $_operateAsTransaction = null;

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
        $this->_c = &$common;
        $this->_s = &$this->_c->req->s;
        $this->dataEncode = &$this->_c->res->dataEncode;
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
        $sSqlConfig = include $this->_c->req->rParser->sqlConfigFile;

        // Rate Limiting request if configured for Route Queries.
        $this->_rateLimitRoute(sqlConfig: $sSqlConfig);

        $this->dataEncode->XSLT = $sSqlConfig['XSLT'] ?? null;

        // Lag Response
        $this->_lagResponse(sqlConfig: $sSqlConfig);

        // Operate as Transaction (BEGIN COMMIT else ROLLBACK on error)
        $this->_operateAsTransaction = isset($sSqlConfig['isTransaction']) ?
            $sSqlConfig['isTransaction'] : false;

        // Set Server mode to execute query on - Read / Write Server
        $this->_c->req->db = $this->_c->req->setDbConnection(fetchFrom: 'Master');
        $this->db = &$this->_c->req->db;

        // Use results in where clause of sub queries recursively
        $useHierarchy = $this->_getUseHierarchy(
            sqlConfig: $sSqlConfig,
            keyword: 'useHierarchy'
        );

        if (Env::$allowConfigRequest && $this->_c->req->rParser->isConfigRequest) {
            $this->_processSupplementConfig(
                sSqlConfig: $sSqlConfig,
                useHierarchy: $useHierarchy
            );
        } else {
            $this->_processSupplement(
                sSqlConfig: $sSqlConfig,
                useHierarchy: $useHierarchy
            );
            if (isset($sSqlConfig['affectedCacheKeys'])) {
                for (
                    $i = 0, $iCount = count(value: $sSqlConfig['affectedCacheKeys']);
                    $i < $iCount;
                    $i++
                ) {
                    $this->_c->req->delDmlCache(
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
    private function _processSupplementConfig(&$sSqlConfig, $useHierarchy): void
    {
        $this->dataEncode->startObject(key: 'Config');
        $this->dataEncode->addKeyData(
            key: 'Route',
            data: $this->_c->req->rParser->configuredUri
        );
        $this->dataEncode->addKeyData(
            key: 'Payload',
            data: $this->_getConfigParams(
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
    private function _processSupplement(&$sSqlConfig, $useHierarchy): void
    {
        // Check for payloadType
        if (isset($sSqlConfig['__PAYLOAD-TYPE__'])) {
            $payloadType = $this->_s['payloadType'];
            if ($payloadType !== $sSqlConfig['__PAYLOAD-TYPE__']) {
                throw new \Exception(
                    message: 'Invalid payload type',
                    code: HttpStatus::$BadRequest
                );
            }
            // Check for maximum objects supported when payloadType is Array
            if ($sSqlConfig['__PAYLOAD-TYPE__'] === 'Array'
                && isset($sSqlConfig['__MAX-PAYLOAD-OBJECTS__'])
                && ($objCount = $this->_c->req->dataDecode->count())
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
        $this->_s['necessaryArr'] = $this->_getRequired(
            sqlConfig: $sSqlConfig,
            isFirstCall: true,
            flag: $useHierarchy
        );

        if ($this->_s['payloadType'] === 'Object') {
            $this->dataEncode->startObject(key: 'Results');
        } else {
            $this->dataEncode->startObject(key: 'Results');
            if (Env::$oRepresentation === 'XML') {
                $this->dataEncode->startArray(key: 'Rows');
            }
        }

        // Perform action
        $iCount = $this->_s['payloadType'] === 'Object' ?
            1 : $this->_c->req->dataDecode->count();

        for ($i=0; $i < $iCount; $i++) {
            $_configKeys = [];
            $_payloadIndexes = [];
            if ($i === 0) {
                if ($this->_s['payloadType'] === 'Object') {
                    $_payloadIndexes[] = '';
                } else {
                    $_payloadIndexes[] = "{$i}";
                }
            } else {
                $_payloadIndexes[] = "{$i}";
            }

            // Check for Idempotent Window
            [$idempotentWindow, $hashKey, $hashJson] = $this->_checkIdempotent(
                sqlConfig: $sSqlConfig,
                _payloadIndexes: $_payloadIndexes
            );

            // Begin DML operation
            if ($hashJson === null) {
                if ($this->_operateAsTransaction) {
                    $this->db->begin();
                }
                $response = [];
                $this->_execSupplement(
                    sSqlConfig: $sSqlConfig,
                    payloadIndexes: $_payloadIndexes,
                    configKeys: $_configKeys,
                    useHierarchy: $useHierarchy,
                    response: $response,
                    necessary: $this->_s['necessaryArr']
                );
                $bool = $this->_operateAsTransaction
                    && ($this->db->beganTransaction === true);
                if (!$this->_operateAsTransaction || $bool) {
                    if ($this->_operateAsTransaction) {
                        $this->db->commit();
                    }
                    $arr = [
                        'Status' => HttpStatus::$Created,
                        'Payload' => $this->_c->req->dataDecode->getCompleteArray(
                            keys: implode(
                                separator: ':',
                                array: $_payloadIndexes
                            )
                        ),
                        'Response' => $response
                    ];
                    if ($idempotentWindow) {
                        $this->_c->req->cache->setCache(
                            key: $hashKey,
                            value: json_encode(value: $arr),
                            expire: $idempotentWindow
                        );
                    }
                } else { // Failure
                    $this->_c->res->httpStatus = HttpStatus::$BadRequest;
                    $arr = [
                        'Status' => HttpStatus::$BadRequest,
                        'Payload' => $this->_c->req->dataDecode->getCompleteArray(
                            keys: implode(
                                separator: ':',
                                array: $_payloadIndexes
                            )
                        ),
                        'Error' => $response
                    ];
                }
            } else {
                $arr = json_decode(json: $hashJson, associative: true);
            }

            if ($_payloadIndexes[0] === '') {
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

        if ($this->_s['payloadType'] === 'Object') {
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
    private function _execSupplement(
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

        $isObject = $this->_c->req->dataDecode->dataType(
            keys: $payloadIndex
        ) === 'Object';

        $iCount = $isObject ?
            1 : $this->_c->req->dataDecode->count(keys: $payloadIndex);

        $counter = -1;
        for ($i=0; $i < $iCount; $i++) {
            $_payloadIndexes = $payloadIndexes;
            if ($this->_operateAsTransaction && !$this->db->beganTransaction) {
                $response['Error'] = 'Transaction rolled back';
                return;
            }

            if ($isObject && $i > 0) {
                return;
            }

            if (!$isObject && !$useHierarchy) {
                array_push($_payloadIndexes, $i);
            }

            $payloadIndex = is_array(value: $_payloadIndexes) ?
                implode(separator: ':', array: $_payloadIndexes) : '';

            if (!$this->_c->req->dataDecode->isset(keys: $payloadIndex)) {
                if ($useHierarchy) {
                    throw new \Exception(
                        message: "Payload key '{$payloadIndex}' not set",
                        code: HttpStatus::$NotFound
                    );
                } else {
                    continue;
                }
            }

            $this->_s['payload'] = $this->_c->req->dataDecode->get(
                keys: $payloadIndex
            );

            if (count(value: $necessary)) {
                $this->_s['necessary'] = $necessary;
            } else {
                $this->_s['necessary'] = [];
            }

            // Validation
            if (!$this->_isValidPayload(sSqlConfig: $sSqlConfig)) {
                continue;
            }

            // Execute Pre Sql Hooks
            if (isset($sSqlConfig['__PRE-SQL-HOOKS__'])) {
                if ($this->_hook === null) {
                    $this->_hook = new Hook(common: $this->_c);
                }
                $this->_hook->triggerHook(
                    hookConfig: $sSqlConfig['__PRE-SQL-HOOKS__']
                );
            }

            // Execute function
            $results = $this->supplementObj->process(
                $sSqlConfig['__FUNCTION__'],
                $this->_s['payload']
            );

            if ($this->_operateAsTransaction && !$this->db->beganTransaction) {
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
                if ($this->_web === null) {
                    $this->_web = new Web(common: $this->_c);
                }
                $response['__TRIGGERS__'] = $this->_web->triggerConfig(
                    triggerConfig: $sSqlConfig['__TRIGGERS__']
                );
            }

            // Execute Post Sql Hooks
            if (isset($sSqlConfig['__POST-SQL-HOOKS__'])) {
                if ($this->_hook === null) {
                    $this->_hook = new Hook(common: $this->_c);
                }
                $this->_hook->triggerHook(
                    hookConfig: $sSqlConfig['__POST-SQL-HOOKS__']
                );
            }

            // subQuery for payload
            if (isset($sSqlConfig['__SUB-PAYLOAD__'])) {
                $this->_callExecSupplement(
                    sSqlConfig: $sSqlConfig,
                    payloadIndexes: $_payloadIndexes,
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
    private function _callExecSupplement(
        &$sSqlConfig,
        $payloadIndexes,
        $configKeys,
        $useHierarchy,
        &$response,
        &$necessary
    ): void {
        if ($useHierarchy) {
            $row = $this->_s['payload'];
            $this->_resetFetchData(
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

        if (isset($sSqlConfig['__SUB-PAYLOAD__'])
            && $this->_isObject(arr: $sSqlConfig['__SUB-PAYLOAD__'])
        ) {
            foreach ($sSqlConfig['__SUB-PAYLOAD__'] as $module => &$_sSqlConfig) {
                $dataExists = false;
                $_payloadIndexes = $payloadIndexes;
                $_configKeys = $configKeys;
                array_push($_payloadIndexes, $module);
                array_push($_configKeys, $module);
                $modulePayloadKey = is_array(value: $_payloadIndexes) ?
                    implode(separator: ':', array: $_payloadIndexes) : '';
                $dataExists = $this->_c->req->dataDecode->isset(
                    keys: $modulePayloadKey
                );
                if ($useHierarchy && !$dataExists) { // use parent data of a payload
                    throw new \Exception(
                        message: "Invalid payload: Module '{$module}' missing",
                        code: HttpStatus::$NotFound
                    );
                }
                if ($dataExists) {
                    $_necessary = $necessary[$module] ?? $necessary;
                    $_useHierarchy = $useHierarchy ?? $this->_getUseHierarchy(
                        sqlConfig: $_sSqlConfig,
                        keyword: 'useHierarchy'
                    );
                    $response[$module] = [];
                    $_response = &$response[$module];
                    $this->_execSupplement(
                        sSqlConfig: $_sSqlConfig,
                        payloadIndexes: $_payloadIndexes,
                        configKeys: $_configKeys,
                        useHierarchy: $_useHierarchy,
                        response: $_response,
                        necessary: $_necessary
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
    private function _isValidPayload($sSqlConfig): bool
    {
        $return = true;
        $isValidData = true;
        if (isset($sSqlConfig['__VALIDATE__'])) {
            [$isValidData, $errors] = $this->validate(
                validationConfig: $sSqlConfig['__VALIDATE__']
            );
            if ($isValidData !== true) {
                $this->_c->res->httpStatus = HttpStatus::$BadRequest;
                $this->dataEncode->startObject();
                $this->dataEncode->addKeyData(
                    key: 'Payload',
                    data: $this->_s['payload']
                );
                $this->dataEncode->addKeyData(key: 'Error', data: $errors);
                $this->dataEncode->endObject();
                $return = false;
            }
        }
        return $return;
    }
}
