<?php
/**
 * Write APIs
 * php version 8.3
 *
 * @category  WriteAPI
 * @package   OpenSwoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/OpenSwoole-Microservices
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
 * Write APIs
 * php version 8.3
 *
 * @category  WriteAPIs
 * @package   OpenSwoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/OpenSwoole-Microservices
 * @since     Class available since Release 1.0.0
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
     * Common Object
     *
     * @var null|Common
     */
    private $_c = null;

    /**
     * Session variable
     *
     * @var null|array
     */
    private $_sess = null;

    /**
     * Trigger Web API Object
     *
     * @var null|Web
     */
    private $_web = null;

    /**
     * Hook Object
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
     * Json Encode Object
     *
     * @var null|AbstractDataEncode
     */
    public $dataEncode = null;

    /**
     * Constructor
     *
     * @param Common $common Common object
     */
    public function __construct(Common &$common)
    {
        $this->_c = &$common;
        $this->_sess = &$this->_c->req->sess;
        $this->dataEncode = &$this->_c->res->dataEncode;
    }

    /**
     * Initialize
     *
     * @return bool
     */
    public function init(): bool
    {
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
        $sess = &$this->_sess;

        // Load Queries
        $wSqlConfig = include $this->_c->req->sqlConfigFile;

        // Rate Limiting request if configured for Route Queries.
        $this->_rateLimitRoute(sqlConfig: $wSqlConfig);

        $this->dataEncode->XSLT = isset($wSqlConfig['XSLT']) ?
            $wSqlConfig['XSLT'] : null;

        // Lag Response
        $this->_lagResponse(sqlConfig: $wSqlConfig);

        // Operate as Transaction (BEGIN COMMIT else ROLLBACK on error)
        $this->_operateAsTransaction = isset($wSqlConfig['isTransaction']) ?
            $wSqlConfig['isTransaction'] : false;

        // Set Server mode to execute query on - Read / Write Server
        $this->_c->req->db = $this->_c->req->setDbConnection(fetchFrom: 'Master');
        $this->db = &$this->_c->req->db;

        // Use results in where clause of sub queries recursively
        $useHierarchy = $this->_getUseHierarchy(
            sqlConfig: $wSqlConfig,
            keyword: 'useHierarchy'
        );

        if (Env::$allowConfigRequest && $this->_c->req->isConfigRequest) {
            $this->_processWriteConfig(
                wSqlConfig: $wSqlConfig,
                useHierarchy: $useHierarchy
            );
        } else {
            $this->_processWrite(
                wSqlConfig: $wSqlConfig,
                useHierarchy: $useHierarchy
            );
        }

        if (isset($wSqlConfig['affectedCacheKeys'])) {
            for (
                $i = 0, $iCount = count(value: $wSqlConfig['affectedCacheKeys']);
                $i < $iCount;
                $i++
            ) {
                $this->_c->req->delDmlCache(
                    cacheKey: $wSqlConfig['affectedCacheKeys'][$i]
                );
            }
        }

        return true;
    }

    /**
     * Process write function for configuration
     *
     * @param array $wSqlConfig   Config from file
     * @param bool  $useHierarchy Use results in where clause of sub queries
     *
     * @return void
     */
    private function _processWriteConfig(&$wSqlConfig, $useHierarchy): void
    {
        $this->dataEncode->startObject(key: 'Config');
        $this->dataEncode->addKeyData(
            key: 'Route',
            data: $this->_c->req->configuredUri
        );
        $this->dataEncode->addKeyData(
            key: 'Payload',
            data: $this->_getConfigParams(
                sqlConfig: $wSqlConfig,
                isFirstCall: true,
                flag: $useHierarchy
            )
        );
        $this->dataEncode->endObject();
    }

    /**
     * Process Function to insert/update
     *
     * @param array $wSqlConfig   Config from file
     * @param bool  $useHierarchy Use results in where clause of sub queries
     *
     * @return void
     * @throws \Exception
     */
    private function _processWrite(&$wSqlConfig, $useHierarchy): void
    {
        // Check for payloadType
        if (isset($wSqlConfig['__PAYLOAD-TYPE__'])) {
            $payloadType = $this->_sess['payloadType'];
            if ($payloadType !== $wSqlConfig['__PAYLOAD-TYPE__']) {
                throw new \Exception(
                    message: 'Invalid payload type',
                    code: HttpStatus::$BadRequest
                );
            }
            // Check for maximum objects supported when payloadType is Array
            if ($wSqlConfig['__PAYLOAD-TYPE__'] === 'Array'
                && isset($wSqlConfig['__MAX-PAYLOAD-OBJECTS__'])
                && ($objCount = $this->_c->req->dataDecode->count())
                && ($objCount > $wSqlConfig['__MAX-PAYLOAD-OBJECTS__'])
            ) {
                throw new \Exception(
                    message: 'Maximum supported payload count is ' .
                        $wSqlConfig['__MAX-PAYLOAD-OBJECTS__'],
                    code: HttpStatus::$BadRequest
                );
            }
        }

        // Set necessary fields
        $this->_sess['necessaryArr'] = $this->_getRequired(
            sqlConfig: $wSqlConfig,
            isFirstCall: true,
            flag: $useHierarchy
        );

        if ($this->_sess['payloadType'] === 'Object') {
            $this->dataEncode->startObject(key: ' Results');
        } else {
            $this->dataEncode->startObject(key: ' Results');
            if (Env::$outputRepresentation === 'Xml') {
                $this->dataEncode->startArray(key: 'Rows');
            }
        }

        // Perform action
        $i_count = $this->_sess['payloadType'] === 'Object' ?
            1 : $this->_c->req->dataDecode->count();

        $configKeys = [];
        $payloadIndexes = [];
        for ($i=0; $i < $i_count; $i++) {
            $_configKeys = $configKeys;
            $_payloadIndexes = $payloadIndexes;
            if ($i === 0) {
                if ($this->_sess['payloadType'] === 'Object') {
                    $_payloadIndexes[] = '';
                } else {
                    $_payloadIndexes[] = "{$i}";
                }
            } else {
                $_payloadIndexes[] = "{$i}";
            }

            // Check for Idempotent Window
            [$idempotentWindow, $hashKey, $hashJson] = $this->_checkIdempotent(
                sqlConfig: $wSqlConfig,
                _payloadIndexes: $_payloadIndexes
            );

            // Begin DML operation
            if (is_null(value: $hashJson)) {
                if ($this->_operateAsTransaction) {
                    $this->db->begin();
                }
                $response = [];
                $this->_writeDB(
                    wSqlConfig: $wSqlConfig,
                    payloadIndexes: $_payloadIndexes,
                    configKeys: $_configKeys,
                    useHierarchy: $useHierarchy,
                    response: $response,
                    necessary: $this->_sess['necessaryArr']
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
                        'Response' => &$response
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
                        'Error' => &$response
                    ];
                }
            } else {
                $arr = json_decode(json: $hashJson, associative: true);
            }
            if (isset($_payloadIndexes[$i]) && $_payloadIndexes[$i] === '') {
                foreach ($arr as $k => $v) {
                    $this->dataEncode->addKeyData(key: $k, data: $v);
                }
            } else {
                if (Env::$outputRepresentation === 'Xml') {
                    $this->dataEncode->startObject(key: 'Row');
                    $this->dataEncode->encode(data: $arr);
                    $this->dataEncode->endObject();
                } else {
                    $this->dataEncode->encode(data: $arr);
                }

            }
        }

        if ($this->_sess['payloadType'] === 'Object') {
            $this->dataEncode->endObject();
        } else {
            if (Env::$outputRepresentation === 'Xml') {
                $this->dataEncode->endArray();
            }
            $this->dataEncode->endObject();
        }
    }

    /**
     * Function to insert/update sub queries recursively
     *
     * @param array $wSqlConfig     Config from file
     * @param array $payloadIndexes Payload Indexes
     * @param array $configKeys     Config Keys
     * @param bool  $useHierarchy   Use results in where clause of sub queries
     * @param array $response       Response by reference
     * @param array $necessary      Required fields
     *
     * @return void
     * @throws \Exception
     */
    private function _writeDB(
        &$wSqlConfig,
        $payloadIndexes,
        $configKeys,
        $useHierarchy,
        &$response,
        &$necessary
    ): void {
        if (isset($payloadIndexes[0]) && $payloadIndexes[0] === '') {
            $payloadIndexes = array_shift($payloadIndexes);
        }
        if (!is_array(value: $payloadIndexes)) $payloadIndexes = [];

        $payloadIndex = is_array(value: $payloadIndexes) ?
            implode(separator: ':', array: $payloadIndexes) : '';
        $isAssoc = $this->_c->req->dataDecode->dataType(
            keys: $payloadIndex
        ) === 'Object';
        $i_count = $isAssoc ?
            1 : $this->_c->req->dataDecode->count(keys: $payloadIndex);

        $counter = 0;
        for ($i=0; $i < $i_count; $i++) {
            $_payloadIndexes = $payloadIndexes;
            if ($this->_operateAsTransaction && !$this->db->beganTransaction) {
                $response['Error'] = 'Transaction rolled back';
                return;
            }

            if ($isAssoc && $i > 0) {
                return;
            }

            if (!$isAssoc && !$useHierarchy) {
                array_push($_payloadIndexes, $i);
            }
            $payloadIndex = is_array(value: $_payloadIndexes) ?
                implode(separator: ':', array: $_payloadIndexes) : '';

            if (!$this->_c->req->dataDecode->isset(keys: $payloadIndex)) {
                throw new \Exception(
                    message: "Payload key '{$payloadIndex}' not set",
                    code: HttpStatus::$NotFound
                );
            }

            $this->_sess['payload'] = $this->_c->req->dataDecode->get(
                keys: $payloadIndex
            );

            if (count(value: $necessary)) {
                $this->_sess['necessary'] = $necessary;
            } else {
                $this->_sess['necessary'] = [];
            }

            // Validation
            if (!$this->_isValidPayload(wSqlConfig: $wSqlConfig)) {
                continue;
            }

            // Execute Pre Sql Hooks
            if (isset($wSqlConfig['__PRE-SQL-HOOKS__'])) {
                if (is_null(value: $this->_hook)) {
                    $this->_hook = new Hook(common: $this->_c);
                }
                $this->_hook->triggerHook(
                    hookConfig: $wSqlConfig['__PRE-SQL-HOOKS__']
                );
            }

            // Get Sql and Params
            [$sql, $sqlParams, $errors] = $this->_getSqlAndParams(
                sqlDetails: $wSqlConfig
            );
            if (!empty($errors)) {
                $response['Error'] = $errors;
                $this->db->rollback();
                return;
            }

            // Execute Query
            $this->db->execDbQuery(sql: $sql, params: $sqlParams);
            if ($this->_operateAsTransaction && !$this->db->beganTransaction) {
                $response['Error'] = 'Something went wrong';
                return;
            }
            if (!$isAssoc && !isset($response[$counter])) {
                $response[$counter] = [];
            }
            if (isset($wSqlConfig['__INSERT-IDs__'])) {
                $id = $this->db->lastInsertId();
                if ($isAssoc) {
                    $response[$wSqlConfig['__INSERT-IDs__']] = $id;
                } else {
                    $response[$counter][$wSqlConfig['__INSERT-IDs__']] = $id;
                }
                $this->_sess['__INSERT-IDs__'][$wSqlConfig['__INSERT-IDs__']] = $id;
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
            if (isset($wSqlConfig['__TRIGGERS__'])) {
                if (is_null(value: $this->_web)) {
                    $this->_web = new Web(common: $this->_c);
                }
                if ($isAssoc) {
                    $response['__TRIGGERS__'] = $this->_web->triggerConfig(
                        triggerConfig: $wSqlConfig['__TRIGGERS__']
                    );
                } else {
                    $response[$counter]['__TRIGGERS__'] = $this->_web->triggerConfig(
                        triggerConfig: $wSqlConfig['__TRIGGERS__']
                    );
                }
            }

            // Execute Post Sql Hooks
            if (isset($wSqlConfig['__POST-SQL-HOOKS__'])) {
                if (is_null(value: $this->_hook)) {
                    $this->_hook = new Hook(common: $this->_c);
                }
                $this->_hook->triggerHook(
                    hookConfig: $wSqlConfig['__POST-SQL-HOOKS__']
                );
            }

            // subQuery for payload
            if (isset($wSqlConfig['__SUB-QUERY__'])) {
                $this->_callWriteDB(
                    isAssoc: $isAssoc,
                    wSqlConfig: $wSqlConfig,
                    payloadIndexes: $_payloadIndexes,
                    configKeys: $configKeys,
                    useHierarchy: $useHierarchy,
                    response: $response,
                    necessary: $necessary
                );
            }

            if (!$isAssoc) {
                $counter++;
            }
        }
    }

    /**
     * Validate and call _writeDB
     *
     * @param bool  $isAssoc        Is Associative array
     * @param array $wSqlConfig     Config from file
     * @param array $payloadIndexes Payload Indexes
     * @param array $configKeys     Config Keys
     * @param bool  $useHierarchy   Use results in where clause of sub queries
     * @param array $response       Response by reference
     * @param array $necessary      Required fields
     *
     * @return void
     */
    private function _callWriteDB(
        $isAssoc,
        &$wSqlConfig,
        $payloadIndexes,
        $configKeys,
        $useHierarchy,
        &$response,
        &$necessary
    ): void {
        if ($useHierarchy) {
            $row = $this->_sess['payload'];
            $this->_resetFetchData(
                fetchFrom: 'sqlPayload',
                keys: $configKeys,
                row: $row
            );
        }

        if (isset($payloadIndexes[0]) && $payloadIndexes[0] === '') {
            $payloadIndexes = array_shift($payloadIndexes);
        }
        if (!is_array(value: $payloadIndexes)) $payloadIndexes = [];

        if (isset($wSqlConfig['__SUB-QUERY__'])
            && $this->_isAssoc(arr: $wSqlConfig['__SUB-QUERY__'])
        ) {
            foreach ($wSqlConfig['__SUB-QUERY__'] as $module => &$_wSqlConfig) {
                $_payloadIndexes = $payloadIndexes;
                $_configKeys = $configKeys;
                $modulePayloadKey = is_array(value: $_payloadIndexes) ?
                    implode(separator: ':', array: $_payloadIndexes) : '';
                if ($useHierarchy) { // use parent data of a payload
                    array_push($_payloadIndexes, $module);
                    array_push($_configKeys, $module);
                    if ($this->_c->req->dataDecode->isset(keys: $modulePayloadKey)) {
                        $_necessary = &$necessary[$module] ?? [];
                    } else {
                        throw new \Exception(
                            message: "Invalid payload: Module '{$module}' missing",
                            code: HttpStatus::$NotFound
                        );
                    }
                } else {
                    $_necessary = $necessary;
                }
                $_useHierarchy = $useHierarchy ?? $this->_getUseHierarchy(
                    sqlConfig: $_wSqlConfig,
                    keyword: 'useHierarchy'
                );
                $response[$module] = [];
                $_response = &$response[$module];
                $this->_writeDB(
                    wSqlConfig: $_wSqlConfig,
                    payloadIndexes: $_payloadIndexes,
                    configKeys: $_configKeys,
                    useHierarchy: $_useHierarchy,
                    response: $_response,
                    necessary: $_necessary
                );
            }
        }
    }

    /**
     * Checks if the payload is valid
     *
     * @param array $wSqlConfig Config from file
     *
     * @return bool
     */
    private function _isValidPayload($wSqlConfig): bool
    {
        $return = true;
        $isValidData = true;
        if (isset($wSqlConfig['__VALIDATE__'])) {
            [$isValidData, $errors] = $this->validate(
                validationConfig: $wSqlConfig['__VALIDATE__']
            );
            if ($isValidData !== true) {
                $this->_c->res->httpStatus = HttpStatus::$BadRequest;
                $this->dataEncode->startObject();
                $this->dataEncode->addKeyData(
                    key: 'Payload',
                    data: $this->_sess['payload']
                );
                $this->dataEncode->addKeyData(key: 'Error', data: $errors);
                $this->dataEncode->endObject();
                $return = false;
            }
        }
        return $return;
    }
}
