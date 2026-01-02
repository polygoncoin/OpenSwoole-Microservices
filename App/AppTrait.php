<?php

/**
 * Read / Write Trait
 * php version 8.3
 *
 * @category  API
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App;

use Microservices\App\Counter;
use Microservices\App\Constants;
use Microservices\App\DatabaseDataTypes;
use Microservices\App\DbFunctions;
use Microservices\App\Env;
use Microservices\App\HttpStatus;
use Microservices\App\RateLimiter;
use Microservices\App\Validator;
use Microservices\App\Start;

/**
 * Trait for API
 * php version 8.3
 *
 * @category  API_Trait
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
trait AppTrait
{
    /**
     * Rate Limiter
     *
     * @var null|RateLimiter
     */
    private $rateLimiter = null;

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
    private function getRequired(&$sqlConfig, $isFirstCall, $flag): array
    {
        $necessaryFields = [];

        foreach (['__PAYLOAD__', '__SET__', '__WHERE__'] as $options) {
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
                        $dataType['necessary'] = $require;
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

                if (
                    $isFirstCall
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
                if (
                    in_array(
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
        if (
            isset($sqlConfig['__SUB-QUERY__'])
            || isset($sqlConfig['__SUB-PAYLOAD__'])
        ) {
            if (
                isset($sqlConfig['__SUB-QUERY__'])
                && !$this->isObject($sqlConfig['__SUB-QUERY__'])
            ) {
                throw new \Exception(
                    message: 'Sub-Query should be an associative array',
                    code: HttpStatus::$InternalServerError
                );
            }
            if (
                isset($sqlConfig['__SUB-PAYLOAD__'])
                && !$this->isObject($sqlConfig['__SUB-PAYLOAD__'])
            ) {
                throw new \Exception(
                    message: 'Sub-Payload should be an associative array',
                    code: HttpStatus::$InternalServerError
                );
            }
            foreach (['__SUB-QUERY__', '__SUB-PAYLOAD__'] as $options) {
                if (isset($sqlConfig[$options])) {
                    foreach ($sqlConfig[$options] as $module => &$sqlDetails) {
                        $flag = ($flag) ?? $this->getUseHierarchy($sqlDetails);
                        $sub_necessaryFields = $this->getRequired(
                            $sqlDetails,
                            false,
                            $flag
                        );
                        if ($flag) {
                            $necessaryFields[$module] = $sub_necessaryFields;
                        } else {
                            foreach (
                                $sub_necessaryFields as $fetchFrom => &$fields
                            ) {
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
            $this->validator = new Validator($this->api);
        }

        return $this->validator->validate(validationConfig: $validationConfig);
    }

    /**
     * Returns Query and Params for execution
     *
     * @param array      $sqlDetails  Config from file
     * @param array|null $configKeys  Config Keys
     *
     * @return array
     */
    private function getSqlAndParamsNamedMode(
        &$sqlDetails,
        $configKeys = null
    ): array {
        $id = null;
        $sql = '';
        /*!999999 comment goes here */
        if (isset($sqlDetails['__SQL-COMMENT__'])) {
            $sql .= '/' . '*!999999 ';
            $sql .= $sqlDetails['__SQL-COMMENT__'];
            $sql .= ' */';
        }
        switch (true) {
            case isset($sqlDetails['__QUERY__']):
                $sql .= $sqlDetails['__QUERY__'];
                break;
            case isset($sqlDetails['__DOWNLOAD__']):
                $sql .= $sqlDetails['__DOWNLOAD__'];
                break;
        }
        $sqlParams = [];
        $paramKeys = [];
        $errors = [];
        $row = [];
        $__SET__ = [];

        $missExecution = $wMissExecution = false;
        // Check __SET__
        if (
            isset($sqlDetails['__SET__'])
            && count(value: $sqlDetails['__SET__']) !== 0
        ) {
            [$params, $errors, $missExecution] = $this->getSqlParams($sqlDetails['__SET__']);
            if (empty($errors) && !$missExecution) {
                if (!empty($params)) {
                    // __SET__ not compulsory in query
                    $found = strpos(haystack: $sql, needle: '__SET__') !== false;
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
                }
            }
        }

        // Check __WHERE__
        if (
            empty($errors)
            && !$missExecution
            && isset($sqlDetails['__WHERE__'])
            && count(value: $sqlDetails['__WHERE__']) !== 0
        ) {
            $wErrors = [];
            [$sqlWhereParams, $wErrors, $wMissExecution] = $this->getSqlParams(
                $sqlDetails['__WHERE__']
            );
            if (empty($wErrors) && !$wMissExecution) {
                if (!empty($sqlWhereParams)) {
                    // __WHERE__ not compulsory in query
                    $wfound = strpos(haystack: $sql, needle: '__WHERE__') !== false;
                    if ($wfound) {
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
                            $__WHERE__[] = "`{$param}` = :{$wparam}";
                            $sqlParams[":{$wparam}"] = $v;
                            $row[$wparam] = $v;
                        }
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
        } else {
            if (
                Env::$enableGlobalCounter
                && strpos(trim(strtolower($sql)), 'insert') === 0
                && !isset($sqlParams[':id'])
                && !isset($row['id'])
            ) {
                $id = Counter::getGlobalCounter();
                $sqlParams[':id'] = $id;
                $row['id'] = $id;

                $__SET__[] = "`id` = :id";
            }
        }
        if (!empty($__SET__)) {
            $sql = str_replace(
                search: '__SET__',
                replace: implode(separator: ', ', array: $__SET__),
                subject: $sql
            );
        }

        if (!empty($row)) {
            $this->resetFetchData('sqlParams', $configKeys, $row);
        }

        return [$id, $sql, $sqlParams, $errors, ($missExecution || $wMissExecution)];
    }

    /**
     * Returns Query and Params for execution
     *
     * @param array      $sqlDetails  Config from file
     * @param array|null $configKeys  Config Keys
     *
     * @return array
     */
    private function getSqlAndParamsUnnamedMode(
        &$sqlDetails,
        $configKeys = null
    ): array {
        $id = null;
        $sql = '';
        /*!999999 comment goes here */
        if (isset($sqlDetails['__SQL-COMMENT__'])) {
            $sql .= '/' . '*!999999 ';
            $sql .= $sqlDetails['__SQL-COMMENT__'];
            $sql .= ' */';
        }
        switch (true) {
            case isset($sqlDetails['__QUERY__']):
                $sql .= $sqlDetails['__QUERY__'];
                break;
            case isset($sqlDetails['__DOWNLOAD__']):
                $sql .= $sqlDetails['__DOWNLOAD__'];
                break;
        }
        $sqlParams = [];
        $paramKeys = [];
        $errors = [];
        $row = [];
        $__SET__ = [];

        $missExecution = $wMissExecution = false;
        // Check __SET__
        if (
            isset($sqlDetails['__SET__'])
            && count(value: $sqlDetails['__SET__']) !== 0
        ) {
            [$params, $errors, $missExecution] = $this->getSqlParams($sqlDetails['__SET__']);
            if (empty($errors) && !$missExecution) {
                if (!empty($params)) {
                    // __SET__ not compulsory in query
                    $found = strpos(haystack: $sql, needle: '__SET__') !== false;
                    foreach ($params as $param => &$v) {
                        $paramKeys[] = $param;
                        if ($found) {
                            $__SET__[] = "{$param} = ?";
                        }
                        $sqlParams[] = $v;
                        $row[$param] = $v;
                    }
                }
            }
        }

        // Check __WHERE__
        if (
            empty($errors)
            && !$missExecution
            && isset($sqlDetails['__WHERE__'])
            && count(value: $sqlDetails['__WHERE__']) !== 0
        ) {
            $wErrors = [];
            [$sqlWhereParams, $wErrors, $wMissExecution] = $this->getSqlParams(
                $sqlDetails['__WHERE__']
            );
            if (empty($wErrors) && !$wMissExecution) {
                if (!empty($sqlWhereParams)) {
                    // __WHERE__ not compulsory in query
                    $wfound = strpos(haystack: $sql, needle: '__WHERE__') !== false;
                    if ($wfound) {
                        $__WHERE__ = [];
                        foreach ($sqlWhereParams as $param => &$v) {
                            $wparam = $param;
                            $i = 0;
                            while (in_array(needle: $wparam, haystack: $paramKeys)) {
                                $i++;
                                $wparam = "{$param}{$i}";
                            }
                            $paramKeys[] = $wparam;
                            $__WHERE__[] = "{$param} = ?";
                            $sqlParams[] = $v;
                            $row[$wparam] = $v;
                        }
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
        } else {
            if (
                Env::$enableGlobalCounter
                && strpos(trim(strtolower($sql)), 'insert') === 0
                && !isset($sqlParams[':id'])
                && !isset($row['id'])
            ) {
                $id = Counter::getGlobalCounter();
                $sqlParams[] = $id;
                $row['id'] = $id;

                $__SET__[] = "id = ?";
            }
        }
        if (!empty($__SET__)) {
            $sql = str_replace(
                search: '__SET__',
                replace: implode(separator: ', ', array: $__SET__),
                subject: $sql
            );
        }

        if (!empty($row)) {
            $this->resetFetchData('sqlParams', $configKeys, $row);
        }

        return [$id, $sql, $sqlParams, $errors, ($missExecution || $wMissExecution)];
    }

    /**
     * Generates Params for statement to execute
     *
     * @param array $sqlConfig Config from file
     *
     * @return array
     * @throws \Exception
     */
    private function getSqlParams(&$sqlConfig): array
    {
        $missExecution = false;
        $sqlParams = [];
        $errors = [];

        // Collect param values as per config respectively
        foreach ($sqlConfig as $config) {
            $var = $config['column'];
            $fetchFrom = $config['fetchFrom'];
            $fKey = $config['fetchFromValue'];
            if ($fetchFrom === 'function') {
                $function = $fKey;
                $value = $function($this->api->req->s);
                $sqlParams[$var] = $value;
                continue;
            } elseif (
                in_array(
                    needle: $fetchFrom,
                    haystack: ['sqlParams', 'sqlPayload']
                )
            ) {
                if (!isset($this->api->req->s[$fetchFrom])) {
                    $errors[] = "Missing key '{$fKey}' in '{$fetchFrom}'";
                    continue;
                }
                $fetchFromKeys = explode(separator: ':', string: $fKey);
                $value = $this->api->req->s[$fetchFrom];
                foreach ($fetchFromKeys as $key) {
                    if (!isset($value[$key])) {
                        $errors[] = "Missing hierarchy key '{$key}' of '{$fKey}' in '{$fetchFrom}'";
                        continue;
                    }
                    $value = $value[$key];
                }
                $sqlParams[$var] = $value;
                continue;
            } elseif ($fetchFrom === 'sqlResults') {
                if (!isset($this->api->req->s[$fetchFrom])) {
                    $missExecution = true;
                    continue;
                }
                $fetchFromKeys = explode(separator: ':', string: $fKey);
                $value = $this->api->req->s[$fetchFrom];
                foreach ($fetchFromKeys as $key) {
                    if (!isset($value[$key])) {
                        $missExecution = true;
                        continue;
                    }
                    $value = $value[$key];
                }
                $sqlParams[$var] = $value;
                continue;
            } elseif ($fetchFrom === 'custom') {
                $value = $fKey;
                $sqlParams[$var] = $value;
                continue;
            } elseif (isset($this->api->req->s[$fetchFrom][$fKey])) {
                if (isset($this->api->req->s['necessary'][$fetchFrom][$fKey])) {
                    if (
                        DatabaseDataTypes::validateDataType(
                            data: $this->api->req->s[$fetchFrom][$fKey],
                            dataType: $this->api->req->s['necessary'][$fetchFrom][$fKey]
                        )
                    ) {
                        $sqlParams[$var] = $this->api->req->s[$fetchFrom][$fKey];
                    }
                } else {
                    $sqlParams[$var] = $this->api->req->s[$fetchFrom][$fKey];
                }
                continue;
            } elseif ($this->api->req->s['necessary'][$fetchFrom][$fKey]['necessary']) {
                $errors[] = "Missing necessary field '{$fetchFrom}' for '{$fKey}'";
                continue;
            } else {
                $errors[] = "Invalid configuration of '{$fetchFrom}' for '{$fKey}'";
                continue;
            }
        }

        return [$sqlParams, $errors, $missExecution];
    }

    /**
     * Function to find wether provided array is associative/simple array
     *
     * @param array $arr Array to search for associative/simple array
     *
     * @return bool
     */
    private function isObject($arr): bool
    {
        $isAssoc = false;

        $i = 0;
        foreach ($arr as $k => &$v) {
            if ($k !== $i++) {
                $isAssoc = true;
                break;
            }
        }

        return $isAssoc;
    }

    /**
     * Use results in where clause of sub queries recursively
     *
     * @param array  $sqlConfig Config from file
     * @param string $keyword   useHierarchy/useResultSet
     *
     * @return bool
     */
    private function getUseHierarchy(&$sqlConfig, $keyword = ''): bool
    {
        if (isset($sqlConfig[$keyword]) && $sqlConfig[$keyword] === true) {
            return true;
        }
        if (
            isset($sqlConfig['useHierarchy'])
            && $sqlConfig['useHierarchy'] === true
        ) {
            return true;
        }
        if (
            isset($sqlConfig['useResultSet'])
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
    private function getConfigParams(&$sqlConfig, $isFirstCall, $flag): array
    {
        $result = [];

        if (isset($sqlConfig['countQuery'])) {
            $sqlConfig['__CONFIG__'][] = [
                'column' => 'page',
                'fetchFrom' => 'queryParams',
                'fetchFromValue' => 'page',
                'dataType' => DatabaseDataTypes::$INT,
                'necessary' => Constants::$REQUIRED
            ];
            $sqlConfig['__CONFIG__'][] = [
                'column' => 'perPage',
                'fetchFrom' => 'queryParams',
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
                if (
                    isset($result[$fKey])
                    && $result[$fKey]['dataMode'] === 'Required'
                ) {
                    continue;
                }
                $dataType['dataMode'] = $require ? 'Required' : 'Optional';
                $result[$fKey] = $dataType;
            }
        }

        foreach (['__PAYLOAD__', '__SET__', '__WHERE__'] as $options) {
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
                    if (
                        isset($result[$fKey])
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
                if (
                    in_array(
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

        // Check in subQuery//'__SUB-PAYLOAD__'
        foreach (['__SUB-PAYLOAD__', '__SUB-QUERY__'] as $options) {
            if (isset($sqlConfig[$options])) {
                foreach ($sqlConfig[$options] as $module => &$sqlConfig) {
                    $flag = ($flag) ?? $this->getUseHierarchy($sqlConfig);
                    $sub_necessaryFields = $this->getConfigParams(
                        $sqlConfig,
                        false,
                        $flag
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
    private function resetFetchData($fetchFrom, $keys, $row): void
    {
        if (empty($keys) || count(value: $keys) === 0) {
            $this->api->req->s[$fetchFrom] = [];
            $this->api->req->s[$fetchFrom]['return'] = [];
        }
        $httpReq = &$this->api->req->s[$fetchFrom]['return'];
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
    private function rateLimitRoute(&$sqlConfig): void
    {
        if (
            ((int)getenv(name: 'enableRateLimitAtRouteLevel')) === 0
            || !isset($sqlConfig['rateLimitMaxRequests'])
            || !isset($sqlConfig['rateLimitSecondsWindow'])
        ) {
            return;
        }

        $payloadSignature = [
            'IP' => $this->api->req->IP,
            'cID' => $this->api->req->s['cDetails']['id'],
            'httpMethod' => $this->api->req->METHOD,
            'Route' => $this->api->req->ROUTE,
        ];
        if (isset($this->api->req->s['uDetails'])) {
            $payloadSignature['gID'] = ($this->api->req->s['gDetails']['id'] !== null ?
                    $this->api->req->s['gDetails']['id'] : 0);
            $payloadSignature['uID'] = ($this->api->req->s['uDetails']['id'] !== null ?
                    $this->api->req->s['uDetails']['id'] : 0);
        }
        // $hash = hash_hmac(
        // 'sha256',
        // json_encode($payloadSignature),
        // getenv(name: 'IdempotentSecret')
        // );
        $hash = json_encode(value: $payloadSignature);
        $hashKey = md5(string: $hash);

        // @throws \Exception
        $rateLimitChecked = $this->checkRateLimit(
            rateLimitPrefix: getenv(name: 'rateLimitRoutePrefix'),
            rateLimitMaxRequests: $sqlConfig['rateLimitMaxRequests'],
            rateLimitSecondsWindow: $sqlConfig['rateLimitSecondsWindow'],
            key: $hashKey
        );
    }

    /**
     * Check for Idempotent Window
     *
     * @param array $sqlConfig       Config from file
     * @param array $payloadIndexes Payload Indexes
     *
     * @return array
     */
    private function checkIdempotent(&$sqlConfig, $payloadIndexes): array
    {
        $idempotentWindow = 0;
        $hashKey = null;
        $hashJson = null;
        if (
            isset($sqlConfig['idempotentWindow'])
            && is_numeric(value: $sqlConfig['idempotentWindow'])
            && $sqlConfig['idempotentWindow'] > 0
        ) {
            $idempotentWindow = (int)$sqlConfig['idempotentWindow'];
            if ($idempotentWindow) {
                $payloadSignature = [
                    'IdempotentSecret' => getenv(name: 'IdempotentSecret'),
                    'idempotentWindow' => $idempotentWindow,
                    'IP' => $this->api->req->IP,
                    'cID' => $this->api->req->s['cDetails']['id'],
                    'httpMethod' => $this->api->req->METHOD,
                    'Route' => $this->api->req->ROUTE,
                    'payload' => $this->api->req->dataDecode->get(
                        implode(separator: ':', array: $payloadIndexes)
                    )
                ];
                if (isset($this->api->req->s['uDetails'])) {
                    $payloadSignature['gID'] = ($this->api->req->s['gDetails']['id'] !== null ?
                            $this->api->req->s['gDetails']['id'] : 0);
                    $payloadSignature['uID'] = ($this->api->req->s['uDetails']['id'] !== null ?
                            $this->api->req->s['uDetails']['id'] : 0);
                }

                $hash = json_encode(value: $payloadSignature);
                $hashKey = md5(string: $hash);
                if (DbFunctions::$gCacheServer->cacheExists(key: $hashKey)) {
                    $hashJson = str_replace(
                        search: 'JSON',
                        replace: DbFunctions::$gCacheServer->getCache(key: $hashKey),
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
    private function lagResponse($sqlConfig): void
    {
        if (
            isset($sqlConfig['responseLag'])
            && isset($sqlConfig['responseLag'])
        ) {
            $payloadSignature = [
                'IP' => $this->api->req->IP,
                'cID' => $this->api->req->s['cDetails']['id'],
                'httpMethod' => $this->api->req->METHOD,
                'Route' => $this->api->req->ROUTE,
            ];
            if (isset($this->api->req->s['uDetails'])) {
                $payloadSignature['gID'] = ($this->api->req->s['gDetails']['id'] !== null ?
                        $this->api->req->s['gDetails']['id'] : 0);
                $payloadSignature['uID'] = ($this->api->req->s['uDetails']['id'] !== null ?
                        $this->api->req->s['uDetails']['id'] : 0);
            }

            $hash = json_encode(value: $payloadSignature);
            $hashKey = 'LAG:' . md5(string: $hash);

            if (DbFunctions::$gCacheServer->cacheExists(key: $hashKey)) {
                $noOfRequests = DbFunctions::$gCacheServer->getCache(key: $hashKey);
            } else {
                $noOfRequests = 0;
            }

            DbFunctions::$gCacheServer->setCache(
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

    /**
     * Check Rate Limit
     *
     * @param string $rateLimitPrefix        Prefix
     * @param int    $rateLimitMaxRequests   Max request
     * @param int    $rateLimitSecondsWindow Window in seconds
     * @param string $key                    Key
     *
     * @return void
     * @throws \Exception
     */
    public function checkRateLimit(
        $rateLimitPrefix,
        $rateLimitMaxRequests,
        $rateLimitSecondsWindow,
        $key
    ): bool {
        if ($this->rateLimiter === null) {
            $this->rateLimiter = new RateLimiter($this->api->req);
        }

        try {
            $result = $this->rateLimiter->check(
                prefix: $rateLimitPrefix,
                maxRequests: $rateLimitMaxRequests,
                secondsWindow: $rateLimitSecondsWindow,
                key: $key
            );

            if ($result['allowed']) {
                // Process the request
                return true;
            } else {
                // Return 429 Too Many Requests
                throw new \Exception(
                    message: $result['resetAt'] - Env::$timestamp,
                    code: HttpStatus::$TooManyRequests
                );
            }
        } catch (\Exception $e) {
            // Handle connection errors
            throw new \Exception(
                message: $e->getMessage(),
                code: $e->getCode()
            );
        }
    }

    /**
     * Get Trigger Data
     *
     * @param array $triggerConfig Trigger Config
     *
     * @return mixed
     */
    public function getTriggerData($triggerConfig): mixed
    {
        if (!isset($this->api->req->s['token'])) {
            throw new \Exception(
                message: 'Missing token',
                code: HttpStatus::$InternalServerError
            );
        }

        $http = [];

        $isAssoc = (!isset($triggerConfig[0])) ? true : false;
        if (
            !$isAssoc
            && isset($triggerConfig[0])
            && count(value: $triggerConfig) === 1
        ) {
            $triggerConfig = $triggerConfig[0];
            $isAssoc = true;
        }

        $triggerOutput = [];
        if ($isAssoc) {
            $http = $this->getTriggerHttp($triggerConfig);
            [$responseheaders, $responseContent, $responseCode] = Start::http(http: $http);
            $triggerOutput = &$responseContent;
        } else {
            for (
                $iTrigger = 0, $iTriggerCount = count($triggerConfig);
                $iTrigger < $iTriggerCount;
                $iTrigger++
            ) {
                $http = $this->getTriggerHttp($triggerConfig[$iTrigger]);
                [$responseheaders, $responseContent, $responseCode] = Start::http(http: $http);
                $triggerOutput[] = &$responseContent;
            }
        }

        return $triggerOutput;
    }

    /**
     * Get Trigger Details
     *
     * @param array $triggerConfig Trigger Config
     *
     * @return mixed
     */
    public function getTriggerHttp($triggerConfig)
    {
        $method = $triggerConfig['__METHOD__'];
        [$routeElementsArr, $errors] = $this->getTriggerParams(
            payloadConfig: $triggerConfig['__ROUTE__']
        );

        if ($errors) {
            return $errors;
        }

        $route = '/' . implode(separator: '/', array: $routeElementsArr);

        $queryStringArr = [];
        $payloadArr = [];

        if (isset($triggerConfig['__QUERY-STRING__'])) {
            [$queryStringArr, $errors] = $this->getTriggerParams(
                payloadConfig: $triggerConfig['__QUERY-STRING__']
            );

            if ($errors) {
                return $errors;
            }
        }
        if (isset($triggerConfig['__PAYLOAD__'])) {
            [$payloadArr, $errors] = $this->getTriggerParams(
                payloadConfig: $triggerConfig['__PAYLOAD__']
            );
            if ($errors) {
                return $errors;
            }
        }

        $http['server']['host'] = $this->api->http['server']['host'];
        $http['server']['method'] = $method;
        $http['server']['ip'] = $this->api->http['server']['ip'];
        $http['header'] = $this->api->http['header'];
        $http['post'] = json_encode($payloadArr);
        $http['get'] = $queryStringArr;
        $http['get'][ROUTE_URL_PARAM] = $route;
        $http['isWebRequest'] = false;

        return $http;
    }

    /**
     * Generates Params for statement to execute
     *
     * @param array $payloadConfig API Payload configuration
     *
     * @return array
     * @throws \Exception
     */
    private function getTriggerParams(&$payloadConfig): array
    {
        $sqlParams = [];
        $errors = [];

        // Collect param values as per config respectively
        foreach ($payloadConfig as &$config) {
            $var = $config['column'] ?? null;

            $fetchFrom = $config['fetchFrom'];
            $fKey = $config['fetchFromValue'];
            if ($fetchFrom === 'function') {
                $function = $fKey;
                $value = $function($this->api->req->s);
                if ($var === null) {
                    $sqlParams[] = $value;
                } else {
                    $sqlParams[$var] = $value;
                }
                continue;
            } elseif (
                in_array(
                    needle: $fetchFrom,
                    haystack: ['sqlResults', 'sqlParams', 'sqlPayload']
                )
            ) {
                $fetchFromKeys = explode(separator: ':', string: $fKey);
                $value = $this->api->req->s[$fetchFrom];
                foreach ($fetchFromKeys as $key) {
                    if (!isset($value[$key])) {
                        throw new \Exception(
                            message: 'Invalid hierarchy:  Missing hierarchy data',
                            code: HttpStatus::$InternalServerError
                        );
                    }
                    $value = $value[$key];
                }
                if ($var === null) {
                    $sqlParams[] = $value;
                } else {
                    $sqlParams[$var] = $value;
                }
                continue;
            } elseif ($fetchFrom === 'custom') {
                $value = $fKey;
                if ($var === null) {
                    $sqlParams[] = $value;
                } else {
                    $sqlParams[$var] = $value;
                }
                continue;
            } elseif (isset($this->api->req->s[$fetchFrom][$fKey])) {
                $value = $this->api->req->s[$fetchFrom][$fKey];
                if ($var === null) {
                    $sqlParams[] = $value;
                } else {
                    $sqlParams[$var] = $value;
                }
                continue;
            } else {
                $errors[] = "Invalid configuration of '{$fetchFrom}' for '{$fKey}'";
                continue;
            }
        }

        return [$sqlParams, $errors];
    }

    /**
     * Process import function of configuration
     *
     * @param array $wSqlConfig   Config from file
     * @param bool  $useHierarchy Use results in where clause of sub queries
     *
     * @return string
     */
    private function processImportConfig(&$wSqlConfig, $useHierarchy): string
    {
        $configParams = $this->getConfigParams(
            sqlConfig: $wSqlConfig,
            isFirstCall: true,
            flag: $useHierarchy
        );
        $params = $this->genCsvHelper(
            header: 'CSV',
            configParams: $configParams
        );

        $header = [];
        $header[] = '__mode__';
        foreach ($params as $r => $p) {
            if (is_array($p)) {
                for ($i = 0, $iCount = count($p); $i < $iCount; $i++) {
                    $header[] = $p[$i];
                }
            } else {
                $header[] = $p;
            }
        }
        $csv = '"' . implode(separator: '","', array: $header) . '"' . PHP_EOL;
        $blankStr = '';
        foreach ($params as $r => $p) {
            if ($r === 'CSV') {
                for ($i = 1, $iCount = count($header); $i < $iCount; $i++) {
                    $blankStr = ',""';
                }
            }
            $csv .= "{$r}{$blankStr}" . PHP_EOL;
        }

        return $csv;
    }

    /**
     * Generate sample CSV helper
     *
     * @param string $header
     * @param array  $configParams
     *
     * @return array
     */
    private function genCsvHelper($header, $configParams): array
    {
        $fields = [];
        foreach ($configParams as $key => $value) {
            if (isset($value['dataType'])) {
                $fields[$header][] = "{$header}:{$key}";
            } else {
                $returnHeader = $this->genCsvHelper("{$header}:{$key}", $value);
                foreach ($returnHeader as $k => $v) {
                    $fields[$k] = $v;
                }
            }
        }

        return $fields;
    }
}
