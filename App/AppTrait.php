<?php
namespace Microservices\App;

use Microservices\App\Constants;
use Microservices\App\Common;
use Microservices\App\Env;
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
     */
    private function getRequired(&$sqlConfig, $first, $useHierarchy)
    {
        $requiredFields = [];
        $requiredFields['__required__'] = [];

        // Get Required
        if (isset($sqlConfig['__CONFIG__'])) {
            foreach ($sqlConfig['__CONFIG__'] as $config) {
                $required = false;
                $count = count($config);
                switch ($count) {
                    case 3:
                        list($type, $typeKey, $required) = $config;
                        break;
                    case 2: 
                        list($type, $typeKey) = $config;
                        break;
                }
                if ($required && $type === 'payload') {
                    if (!in_array($typeKey, $requiredFields['__required__'])) {
                        $requiredFields['__required__'][] = $typeKey;
                    }
                }
            }
        }

        // Check for hierarchy setting
        $foundHierarchy = false;
        if (isset($sqlConfig['__WHERE__'])) {
            foreach ($sqlConfig['__WHERE__'] as $var => $where) {
                list($type, $typeKey) = $where;
                if ($first && $type === 'hierarchyData') {
                    throw new \Exception('Invalid config: First query can not have hierarchyData config', 501);
                }
                if ($type === 'hierarchyData') {
                    $foundHierarchy = true;
                    break;
                }
            }
            if (!$first && $useHierarchy && !$foundHierarchy) {
                throw new \Exception('Invalid config: missing hierarchyData', 501);
            }
        }

        // Check in subQuery
        if (isset($sqlConfig['subQuery'])) {
            if (!$this->isAssoc($sqlConfig['subQuery'])) {
                throw new \Exception('Invalid Configuration: subQuery should be an associative array', 501);
                return;
            }
            foreach ($sqlConfig['subQuery'] as $module => &$sqlDetails) {
                $_useHierarchy = ($useHierarchy) ?? $this->getUseHierarchy($sqlDetails);
                $sub_requiredFields = $this->getRequired($sqlDetails, false, $_useHierarchy);
                if ($_useHierarchy) {
                    $requiredFields[$module] = $sub_requiredFields;
                } else {
                    foreach ($sub_requiredFields as $field) {
                        if (!in_array($field, $requiredFields)) {
                            $requiredFields['__required__'][] = $field;
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

        return $this->validator->validate($this->c->httpRequest->input, $validationConfig);
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
     */
    private function getSqlParams(&$sqlConfig)
    {
        $sqlParams = [];
        $errors = [];

        // Collect param values as per config respectively
        foreach ($sqlConfig as $var => [$type, $typeKey]) {
            if ($type === 'function') {
                $sqlParams[$var] = $typeKey($this->c->httpRequest->input);
            } else if ($type === 'hierarchyData') {
                $typeKeys = explode(':',$typeKey);
                $value = $this->c->httpRequest->input['hierarchyData'];
                foreach($typeKeys as $key) {
                    if (!isset($value[$key])) {
                        throw new \Exception('Invalid hierarchy:  Missing hierarchy data', 501);
                    }
                    $value = $value[$key];
                }
                $sqlParams[$var] = $value;
            } else if ($type === 'custom') {
                $sqlParams[$var] = $typeKey;
            } else if ($type === 'payload' && isset($this->c->httpRequest->input['currentPayload'][$typeKey])) {
                $sqlParams[$var] = $this->c->httpRequest->input['currentPayload'][$typeKey];
            } else if ($type === 'payload' && !in_array($typeKey, $this->c->httpRequest->input['currentRequired']) && !isset($this->c->httpRequest->input['currentPayload'][$typeKey])) {
                continue;
            } else if ($type === 'payload' && in_array($typeKey, $this->c->httpRequest->input['currentRequired']) && !isset($this->c->httpRequest->input['currentPayload'][$typeKey])) {
                $errors[] = "Missing required field of '{$type}' for '{$typeKey}'";
            } else {
                if (!isset($this->c->httpRequest->input[$type][$typeKey])) {
                    $errors[] = "Invalid configuration of '{$type}' for '{$typeKey}'";
                }
                $sqlParams[$var] = $this->c->httpRequest->input[$type][$typeKey];
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
     */
    private function getConfigParams(&$sqlConfig, $first, $useHierarchy)
    {
        $result = [];

        // Get required and optional params for a route
        if (isset($sqlConfig['__CONFIG__'])) {
            foreach ($sqlConfig['__CONFIG__'] as $config) {
                $required = false;
                $count = count($config);
                switch ($count) {
                    case 3:
                        list($type, $typeKey, $required) = $config;
                        break;
                    case 2: 
                        list($type, $typeKey) = $config;
                        break;
                }
                if ($type === 'payload') {
                    if ($required && !isset($result[$typeKey])) {
                        $result[$typeKey] = 'Required';
                        continue;
                    }
                    if (!isset($result[$typeKey])) {
                        $result[$typeKey] = 'Optional';
                        continue;
                    }
                }
            }
        }

        // Check for hierarchy
        $foundHierarchy = false;
        if (isset($sqlConfig['__WHERE__'])) {
            foreach ($sqlConfig['__WHERE__'] as $var => $payload) {
                list($type, $typeKey) = $payload;
                if ($type === 'hierarchyData') {
                    $foundHierarchy = true;
                    break;
                }
            }
            if (!$first && $useHierarchy && !$foundHierarchy) {
                throw new \Exception('Invalid config: missing hierarchyData', 501);
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
                    foreach ($sub_requiredFields as $field => $required) {
                        if (!isset($result[$field])) {
                            $result[$field] = $required;
                        }    
                    }
                }
            }
        }

        return $result;
    }
}