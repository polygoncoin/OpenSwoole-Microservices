<?php
/**
 * Read / Write Trait
 * php version 8.3
 *
 * @category  API
 * @package   Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
namespace Microservices\App;

use Microservices\App\Constants;
use Microservices\App\DatabaseDataTypes;
use Microservices\App\HttpStatus;
use Microservices\App\Validator;

/**
 * Trait for API
 * php version 8.3
 *
 * @category  API_Trait
 * @package   Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
trait AppTrait
{
    /**
     * Validator class object
     *
     * @var null|Validator
     */
    public $validator = null;

    /**
     * Function to help execute PHP functions enclosed with double quotes
     *
     * @param mixed $param Returned values by PHP inbuilt functions
     *
     * @return mixed
     */
    public function execPhpFunc($param): mixed
    {
         return $param;
    }

    /**
     * Sets necessary payload
     *
     * @param array $sqlConfig   Config from file
     * @param bool  $isFirstCall true to represent the first call in recursion
     * @param bool  $flag        useHierarchy / useResultSet flag
     *
     * @return array
     * @throws \Exception
     */
    private function _getRequired(&$sqlConfig, $isFirstCall, $flag): array
    {
        $necessaryFields = [];

        foreach (['__SET__', '__WHERE__'] as $options) {
            if (isset($sqlConfig[$options])) {
                foreach ($sqlConfig[$options] as $config) {
                    $fetchFrom = $config['fetchFrom'];
                    $fKey = $config['fetchFromValue'];
                    $dataType = isset($config['dataType']) ?
                        $config['dataType'] : DatabaseDataTypes::$Default;
                    $require = isset($config['necessary']) ?
                        $config['necessary'] : false;

                    if ($fetchFrom === 'function') {
                        continue;
                    }
                    if (!isset($necessaryFields[$fetchFrom][$fKey])) {
                        $dataType['dataKey'] = $fKey;
                        $dataType['nec'] = $require;
                        $necessaryFields[$fetchFrom][$fKey] = $dataType;
                    }
                }
            }
        }

        // Check for hierarchy setting
        $foundHierarchy = false;
        if (isset($sqlConfig['__WHERE__'])) {
            foreach ($sqlConfig['__WHERE__'] as $var => $where) {
                $fetchFrom = $where['fetchFrom'];
                $fKey = $where['fetchFromValue'];

                if ($isFirstCall
                    && in_array(
                        needle: $fetchFrom,
                        haystack: ['sqlResults', 'sqlParams', 'sqlPayload']
                    )
                ) {
                    throw new \Exception(
                        message: "First query can not have {$fetchFrom} config",
                        code: HttpStatus::$InternalServerError
                    );
                }
                if (in_array(
                    needle: $fetchFrom,
                    haystack: ['sqlResults', 'sqlParams', 'sqlPayload']
                )
                ) {
                    $foundHierarchy = true;
                    break;
                }
            }
            // if (!$isFirstCall && $flag && !$foundHierarchy) {
            //     throw new \Exception(
            //          message: 'Invalid config: missing ' . $fetchFrom,
            //          code: HttpStatus::$InternalServerError
            //      );
            // }
        }

        // Check in subQuery
        if (isset($sqlConfig['__SUB-QUERY__'])) {
            if (!$this->_isObject($sqlConfig['__SUB-QUERY__'])) {
                throw new \Exception(
                    message: 'Sub-Query should be an associative array',
                    code: HttpStatus::$InternalServerError
                );
            }
            foreach ($sqlConfig['__SUB-QUERY__'] as $module => &$sqlDetails) {
                $_flag = ($flag) ?? $this->_getUseHierarchy($sqlDetails);
                $sub_necessaryFields = $this->_getRequired(
                    $sqlDetails, false, $_flag
                );
                if ($_flag) {
                    $necessaryFields[$module] = $sub_necessaryFields;
                } else {
                    foreach ($sub_necessaryFields as $fetchFrom => &$fields) {
                        if (!isset($necessaryFields[$fetchFrom])) {
                            $necessaryFields[$fetchFrom] = [];
                        }
                        foreach ($fields as $fKey => $field) {
                            if (!isset($necessaryFields[$fetchFrom][$fKey])) {
                                $necessaryFields[$fetchFrom][$fKey] = $field;
                            }
                        }
                    }
                }
            }
        }

        return $necessaryFields;
    }

    /**
     * Validate payload
     *
     * @param array $validationConfig Validation config from Config file
     *
     * @return array
     */
    public function validate(&$validationConfig): array
    {
        if ($this->validator === null) {
            $this->validator = new Validator(common: $this->_c);
        }

        return $this->validator->validate(validationConfig: $validationConfig);
    }

    /**
     * Returns Query and Params for execution
     *
     * @param array      $sqlDetails  Config from file
     * @param bool|null  $isFirstCall true to represent the first call in recursion
     * @param array|null $configKeys  Config Keys
     * @param bool|null  $flag        useHierarchy / useResultSet flag
     *
     * @return array
     */
    private function _getSqlAndParams(
        &$sqlDetails,
        $isFirstCall = null,
        $configKeys = null,
        $flag = null
    ): array {
        $sql = $sqlDetails['__QUERY__'];
        $sqlParams = [];
        $paramKeys = [];
        $errors = [];
        $row = [];

        // Check __SET__
        if (isset($sqlDetails['__SET__'])
            && count(value: $sqlDetails['__SET__']) !== 0
        ) {
            [$params, $errors] = $this->_getSqlParams($sqlDetails['__SET__']);
            if (empty($errors)) {
                if (!empty($params)) {
                    // __SET__ not compulsory in query
                    $found = strpos(haystack: $sql, needle: '__SET__') !== false;
                    $__SET__ = [];
                    foreach ($params as $param => &$v) {
                        $param = str_replace(
                            search: ['`', ' '],
                            replace: '',
                            subject: $param
                        );
                        $paramKeys[] = $param;
                        if ($found) {
                            $__SET__[] = "`{$param}` = :{$param}";
                        }
                        $sqlParams[":{$param}"] = $v;
                        $row[$param] = $v;
                    }
                    if ($found) {
                        $sql = str_replace(
                            search: '__SET__',
                            replace: implode(separator: ', ', array: $__SET__),
                            subject: $sql
                        );
                    }
                }
            }
        }

        // Check __WHERE__
        if (empty($errors)
            && isset($sqlDetails['__WHERE__'])
            && count(value: $sqlDetails['__WHERE__']) !== 0
        ) {
            $wErrors = [];
            [$sqlWhereParams, $wErrors] = $this->_getSqlParams(
                $sqlDetails['__WHERE__']
            );
            if (empty($wErrors)) {
                if (!empty($sqlWhereParams)) {
                    // __WHERE__ not compulsory in query
                    $wfound = strpos(haystack: $sql, needle: '__WHERE__') !== false;
                    $__WHERE__ = [];
                    foreach ($sqlWhereParams as $param => &$v) {
                        $wparam = $param = str_replace(
                            search: ['`', ' '],
                            replace: '',
                            subject: $param
                        );
                        $i = 0;
                        while (in_array(needle: $wparam, haystack: $paramKeys)) {
                            $i++;
                            $wparam = "{$param}{$i}";
                        }
                        $paramKeys[] = $wparam;
                        if ($wfound) {
                            $__WHERE__[] = "`{$param}` = :{$wparam}";
                        }
                        $sqlParams[":{$wparam}"] = $v;
                        $row[$wparam] = $v;
                    }
                    if ($wfound) {
                        $sql = str_replace(
                            search: '__WHERE__',
                            replace: implode(separator: ' AND ', array: $__WHERE__),
                            subject: $sql
                        );
                    }
                }
            } else {
                $errors = array_merge($errors, $wErrors);
            }
        }

        if (!empty($row)) {
            $this->_resetFetchData('sqlParams', $configKeys, $row);
        }

        return [$sql, $sqlParams, $errors];
    }

    /**
     * Generates Params for statement to execute
     *
     * @param array $sqlConfig Config from file
     *
     * @return array
     * @throws \Exception
     */
    private function _getSqlParams(&$sqlConfig): array
    {
        $sqlParams = [];
        $errors = [];

        // Collect param values as per config respectively
        foreach ($sqlConfig as $config) {
            $var = $config['column'];
            $fetchFrom = $config['fetchFrom'];
            $fKey = $config['fetchFromValue'];
            if ($fetchFrom === 'function') {
                $function = $fKey;
                $value = $function($this->_c->req->session);
                $sqlParams[$var] = $value;
                continue;
            } elseif (in_array(
                needle: $fetchFrom,
                haystack: ['sqlResults', 'sqlParams', 'sqlPayload']
            )
            ) {
                $fetchFromKeys = explode(separator: ':', string: $fKey);
                $value = $this->_c->req->session[$fetchFrom];
                foreach ($fetchFromKeys as $key) {
                    if (!isset($value[$key])) {
                        throw new \Exception(
                            message: 'Invalid hierarchy:  Missing hierarchy data',
                            code: HttpStatus::$InternalServerError
                        );
                    }
                    $value = $value[$key];
                }
                $sqlParams[$var] = $value;
                continue;
            } elseif ($fetchFrom === 'custom') {
                $value = $fKey;
                $sqlParams[$var] = $value;
                continue;
            } elseif (isset($this->_c->req->session[$fetchFrom][$fKey])) {
                if (DatabaseDataTypes::validateDataType(
                    data: $this->_c->req->session[$fetchFrom][$fKey],
                    dataType: $this->_c->req->session['necessary'][$fetchFrom][$fKey]
                )
                ) {
                    $sqlParams[$var] = $this->_c->req->session[$fetchFrom][$fKey];
                }
                continue;
            } elseif ($this->_c->req->session['necessary'][$fetchFrom][$fKey]['nec']) {
                $errors[] = "Missing necessary field '{$fetchFrom}' for '{$fKey}'";
                continue;
            } else {
                $errors[] = "Invalid configuration of '{$fetchFrom}' for '{$fKey}'";
                continue;
            }
        }

        return [$sqlParams, $errors];
    }

    /**
     * Function to find wether provided array is associative/simple array
     *
     * @param array $arr Array to search for associative/simple array
     *
     * @return bool
     */
    private function _isObject($arr): bool
    {
        $assoc = false;

        $i = 0;
        foreach ($arr as $k => &$v) {
            if ($k !== $i++) {
                $assoc = true;
                break;
            }
        }

        return $assoc;
    }

    /**
     * Use results in where clause of sub queries recursively
     *
     * @param array  $sqlConfig Config from file
     * @param string $keyword   useHierarchy/useResultSet
     *
     * @return bool
     */
    private function _getUseHierarchy(&$sqlConfig, $keyword = ''): bool
    {
        if (isset($sqlConfig[$keyword]) && $sqlConfig[$keyword] === true) {
            return true;
        }
        if (isset($sqlConfig['useHierarchy'])
            && $sqlConfig['useHierarchy'] === true
        ) {
            return true;
        }
        if (isset($sqlConfig['useResultSet'])
            && $sqlConfig['useResultSet'] === true
        ) {
            return true;
        }
        return false;
    }

    /**
     * Return config par recursively
     *
     * @param array $sqlConfig   Config from file
     * @param bool  $isFirstCall Flag to check if this is first request
     * @param bool  $flag        useHierarchy/useResultSet flag
     *
     * @return array
     * @throws \Exception
     */
    private function _getConfigParams(&$sqlConfig, $isFirstCall, $flag): array
    {
        $result = [];

        if (isset($sqlConfig['countQuery'])) {
            $sqlConfig['__CONFIG__'][] = [
                'column' => 'page',
                'fetchFrom' => 'payload',
                'fetchFromValue' => 'page',
                'dataType' => DatabaseDataTypes::$INT,
                'necessary' => Constants::$REQUIRED
            ];
            $sqlConfig['__CONFIG__'][] = [
                'column' => 'perPage',
                'fetchFrom' => 'payload',
                'fetchFromValue' => 'perPage',
                'dataType' => DatabaseDataTypes::$INT
            ];

            foreach ($sqlConfig['__CONFIG__'] as $config) {
                $fetchFrom = $config['fetchFrom'];
                $fKey = $config['fetchFromValue'];
                $dataType = isset($config['dataType'])
                    ? $config['dataType'] : DatabaseDataTypes::$Default;
                $require = isset($config['necessary'])
                    ? $config['necessary'] : false;

                if ($fetchFrom !== 'payload') {
                    continue;
                }
                if (isset($result[$fKey])
                    && $result[$fKey]['dataMode'] === 'Required'
                ) {
                    continue;
                }
                $dataType['dataMode'] = $require ? 'Required' : 'Optional';
                $result[$fKey] = $dataType;
            }
        }

        foreach (['__SET__', '__WHERE__'] as $options) {
            if (isset($sqlConfig[$options])) {
                foreach ($sqlConfig[$options] as $config) {
                    $fetchFrom = $config['fetchFrom'];
                    $fKey = $config['fetchFromValue'];
                    $dataType = isset($config['dataType']) ?
                        $config['dataType'] : DatabaseDataTypes::$Default;
                    $require = isset($config['necessary']) ?
                        $config['necessary'] : false;

                    if ($fetchFrom !== 'payload') {
                        continue;
                    }
                    if (isset($result[$fKey])
                        && $result[$fKey]['dataMode'] === 'Required'
                    ) {
                        continue;
                    }
                    $dataType['dataMode'] = $require ? 'Required' : 'Optional';
                    $result[$fKey] = $dataType;
                }
            }
        }

        // Check for hierarchy
        $foundHierarchy = false;
        if (isset($sqlConfig['__WHERE__'])) {
            foreach ($sqlConfig['__WHERE__'] as $var => $payload) {
                $fetchFrom = $payload[0];
                $fKey = $payload[1];
                if (in_array(
                    needle: $fetchFrom,
                    haystack: ['sqlResults', 'sqlParams', 'sqlPayload']
                )
                ) {
                    $foundHierarchy = true;
                    break;
                }
            }
            if (!$isFirstCall && $flag && !$foundHierarchy) {
                throw new \Exception(
                    message: 'Invalid config: missing ' . $fetchFrom,
                    code: HttpStatus::$InternalServerError
                );
            }
        }

        // Check in subQuery
        if (isset($sqlConfig['__SUB-QUERY__'])) {
            foreach ($sqlConfig['__SUB-QUERY__'] as $module => &$_sqlConfig) {
                $_flag = ($flag) ?? $this->_getUseHierarchy($_sqlConfig);
                $sub_necessaryFields = $this->_getConfigParams(
                    $_sqlConfig,
                    false,
                    $_flag
                );
                if ($flag) {
                    if (!empty($sub_necessaryFields)) {
                        $result[$module] = $sub_necessaryFields;
                    }
                } else {
                    foreach ($sub_necessaryFields as $fKey => $field) {
                        if (!isset($result[$fKey])) {
                            $result[$fKey] = $field;
                        }
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Function to reset data for module key wise
     *
     * @param string $fetchFrom sqlResults / sqlParams / sqlPayload
     * @param array  $keys      Module Keys in recursion
     * @param array  $row       Row data fetched from DB
     *
     * @return void
     */
    private function _resetFetchData($fetchFrom, $keys, $row): void
    {
        if (empty($keys) || count(value: $keys) === 0) {
            $this->_c->req->session[$fetchFrom] = [];
            $this->_c->req->session[$fetchFrom]['return'] = [];
        }
        $httpReq = &$this->_c->req->session[$fetchFrom]['return'];
        if (!empty($keys)) {
            foreach ($keys as $k) {
                if (!isset($httpReq[$k])) {
                    $httpReq[$k] = [];
                }
                $httpReq = &$httpReq[$k];
            }
        }
        $httpReq = $row;
    }

    /**
     * Rate Limiting request if configured for Route Queries
     *
     * @param array $sqlConfig Config from file
     *
     * @return void
     * @throws \Exception
     */
    private function _rateLimitRoute(&$sqlConfig): void
    {
        if (isset($sqlConfig['rateLimiterMaxRequests'])
            && isset($sqlConfig['rateLimiterSecondsWindow'])
        ) {
            $payloadSignature = [
                'IP' => $this->_c->req->REMOTE_ADDR,
                'clientId' => $this->_c->req->clientId,
                'groupId' => ($this->_c->req->groupId !== null ?
                    $this->_c->req->groupId : 0),
                'userId' => ($this->_c->req->userId !== null ?
                    $this->_c->req->userId : 0),
                'httpMethod' => $this->_c->req->REQUEST_METHOD,
                'Route' => $this->_c->req->ROUTE,
            ];
            // $hash = hash_hmac(
            // 'sha256',
            // json_encode($payloadSignature),
            // getenv(name: 'IdempotentSecret')
            // );
            $hash = json_encode(value: $payloadSignature);
            $hashKey = md5(string: $hash);

            // @throws \Exception
            $rateLimitChecked = $this->_c->req->checkRateLimit(
                rateLimiterPrefix: getenv(name: 'rateLimiterRoutePrefix'),
                rateLimiterMaxRequests: $sqlConfig['rateLimiterMaxRequests'],
                rateLimiterSecondsWindow: $sqlConfig['rateLimiterSecondsWindow'],
                key: $hashKey
            );
        }
    }

    /**
     * Check for Idempotent Window
     *
     * @param array $sqlConfig       Config from file
     * @param array $_payloadIndexes Payload Indexes
     *
     * @return array
     */
    private function _checkIdempotent(&$sqlConfig, $_payloadIndexes): array
    {
        $idempotentWindow = 0;
        $hashKey = null;
        $hashJson = null;
        if (isset($sqlConfig['idempotentWindow'])
            && is_numeric(value: $sqlConfig['idempotentWindow'])
            && $sqlConfig['idempotentWindow'] > 0
        ) {
            $idempotentWindow = (int)$sqlConfig['idempotentWindow'];
            if ($idempotentWindow) {
                $payloadSignature = [
                    'IdempotentSecret' => getenv(name: 'IdempotentSecret'),
                    'idempotentWindow' => $idempotentWindow,
                    'IP' => $this->_c->req->REMOTE_ADDR,
                    'clientId' => $this->_c->req->clientId,
                    'groupId' => ($this->_c->req->groupId !== null ?
                        $this->_c->req->groupId : 0),
                    'userId' => ($this->_c->req->userId !== null ?
                        $this->_c->req->userId : 0),
                    'httpMethod' => $this->_c->req->REQUEST_METHOD,
                    'Route' => $this->_c->req->ROUTE,
                    'payload' => $this->_c->req->dataDecode->get(
                        implode(separator: ':', array: $_payloadIndexes)
                    )
                ];

                $hash = json_encode(value: $payloadSignature);
                $hashKey = md5(string: $hash);
                if ($this->_c->req->cache->cacheExists(key: $hashKey)) {
                    $hashJson = str_replace(
                        search: 'JSON',
                        replace: $this->_c->req->cache->getCache(key: $hashKey),
                        subject: '{"Idempotent": JSON, "Status": 200}'
                    );
                }
            }
        }

        return [$idempotentWindow, $hashKey, $hashJson];
    }

    /**
     * Lag Response
     *
     * @param array $sqlConfig Config from file
     *
     * @return void
     */
    private function _lagResponse($sqlConfig): void
    {
        if (isset($sqlConfig['responseLag'])
            && isset($sqlConfig['responseLag'])
        ) {
            $payloadSignature = [
                'IP' => $this->_c->req->REMOTE_ADDR,
                'clientId' => $this->_c->req->clientId,
                'groupId' => ($this->_c->req->groupId !== null ?
                    $this->_c->req->groupId : 0),
                'userId' => ($this->_c->req->userId !== null ?
                    $this->_c->req->userId : 0),
                'httpMethod' => $this->_c->req->REQUEST_METHOD,
                'Route' => $this->_c->req->ROUTE,
            ];

            $hash = json_encode(value: $payloadSignature);
            $hashKey = 'LAG:' . md5(string: $hash);

            if ($this->_c->req->cache->cacheExists(key: $hashKey)) {
                $noOfRequests = $this->_c->req->cache->getCache(key: $hashKey);
            } else {
                $noOfRequests = 0;
            }

            $this->_c->req->cache->setCache(
                key: $hashKey,
                value: ++$noOfRequests,
                expire: 3600
            );

            $lag = 0;
            $responseLag = &$sqlConfig['responseLag'];
            if (is_array(value: $responseLag)) {
                foreach ($responseLag as $start => $newLag) {
                    if ($noOfRequests > $start) {
                        $lag = $newLag;
                    }
                }
            }

            if ($lag > 0) {
                sleep(seconds: $lag);
            }
        }
    }
}
