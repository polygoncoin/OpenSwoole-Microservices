# Routes Configuration Rules

## Available configuration syntax explained

- For configuring route **/tableName/parts** GET method
```PHP
return [
	'tableName' => [
		'parts' => [
			'__FILE__' => 'SQL file location'
		]
	]
];
```

- For configuring route **/tableName/{id}** where id is dynamic **integer** value to be collected.
```PHP
return [
	'tableName' => [
		'{id:int}' => [
			'dataType' => DatabaseDataTypes::$PrimaryKey,
			'__FILE__' => 'SQL file location'
		]
	]
];
```

- Same dynamic variable but with a different data type, for e.g. **{id}** will be treated differently for **string** and **integer** values to be collected.

```PHP
return [
	'tableName' => [
		'{id:int}' => [
			'dataType' => DatabaseDataTypes::$PrimaryKey,
			'__FILE__' => 'SQL file location for integer data type'
		],
		'{id:string}' => [
			'dataType' => DatabaseDataTypes::$Default,
			'__FILE__' => 'SQL file location for string data type'
		]
	]
];
```

- To restrict dynamic values to a certain set of values. One can do the same by defining its data type.

```PHP
return [
	'{tableName:string}' => [
		'dataType' => DatabaseDataTypes::$Tables,
		'{id:int}' => [
			'__FILE__' => 'SQL file location'
		]
	]
];
```

## Hooks

```PHP
return [
	'{tableName:string}' => [
		'dataType' => DatabaseDataTypes::$Tables,
		'__FILE__' => 'SQL file location',
		'__PRE-ROUTE-HOOKS__' => [// These will apply recursively
			'Hook_1',
			'...'
		],
		'__POST-ROUTE-HOOKS__' => [// These will apply recursively
			'Hook_1',
			'...'
		]
		'{id:int}' => [
			'dataType' => DatabaseDataTypes::$PrimaryKey,
			'__FILE__' => 'SQL file location',
			'__PRE-ROUTE-HOOKS__' => [], // For noi hooks
			'__POST-ROUTE-HOOKS__' => [] // For noi hooks
		],

		// Input Data Representation
		'iRepresentation' => 'XML' // JSON/XML - Defaults to JSON
	]
];
```

- This '{id:int|!0}' means id is integer but can't be zero.

## Defining Custom DataTypes

```PHP
public static $PrimaryKey = [

// Required param
	// PHP data type (bool, int, float, string)
	'dataType' => 'int',

// Optional params
	// Value can be null
	'canBeNull' => false,
	// Minimum value (int)
	'minValue' => 1,
	// Maximum value (int)
	'maxValue' => false,
	// Minimum length (string)
	'minLength' => false,
	// Maximum length (string)
	'maxLength' => false,
	// Any one value from the Array
	'enumValues' => false,
	// Values belonging to this Array
	'setValues' => false,

	// Values should pass this regex before use
	'regex' => false
];
```

## Special Routes

- Appending route with **/config** returns the payload information that should be supplied; both necessary and optional with desired format.

Examples:

- route=/registration/config (returns the payload information)
- route=/registration/import (import CSV)
- route=/registration/import-sample (sample CSV download for respective HTTP Method)

One need to enable same in .env file as below

```ini
; Allow particular route config request (global flag)
; Useful to get details of the  payload necessary by the API
enableConfigRequest=1               ; 1 = true / 0 = false
enableImportRequest=1               ; 1 = true / 0 = false

; Keyword to append with in route with slash.
configRequestRouteKeyword='config'  ; to append "/config" at the end of route
importRequestRouteKeyword='import'  ; to append "/import" at the end of route
importSampleRequestRouteKeyword='import-sample'
```

## 🤝 Contributing

Issues and feature request are welcome.<br />
Feel free to share them on [issues page](https://github.com/polygoncoin/Microservices/issues)

## Author

- **Ramesh N. Jangid (Sharma)**

Github: [@polygoncoin](https://github.com/polygoncoin)

## 📝 License

Copyright © 2026 [Ramesh N. Jangid (Sharma)](https://github.com/polygoncoin).<br />
This project is [MIT](License) licensed.
