<?php
namespace Microservices\App;

use Microservices\App\Constants;
use Microservices\App\Common;
use Microservices\App\DatabaseDataTypes;
use Microservices\App\Env;
use Microservices\App\HttpStatus;
use Microservices\App\Validator;

/**
 * Trait for PHP functions
 *
 * This trait constains only one function so that one can execute inbuilt PHP
 * functions in strings enclosed with double quotes
 *
 * @category   PHP Trait
 * @package    Microservices
 * @author     Ramesh Narayan Jangid
 * @copyright  Ramesh Narayan Jangid
 * @version    Release: @1.0.0@
 * @since      Class available since Release 1.0.0
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
     * @return mixed
     */
    function execPhpFunc($param) { return $param;}

    /**
     * Sets required payload
     *
     * @param array   $sqlConfig   Config from file
     * @param boolean $isFirstCall true to represent the first call in recursion
     * @param boolean $flag        useHierarchy / useResultSet flag
     * @return void
     * @throws \Exception
     */
    private function getRequired(&$sqlConfig, $isFirstCall, $flag)
    {
        $requiredFields = [];

        foreach (['__SET__', '__WHERE__'] as $options) {
            if (isset($sqlConfig[$options])) {
                foreach ($sqlConfig[$options] as $config) {
                    $require = false;
                    $dataType = DatabaseDataTypes::$Default;
                    $count = count($config);
                    switch ($count) {
                        case 4:
                            list($dataPayloadType, $dataPayloadTypeKey, $dataType, $require) = $config;
                            break;
                        case 3:
                            list($dataPayloadType, $dataPayloadTypeKey, $dataType) = $config;
                            break;
                        case 2:
                            list($dataPayloadType, $dataPayloadTypeKey) = $config;
                            break;
                    }
                    if ($dataPayloadType === 'function') {
                        continue;
                    }
                    if (!isset($requiredFields[$dataPayloadType][$dataPayloadTypeKey])) {
                        $dataType['dataKey'] = $dataPayloadTypeKey;
                        $dataType['require'] = $require;
                        $requiredFields[$dataPayloadType][$dataPayloadTypeKey] = $dataType;
                    }
                }
            }
        }

        // Check for hierarchy setting
        $foundHierarchy = false;
        if (isset($sqlConfig['__WHERE__'])) {
            foreach ($sqlConfig['__WHERE__'] as $var => $where) {
                $dataPayloadType = $where[0];
                $dataPayloadTypeKey = $where[1];
                if ($isFirstCall && in_array($dataPayloadType, ['sqlResults', 'sqlInputs', 'sqlPayload'])) {
                    throw new \Exception('Invalid config: First query can not have ' . $dataPayloadType . ' config', HttpStatus::$InternalServerError);
                }
                if (in_array($dataPayloadType, ['sqlResults', 'sqlInputs', 'sqlPayload'])) {
                    $foundHierarchy = true;
                    break;
                }
            }
            // if (!$isFirstCall && $flag && !$foundHierarchy) {
            //     throw new \Exception('Invalid config: missing ' . $dataPayloadType, HttpStatus::$InternalServerError);
            // }
        }

        // Check in subQuery
        if (isset($sqlConfig['subQuery'])) {
            if (!$this->isAssoc($sqlConfig['subQuery'])) {
                throw new \Exception('Invalid Configuration: subQuery should be an associative array', HttpStatus::$InternalServerError);
                return;
            }
            foreach ($sqlConfig['subQuery'] as $module => &$sqlDetails) {
                $_flag = ($flag) ?? $this->getUseHierarchy($sqlDetails);
                $sub_requiredFields = $this->getRequired($sqlDetails, $isGetRequiredFirstCall = false, $_flag);
                if ($_flag) {
                    $requiredFields[$module] = $sub_requiredFields;
                } else {
                    foreach ($sub_requiredFields as $dataPayloadType => &$fields) {
                        if (!isset($requiredFields[$dataPayloadType])) {
                            $requiredFields[$dataPayloadType] = [];
                        }
                        foreach ($fields as $dataPayloadTypeKey => $field) {
                            if (!isset($requiredFields[$dataPayloadType][$dataPayloadTypeKey])) {
                                $requiredFields[$dataPayloadType][$dataPayloadTypeKey] = $field;
                            }
                        }
                    }
                }
            }
        }

        return $requiredFields;
    }

    /**
     * Validate payload
     *
     * @param array $validationConfig Validation config from Config file
     * @return array
     */
    private function validate(&$validationConfig)
    {
        if (is_null($this->validator)) {
            $this->validator = new Validator($this->c);
        }

        return $this->validator->validate($validationConfig);
    }

    /**
     * Returns Query and Params for execution
     *
     * @param array        $sqlDetails  Config from file
     * @param boolean|null $isFirstCall true to represent the first call in recursion
     * @param array|null   $configKeys  Config Keys
     * @param boolean|null $flag        useHierarchy / useResultSet flag
     * @return array
     */
    private function getSqlAndParams(&$sqlDetails, $isFirstCall = null, $configKeys = null, $flag = null)
    {
        $sql = $sqlDetails['query'];
        $sqlParams = [];
        $paramKeys = [];
        $errors = [];
        $row = [];

        // Check __SET__
        if (isset($sqlDetails['__SET__']) && count($sqlDetails['__SET__']) !== 0) {
            list($params, $errors) = $this->getSqlParams($sqlDetails['__SET__']);
            if (empty($errors)) {
                if (!empty($params)) {
                    // __SET__ not compulsary in query
                    $found = strpos($sql, '__SET__') !== false;
                    $__SET__ = [];
                    foreach ($params as $param => &$v) {
                        $param = str_replace(['`', ' '], '', $param);
                        $paramKeys[] = $param;
                        if ($found) {
                            $__SET__[] = "`{$param}` = :{$param}";
                        }
                        $sqlParams[":{$param}"] = $v;
                        $row[$param] = $v;
                    }
                    if ($found) {
                        $sql = str_replace('__SET__', implode(', ', $__SET__), $sql);
                    }
                }
            }
        }

        // Check __WHERE__
        if (empty($errors) && isset($sqlDetails['__WHERE__']) && count($sqlDetails['__WHERE__']) !== 0) {
            list($sqlWhereParams, $werrors) = $this->getSqlParams($sqlDetails['__WHERE__']);
            if (empty($werrors)) {
                if(!empty($sqlWhereParams)) {
                    // __WHERE__ not compulsary in query
                    $wfound = strpos($sql, '__WHERE__') !== false;
                    $__WHERE__ = [];
                    foreach ($sqlWhereParams as $param => &$v) {
                        $wparam = $param = str_replace(['`', ' '], '', $param);
                        $i = 0;
                        while (in_array($wparam, $paramKeys)) {
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
                        $sql = str_replace('__WHERE__', implode(' AND ', $__WHERE__), $sql);
                    }
                }
            } else {
                $errors = array_merge($errors, $werrors);
            }
        }

        if (!empty($row)) {
            $this->resetFetchData($dataPayloadType = 'sqlInputs', $configKeys, $row);
        }

        return [$sql, $sqlParams, $errors];
    }

    /**
     * Generates Params for statement to execute
     *
     * @param array $sqlConfig Config from file
     * @return array
     * @throws \Exception
     */
    private function getSqlParams(&$sqlConfig)
    {
        $sqlParams = [];
        $errors = [];

        // Collect param values as per config respectively
        foreach ($sqlConfig as $var => [$dataPayloadType, $dataPayloadTypeKey]) {
            if ($dataPayloadType === 'function') {
                $function = $dataPayloadTypeKey;
                $value = $function($this->c->httpRequest->session);
                $sqlParams[$var] = $value;
                continue;
            } else if (in_array($dataPayloadType, ['sqlResults', 'sqlInputs', 'sqlPayload'])) {
                $dataPayloadTypeKeys = explode(':',$dataPayloadTypeKey);
                $value = $this->c->httpRequest->session[$dataPayloadType];
                foreach($dataPayloadTypeKeys as $key) {
                    if (!isset($value[$key])) {
                        throw new \Exception('Invalid hierarchy:  Missing hierarchy data', HttpStatus::$InternalServerError);
                    }
                    $value = $value[$key];
                }
                $sqlParams[$var] = $value;
                continue;
            } else if ($dataPayloadType === 'custom') {
                $value = $dataPayloadTypeKey;
                $sqlParams[$var] = $value;
                continue;
            } else if (isset($this->c->httpRequest->session[$dataPayloadType][$dataPayloadTypeKey])) {
                $sqlParams[$var] = DatabaseDataTypes::validateDataType(
                    $this->c->httpRequest->session[$dataPayloadType][$dataPayloadTypeKey],
                    $this->c->httpRequest->session['required'][$dataPayloadType][$dataPayloadTypeKey]
                );
                continue;
            } else if ($this->c->httpRequest->session['required'][$dataPayloadType][$dataPayloadTypeKey]['require']) {
                $errors[] = "Missing required field of '{$dataPayloadType}' for '{$dataPayloadTypeKey}'";
                continue;
            } else {
                $errors[] = "Invalid configuration of '{$dataPayloadType}' for '{$dataPayloadTypeKey}'";
                continue;
            }
        }

        return [$sqlParams, $errors];
    }

    /**
     * Function to find wether privider array is associative/simple array
     *
     * @param array $arr Array to search for associative/simple array
     * @return boolean
     */
    private function isAssoc($arr)
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
     * @return boolean
     */
    private function getUseHierarchy(&$sqlConfig, $keyword)
    {
        $flag = false;
        if (isset($sqlConfig[$keyword]) && $sqlConfig[$keyword] === true) {
            $flag = true;
        }
        return $flag;
    }

    /**
     * Return config par recursively
     *
     * @param array   $sqlConfig   Config from file
     * @param array   $isFirstCall Flag to check if this is first request in a recursive call
     * @param boolean $flag        useHierarchy/useResultSet flag
     * @return array
     * @throws \Exception
     */
    private function getConfigParams(&$sqlConfig, $isFirstCall, $flag)
    {
        $result = [];

        if (isset($sqlConfig['countQuery'])) {
            $sqlConfig['__CONFIG__'][] = ['payload', 'page', 'int', Constants::$REQUIRED];
            $sqlConfig['__CONFIG__'][] = ['payload', 'perpage', 'int'];

            foreach ($sqlConfig['__CONFIG__'] as $config) {
                $require = false;
                $dataType = DatabaseDataTypes::$Default;
                $count = count($config);
                switch ($count) {
                    case 4:
                        list($dataPayloadType, $dataPayloadTypeKey, $dataType, $require) = $config;
                        break;
                    case 3:
                        list($dataPayloadType, $dataPayloadTypeKey, $dataType) = $config;
                        break;
                    case 2:
                        list($dataPayloadType, $dataPayloadTypeKey) = $config;
                        break;
                }
                if (!in_array($dataPayloadType, ['payload'])) continue;
                if (isset($result[$dataPayloadTypeKey]) && $result[$dataPayloadTypeKey]['dataMode'] === 'Required') {
                    continue;
                }
                $dataType['dataMode'] = $require ? 'Required' : 'Optional';
                $result[$dataPayloadTypeKey] = $dataType;
            }
        }

        foreach (['__SET__', '__WHERE__'] as $options) {
            if (isset($sqlConfig[$options])) {
                foreach ($sqlConfig[$options] as $config) {
                    $require = false;
                    $dataType = DatabaseDataTypes::$Default;
                    $count = count($config);
                    switch ($count) {
                        case 4:
                            list($dataPayloadType, $dataPayloadTypeKey, $dataType, $require) = $config;
                            break;
                        case 3:
                            list($dataPayloadType, $dataPayloadTypeKey, $dataType) = $config;
                            break;
                        case 2:
                            list($dataPayloadType, $dataPayloadTypeKey) = $config;
                            break;
                    }
                    if (!in_array($dataPayloadType, ['payload'])) continue;
                    if (isset($result[$dataPayloadTypeKey]) && $result[$dataPayloadTypeKey]['dataMode'] === 'Required') {
                        continue;
                    }
                    $dataType['dataMode'] = $require ? 'Required' : 'Optional';
                    $result[$dataPayloadTypeKey] = $dataType;
                }
            }
        }

        // Check for hierarchy
        $foundHierarchy = false;
        if (isset($sqlConfig['__WHERE__'])) {
            foreach ($sqlConfig['__WHERE__'] as $var => $payload) {
                $dataPayloadType = $payload[0];
                $dataPayloadTypeKey = $payload[1];
                if (in_array($dataPayloadType, ['sqlResults', 'sqlInputs', 'sqlPayload'])) {
                    $foundHierarchy = true;
                    break;
                }
            }
            if (!$isFirstCall && $flag && !$foundHierarchy) {
                throw new \Exception('Invalid config: missing ' . $dataPayloadType, HttpStatus::$InternalServerError);
            }
        }

        // Check in subQuery
        if (isset($sqlConfig['subQuery'])) {
            foreach ($sqlConfig['subQuery'] as $module => &$_sqlConfig) {
                $_flag = ($flag) ?? $this->getUseHierarchy($_sqlConfig);
                $sub_requiredFields = $this->getConfigParams($_sqlConfig, $isGetConfigParamsFirstCall = false, $_flag);
                if ($flag) {
                    if (!empty($sub_requiredFields)) {
                        $result[$module] = $sub_requiredFields;
                    }
                } else {
                    foreach ($sub_requiredFields as $dataPayloadTypeKey => $field) {
                        if (!isset($result[$dataPayloadTypeKey])) {
                            $result[$dataPayloadTypeKey] = $field;
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
     * @param string  $dataPayloadType sqlResults / sqlInputs / sqlPayload
     * @param array   $keys            Module Keys in recursion
     * @param array   $row             Row data fetched from DB
     * @return void
     */
    private function resetFetchData($dataPayloadType, $keys, $row)
    {
        if (empty($keys) || count($keys) === 0) {
            $this->c->httpRequest->session[$dataPayloadType] = [];
            $this->c->httpRequest->session[$dataPayloadType]['return'] = [];
        }
        $httpReq = &$this->c->httpRequest->session[$dataPayloadType]['return'];
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
     * @return void
     * @throws \Exception
     */
    private function rateLimitRoute(&$sqlConfig)
    {
        // 
        if (
            isset($sqlConfig['rateLimiterMaxRequests'])
            && isset($sqlConfig['rateLimiterSecondsWindow'])
        ) {
            $payloadSignature = [
                'IP' => $this->c->httpRequest->REMOTE_ADDR,
                'clientId' => $this->c->httpRequest->clientId,
                'groupId' => (!is_null($this->c->httpRequest->groupId) ? $this->c->httpRequest->groupId : 0),
                'userId' => (!is_null($this->c->httpRequest->userId) ? $this->c->httpRequest->userId : 0),
                'httpMethod' => $this->c->httpRequest->REQUEST_METHOD,
                'Route' => $this->c->httpRequest->ROUTE,
            ];
            // $hash = hash_hmac('sha256', json_encode($payloadSignature), getenv('IdempotentSecret'));
            $hash = json_encode($payloadSignature);
            $hashKey = md5($hash);
            
            // @throws \Exception
            $rateLimitChecked = $this->c->httpRequest->checkRateLimit(
                $RateLimiterRoutePrefix = getenv('RateLimiterRoutePrefix'),
                $RateLimiterMaxRequests = $sqlConfig['rateLimiterMaxRequests'],
                $RateLimiterSecondsWindow = $sqlConfig['rateLimiterSecondsWindow'],
                $key = $hashKey
            );
        }
    }

    /**
     * Check for Idempotent Window
     * 
     * @param array $sqlConfig       Config from file
     * @param array $_payloadIndexes Payload Indexes
     * @return array
     */
    private function checkIdempotent(&$sqlConfig, $_payloadIndexes)
    {
        $idempotentWindow = 0;
        $hashKey = null;
        $hashJson = null;
        if (
            isset($sqlConfig['idempotentWindow'])
            && is_numeric($sqlConfig['idempotentWindow'])
            && $sqlConfig['idempotentWindow'] > 0
        ) {
            $idempotentWindow = (int)$sqlConfig['idempotentWindow'];
            if ($idempotentWindow) {
                $payloadSignature = [
                    'IdempotentSecret' => getenv('IdempotentSecret'),
                    'idempotentWindow' => $idempotentWindow,
                    'IP' => $this->c->httpRequest->REMOTE_ADDR,
                    'clientId' => $this->c->httpRequest->clientId,
                    'groupId' => (!is_null($this->c->httpRequest->groupId) ? $this->c->httpRequest->groupId : 0),
                    'userId' => (!is_null($this->c->httpRequest->userId) ? $this->c->httpRequest->userId : 0),
                    'httpMethod' => $this->c->httpRequest->REQUEST_METHOD,
                    'Route' => $this->c->httpRequest->ROUTE,
                    'payload' => $this->c->httpRequest->jsonDecode->get(implode(':', $_payloadIndexes))
                ];
                // $hash = hash_hmac('sha256', json_encode($payloadSignature), getenv('IdempotentSecret'));
                $hash = json_encode($payloadSignature);
                $hashKey = md5($hash);
                if ($this->c->httpRequest->cache->cacheExists($hashKey)) {
                    $hashJson = str_replace('JSON', $this->c->httpRequest->cache->getCache($hashKey), '{"Idempotent": JSON, "Status": 200}');
                }
            }
        }

        return [$idempotentWindow, $hashKey, $hashJson];
    }

    /**
     * Lag Response
     *
     * @param array $sqlConfig    Config from file
     * @return void
     */
    private function lagResponse($sqlConfig)
    {
        if (
            isset($sqlConfig['responseLag'])
            && isset($sqlConfig['responseLag'])
        ) {
            $payloadSignature = [
                'IP' => $this->c->httpRequest->REMOTE_ADDR,
                'clientId' => $this->c->httpRequest->clientId,
                'groupId' => (!is_null($this->c->httpRequest->groupId) ? $this->c->httpRequest->groupId : 0),
                'userId' => (!is_null($this->c->httpRequest->userId) ? $this->c->httpRequest->userId : 0),
                'httpMethod' => $this->c->httpRequest->REQUEST_METHOD,
                'Route' => $this->c->httpRequest->ROUTE,
            ];

            // $hash = hash_hmac('sha256', json_encode($payloadSignature), getenv('IdempotentSecret'));
            $hash = json_encode($payloadSignature);
            $hashKey = 'LAG:' . md5($hash);

            // @throws \Exception
            if ($this->c->httpRequest->cache->cacheExists($hashKey)) {
                $noOfRequests = $this->c->httpRequest->cache->getCache($hashKey);
            } else {
                $noOfRequests = 0;
            }

            $this->c->httpRequest->cache->setCache($hashKey, ++$noOfRequests, $expire = 3600);

            $lag = 0;
            $responseLag = &$sqlConfig['responseLag'];
            if (is_array($responseLag)) {
                foreach ($responseLag as $start => $newLag) {
                    if ($noOfRequests > $start) {
                        $lag = $newLag;
                    }
                }
            }

            if ($lag > 0) {
                sleep($lag);
            }
        }
    }
}
