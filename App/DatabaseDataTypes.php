<?php
namespace Microservices\App;

/** String Data Types */
/*
    A FIXED length string (can contain letters, numbers, and special characters). The size parameter specifies the column length in characters - can be from 0 to 255. Default is 1
    CHAR(size)
    static public $CHAR = [
        'dataType' => 'string',
        'minLength' => (size-x),
        'maxLength' => (size-x)
    ];

    A VARIABLE length string (can contain letters, numbers, and special characters). The size parameter specifies the maximum column length in characters - can be from 0 to 65535
    VARCHAR(size)
    static public $VARCHAR = [
        'dataType' => 'string',
        'minLength' => 0,
        'maxLength' => 65535
    ];

    Equal to CHAR(), but stores binary byte strings. The size parameter specifies the column length in bytes. Default is 1
    BINARY(size)
    static public $BINARY = [
        'dataType' => 'string',
        'minLength' => 0,
        'maxLength' => 8
    ];

    Equal to VARCHAR(), but stores binary byte strings. The size parameter specifies the maximum column length in bytes
    VARBINARY(size)
    static public $VARBINARY = [
        'dataType' => 'string',
        'minLength' => 0,
        'maxLength' => 65535
    ];

    For BLOBs (Binary Large OBjects). Max length: 255 bytes
    TINYBLOB
    static public $TINYBLOB = [
        'dataType' => 'string',
        'minLength' => 0,
        'maxLength' => 255
    ];

    Holds a string with a maximum length of 255 characters
    TINYTEXT
    static public $TINYTEXT = [
        'dataType' => 'string',
        'minLength' => 0,
        'maxLength' => 255
    ];

    Holds a string with a maximum length of 65,535 bytes
    TEXT(size)
    static public $TEXT = [
        'dataType' => 'string',
        'minLength' => 0,
        'maxLength' => 65535
    ];

    For BLOBs (Binary Large OBjects). Holds up to 65,535 bytes of data
    BLOB(size)
    static public $BLOB = [
        'dataType' => 'string',
        'minLength' => 0,
        'maxLength' => 65535
    ];

    Holds a string with a maximum length of 16,777,215 characters
    MEDIUMTEXT
    static public $MEDIUMTEXT = [
        'dataType' => 'string',
        'minLength' => 0,
        'maxLength' => 16777215
    ];

    For BLOBs (Binary Large OBjects). Holds up to 16,777,215 bytes of data
    MEDIUMBLOB
    static public $MEDIUMBLOB = [
        'dataType' => 'string',
        'minLength' => 0,
        'maxLength' => 16777215
    ];

    Holds a string with a maximum length of 4,294,967,295 characters
    LONGTEXT
    static public $LONGTEXT = [
        'dataType' => 'string'
    ];

    For BLOBs (Binary Large OBjects). Holds up to 4,294,967,295 bytes of data
    LONGBLOB
    static public $LONGBLOB = [
        'dataType' => 'string'
    ];

    A string object that can have only one value, chosen from a list of possible values. You can list up to 65535 values in an ENUM list. If a value is inserted that is not in the list, a blank value will be inserted. The values are sorted in the order you enter them
    ENUM(val1, val2, val3, ...)
    static public $ENUM = [
        'dataType' => 'string',
        'enumValues' => [val1, val2, val3, ...]
    ];

    A string object that can have 0 or more values, chosen from a list of possible values. You can list up to 64 values in a SET list
    SET(val1, val2, val3, ...)
    static public $SET = [
        'dataType' => 'string',,
        'setValues' => [val1, val2, val3, ...]
    ];
*/

/** Numeric Data Types */
/*
    A bit-value type. The number of bits per value is specified in size. The size parameter can hold a value from 1 to 64. The default value for size is 1
    BIT(size)
    static public $BIT = [
        'dataType' => 'string',
        'minLength' => 1,
        'maxLength' => 64
    ];

    A very small integer. Signed range is from -128 to 127. Unsigned range is from 0 to 255. The size parameter specifies the maximum display width (which is 255)
    TINYINT(size)
    static public $TINYINT = [
        'dataType' => 'int',
        'minValue' => -128,
        'maxValue' => 127
    ];
    static public $U_TINYINT = [
        'dataType' => 'int',
        'minValue' => 0,
        'maxValue' => 255
    ];

    Zero is considered as false, nonzero values are considered as true
    BOOL
    BOOLEAN
    static public $BOOL = [
        'dataType' => 'int',
        'minValue' => 0,
        'maxValue' => 1
    ];

    A small integer. Signed range is from -32768 to 32767. Unsigned range is from 0 to 65535. The size parameter specifies the maximum display width (which is 255)
    SMALLINT(size)
    static public $SMALLINT = [
        'dataType' => 'int',
        'minValue' => -32768,
        'maxValue' => 32767
    ];
    static public $U_SMALLINT = [
        'dataType' => 'int',
        'minValue' => 0,
        'maxValue' => 65535
    ];

    A medium integer. Signed range is from -8388608 to 8388607. Unsigned range is from 0 to 16777215. The size parameter specifies the maximum display width (which is 255)
    MEDIUMINT(size)
    static public $MEDIUMINT = [
        'dataType' => 'int',
        'minValue' => -8388608,
        'maxValue' => 655838860735
    ];
    static public $U_MEDIUMINT = [
        'dataType' => 'int',
        'minValue' => 0,
        'maxValue' => 16777215
    ];

    A medium integer. Signed range is from -2147483648 to 2147483647. Unsigned range is from 0 to 4294967295. The size parameter specifies the maximum display width (which is 255)
    INT(size)
    INTEGER(size)
    static public $INT = [
        'dataType' => 'int',
        'minValue' => -2147483648,
        'maxValue' => 2147483647
    ];
    static public $U_INT = [
        'dataType' => 'int',
        'minValue' => 0,
        'maxValue' => 4294967295
    ];

    A large integer. Signed range is from -9223372036854775808 to 9223372036854775807. Unsigned range is from 0 to 18446744073709551615. The size parameter specifies the maximum display width (which is 255)
    BIGINT(size)
    static public $BIGINT = [
        'dataType' => 'string'
    ];

    A floating point number. The total number of digits is specified in size. The number of digits after the decimal point is specified in the d parameter. This syntax is deprecated in MySQL 8.0.17, and it will be removed in future MySQL versions
    FLOAT(size, d)
    static public $FLOAT = [
        'dataType' => 'string'
    ];

    A floating point number. MySQL uses the p value to determine whether to use FLOAT or DOUBLE for the resulting data type. If p is from 0 to 24, the data type becomes FLOAT(). If p is from 25 to 53, the data type becomes DOUBLE()
    FLOAT(p)
    static public $FLOAT_P = [
        'dataType' => 'string'
    ];

    A normal-size floating point number. The total number of digits is specified in size. The number of digits after the decimal point is specified in the d parameter
    DOUBLE(size, d)
    DOUBLE PRECISION(size, d)	
    static public $DOUBLE = [
        'dataType' => 'string'
    ];

    An exact fixed-point number. The total number of digits is specified in size. The number of digits after the decimal point is specified in the d parameter. The maximum number for size is 65. The maximum number for d is 30. The default value for size is 10. The default value for d is 0
    DECIMAL(size, d)
    DEC(size, d)
    static public $DECIMAL = [
        'dataType' => 'string'
    ];

    Note: All the numeric data types may have an extra option: UNSIGNED or ZEROFILL. If you add the UNSIGNED option, MySQL disallows negative values for the column. If you add the ZEROFILL option, MySQL automatically also adds the UNSIGNED attribute to the column
*/

/** Date and Time Data Types */
/*
    A date. Format: YYYY-MM-DD. The supported range is from '1000-01-01' to '9999-12-31'
    DATE
    static public $DATE = [
        'dataType' => 'string',
        'regex' => '/\d{4}-\d{2}-\d{2}/'
    ];

    A date and time combination. Format: YYYY-MM-DD hh:mm:ss. The supported range is from '1000-01-01 00:00:00' to '9999-12-31 23:59:59'. Adding DEFAULT and ON UPDATE in the column definition to get automatic initialization and updating to the current date and time
    DATETIME(fsp)
    static public $DATETIME = [
        'dataType' => 'string',
        'regex' => '/\d{4}-\d{2}-\d{2}\s{1}\d{2}:\d{2}:\d{2}/'
    ];

    A timestamp. TIMESTAMP values are stored as the number of seconds since the Unix epoch ('1970-01-01 00:00:00' UTC). Format: YYYY-MM-DD hh:mm:ss. The supported range is from '1970-01-01 00:00:01' UTC to '2038-01-09 03:14:07' UTC. Automatic initialization and updating to the current date and time can be specified using DEFAULT CURRENT_TIMESTAMP and ON UPDATE CURRENT_TIMESTAMP in the column definition
    TIMESTAMP(fsp)
    static public $TIMESTAMP = [
        'dataType' => 'int'
    ];

    A time. Format: hh:mm:ss. The supported range is from '-838:59:59' to '838:59:59'
    TIME(fsp)
    static public $TIME = [
        'dataType' => 'string',
        'regex' => '/\d{2}:\d{2}:\d{2}/'
    ];

    A year in four-digit format. Values allowed in four-digit format: 1901 to 2155, and 0000
    YEAR
    static public $YEAR = [
        'dataType' => 'int',
        'minValue' => 1901,
        'maxValue' => 2155
    ];

    MySQL 8.0 does not support year in two-digit format
*/

/**
 * Constants
 *
 * Contains all constants related to Microservices
 *
 * @category   Custom Database Data Types (MySql)
 * @package    Microservices
 * @author     Ramesh Narayan Jangid
 * @copyright  Ramesh Narayan Jangid
 * @version    Release: @1.0.0@
 * @since      Class available since Release 1.0.0
 */
class DatabaseDataTypes
{
    /**
     * Example to create custom data type
     * You can configure them in /Config/Queries folder as a data type
     * This will validate the received payload/uriParam/etc data where thisnis configured
     * DatabaseDataTypes::$CustomINT
     *
     *  static public $CustomINT = [
     *      // PHP data type (null, bool, int, float, string)
     *      'dataType' => 'int',
     *      // if needed append required options from below with values
     *      'minValue' => false,
     *      'maxValue' => false,
     *      'minLength' => false,
     *      'maxLength' => false,
     *      'enumValues' => false,
     *      'setValues' => false,
     *      'regex' => false
     *  ];
     */

    static public $INT = [
        'dataType' => 'int'
    ];

    static public $PrimaryKey = [
        'dataType' => 'int'
    ];

    static public $Default = [
        'dataType' => 'string'
    ];

    /**
     * Return data based on data-type
     *
     * @param mixed $data
     * @param array $dataType
     * @return mixed
     * @throws \Exception
     */
    static public function validateDataType(&$data, &$dataType)
    {
        switch ($dataType['dataType']) {
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
                throw new \Exception('Invalid Data-type:'.$dataType['dataType'], HttpStatus::$InternalServerError);
        }

        $returnFlag = true;
        if ($returnFlag && isset($dataType['minValue']) && $dataType['minValue'] <= $data) {
            $returnFlag = false;
        }
        if ($returnFlag && isset($dataType['maxValue']) && $data <= $dataType['maxValue']) {
            $returnFlag = false;
        }
        if ($returnFlag && isset($dataType['minLength']) && $dataType['minLength'] <= strlen($data)) {
            $returnFlag = false;
        }
        if ($returnFlag && isset($dataType['maxLength']) && strlen($data) <= $dataType['maxLength']) {
            $returnFlag = false;
        }
        if ($returnFlag && isset($dataType['enumValues']) && in_array($data, $dataType['enumValues'])) {
            $returnFlag = false;
        }
        if ($returnFlag && isset($dataType['setValues']) && empty(array_diff($data, $dataType['setValues']))) {
            $returnFlag = false;
        }
        if ($returnFlag && isset($dataType['regex']) && preg_match($dataType['regex'], $data) === 0) {
            $returnFlag = false;
        }

        if (!$returnFlag) {
            throw new \Exception('Invalid data based on Data-type details', HttpStatus::$BadRequest);
        }

        return $data;
    }
}
