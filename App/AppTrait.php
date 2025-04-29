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
     * @param boolean $flag        useHierarchy/useResultSet flag
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
                            list($dataPaylaodType, $dataPaylaodTypeKey, $dataType, $require) = $config;
                            break;
                        case 3:
                            list($dataPaylaodType, $dataPaylaodTypeKey, $dataType) = $config;
                            break;
                        case 2:
                            list($dataPaylaodType, $dataPaylaodTypeKey) = $config;
                            break;
                    }
                    if (!isset($requiredFields[$dataPaylaodType][$dataPaylaodTypeKey])) {
                        $dataType['dataKey'] = $dataPaylaodTypeKey;
                        $dataType['require'] = $require;
                        $requiredFields[$dataPaylaodType][$dataPaylaodTypeKey] = $dataType;
                    }
                }
            }
        }

        // Check for hierarchy setting
        $foundHierarchy = false;
        if (isset($sqlConfig['__WHERE__'])) {
            foreach ($sqlConfig['__WHERE__'] as $var => $where) {
                $dataPaylaodType = $where[0];
                $dataPaylaodTypeKey = $where[1];
                if ($isFirstCall && $dataPaylaodType === 'resultSetData') {
                    throw new \Exception('Invalid config: First query can not have resultSetData config', HttpStatus::$InternalServerError);
                }
                if ($dataPaylaodType === 'resultSetData') {
                    $foundHierarchy = true;
                    break;
                }
            }
            if (!$isFirstCall && $flag && !$foundHierarchy) {
                throw new \Exception('Invalid config: missing resultSetData', HttpStatus::$InternalServerError);
            }
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
                    foreach ($sub_requiredFields as $dataPaylaodType => &$fields) {
                        if (!isset($requiredFields[$dataPaylaodType])) {
                            $requiredFields[$dataPaylaodType] = [];
                        }
                        foreach ($fields as $dataPaylaodTypeKey => $field) {
                            if (!isset($requiredFields[$dataPaylaodType][$dataPaylaodTypeKey])) {
                                $requiredFields[$dataPaylaodType][$dataPaylaodTypeKey] = $field;
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
     * @param array $sqlDetails   Config from file
     * @return array
     */
    private function getSqlAndParams(&$sqlDetails)
    {
        $sql = $sqlDetails['query'];
        $sqlParams = [];
        $paramKeys = [];
        $errors = [];

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
                        $wparam = str_replace(['`', ' '], '', $param);
                        while (in_array($wparam, $paramKeys)) {
                            $wparam .= '0';
                        }
                        $paramKeys[] = $wparam;
                        if ($wfound) {
                            $__WHERE__[] = "`{$param}` = :{$wparam}";
                        }
                        $sqlParams[":{$wparam}"] = $v;
                    }
                    if ($wfound) {
                        $sql = str_replace('__WHERE__', implode(' AND ', $__WHERE__), $sql);
                    }
                }
            } else {
                $errors = array_merge($errors, $werrors);
            }
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
        foreach ($sqlConfig as $var => [$dataPaylaodType, $dataPaylaodTypeKey]) {
            if ($dataPaylaodType === 'function') {
                $function = $dataPaylaodTypeKey;
                $value = $function($this->c->httpRequest->session);
                $sqlParams[$var] = $value;
                continue;
            } else if ($dataPaylaodType === 'resultSetData') {
                $dataPaylaodTypeKeys = explode(':',$dataPaylaodTypeKey);
                $value = $this->c->httpRequest->session['resultSetData'];
                foreach($dataPaylaodTypeKeys as $key) {
                    if (!isset($value[$key])) {
                        throw new \Exception('Invalid hierarchy:  Missing hierarchy data', HttpStatus::$InternalServerError);
                    }
                    $value = $value[$key];
                }
                $sqlParams[$var] = $value;
                continue;
            } else if ($dataPaylaodType === 'custom') {
                $value = $dataPaylaodTypeKey;
                $sqlParams[$var] = $value;
                continue;
            } else if (isset($this->c->httpRequest->session[$dataPaylaodType][$dataPaylaodTypeKey])) {
                $sqlParams[$var] = DatabaseDataTypes::validateDataType(
                    $this->c->httpRequest->session[$dataPaylaodType][$dataPaylaodTypeKey],
                    $this->c->httpRequest->session['required'][$dataPaylaodType][$dataPaylaodTypeKey]
                );
                continue;
            } else if ($this->c->httpRequest->session['required'][$dataPaylaodType][$dataPaylaodTypeKey]['require']) {
                $errors[] = "Missing required field of '{$dataPaylaodType}' for '{$dataPaylaodTypeKey}'";
                continue;
            } else {
                $errors[] = "Invalid configuration of '{$dataPaylaodType}' for '{$dataPaylaodTypeKey}'";
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
                        list($dataPaylaodType, $dataPaylaodTypeKey, $dataType, $require) = $config;
                        break;
                    case 3:
                        list($dataPaylaodType, $dataPaylaodTypeKey, $dataType) = $config;
                        break;
                    case 2:
                        list($dataPaylaodType, $dataPaylaodTypeKey) = $config;
                        break;
                }
                if (!in_array($dataPaylaodType, ['payload'])) continue;
                if (isset($result[$dataPaylaodTypeKey]) && $result[$dataPaylaodTypeKey]['dataMode'] === 'Required') {
                    continue;
                }
                $dataType['dataMode'] = $require ? 'Required' : 'Optional';
                $result[$dataPaylaodTypeKey] = $dataType;
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
                            list($dataPaylaodType, $dataPaylaodTypeKey, $dataType, $require) = $config;
                            break;
                        case 3:
                            list($dataPaylaodType, $dataPaylaodTypeKey, $dataType) = $config;
                            break;
                        case 2:
                            list($dataPaylaodType, $dataPaylaodTypeKey) = $config;
                            break;
                    }
                    if (!in_array($dataPaylaodType, ['payload'])) continue;
                    if (isset($result[$dataPaylaodTypeKey]) && $result[$dataPaylaodTypeKey]['dataMode'] === 'Required') {
                        continue;
                    }
                    $dataType['dataMode'] = $require ? 'Required' : 'Optional';
                    $result[$dataPaylaodTypeKey] = $dataType;
                }
            }
        }

        // Check for hierarchy
        $foundHierarchy = false;
        if (isset($sqlConfig['__WHERE__'])) {
            foreach ($sqlConfig['__WHERE__'] as $var => $payload) {
                $dataPaylaodType = $payload[0];
                $dataPaylaodTypeKey = $payload[1];
                if ($dataPaylaodType === 'resultSetData') {
                    $foundHierarchy = true;
                    break;
                }
            }
            if (!$isFirstCall && $flag && !$foundHierarchy) {
                throw new \Exception('Invalid config: missing resultSetData', HttpStatus::$InternalServerError);
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
                    foreach ($sub_requiredFields as $dataPaylaodTypeKey => $field) {
                        if (!isset($result[$dataPaylaodTypeKey])) {
                            $result[$dataPaylaodTypeKey] = $field;
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
     * @param array   $keys Module Keys in recursion
     * @param array   $row  Row data fetched from DB
     * @param boolean $flag useHierarchy/useResultSet flag
     * @return void
     */
    private function resetFetchData($keys, $row, $flag)
    {
        if ($flag) {
            if (count($keys) === 0) {
                $this->c->httpRequest->session['resultSetData'] = [];
                $this->c->httpRequest->session['resultSetData']['return'] = [];
            }
            $httpReq = &$this->c->httpRequest->session['resultSetData']['return'];
            foreach ($keys as $k) {
                if (!isset($httpReq[$k])) {
                    $httpReq[$k] = [];
                }
                $httpReq = &$httpReq[$k];
            }
            $httpReq = $row;
        }
    }
}
