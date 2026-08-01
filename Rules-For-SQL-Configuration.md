# Sql Configuration Rules

## Available configuration options explained

```PHP
//return represents root for sqlResults
return [
	// Required to implementing pagination
	'__COUNT-SQL-COMMENT__' => '',
	'countQuery' => 'SELECT count(1) as `count` FROM TableName WHERE __WHERE__', // OR
	'countQuery' => 'SELECT count(1) as `count` FROM TableName WHERE column1 = :column1 AND  id = :id',

	// Query to perform task
	'__SQL-COMMENT__' => 'Comment prepended to query for monitoring queries in logs',
	'__QUERY__' => 'SELECT columns FROM TableName WHERE __WHERE__', // OR
	'__QUERY__' => 'SELECT columns FROM TableName WHERE column1 = :column1 AND id = :id',

	// Static variables to be used/fetched in __SET__ / __WHERE__
	'__VARIABLES__' => [
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
			// 'activeRequestDataKey' => 'variables', // to fetch values as per __VARIABLES__ key's
			'activeRequestDataKeySubKey' => 'id',          // key (id)
			'dataType' => DatabaseServerDataType::$PrimaryKey,   // key data type
			'isRequired' => Constant::$REQUIRED              // Represents required field
		],
		[
			// Fetch value from function
			'column' => 'password',
			'activeRequestDataKey' => 'function',                       // function
			'activeRequestDataKeySubKey' => function($activeRequestData) {        // execute a function and return value
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
			// 'activeRequestDataKey' => 'variables', // to fetch values as per __VARIABLES__ key's
			'activeRequestDataKeySubKey' => 'id',                       // key (id)
			'dataType' => DatabaseServerDataType::$PrimaryKey,   // key data type
			'isRequired' => Constant::$REQUIRED              // Represents required field
		],
		[...]
	],

	// Last insert id to be made available as $activeRequestData['__INSERT-IDs__'][uniqueParamString];
	'__INSERT-IDs__' => '<keyName>:id',

	// mention primary key column name when using global counter
	'__PRIMARY-KEY__' => 'table_primary_column_name',

	// Indicator to generate JSON in Single(Object) record / Multiple(Array) rows format.
	'__MODE__' => 'singleRecordFormat/multipleRecordFormat',

	// subQuery is a keyword to perform recursive operations
	/** Supported configuration for recursive operations are :
	 * __SQL-COMMENT__,
	 * __QUERY__,
	 * __VARIABLES__,
	 * __SET__,
	 * __WHERE__,
	 * __MODE__,
	 * __SUB-QUERY__,
	 * __INSERT-IDs__,
	 * __TRIGGERS__,
	 * __PRE-SQL-HOOKS__,
	 * __POST-SQL-HOOKS__,
	 * __VALIDATE__,
	 * __PAYLOAD-TYPE__,
	 * __MAX-PAYLOAD-OBJECTS__,
	 */

	'__SUB-QUERY__' => [
		'<sub-key>' => [
			// Query to perform task
			'__QUERY__' => 'SQL',
			'__SQL-COMMENT__' => 'Comment prepended to query for monitoring queries in logs',
			'__VARIABLES__' => [
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
					// 'activeRequestDataKey' => 'variables', // to fetch values as per current module/<sub-key> __VARIABLES__ key's
					'activeRequestDataKeySubKey' => 'id',                       // key (id)
					'dataType' => DatabaseServerDataType::$PrimaryKey,   // key data type
					'isRequired' => Constant::$REQUIRED              // Represents required field
				],
				// Database DataTypes settings required when maintainHierarchy is true
				// to validate each data set before procedding forward
				[
					// Fetch value of last insert IDs
					'column' => 'id',
					'activeRequestDataKey' => '__INSERT-IDs__',                // userData from session
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
					'activeRequestDataKey' => 'sqlPayload',                    // sqlPayload (with maintainHierarchy)
					'activeRequestDataKeySubKey' => '<return:keys-separated-by-colon>'
				],
				[
					'column' => 'any-table- column',
					'activeRequestDataKey' => 'variables',      // custom
					'activeRequestDataKeySubKey' => 'sub-var1'   // returns static sub-var1 value set in __VARIABLES__ of current module/<sub-key>
				]
			],
			'__TRIGGERS__' => [...],
			'__PRE-SQL-HOOKS__' => [...],
			'__POST-SQL-HOOKS__' => [...],
			'__VALIDATE__' => [...],
			'__PAYLOAD-TYPE__' => 'Object/Array',
			'__MAX-PAYLOAD-OBJECTS__' => 'Integer',
			'__SUB-QUERY__' => [...],
		],
		'<sub-key>' => [
			[...]
		],
		[...]
	],

	// Trigger set of routes
	'__TRIGGERS__' => [// Array of triggers
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
					// 'activeRequestDataKey' => '__INSERT-IDs__', // Sql Insert IDs
					'activeRequestDataKeySubKey' => 'address'
				],
				[
					// Sql Insert IDs
					'activeRequestDataKey' => '__INSERT-IDs__',
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
					// 'activeRequestDataKey' => '__INSERT-IDs__', // Sql Insert IDs
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
					// 'activeRequestDataKey' => '__INSERT-IDs__', // Sql Insert IDs
					'activeRequestDataKeySubKey' => 'address'
				],
				[...]
			]
		]
		[...]
	],

	// Hook
	'__PRE-SQL-HOOKS__' => [// Array of Hook class name in exec order
		'Hook_Example1',
		'...'
	],
	'__POST-SQL-HOOKS__' => [// Array of Hook class name in exec order
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
	'__MAX-PAYLOAD-OBJECTS__' => 2, // Max number of allowed Objects if __PAYLOAD-TYPE__ is 'Array'

	'isTransaction' => Constant::$FALSE, // Flag to follow transaction Begin, Commit and rollback on error

	'maintainHierarchy' => Constant::$TRUE, // For DML

	// Rate Limiting Route access
	'rateLimitMaxRequest' => 1, // Allowed number of request in defined seconds window
	'rateLimitMaxRequestWindow' => 3600, // Seconds Window for restricting number of request

	// Maintain responseLag for window
	'responseLagWindow' => 3600,

	// Control response time as per number of hits by configuring lags in seconds as below
	'responseLag' => [
		// No of request => Seconds Lag
		10	=> 0,
		20	=> 10,
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

	// Any among below can be used for DML operations (These are Optional key's)
	// Caching
	'queryCacheKey' => '<unique-key-for-redis-to-cache-results>(e.g, key:1)', // Use cacheKey to cache and reuse results (Optional)
	'affectedQueryCacheKeyArray' => [ // List down key's which effects configured cacheKey on DML operation
		'<unique-key-for-redis-to-drop-cached-results>(key:1)',
		'<unique-key-for-redis-to-drop-cached-results>(category etc.)',
		'...'
	],

	// Data Representation
	'outputRepresentation' => 'XML', // JSON/XML/XSLT/HTML/PHP - Defaults to JSON

	// Respective Data Representation File (XSLT/HTML/PHP)
	'phpFile' => 'file-path',
	'htmlFile' => 'file-path',
	'xsltFile' => 'file-path',

	// Limiting duplicates
	'idempotentWindow' => 3, // Idempotent Window for DML operation (seconds)

	// Optional custom configuration to connect to master / slave Database
	'fetchDbMode' => 'Slave' // values - Master / Slave
];
```

- **Note**: 'maintainHierarchy' => Constant::$TRUE also includes 'maintainHierarchy' => Constant::$TRUE feature.

- If there are repeated modules or configurations; one can reuse them by palcing them in a separate file and including as below.

```PHP
'__SUB-QUERY__' => [
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
	'fetchDbMode' => 'Slave' // values - Master / Slave
];
```

## Available configuration options for Supplement

- Here one can configure and collect payload to perform customized operations

```PHP
//return represents root for sqlResults
return [
	// detail of data to perform task
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
			'activeRequestDataKeySubKey' => 'id',                       // key (id)
			'dataType' => DatabaseServerDataType::$PrimaryKey,   // key data type
			'isRequired' => Constant::$REQUIRED              // Represents required field
		],
		[...]
	],
	// subQuery is a keyword to perform recursive operations
	/** Supported configuration for recursive operations are :
	 * __PAYLOAD__,
	 * __SUB-PAYLOAD__,
	 * __TRIGGERS__,
	 * __PRE-SQL-HOOKS__,
	 * __POST-SQL-HOOKS__,
	 * __VALIDATE__,
	 * __PAYLOAD-TYPE__,
	 * __MAX-PAYLOAD-OBJECTS__,
	 */

	'__SUB-PAYLOAD__' => [
		'<sub-key>' => [
			// Payload to perform task
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
					'activeRequestDataKeySubKey' => 'id',                       // key (id)
					'dataType' => DatabaseServerDataType::$PrimaryKey,   // key data type
					'isRequired' => Constant::$REQUIRED              // Represents required field
				],
				// Database DataTypes settings required when maintainHierarchy is true
				// to validate each data set before procedding forward
				[	// Fetch values of params from previous queries
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
					'activeRequestDataKey' => 'sqlPayload',                    // sqlPayload (with maintainHierarchy)
					'activeRequestDataKeySubKey' => '<return:keys-separated-by-colon>'
				],
			],
			'__TRIGGERS__' => [...],
			'__PRE-SQL-HOOKS__' => [...],
			'__POST-SQL-HOOKS__' => [...],
			'__VALIDATE__' => [...],
			'__PAYLOAD-TYPE__' => 'Object/Array',
			'__MAX-PAYLOAD-OBJECTS__' => 'Integer',
			'__SUB-PAYLOAD__' => [...],
		],
		'<sub-key>' => [
			[...]
		],
		[...]
	],

	// Trigger set of routes
	'__TRIGGERS__' => [// Array of triggers
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
					'activeRequestDataKeySubKey' => 'address'
				],
				[...]
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
					'activeRequestDataKeySubKey' => 'address'
				],
				[...]
			]
		],
		[...]
	],

	// Hook
	'__PRE-SQL-HOOKS__' => [// Array of Hook class name in exec order
		'Hook_Example1',
		'...'
	],
	'__POST-SQL-HOOKS__' => [// Array of Hook class name in exec order
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
	'__MAX-PAYLOAD-OBJECTS__' => 2, // Max number of allowed Objects if __PAYLOAD-TYPE__ is 'Array'

	'isTransaction' => Constant::$FALSE, // Flag to follow transaction Begin, Commit and rollback on error

	'maintainHierarchy' => Constant::$TRUE, // For DML

	// Rate Limiting Route access
	'rateLimitMaxRequest' => 1, // Allowed number of request in defined seconds window
	'rateLimitMaxRequestWindow' => 3600, // Seconds Window for restricting number of request

	// Maintain responseLag for window
	'responseLagWindow' => 3600,

	// Control response time as per number of hits by configuring lags in seconds as below
	'responseLag' => [
		// No of request => Seconds Lag
		10	=> 0,
		20	=> 10,
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

	// Any among below can be used for DML operations (These are Optional key's)
	// Caching
	'queryCacheKey' => '<unique-key-for-redis-to-cache-results>(e.g, key:1)', // Use cacheKey to cache and reuse results (Optional)
	'affectedQueryCacheKeyArray' => [ // List down key's which effects configured cacheKey on DML operation
		'<unique-key-for-redis-to-drop-cached-results>(key:1)',
		'<unique-key-for-redis-to-drop-cached-results>(category etc.)',
		'...'
	],

	// Data Representation
	'outputRepresentation' => 'XML', // JSON/XML/XSLT/HTML/PHP - Defaults to JSON

	// Respective Data Representation File (XSLT/HTML/PHP)
	'phpFile' => 'file-path',
	'htmlFile' => 'file-path',
	'xsltFile' => 'file-path',

	// Limiting duplicates
	'idempotentWindow' => 3, // Idempotent Window for DML operation (seconds)

	// Optional custom configuration to connect to master / slave Database
	'fetchDbMode' => 'Slave' // values - Master / Slave
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
