# Custom DataTypes

## Defining Custom DataTypes

The custom data types key's are classified in two sections depending on key's - Required / Optional key's

## Required

### dataType

This can take one fo the PHP data type (bool, int, float, string). This is a required key.

## Optional

### canBeNull

This represents the Custom DataType can be NULL. This is an optional key.

### minValue

This represents the minimum value of the Custom DataType. This is an optional key.

### maxValue

This represents the maximum value of the Custom DataType. This is an optional key.

### minLength

This represents the minimum length of the Custom DataType. This is an optional key.

### maxLength

This represents the maximum length of the Custom DataType. This is an optional key.

### enumValues

This represents Custom DataType should have any one value from the defined array. This is an optional key.

### setValues

This represents Custom DataType should have values belonging in this array. This is an optional key.

### regex

This represents Custom DataType should pass this regular expression. This is an optional key.

## Custom DataTypes Example

```PHP
public static $CustomINT = [

// Required param
	// PHP data type (bool, int, float, string)
	'dataType' => 'int',

// Optional params
	// Value can be null
	'canBeNull' => Constant::$FALSE,
	// Minimum value (int)
	'minValue' => Constant::$FALSE,
	// Maximum value (int)
	'maxValue' => Constant::$FALSE,
	// Minimum length (string)
	'minLength' => Constant::$FALSE,
	// Maximum length (string)
	'maxLength' => Constant::$FALSE,
	// Any one value from the Array
	'enumValues' => Constant::$FALSE,
	// Values belonging to this Array
	'setValues' => Constant::$FALSE,

	// Values should pass this regex before use
	'regex' => Constant::$FALSE
];
```

### Custom DataTypes Example

- $PrimaryKey

```PHP
public static $PrimaryKey = [
	// PHP data type (bool, int, float, string)
	'dataType' => 'int',
	// Minimum value (int)
	'minValue' => 1
];

- $Varchar50

public static $Varchar50 = [
	// PHP data type (bool, int, float, string)
	'dataType' => 'string',
	// Minimum length (string)
	'minLength' => 0,
	// Maximum length (string)
	'maxLength' => 50
];
```

## Usage

When these data types are configured for a param in route or Sql config payload; there is a validation performed on the received data to fit the configured criteria of respective data type.

## Contributing

Issues and feature request are welcome.<br />
Feel free to share them on [issues page](https://github.com/polygoncoin/Openswoole-Microservices/issues)

## Author

- **Ramesh N. Jangid (Sharma)**

Github: [@polygoncoin](https://github.com/polygoncoin)

## License

Copyright © 2026 [Ramesh N. Jangid (Sharma)](https://github.com/polygoncoin).<br />
This project is [MIT](License) licensed.
