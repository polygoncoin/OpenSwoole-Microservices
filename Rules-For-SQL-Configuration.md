# Sql Configuration Rules

## Available configuration options explained

```PHP
//return represents root for sqlResults
return [
	/** Supported configuration for recursive operations are :
	 * __COUNT-SQL-COMMENT__, - for Sql Config only
	 * __COUNT-SQL__, - for Sql Config only
	 * __SQL-COMMENT__, - for Sql Config only
	 * __SQL__, - for Sql Config only
	 * __SET__, - for Sql Config only
	 * __WHERE__, - for Sql Config only
	 * __MODE__, - for Sql Config only
	 * __INSERT-ID__, - for Sql Config only
	 * __PRIMARY-KEY__, - for Sql Config only
	 * __PRE-CONFIG-HOOK__, - for Sql Config only
	 * __POST-CONFIG-HOOK__, - for Sql Config only
	 * 
	 * __PAYLOAD__, - for Supplement Config only
	 * 
	 * __SUB-CONFIG__, - for all
	 * __TRANSACTION__, - for all
	 * __HIERARCHY__, - for all
	 * __FETCH-MODE__, - for all
	 * __TRIGGER__, - for all
	 * __VALIDATE__, - for all
	 * __PAYLOAD-TYPE__, - for all
	 * __MAX-PAYLOAD-OBJECT__, - for all
	 * __TRANSACTION__, - for all
	 * __HIERARCHY__, - for all
	 * __CACHE-KEY__, - for all
	 * __AFFECTED-CACHE-KEY__, - for all
	 * 
	 * __VARIABLE__,
	 */

	// Required to implementing pagination
	'__COUNT-SQL-COMMENT__' => '',
	'__COUNT-SQL__' => 'SELECT count(1) as `count` FROM TableName WHERE __WHERE__', // OR
	'__COUNT-SQL__' => 'SELECT count(1) as `count` FROM TableName WHERE column1 = :column1 AND  id = :id',

	// Query to perform task
	'__SQL-COMMENT__' => 'Comment prepended to query for monitoring queries in logs',
	'__SQL__' => 'SELECT columns FROM TableName WHERE __WHERE__', // OR
	'__SQL__' => 'SELECT columns FROM TableName WHERE column1 = :column1 AND id = :id',

	// Static variables to be used/fetched in __SET__ / __WHERE__
	'__VARIABLE__' => [
		'var1' => 'var1-data',
		'var2' => 'var2-data',
	],

	// detail of data to be set by Query to perform task
	'__SET__' => [
		[
			'column' => 'id',
			'activeRequestDataKey' => 'routeParamArray', // Fetch value from parsed route
			// 'activeRequestDataKey' => 'queryParamArray', // Fetch value from query string
			// 'activeRequestDataKey' => 'payload', // Fetch value from payload
			// 'activeRequestDataKey' => 'function', // Fetch value from function
			// 'activeRequestDataKey' => 'customerData', // Fetch value from Customer Data
			// 'activeRequestDataKey' => 'userData', // Fetch value from User Data session
			// 'activeRequestDataKey' => 'custom', // Static values
			// 'activeRequestDataKey' => 'variables', // to fetch values as per __VARIABLE__ key's
			'activeRequestDataKeySubKey' => 'id',          // key (id)
			'dataType' => DatabaseServerDataType::$PrimaryKey,   // key data type
			'isRequired' => Constant::$REQUIRED              // Represents required field
		],
		[
			// Fetch value from function
			'column' => 'password',
			'activeRequestDataKey' => 'function',                       // function
			'activeRequestDataKeySubKey' => function(
				$activeRequestData,
				$payload
			) {        // execute a function and return value
				return 'value';
			}
		],
		[
			// Fetch value of last insert IDs
			'column' => 'is_deleted',
			'activeRequestDataKey' => 'custom',                        // custom
			'activeRequestDataKeySubKey' => Constant::$NO                        // Static values
		]
	],

	// Where clause of the Query to perform task
	'__WHERE__' => [
		[
			'column' => 'id',
			'activeRequestDataKey' => 'routeParamArray', // Fetch value from parsed route
			// 'activeRequestDataKey' => 'queryParamArray', // Fetch value from query string
			// 'activeRequestDataKey' => 'payload', // Fetch value from payload
			// 'activeRequestDataKey' => 'function', // Fetch value from function
			// 'activeRequestDataKey' => 'customerData', // Fetch value from Customer Data
			// 'activeRequestDataKey' => 'userData', // Fetch value from User Data session
			// 'activeRequestDataKey' => 'custom', // Static values
			// 'activeRequestDataKey' => 'variables', // to fetch values as per __VARIABLE__ key's
			'activeRequestDataKeySubKey' => 'id',                       // key (id)
			'dataType' => DatabaseServerDataType::$PrimaryKey,   // key data type
			'isRequired' => Constant::$REQUIRED              // Represents required field
		],
		[...]
	],

	// Where clause of the Query to perform task
	'__PAYLOAD__' => [
		[
			'column' => 'id',
			'activeRequestDataKey' => 'routeParamArray', // Fetch value from parsed route
			// 'activeRequestDataKey' => 'queryParamArray', // Fetch value from query string
			// 'activeRequestDataKey' => 'payload', // Fetch value from payload
			// 'activeRequestDataKey' => 'function', // Fetch value from function
			// 'activeRequestDataKey' => 'customerData', // Fetch value from Customer Data
			// 'activeRequestDataKey' => 'userData', // Fetch value from User Data session
			// 'activeRequestDataKey' => 'custom', // Static values
			// 'activeRequestDataKey' => 'variables', // to fetch values as per __VARIABLE__ key's
			'activeRequestDataKeySubKey' => 'id',                       // key (id)
			'dataType' => DatabaseServerDataType::$PrimaryKey,   // key data type
			'isRequired' => Constant::$REQUIRED              // Represents required field
		],
		[...]
	],

	// Last insert id to be made available as $activeRequestData['__INSERT-ID__'][uniqueParamString];
	'__INSERT-ID__' => '<keyName>:id',

	// mention primary key column name when using global counter
	'__PRIMARY-KEY__' => 'table_primary_column_name',

	// Indicator to generate JSON in Single(Object) record / Multiple(Array) rows format.
	'__MODE__' => 'singleRecordFormat/multipleRecordFormat',

	'__SUB-CONFIG__' => [
		/** Supported configuration for recursive operations are :
		 * __SQL__, - for Sql Config only
		 * __SET__, - for Sql Config only
		 * __WHERE__, - for Sql Config only
		 * __MODE__, - for Sql Config only
		 * __INSERT-ID__, - for Sql Config only
		 * __PRIMARY-KEY__, - for Sql Config only
		 * __PRE-CONFIG-HOOK__, - for Sql Config only
		 * __POST-CONFIG-HOOK__, - for Sql Config only
		 * 
		 * __PAYLOAD__, - for Supplement Config only
		 * 
		 * __SUB-CONFIG__, - for all
		 * __TRANSACTION__, - for all
		 * __HIERARCHY__, - for all
		 * __FETCH-MODE__, - for all
		 * __TRIGGER__, - for all
		 * __VALIDATE__, - for all
		 * __PAYLOAD-TYPE__, - for all
		 * __MAX-PAYLOAD-OBJECT__, - for all
		 * __TRANSACTION__, - for all
		 * __HIERARCHY__, - for all
		 * __CACHE-KEY__, - for all
		 * __AFFECTED-CACHE-KEY__, - for all
		 * 
		 * __VARIABLE__,
		 */
		'<sub-key>' => [
			// Query to perform task
			'__SQL__' => 'SQL',
			'__SQL-COMMENT__' => 'Comment prepended to query for monitoring queries in logs',
			'__VARIABLE__' => [
				'sub-var1' => 'sub-var1-data',
				'sub-var2' => 'sub-var2-data',
			],
			'__SET__/__WHERE__' => [
				[
					'column' => 'id',
					'activeRequestDataKey' => 'routeParamArray', // Fetch value from parsed route
					// 'activeRequestDataKey' => 'queryParamArray', // Fetch value from query string
					// 'activeRequestDataKey' => 'payload', // Fetch value from payload
					// 'activeRequestDataKey' => 'function', // Fetch value from function
					// 'activeRequestDataKey' => 'customerData', // Fetch value from Customer Data
					// 'activeRequestDataKey' => 'userData', // Fetch value from User Data session
					// 'activeRequestDataKey' => 'custom', // Static values
					// 'activeRequestDataKey' => 'variables', // to fetch values as per current module/<sub-key> __VARIABLE__ key's
					'activeRequestDataKeySubKey' => 'id',                       // key (id)
					'dataType' => DatabaseServerDataType::$PrimaryKey,   // key data type
					'isRequired' => Constant::$REQUIRED              // Represents required field
				],
				// Database DataTypes settings required when maintainHierarchy is true
				// to validate each data set before procedding forward
				[
					// Fetch value of last insert IDs
					'column' => 'id',
					'activeRequestDataKey' => '__INSERT-ID__',                // userData from session
					'activeRequestDataKeySubKey' => '<saved-id-key>'            // previous Insert IDs
				],
				[
					// Fetch values of params from previous queries
					'column' => 'id',
					'activeRequestDataKey' => 'sqlParamArray',                     // sqlParamArray (with maintainHierarchy)
					'activeRequestDataKeySubKey' => '<return:keys-separated-by-colon>'
				],
				[
					// Fetch values of Sql results from previous queries
					'column' => 'id',
					'activeRequestDataKey' => 'sqlResults',                    // sqlResults for DQL operations (with maintainHierarchy)
					'activeRequestDataKeySubKey' => '<return:keys-separated-by-colon>'
				],
				[
					// Fetch values of Sql payload for previous queries
					'column' => 'id',
					'activeRequestDataKey' => 'previousPayload',                    // previousPayload (with maintainHierarchy)
					'activeRequestDataKeySubKey' => '<return:keys-separated-by-colon>'
				],
				[
					'column' => 'any-table- column',
					'activeRequestDataKey' => 'variables',      // custom
					'activeRequestDataKeySubKey' => 'sub-var1'   // returns static sub-var1 value set in __VARIABLE__ of current module/<sub-key>
				]
			],
			'__TRIGGER__' => [...],
			'__PRE-CONFIG-HOOK__' => [...],
			'__POST-CONFIG-HOOK__' => [...],
			'__VALIDATE__' => [...],
			'__PAYLOAD-TYPE__' => 'Object/Array',
			'__MAX-PAYLOAD-OBJECT__' => 'Integer',
			'__SUB-CONFIG__' => [...],
		],
		'<sub-key>' => [
			[...]
		],
		[...]
	],

	// Trigger set of routes
	'__TRIGGER__' => [// Array of triggers
		[
			'__ROUTE__' => [
				[
					'activeRequestDataKey' => 'routeParamArray', // Fetch value from parsed route
					// 'activeRequestDataKey' => 'queryParamArray', // Fetch value from query string
					// 'activeRequestDataKey' => 'payload', // Fetch value from payload
					// 'activeRequestDataKey' => 'function', // Fetch value from function
					// 'activeRequestDataKey' => 'customerData', // Fetch value from Customer Data
					// 'activeRequestDataKey' => 'userData', // Fetch value from User Data session
					// 'activeRequestDataKey' => 'custom', // Static values
					// 'activeRequestDataKey' => '__INSERT-ID__', // Sql Insert IDs
					'activeRequestDataKeySubKey' => 'address'
				],
				[
					// Sql Insert IDs
					'activeRequestDataKey' => '__INSERT-ID__',
					'activeRequestDataKeySubKey' => 'address:id'
				]
			],
			'__QUERY-STRING__' => [
				[
					'column' => 'param-1',
					'activeRequestDataKey' => 'routeParamArray', // Fetch value from parsed route
					// 'activeRequestDataKey' => 'queryParamArray', // Fetch value from query string
					// 'activeRequestDataKey' => 'payload', // Fetch value from payload
					// 'activeRequestDataKey' => 'function', // Fetch value from function
					// 'activeRequestDataKey' => 'customerData', // Fetch value from Customer Data
					// 'activeRequestDataKey' => 'userData', // Fetch value from User Data session
					// 'activeRequestDataKey' => 'custom', // Static values
					// 'activeRequestDataKey' => '__INSERT-ID__', // Sql Insert IDs
					'activeRequestDataKeySubKey' => 'address'
				],
				[...]
			],
			'__METHOD__' => 'PATCH',
			'__PAYLOAD__' => [
				[
					'column' => 'param-1',
					'activeRequestDataKey' => 'routeParamArray', // Fetch value from parsed route
					// 'activeRequestDataKey' => 'queryParamArray', // Fetch value from query string
					// 'activeRequestDataKey' => 'payload', // Fetch value from payload
					// 'activeRequestDataKey' => 'function', // Fetch value from function
					// 'activeRequestDataKey' => 'customerData', // Fetch value from Customer Data
					// 'activeRequestDataKey' => 'userData', // Fetch value from User Data session
					// 'activeRequestDataKey' => 'custom', // Static values
					// 'activeRequestDataKey' => '__INSERT-ID__', // Sql Insert IDs
					'activeRequestDataKeySubKey' => 'address'
				],
				[...]
			]
		]
		[...]
	],

	// Hook
	'__PRE-CONFIG-HOOK__' => [// Array of Hook class name in exec order
		'Hook_Example1',
		'...'
	],
	'__POST-CONFIG-HOOK__' => [// Array of Hook class name in exec order
		'Hook_Example2',
		'...'
	],

	// Array of validation functions to be performed
	'__VALIDATE__' => [
		[
			'function' => 'validateGroupID',
			'functionArgs' => [
				'id' => ['payload', 'id']
			],
			'errorMessage' => 'Invalid Group Id'
		],
		[...]
	],

	'__PAYLOAD-TYPE__' => 'Object', // Allow single 'Object' / 'Array' of Object (if not set will accept both)
	'__MAX-PAYLOAD-OBJECT__' => 2, // Max number of allowed Objects if __PAYLOAD-TYPE__ is 'Array'

	'__TRANSACTION__' => Constant::$FALSE, // Flag to follow transaction Begin, Commit and rollback on error

	'__HIERARCHY__' => Constant::$TRUE, // For DML

	// Optional custom configuration to connect to master / slave Database
	'__FETCH-MODE__' => 'Slave' // values - Master / Slave

	// Any among below can be used for DML operations (These are Optional key's)
	// Caching
	'__CACHE-KEY__' => '<unique-key-for-redis-to-cache-results>(e.g, key:1)', // Use cacheKey to cache and reuse results (Optional)
	'__AFFECTED-CACHE-KEY__' => [ // List down key's which effects configured cacheKey on DML operation
		'<unique-key-for-redis-to-drop-cached-results>(key:1)',
		'<unique-key-for-redis-to-drop-cached-results>(category etc.)',
		'...'
	],

	// Data Representation
	'outputRepresentation' => 'XML', // JSON/XML/XSLT/HTML/PHP - Defaults to JSON

	// Respective Data Representation File (XSLT/HTML/PHP)
	'outputRepresentationFileLocation' => 'file-path',

	// Rate Limiting Route access
	'rateLimitMaxRequest' => 1, // Allowed number of request in defined seconds window
	'rateLimitMaxRequestWindow' => 3600, // Seconds Window for restricting number of request

	// Maintain responseLag for window
	'responseLagWindow' => 3600,

	// Control response time as per number of hits by configuring lags in seconds as below
	'responseLag' => [
		[
			'requestCount' => 10,
			'lagResponse' => 0
		],
		[
			'requestCount' => 20,
			'lagResponse' => 10
		]
	],

	// Enable referrer lag for current route
	// To be configured in source route Sql config
	'enableReferrerLag' => Constant::$YES,

	// Minimum Lag time between current request and referrer/previous request
	// To be configured in target route Sql config
	'referrerLagWindow' => [
		[
			'referrer' => '/referrer-route-1',
			'minimumReferrerLagWindow'	=> 7,
			'maximumReferrerLagWindow'	=> 10,
		],
		[
			'referrer' => '/referrer-route-2',
			'minimumReferrerLagWindow'	=> 8,
			'maximumReferrerLagWindow'	=> 11,
		],
	],

	// Limiting duplicates
	'idempotentWindow' => 3, // Idempotent Window for DML operation (seconds)
];
```

- **Note**: '__HIERARCHY__' => Constant::$TRUE also includes '__HIERARCHY__' => Constant::$TRUE feature.

- If there are repeated modules or configurations; one can reuse them by palcing them in a separate file and including as below.

```PHP
'__SUB-CONFIG__' => [
	//Here the module1 properties are reused for write operation.
	'module1' => include Constant::$ROOT . DIRECTORY_SEPARATOR . 'Config/Sql/CustomerDB/Common/reusefilename.php',
]
```

- For POST, PUT, PATCH, and DELETE methods one can configure both INSERT as well as UPDATE queries if required for sub modules.

## Available configuration options for Download CSV

```PHP
return [
	// Query to perform task
	'__SQL-COMMENT__' => 'Comment prepended to query for monitoring queries in logs',
	'__DOWNLOAD__' => 'SELECT columns FROM TableName WHERE __WHERE__',
	// Where clause of the Query to perform task
	'__WHERE__' => [
		[
			'column' => 'id',
			'activeRequestDataKey' => 'routeParamArray', // Fetch value from parsed route
			// 'activeRequestDataKey' => 'queryParamArray', // Fetch value from query string
			// 'activeRequestDataKey' => 'payload', // Fetch value from payload
			// 'activeRequestDataKey' => 'function', // Fetch value from function
			// 'activeRequestDataKey' => 'customerData', // Fetch value from Customer Data
			// 'activeRequestDataKey' => 'userData', // Fetch value from User Data session
			// 'activeRequestDataKey' => 'custom', // Static values
			'activeRequestDataKeySubKey' => 'id',                       // key (id)
			'dataType' => DatabaseServerDataType::$PrimaryKey,   // key data type
			'isRequired' => Constant::$REQUIRED              // Represents required field
		],
		[...]
	],

	// Optional custom configuration to connect to master / slave Database
	'__FETCH-MODE__' => 'Slave' // values - Master / Slave
];
```

## Database

- Dedicated database for respective customer can be configured
- This can also handle Master / Slave implementaion respectively

## activeRequestDataKey

- **activeRequestDataKey** is a Sql config feature where one can force the fetch from Master (Since usually it is Slave)

## Defining Custom DataTypes

```PHP
public static $PrimaryKey = [

// Required param
	// PHP data type (bool, int, float, string)
	'dataType' => 'int',

// Optional params
	// Value can be null
	'canBeNull' => Constant::$FALSE,
	// Minimum value (int)
	'minValue' => 1,
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

## Contributing

Issues and feature request are welcome.<br />
Feel free to share them on [issues page](https://github.com/polygoncoin/Openswoole-Microservices/issues)

## Author

- **Ramesh N. Jangid (Sharma)**

Github: [@polygoncoin](https://github.com/polygoncoin)

## License

Copyright © 2026 [Ramesh N. Jangid (Sharma)](https://github.com/polygoncoin).<br />
This project is [MIT](License) licensed.
