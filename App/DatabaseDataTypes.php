<?php

/**
 * DataTypes
 * php version 8.3
 *
 * @category  DataTypes
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */

namespace Microservices\App;

/* String Data Types */
/*
    A FIXED length string (can contain letters, numbers, and special characters).
    The size parameter specifies the column length in characters - can be from 0 to
    255. Default is 1
    CHAR(size)
    public static $CHAR = [
        'dataType' => 'string',
        'minLength' => (size-x),
        'maxLength' => (size-x)
    ];

    A VARIABLE length string (can contain letters, numbers, and special characters).
    The size parameter specifies the maximum column length in characters - can be
    from 0 to 65535
    VARCHAR(size)
    public static $VARCHAR = [
        'dataType' => 'string',
        'minLength' => 0,
        'maxLength' => 65535
    ];

    Equal to CHAR(), but stores binary byte strings. The size parameter specifies
    the column length in bytes. Default is 1
    BINARY(size)
    public static $BINARY = [
        'dataType' => 'string',
        'minLength' => 0,
        'maxLength' => 8
    ];

    Equal to VARCHAR(), but stores binary byte strings. The size parameter specifies
    the maximum column length in bytes
    VARBINARY(size)
    public static $VARBINARY = [
        'dataType' => 'string',
        'minLength' => 0,
        'maxLength' => 65535
    ];

    For BLOBs (Binary Large OBjects). Max length: 255 bytes
    TINYBLOB
    public static $TINYBLOB = [
        'dataType' => 'string',
        'minLength' => 0,
        'maxLength' => 255
    ];

    Holds a string with a maximum length of 255 characters
    TINYTEXT
    public static $TINYTEXT = [
        'dataType' => 'string',
        'minLength' => 0,
        'maxLength' => 255
    ];

    Holds a string with a maximum length of 65, 535 bytes
    TEXT(size)
    public static $TEXT = [
        'dataType' => 'string',
        'minLength' => 0,
        'maxLength' => 65535
    ];

    For BLOBs (Binary Large OBjects). Holds up to 65, 535 bytes of data
    BLOB(size)
    public static $BLOB = [
        'dataType' => 'string',
        'minLength' => 0,
        'maxLength' => 65535
    ];

    Holds a string with a maximum length of 16, 777, 215 characters
    MEDIUMTEXT
    public static $MEDIUMTEXT = [
        'dataType' => 'string',
        'minLength' => 0,
        'maxLength' => 16777215
    ];

    For BLOBs (Binary Large OBjects). Holds up to 16, 777, 215 bytes of data
    MEDIUMBLOB
    public static $MEDIUMBLOB = [
        'dataType' => 'string',
        'minLength' => 0,
        'maxLength' => 16777215
    ];

    Holds a string with a maximum length of 4, 294, 967, 295 characters
    LONGTEXT
    public static $LONGTEXT = [
        'dataType' => 'string'
    ];

    For BLOBs (Binary Large OBjects). Holds up to 4, 294, 967, 295 bytes of data
    LONGBLOB
    public static $LONGBLOB = [
        'dataType' => 'string'
    ];

    A string object that can have only one value, chosen from a list of possible
    values. You can list up to 65535 values in an ENUM list. If a value is
    inserted that is not in the list, a blank value will be inserted. The
    values are sorted in the order you enter them
    ENUM(val1, val2, val3, ...)
    public static $ENUM = [
        'dataType' => 'string',
        'enumValues' => [val1, val2, val3, ...]
    ];

    A string object that can have 0 or more values, chosen from a list of
    possible values. You can list up to 64 values in a SET list
    SET(val1, val2, val3, ...)
    public static $SET = [
        'dataType' => 'string',
        'setValues' => [val1, val2, val3, ...]
    ];
*/

/* Numeric Data Types */
/*
    A bit-value type. The number of bits per value is specified in size. The size
    parameter can hold a value from 1 to 64. The default value for size is 1
    BIT(size)
    public static $BIT = [
        'dataType' => 'string',
        'minLength' => 1,
        'maxLength' => 64
    ];

    A very small int. Signed range is from -128 to 127. Unsigned range is from 0 to
    255. The size parameter specifies the maximum display width (which is 255)
    TINYINT(size)
    public static $TINYINT = [
        'dataType' => 'int',
        'minValue' => -128,
        'maxValue' => 127
    ];
    public static $U_TINYINT = [
        'dataType' => 'int',
        'minValue' => 0,
        'maxValue' => 255
    ];

    Zero is considered as false, nonzero values are considered as true
    BOOL
    bool
    public static $BOOL = [
        'dataType' => 'int',
        'minValue' => 0,
        'maxValue' => 1
    ];

    A small int. Signed range is from -32768 to 32767. Unsigned range is from 0 to
    65535. The size parameter specifies the maximum display width (which is 255)
    SMALLINT(size)
    public static $SMALLINT = [
        'dataType' => 'int',
        'minValue' => -32768,
        'maxValue' => 32767
    ];
    public static $U_SMALLINT = [
        'dataType' => 'int',
        'minValue' => 0,
        'maxValue' => 65535
    ];

    A medium int. Signed range is from -8388608 to 8388607. Unsigned range is from
    0 to 16777215. The size parameter specifies the maximum display width (which
    is 255)
    MEDIUMINT(size)
    public static $MEDIUMINT = [
        'dataType' => 'int',
        'minValue' => -8388608,
        'maxValue' => 655838860735
    ];
    public static $U_MEDIUMINT = [
        'dataType' => 'int',
        'minValue' => 0,
        'maxValue' => 16777215
    ];

    A medium int. Signed range is from -2147483648 to 2147483647. Unsigned range
    is from 0 to 4294967295. The size parameter specifies the maximum display width
    (which is 255)
    INT(size)
    int(size)
    public static $INT = [
        'dataType' => 'int',
        'minValue' => -2147483648,
        'maxValue' => 2147483647
    ];
    public static $U_INT = [
        'dataType' => 'int',
        'minValue' => 0,
        'maxValue' => 4294967295
    ];

    A large int. Signed range is from -9223372036854775808 to 9223372036854775807.
     Unsigned range is from 0 to 18446744073709551615. The size parameter specifies
      the maximum display width (which is 255)
    BIGINT(size)
    public static $BIGINT = [
        'dataType' => 'string'
    ];

    A floating point number. The total number of digits is specified in size. The
    number of digits after the decimal point is specified in the d parameter.
    This syntax is deprecated in MySql 8.0.17, and it will be removed in future
    MySql versions
    FLOAT(size, d)
    public static $FLOAT = [
        'dataType' => 'string'
    ];

    A floating point number. MySql uses the p value to determine whether to use
    FLOAT or DOUBLE for the resulting data type. If p is from 0 to 24, the data
    type becomes FLOAT(). If p is from 25 to 53, the data type becomes DOUBLE()
    FLOAT(p)
    public static $FLOAT_P = [
        'dataType' => 'string'
    ];

    A normal-size floating point number. The total number of digits is specified in
    size. The number of digits after the decimal point is specified in the d
    parameter
    DOUBLE(size, d)
    DOUBLE PRECISION(size, d)
    public static $DOUBLE = [
        'dataType' => 'string'
    ];

    An exact fixed-point number. The total number of digits is specified in size.
    The number of digits after the decimal point is specified in the d parameter.
    The maximum number for size is 65. The maximum number for d is 30. The default
    value for size is 10. The default value for d is 0
    DECIMAL(size, d)
    DEC(size, d)
    public static $DECIMAL = [
        'dataType' => 'string'
    ];

    Note: All the numeric data types may have an extra option: UNSIGNED or
    ZEROFILL. If you add the UNSIGNED option, MySql disallows negative values
    for the column. If you add the ZEROFILL option, MySql automatically also adds
    the UNSIGNED attribute to the column
*/

/* Date and Time Data Types */
/*
    A date. Format: YYYY-MM-DD. The supported range is from '1000-01-01'
    to '9999-12-31'

    DATE
    public static $DATE = [
        'dataType' => 'string',
        'regex' => '/\d{4}-\d{2}-\d{2}/'
    ];

    A date and time combination. Format: YYYY-MM-DD hh:mm:ss. The supported range is
    from '1000-01-01 00:00:00' to '9999-12-31 23:59:59'. Adding DEFAULT and ON
    UPDATE in the column definition to get automatic initialization and updating to
    the current date and time
    DATETIME(fsp)
    public static $DATETIME = [
        'dataType' => 'string',
        'regex' => '/\d{4}-\d{2}-\d{2}\s{1}\d{2}:\d{2}:\d{2}/'
    ];

    A timestamp. TIMESTAMP values are stored as the number of seconds since the
    Unix epoch ('1970-01-01 00:00:00' UTC). Format: YYYY-MM-DD hh:mm:ss.
    The supported range is from '1970-01-01 00:00:01' UTC to '2038-01-09 03:14:07'
    UTC. Automatic initialization and updating to the current date and time can be
    specified using DEFAULT CURRENT_TIMESTAMP and ON UPDATE CURRENT_TIMESTAMP in the
    column definition
    TIMESTAMP(fsp)
    public static $TIMESTAMP = [
        'dataType' => 'int'
    ];

    A time. Format: hh:mm:ss. The supported range is from '-838:59:59' - '838:59:59'
    TIME(fsp)
    public static $TIME = [
        'dataType' => 'string',
        'regex' => '/\d{2}:\d{2}:\d{2}/'
    ];

    A year in four-digit format. Values allowed in four-digit format: 1901 to 2155,
    and 0000
    YEAR
    public static $YEAR = [
        'dataType' => 'int',
        'minValue' => 1901,
        'maxValue' => 2155
    ];

    MySql 8.0 does not support year in two-digit format
*/

/**
 * Custom DataTypes
 * php version 8.3
 *
 * @category  DataTypes
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
class DatabaseDataTypes
{
    /**
     * Example to create custom data type
     * You can configure them in /Config/Queries folder as a data type
     * This will validate the received payload/uriParam/etc data where this is
     * configured
     *
     * DatabaseDataTypes::$CustomINT
     *
     * public static $CustomINT = [
     *      // PHP data type (null, bool, int, float, string)
     *      'dataType' => 'int',
     *      // if needed append necessary options from below with values
     *      'minValue' => false,
     *      'maxValue' => false,
     *      'minLength' => false,
     *      'maxLength' => false,
     *      'enumValues' => false,
     *      'setValues' => false,
     *      'regex' => false
     *  ];
     */

    /**
     * Custom int DataType
     *
     * @var array $INT
     */
    public static $INT = [
        'dataType' => 'int'
    ];

    /**
     * Custom primary key DataType
     *
     * @var array $PrimaryKey
     */
    public static $PrimaryKey = [
        'dataType' => 'int'
    ];

    /**
     * Custom default DataType
     *
     * @var array $Default
     */
    public static $Default = [
        'dataType' => 'string'
    ];

    public static $HttpMethods = [
        'dataType' => 'string',
        'enumValues' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE']
    ];

    /**
     * Validates DataType
     *
     * @param bool|float|int|string|null $data     Data
     * @param array                      $dataType Custom data type
     *
     * @return bool|float|int|string|null
     * @throws \Exception
     */
    public static function validateDataType(
        &$data,
        &$dataType
    ): bool {
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
                $data = (string)json_encode(value: $data);
                break;
            default:
                throw new \Exception(
                    message: 'Invalid Data-type:' . $dataType['dataType'],
                    code: HttpStatus::$InternalServerError
                );
        }

        $returnFlag = true;
        if (
            $returnFlag
            && isset($dataType['minValue'])
            && $dataType['minValue'] <= $data
        ) {
            $returnFlag = false;
        }
        if (
            $returnFlag
            && isset($dataType['maxValue'])
            && $data <= $dataType['maxValue']
        ) {
            $returnFlag = false;
        }
        if (
            $returnFlag
            && isset($dataType['minLength'])
            && $dataType['minLength'] <= strlen(string: $data)
        ) {
            $returnFlag = false;
        }
        if (
            $returnFlag
            && isset($dataType['maxLength'])
            && strlen(string: $data) <= $dataType['maxLength']
        ) {
            $returnFlag = false;
        }
        if (
            $returnFlag
            && isset($dataType['enumValues'])
            && in_array(needle: $data, haystack: $dataType['enumValues'])
        ) {
            $returnFlag = false;
        }
        if (
            $returnFlag
            && isset($dataType['setValues'])
            && empty(array_diff([$data], $dataType['setValues']))
        ) {
            $returnFlag = false;
        }
        if (
            $returnFlag
            && isset($dataType['regex'])
            && preg_match(pattern: $dataType['regex'], subject: $data) === 0
        ) {
            $returnFlag = false;
        }

        if (!$returnFlag) {
            throw new \Exception(
                message: 'Invalid data based on Data-type details',
                code: HttpStatus::$BadRequest
            );
        }

        return true;
    }
}
