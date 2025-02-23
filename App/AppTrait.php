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
     * Function to help execute PHP functions enclosed with double quotes.
     *
     * @param $param Returned values by PHP inbuilt functions.
     */
    function execPhpFunc($param) { return $param;}

    /**
     * Sets required payload.
     *
     * @param array   $sqlConfig    Config from file
     * @param boolean $first        true to represent the first call in recursion.
     * @param boolean $useHierarchy Use results in where clause of sub queries recursively.
     * @return void
     * @throws \Exception
     */
    private function getRequired(&$sqlConfig, $first, $useHierarchy)
    {
        $requiredFields = [];

        // Get Required
        if (isset($sqlConfig['__CONFIG__'])) {
            foreach ($sqlConfig['__CONFIG__'] as $config) {
                $require = false;
                $dataTypeDetails = DatabaseDataTypes::$Default;
                $count = count($config);
                switch ($count) {
                    case 4:
                        list($dataPaylaodType, $dataPaylaodTypeKey, $dataTypeDetails, $require) = $config;
                        break;
                    case 3:
                        list($dataPaylaodType, $dataPaylaodTypeKey, $dataTypeDetails) = $config;
                        break;
                    case 2:
                        list($dataPaylaodType, $dataPaylaodTypeKey) = $config;
                        break;
                }
                if (!isset($requiredFields[$dataPaylaodType][$dataPaylaodTypeKey])) {
                    $dataTypeDetails['dataKey'] = $dataPaylaodTypeKey;
                    $dataTypeDetails['require'] = $require;
                    $requiredFields[$dataPaylaodType][$dataPaylaodTypeKey] = $dataTypeDetails;
                }
            }
            
            return $requiredFields;
        }

        foreach (['__SET__', '__WHERE__'] as $options) {
            if (isset($sqlConfig[$options])) {
                foreach ($sqlConfig[$options] as $config) {
                    $require = false;
                    $dataTypeDetails = DatabaseDataTypes::$Default;
                    $count = count($config);
                    switch ($count) {
                        case 4:
                            list($dataPaylaodType, $dataPaylaodTypeKey, $dataTypeDetails, $require) = $config;
                            break;
                        case 3:
                            list($dataPaylaodType, $dataPaylaodTypeKey, $dataTypeDetails) = $config;
                            break;
                        case 2:
                            list($dataPaylaodType, $dataPaylaodTypeKey) = $config;
                            break;
                    }
                    if (!isset($requiredFields[$dataPaylaodType][$dataPaylaodTypeKey])) {
                        $dataTypeDetails['dataKey'] = $dataPaylaodTypeKey;
                        $dataTypeDetails['require'] = $require;
                        $requiredFields[$dataPaylaodType][$dataPaylaodTypeKey] = $dataTypeDetails;
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
                if ($first && $dataPaylaodType === 'hierarchyData') {
                    throw new \Exception('Invalid config: First query can not have hierarchyData config', HttpStatus::$InternalServerError);
                }
                if ($dataPaylaodType === 'hierarchyData') {
                    $foundHierarchy = true;
                    break;
                }
            }
            if (!$first && $useHierarchy && !$foundHierarchy) {
                throw new \Exception('Invalid config: missing hierarchyData', HttpStatus::$InternalServerError);
            }
        }

        // Check in subQuery
        if (isset($sqlConfig['subQuery'])) {
            if (!$this->isAssoc($sqlConfig['subQuery'])) {
                throw new \Exception('Invalid Configuration: subQuery should be an associative array', HttpStatus::$InternalServerError);
                return;
            }
            foreach ($sqlConfig['subQuery'] as $module => &$sqlDetails) {
                $_useHierarchy = ($useHierarchy) ?? $this->getUseHierarchy($sqlDetails);
                $sub_requiredFields = $this->getRequired($sqlDetails, false, $_useHierarchy);
                if ($_useHierarchy) {
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
     * @param array $validationConfig Validation config from Config file.
     * @return array
     */
    private function validate(&$validationConfig)
    {
        if (is_null($this->validator)) {
            $this->validator = new Validator($this->c);
        }

        return $this->validator->validate($this->c->httpRequest->session, $validationConfig);
    }

    /**
     * Returns Query and Params for execution.
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
     * Generates Params for statement to execute.
     *
     * @param array $sqlConfig    Config from file
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
            } else if ($dataPaylaodType === 'hierarchyData') {
                $dataPaylaodTypeKeys = explode(':',$dataPaylaodTypeKey);
                $value = $this->c->httpRequest->session['hierarchyData'];
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
                $sqlParams[$var] = $this->getDataBasedOnDataType(
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
     * @param array $sqlConfig Config from file
     * @return boolean
     */
    private function getUseHierarchy(&$sqlConfig)
    {
        $useHierarchy = false;
        if (isset($sqlConfig['useHierarchy']) && $sqlConfig['useHierarchy'] === true) {
            $useHierarchy = true;
        }
        return $useHierarchy;
    }

    /**
     * Return config par recursively
     *
     * @param array   $sqlConfig    Config from file
     * @param array   $first        Flag to check if this is first request in a recursive call
     * @param boolean $useHierarchy Use results in where clause of sub queries recursively.
     * @return array
     * @throws \Exception
     */
    private function getConfigParams(&$sqlConfig, $first, $useHierarchy)
    {
        $result = [];

        if (isset($sqlConfig['countQuery'])) {
            $sqlConfig['__CONFIG__'][] = ['payload', 'page', 'int', Constants::$REQUIRED];
            $sqlConfig['__CONFIG__'][] = ['payload', 'perpage', 'int'];
        }
        // Get required and optional params for a route
        if (isset($sqlConfig['__CONFIG__'])) {
            foreach ($sqlConfig['__CONFIG__'] as $config) {
                $require = false;
                $dataTypeDetails = DatabaseDataTypes::$Default;
                $count = count($config);
                switch ($count) {
                    case 4:
                        list($dataPaylaodType, $dataPaylaodTypeKey, $dataTypeDetails, $require) = $config;
                        break;
                    case 3:
                        list($dataPaylaodType, $dataPaylaodTypeKey, $dataTypeDetails) = $config;
                        break;
                    case 2:
                        list($dataPaylaodType, $dataPaylaodTypeKey) = $config;
                        break;
                }
                if (!in_array($dataPaylaodType, ['payload'])) continue;
                if (isset($result[$dataPaylaodTypeKey]) && $result[$dataPaylaodTypeKey]['dataMode'] === 'Required') {
                    continue;
                }
                $dataTypeDetails['dataMode'] = $require ? 'Required' : 'Optional';
                $result[$dataPaylaodTypeKey] = $dataTypeDetails;
            }
        }

        if (isset($sqlConfig['__SET__'])) {
            foreach ($sqlConfig['__SET__'] as $config) {
                $require = false;
                $dataTypeDetails = DatabaseDataTypes::$Default;
                $count = count($config);
                switch ($count) {
                    case 4:
                        list($dataPaylaodType, $dataPaylaodTypeKey, $dataTypeDetails, $require) = $config;
                        break;
                    case 3:
                        list($dataPaylaodType, $dataPaylaodTypeKey, $dataTypeDetails) = $config;
                        break;
                    case 2:
                        list($dataPaylaodType, $dataPaylaodTypeKey) = $config;
                        break;
                }
                if (!in_array($dataPaylaodType, ['payload'])) continue;
                if (isset($result[$dataPaylaodTypeKey]) && $result[$dataPaylaodTypeKey]['dataMode'] === 'Required') {
                    continue;
                }
                $dataTypeDetails['dataMode'] = $require ? 'Required' : 'Optional';
                $result[$dataPaylaodTypeKey] = $dataTypeDetails;
            }
        }

        // Check for hierarchy
        $foundHierarchy = false;
        if (isset($sqlConfig['__WHERE__'])) {
            foreach ($sqlConfig['__WHERE__'] as $var => $payload) {
                list($dataPaylaodType, $dataPaylaodTypeKey) = $payload;
                if ($dataPaylaodType === 'hierarchyData') {
                    $foundHierarchy = true;
                    break;
                }
            }
            if (!$first && $useHierarchy && !$foundHierarchy) {
                throw new \Exception('Invalid config: missing hierarchyData', HttpStatus::$InternalServerError);
            }
        }

        // Check in subQuery
        if (isset($sqlConfig['subQuery'])) {
            foreach ($sqlConfig['subQuery'] as $module => &$_sqlConfig) {
                $_useHierarchy = ($useHierarchy) ?? $this->getUseHierarchy($_sqlConfig);
                $sub_requiredFields = $this->getConfigParams($_sqlConfig, false, $_useHierarchy);
                if ($useHierarchy) {
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
     * Return data based on data-type
     *
     * @param string|array $data
     * @param string       $dataTypeDetails
     * @return mixed
     * @throws \Exception
     */
    private function getDataBasedOnDataType($data, $dataTypeDetails)
    {
        switch ($dataTypeDetails['dataType']) {
            case 'null':
                $data = null;
                break;
            case 'bool':
                $data = (bool)$data;
                break;
            case 'int':
                $data = (int)$data;
                break;
            case 'float':
                $data = (float)$data;
                break;
            case 'string':
                $data = (string)$data;
                break;
            case 'json':
                $data = (string)json_encode($data);
                break;
            default:
                throw new \Exception('Invalid Data-type:'.$dataTypeDetails['dataType'], HttpStatus::$InternalServerError);
        }

        $returnFlag = true;
        if ($returnFlag && isset($dataTypeDetails['minValue']) && $dataTypeDetails['minValue'] <= $data) {
            $returnFlag = false;
        }
        if ($returnFlag && isset($dataTypeDetails['maxValue']) && $data <= $dataTypeDetails['maxValue']) {
            $returnFlag = false;
        }
        if ($returnFlag && isset($dataTypeDetails['minLength']) && $dataTypeDetails['minLength'] <= strlen($data)) {
            $returnFlag = false;
        }
        if ($returnFlag && isset($dataTypeDetails['maxLength']) && strlen($data) <= $dataTypeDetails['maxLength']) {
            $returnFlag = false;
        }
        if ($returnFlag && isset($dataTypeDetails['enumValues']) && in_array($data, $dataTypeDetails['enumValues'])) {
            $returnFlag = false;
        }
        if ($returnFlag && isset($dataTypeDetails['setValues']) && empty(array_diff($data, $dataTypeDetails['setValues']))) {
            $returnFlag = false;
        }
        if ($returnFlag && isset($dataTypeDetails['regex']) && preg_match($dataTypeDetails['regex'], $data) === 0) {
            $returnFlag = false;
        }

        if (!$returnFlag) {
            throw new \Exception('Invalid data based on Data-type details', HttpStatus::$BadRequest);
        }

        return $data;
    }
}
