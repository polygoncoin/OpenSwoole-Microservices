# Openswoole based Low code API generator

This is a light & easy Openswoole based low code API generator using configuration arrays. It can be used to create APIs in a very short time once you are done with your database.

## Contents

- [Important Files](#important-files)
- [Environment File](#environment-file)
- [Folders](#folders)
- [Routes Folder](#routes-folder)
- [Params Data Types Configuration Rules](#params-data-types-configuration-rules)
- [SQL Configuration Rules](#sql-configuration-rules)
- [Security](#security)
- [HTTP Request](#http-request)
- [Hierarchy Data](#hierarchy-data)
- [Configuration Route](#configuration-route)
- [Database](#database)
- [Javascript HTTP request example](#javascript-http-request-example)
- [License](#license)

***

## Important Files

- **.env.example** Create a copy of this file as **.env**
- **Sql/global.sql** Import this SQL file on your **MySql global** instance
- **Sql/client\_master.sql** Import this SQL file on your **MySql client** instance
- **Sql/cache.sql** Import this SQL file for cache in **MySql cache** instance if Redis is not the choice (To be configured in .env)

> **Note**: One can import all three sql's in a single database to start with. Just configure the same details in the .env file.

## Environment File

Below are the configuration settings details in .env

```ini
ENVIRONMENT=0                   ;Environment PRODUCTION = 1 / DEVELOPMENT = 0
OUTPUT_PERFORMANCE_STATS=1      ;Add Performance Stats in JSON output: 1 = true / 0 = false
allowConfigRequest=1            ;Allow config request (global flag): 1 = true / 0 = false
cronRestrictedIp='127.0.0.1'    ;Crons Details
maxPerPage=10000                ;Maximum value of perPage (records per page)

;Data Representation: JSON/XML
;To override below setting pass below params with route separated with &
iRepresentation='JSON'
oRepresentation='JSON'
allowGetRepresentation=1
```

### Cache Server Details (Redis)
```ini
cacheType='Redis'
cacheHostname='127.0.0.1'
cachePort=6379
cacheUsername='ramesh'
cachePassword='shames11'
cacheDatabase=0
```

### Global Database details - global.sql
```ini
globalType='MySql'
globalHostname='127.0.0.1'
globalPort=3306
globalUsername='root'
globalPassword='shames11'
globalDatabase='global'
```

### global Database tables details
```ini
groups='m002_master_groups'
clients='m001_master_clients'
```

### Default database shared by most of the clients
```ini
defaultDbType='MySql'
defaultDbHostname='127.0.0.1'
defaultDbPort=3306
defaultDbUsername='root'
defaultDbPassword='shames11'
defaultDbDatabase='global'
```

### Example of seperate database for client 1 on Default database server
```ini
dbDatabaseClient001='client_001'
```

### Example of a dedicated database server for client 1
```ini
dbHostnameClient001='127.0.0.1'
dbUsernameClient001='root'
dbPasswordClient001='shames11'
dbDatabaseClient001='client_001'
```

### Additional table details for database server
```ini
clientMasterDbName='client_master'  ;contains all entities necessary for a new client.
client_users='master_users'         ;Table in client database containing user details.
```

These DB/Cache configurations can be set in below columns respectively for each client.
```SQL
`m001_master_clients`.`master_db_server_type` varchar(255) NOT NULL,
`m001_master_clients`.`master_db_hostname` varchar(255) NOT NULL,
`m001_master_clients`.`master_db_port` varchar(255) NOT NULL,
`m001_master_clients`.`master_db_username` varchar(255) NOT NULL,
`m001_master_clients`.`master_db_password` varchar(255) NOT NULL,
`m001_master_clients`.`master_db_database` varchar(255) NOT NULL,
`m001_master_clients`.`slave_db_server_type` varchar(255) NOT NULL,
`m001_master_clients`.`slave_db_hostname` varchar(255) NOT NULL,
`m001_master_clients`.`slave_db_port` varchar(255) NOT NULL,
`m001_master_clients`.`slave_db_username` varchar(255) NOT NULL,
`m001_master_clients`.`slave_db_password` varchar(255) NOT NULL,
`m001_master_clients`.`slave_db_database` varchar(255) NOT NULL,
`m001_master_clients`.`master_cache_server_type` varchar(255) NOT NULL,
`m001_master_clients`.`master_cache_hostname` varchar(255) NOT NULL,
`m001_master_clients`.`master_cache_port` varchar(255) NOT NULL,
`m001_master_clients`.`master_cache_username` varchar(255) NOT NULL,
`m001_master_clients`.`master_cache_password` varchar(255) NOT NULL,
`m001_master_clients`.`master_cache_database` varchar(255) NOT NULL,
`m001_master_clients`.`slave_cache_server_type` varchar(255) NOT NULL,
`m001_master_clients`.`slave_cache_hostname` varchar(255) NOT NULL,
`m001_master_clients`.`slave_cache_port` varchar(255) NOT NULL,
`m001_master_clients`.`slave_cache_username` varchar(255) NOT NULL,
`m001_master_clients`.`slave_cache_password` varchar(255) NOT NULL,
`m001_master_clients`.`slave_cache_database` varchar(255) NOT NULL,
```

### The Rate Limiting configurations can be set as below.

#### Cache server configuration for Rate Limiting
```ini
; ---- Rate Limit Server Details (Redis)
;used to save Rate Limiting related details
rateLimiterHost='127.0.0.1'     ; Redis host dealing with Rate limit
rateLimiterHostPort=6379        ; Redis host port
```

#### IP based Rate Limiting
```ini
rateLimiterIPMaxRequests=600    ; Max request allowed per IP
rateLimiterIPSecondsWindow=300  ; Window in seconds of Max request allowed per IP
rateLimiterIPPrefix='IPRL:'     ; Rate limit open traffic (not limited by allowed IPs/CIDR and allowed Rate Limits to users)
```

#### Client/Group/User based Rate Limiting
```ini
rateLimiterClientPrefix='CRL:'  ; Client based Rate Limitng (GRL) key prefix used in Redis
rateLimiterGroupPrefix='GRL:'   ; Group based Rate Limitng (GRL) key prefix used in Redis
rateLimiterUserPrefix='URL:'    ; User based Rate Limitng (URL) key prefix used in Redis
```

##### Configure these in tables below
```SQL
# Client level
`m001_master_clients`.`rateLimiterMaxRequests` int DEFAULT NULL,
`m001_master_clients`.`rateLimiterSecondsWindow` int DEFAULT NULL,

# Group level
`m002_master_groups`.`rateLimiterMaxRequests` int DEFAULT NULL,
`m002_master_groups`.`rateLimiterSecondsWindow` int DEFAULT NULL,

# User level
`master_users`.`rateLimiterMaxRequests` int DEFAULT NULL,
`master_users`.`rateLimiterSecondsWindow` int DEFAULT NULL,
```

#### Route based Rate Limiting
```ini
rateLimiterRoutePrefix='RRL:'   ; Route based Rate Limiting (RRL) key prefix used in Redis
```

##### Configure these in SQL configuration as below
```PHP
return [
    [...]
    'rateLimiterMaxRequests' => 1, // Allowed number of requests
    'rateLimiterSecondsWindow' => 3600, // Window in Seconds for allowed number of requests
    [...]
];
```

## Folders

- **App** Basic application folder
- **Config** Basic configuration folder
- **Dropbox** Folder for uploaded files.
- **Hooks** Hooks.
- **public\_html** Applicatipn doc root folder
- **Supplement/Crons** Contains classes for cron API's
- **Supplement/Custom** Contains classes for custom API's
- **Supplement/ThirdParty** Contains classes for third-party API's
- **Supplement/Upload** Contains classes for upload file API's
- **Validation** Contains validation classes.

## Routes Folder

- **/Config/Routes/Auth/&lt;GroupName&gt;**
- **/Config/Routes/Open**

**&lt;GroupName&gt;** is the group user belongs to for accessing the API's

### Files

- **/GETroutes.php** for all GET method routes configuration.
- **/POSTroutes.php** for all POST method routes configuration.
- **/PUTroutes.php** for all PUT method routes configuration.
- **/PATCHroutes.php** for all PATCH method routes configuration.
- **/DELETEroutes.php** for all DELETE method routes configuration.

### Routes Configuration Rules

* For configuring route **/tableName/parts** GET method
```PHP
return [
    'tableName' => [
        'parts' => [
            '__FILE__' => 'SQL file location'
        ]
    ]
];
```

* For configuring route **/tableName/{id}** where id is dynamic **int** value to be collected.
```PHP
return [
    'tableName' => [
        '{id:int}' => [
            '__FILE__' => 'SQL file location'
        ]
    ]
];
```

* Same dynamic variable but with a different data type, for e.g. **{id}** will be treated differently for **string** and **int** values to be collected.
```PHP
return [
    'tableName' => [
        '{id:int}' => [
            '__FILE__' => 'SQL file location for int data type'
        ],
        '{id:string}' => [
            '__FILE__' => 'SQL file location for string data type'
        ]
    ]
];
```

* To restrict dynamic values to a certain set of values. One can do the same by appending comma-separated values after OR key.
```PHP
return [
    '{tableName:string|admin, group, client, routes}' => [
        '{id:int}' => [
            '__FILE__' => 'SQL file location'
        ]
    ]
];
```

* On other side; to exclude dynamic values. One can do the same by prefixing NOT(!) synbol to comma-separated values.
```PHP
return [
    '{tableName:string}' => [
        '{id:int|!0}' => [
            '__FILE__' => 'SQL file location'
        ]
    ]
];
```

> Hooks
```PHP
return [
    '{tableName:string}' => [
        '__FILE__' => 'SQL file location',
        '__PRE-ROUTE-HOOKS__' => [// These will apply recursively
            'Hook_1',
            '...'
        ],
        '__POST-ROUTE-HOOKS__' => [// These will apply recursively
            'Hook_1',
            '...'
        ]
        '{id:int|!0}' => [
            '__FILE__' => 'SQL file location',
            '__PRE-ROUTE-HOOKS__' => [], // For noi hooks
            '__POST-ROUTE-HOOKS__' => [] // For noi hooks
        ],

        // Input Data Representation
        'iRepresentation' => 'XML' // JSON/XML - Defaults to JSON
    ]
];
```

> This '{id:int|!0}' means id is int but can't be zero.

## Queries Folder

- **/Config/Queries/Auth/GlobalDB** for global database.
- **/Config/Queries/Auth/ClientDB** for Clients (including all hosts and their databases).
- **/Config/Queries/Open** for Open to Web API's (No Authentication).

### Files

- **/GET/&lt;filenames&gt;.php** GET method SQL.
- **/POST/&lt;filenames&gt;;.php** POST method SQL.
- **/PUT/&lt;filenames&gt;.php** PUT method SQL.
- **/PATCH/&lt;filenames&gt;.php** PATCH method SQL.
- **/DELETE/&lt;filenames&gt;.php** DELETE method SQL.

> One can replace **&lt;filenames&gt;** tag with desired name as per functionality.

## Params Data Types Configuration Rules

### Database Field DataTypes Configuration in DatabaseDataTypes class

```PHP
public static $CustomINT = [

    // Required param

    // PHP data type (bool, int, float, string)
    'dataType' => 'int',

    // Optional params

    // Minimum value (int)
    'minValue' => false,
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

### SQL Configuration Rules

#### Available configuration options

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

    // Details of data to be set by Query to perform task
    '__SET__' => [
        [ // Fetch value from parsed route
            'column' => 'id',
            'fetchFrom' => 'uriParams',                     // uriParams / payload
            'fetchFromValue' => 'id',                       // key (id)
            'dataType' => DatabaseDataTypes::$PrimaryKey,   // key data type
            'required' => Constants::$REQUIRED              // Represents required field
        ],
        [ // Fetch value from payload
            'column' => 'id',
            'fetchFrom' => 'payload',                       // payload
            'fetchFromValue' => '<key>',                    // key (<key>)
        ],
        [ // Fetch value from function
            'column' => 'password',
            'fetchFrom' => 'function',                      // function
            'fetchFromValue' => function ($session) {        // execute a function and return value
                return 'value';
            }
        ],
        [ // Fetch value from uDetails session
            'column' => 'id',
            'fetchFrom' => 'uDetails',                   // uDetails from session
            'fetchFromValue' => 'id'                   // id Key
        ],
        [ // Fetch value of last insert ids
            'column' => 'is_deleted',
            'fetchFrom' => 'custom',                        // custom
            'fetchFromValue' => '<static-value>'            // Static values
        ]
    ],

    // Where clause of the Query to perform task
    '__WHERE__' => [
        [ // Fetch value from parsed route
            'column' => 'id',
            'fetchFrom' => 'uriParams',                     // uriParams / payload
            'fetchFromValue' => 'id',                       // key (id)
            'dataType' => DatabaseDataTypes::$PrimaryKey,   // key data type
            'required' => Constants::$REQUIRED              // Represents required field
        ],
        [ // Fetch value from payload
            'column' => 'id',
            'fetchFrom' => 'payload',                       // payload
            'fetchFromValue' => '<key>',                    // key (<key>)
        ],
        [ // Fetch value from function
            'column' => 'password',
            'fetchFrom' => 'function',                      // function
            'fetchFromValue' => function ($session) {        // execute a function and return value
                return 'value';
            }
        ],
        [ // Fetch value from uDetails session
            'column' => 'id',
            'fetchFrom' => 'uDetails',                   // uDetails from session
            'fetchFromValue' => 'id'                   // id Key
        ],
        [ // Fetch value of last insert ids
            'column' => 'is_deleted',
            'fetchFrom' => 'custom',                        // custom
            'fetchFromValue' => '<static-value>'            // Static values
        ]
    ],

    // Last insert id to be made available as $session['__INSERT-IDs__'][uniqueParamString];
    '__INSERT-IDs__' => '<keyName>:id',

    // Indicator to generate JSON in Single(Object) row / Multiple(Array) rows format.
    '__MODE__' => 'singleRowFormat/multipleRowFormat',

    // subQuery is a keyword to perform recursive operations
    /** Supported configuration for recursive operations are :
     * __SQL-COMMENT__,
     * __QUERY__,
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
            '__SET__/__WHERE__' => [
                [...]
                // Database DataTypes settings required when useHierarchy is true
                // to validate each data set before procedding forward
                [ // Fetch value of last insert ids
                    'column' => 'id',
                    'fetchFrom' => '__INSERT-IDs__',                // uDetails from session
                    'fetchFromValue' => '<saved-id-key>'            // previous Insert ids
                ],
                [ // Fetch values of params from previous queries
                    'column' => 'id',
                    'fetchFrom' => 'sqlParams',                     // sqlParams (with useHierarchy)
                    'fetchFromValue' => '<return:keys-separated-by-colon>'
                ],
                [ // Fetch values of sql results from previous queries
                    'column' => 'id',
                    'fetchFrom' => 'sqlResults',                    // sqlResults for DQL operations (with useResultSet)
                    'fetchFromValue' => '<return:keys-separated-by-colon>'
                ],
                [ // Fetch values of sql payload for previous queries
                    'column' => 'id',
                    'fetchFrom' => 'sqlPayload',                    // sqlPayload (with useHierarchy)
                    'fetchFromValue' => '<return:keys-separated-by-colon>'
                ],
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
                    'fetchFrom' => 'custom', 'fetchFromValue' => 'address'
                ],
                [
                    'fetchFrom' => '__INSERT-IDs__', 'fetchFromValue' => 'address:id'
                ]
            ],
            '__QUERY-STRING__' => [
                [
                    'column' => 'param-1', 'fetchFrom' => 'custom', 'fetchFromValue' => 'address'
                ],
                [
                    'column' => 'param-2', 'fetchFrom' => '__INSERT-IDs__', 'fetchFromValue' => 'address:id'
                ]
            ],
            '__METHOD__' => 'PATCH',
            '__PAYLOAD__' => [
                [
                    'column' => 'address',
                    'fetchFrom' => 'custom',
                    'fetchFromValue' => 'updated-address'
                ]
            ]
        ]
        [...]
    ],

    // Hooks
    '__PRE-SQL-HOOKS__' => [// Array of Hooks class name in exec order
        'Hook_Example1',
        '...'
    ],
    '__POST-SQL-HOOKS__' => [// Array of Hooks class name in exec order
        'Hook_Example2',
        '...'
    ],

    // Array of validation functions to be performed
    '__VALIDATE__' => [
        [
            'fn' => 'validateGroupId',
            'fnArgs' => [
                'id' => ['payload', 'id']
            ],
            'errorMessage' => 'Invalid Group Id'
        ],
        [...]
    ],

    '__PAYLOAD-TYPE__' => 'Object', // Allow single 'Object' / 'Array' of Object (if not set will accept both)
    '__MAX-PAYLOAD-OBJECTS__' => 2, // Max number of allowed Objects if __PAYLOAD-TYPE__ is 'Array'

    'isTransaction' => false, // Flag to follow transaction Begin, Commit and rollback on error

    'useHierarchy' => true, // For DML
    'useResultSet' => true, // For DQL

    // Rate Limiting Route access
    'rateLimitMaxRequests' => 1, // Allowed number of request in defined seconds window
    'rateLimitSecondsWindow' => 3600, // Seconds Window for restricting number of request

    // Control response time as per number of hits by configuring lags in seconds as below
    'responseLag' => [
        // No of Requests => Seconds Lag
        0 => 0,
        2 => 10,
    ],

    // Any among below can be used for DML operations (These are Optional keys)
    // Caching
    'cacheKey' => '<unique-key-for-redis-to-cache-results>(e.g, key:1)', // Use cacheKey to cache and reuse results (Optional)
    'affectedCacheKeys' => [ // List down keys which effects configured cacheKey on DML operation
        '<unique-key-for-redis-to-drop-cached-results>(key:1)',
        '<unique-key-for-redis-to-drop-cached-results>(category etc.)',
        '...'
    ],

    // Data Representation
    'oRepresentation' => 'XML', // JSON/XML - Defaults to JSON

    // Limiting duplicates
    'idempotentWindow' => 3 // Idempotent Window for DML operation (seconds)
];
```

> **Note**: 'useHierarchy' => true also includes 'useResultSet' => true feature.

> If there are repeated modules or configurations; one can reuse them by palcing them in a separate file and including as below.

```PHP
'__SUB-QUERY__' => [
    //Here the module1 properties are reused for write operation.
    'module1' => include $Constants::$DOC_ROOT . DIRECTORY_SEPARATOR . 'Config/Queries/ClientDB/Common/reusefilename.php',
]
```

> For POST, PUT, PATCH, and DELETE methods one can configure both INSERT as well as UPDATE queries if required for sub modules.

#### Available configuration options for Supplement

> Here one can configure and collect payload to perform customized operations (for Supplement folder)

```PHP
//return represents root for sqlResults
return [
    // Details of data to perform task
    '__PAYLOAD__' => [
        [ // Fetch value from parsed route
            'column' => 'id',
            'fetchFrom' => 'uriParams',                     // uriParams / payload
            'fetchFromValue' => 'id',                       // key (id)
            'dataType' => DatabaseDataTypes::$PrimaryKey,   // key data type
            'required' => Constants::$REQUIRED              // Represents required field
        ],
        [ // Fetch value from payload
            'column' => 'id',
            'fetchFrom' => 'payload',                       // payload
            'fetchFromValue' => '<key>',                    // key (<key>)
        ],
        [ // Fetch value from function
            'column' => 'password',
            'fetchFrom' => 'function',                      // function
            'fetchFromValue' => function ($session) {        // execute a function and return value
                return 'value';
            }
        ],
        [ // Fetch value from uDetails session
            'column' => 'id',
            'fetchFrom' => 'uDetails',                   // uDetails from session
            'fetchFromValue' => 'id'                   // id Key
        ],
        [ // Fetch value of last insert ids
            'column' => 'is_deleted',
            'fetchFrom' => 'custom',                        // custom
            'fetchFromValue' => '<static-value>'            // Static values
        ]
    ],
    '__FUNCTION__' => 'process',
    // subQuery is a keyword to perform recursive operations
    /** Supported configuration for recursive operations are :
     * __PAYLOAD__,
     * __FUNCTION__,
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
                [...]
                // Database DataTypes settings required when useHierarchy is true
                // to validate each data set before procedding forward
                [ // Fetch value of last insert ids
                    'column' => 'id',
                    'fetchFrom' => '__INSERT-IDs__',                // uDetails from session
                    'fetchFromValue' => '<saved-id-key>'            // previous Insert ids
                ],
                [ // Fetch values of params from previous queries
                    'column' => 'id',
                    'fetchFrom' => 'sqlParams',                     // sqlParams (with useHierarchy)
                    'fetchFromValue' => '<return:keys-separated-by-colon>'
                ],
                [ // Fetch values of sql results from previous queries
                    'column' => 'id',
                    'fetchFrom' => 'sqlResults',                    // sqlResults for DQL operations (with useResultSet)
                    'fetchFromValue' => '<return:keys-separated-by-colon>'
                ],
                [ // Fetch values of sql payload for previous queries
                    'column' => 'id',
                    'fetchFrom' => 'sqlPayload',                    // sqlPayload (with useHierarchy)
                    'fetchFromValue' => '<return:keys-separated-by-colon>'
                ],
            ],
            '__FUNCTION__' => 'subProcess',
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
                    'fetchFrom' => 'custom', 'fetchFromValue' => 'address'
                ],
                [
                    'fetchFrom' => '__INSERT-IDs__', 'fetchFromValue' => 'address:id'
                ]
            ],
            '__QUERY-STRING__' => [
                [
                    'column' => 'param-1', 'fetchFrom' => 'custom', 'fetchFromValue' => 'address'
                ],
                [
                    'column' => 'param-2', 'fetchFrom' => '__INSERT-IDs__', 'fetchFromValue' => 'address:id'
                ]
            ],
            '__METHOD__' => 'PATCH',
            '__PAYLOAD__' => [
                [
                    'column' => 'address',
                    'fetchFrom' => 'custom',
                    'fetchFromValue' => 'updated-address'
                ]
            ]
        ]
        [...]
    ],

    // Hooks
    '__PRE-SQL-HOOKS__' => [// Array of Hooks class name in exec order
        'Hook_Example1',
        '...'
    ],
    '__POST-SQL-HOOKS__' => [// Array of Hooks class name in exec order
        'Hook_Example2',
        '...'
    ],

    // Array of validation functions to be performed
    '__VALIDATE__' => [
        [
            'fn' => 'validateGroupId',
            'fnArgs' => [
                'id' => ['payload', 'id']
            ],
            'errorMessage' => 'Invalid Group Id'
        ],
        [...]
    ],

    '__PAYLOAD-TYPE__' => 'Object', // Allow single 'Object' / 'Array' of Object (if not set will accept both)
    '__MAX-PAYLOAD-OBJECTS__' => 2, // Max number of allowed Objects if __PAYLOAD-TYPE__ is 'Array'

    'isTransaction' => false, // Flag to follow transaction Begin, Commit and rollback on error

    'useHierarchy' => true, // For DML

    // Rate Limiting Route access
    'rateLimitMaxRequests' => 1, // Allowed number of request in defined seconds window
    'rateLimitSecondsWindow' => 3600, // Seconds Window for restricting number of request

    // Control response time as per number of hits by configuring lags in seconds as below
    'responseLag' => [
        // No of Requests => Seconds Lag
        0 => 0,
        2 => 10,
    ],

    // Any among below can be used for DML operations (These are Optional keys)
    // Caching
    'cacheKey' => '<unique-key-for-redis-to-cache-results>(e.g, key:1)', // Use cacheKey to cache and reuse results (Optional)
    'affectedCacheKeys' => [ // List down keys which effects configured cacheKey on DML operation
        '<unique-key-for-redis-to-drop-cached-results>(key:1)',
        '<unique-key-for-redis-to-drop-cached-results>(category etc.)',
        '...'
    ],

    // Data Representation
    'oRepresentation' => 'XML', // JSON/XML - Defaults to JSON

    // Limiting duplicates
    'idempotentWindow' => 3 // Idempotent Window for DML operation (seconds)
];
```

## Security

### Allowed IPs

Classless Inter-Domain Routing (CIDR) is a method for assigning IP addresses to devices on the internet. Multiple CIDR separated by comma can be set in (groups table) in **global** database.

```SQL
`m002_master_groups`.`allowed_ips` text
```

### Rate Limiting

One can configure Rate Limiting server details in **.env** file.

#### Rate Limit Server(Redis) Configuration in .env

```ini
rateLimiterHost='127.0.0.1'     ; Redis host dealing with Rate limit
rateLimiterHostPort=6379        ; Redis host port
rateLimiterIPMaxRequests=600    ; Max request allowed per IP
rateLimiterIPSecondsWindow=300  ; Window in seconds of Max request allowed per IP
rateLimiterIPPrefix='IPRL:'     ; IP based Rate Limitng (IPRL) key prefix used in Redis
rateLimiterClientPrefix='CRL:'  ; Client based Rate Limitng (GRL) key prefix used in Redis
rateLimiterGroupPrefix='GRL:'   ; Group based Rate Limitng (GRL) key prefix used in Redis
rateLimiterUserPrefix='URL:'    ; User based Rate Limitng (URL) key prefix used in Redis
rateLimiterRoutePrefix='RRL:'   ; Route based Rate Limiting (RRL) key prefix used in Redis
```

#### Rate Limit at group level (global database)

One can set these details for respective group in m002_master_groups table of global database

```SQL
`m002_master_groups`.`rateLimiterMaxRequests` int DEFAULT NULL
`m002_master_groups`.`rateLimiterSecondsWindow` int DEFAULT NULL
```

#### Rate Limit at user account level (client database)

One can set these details for respective user in master_users table of respective client database

```SQL
`master_users`.`rateLimiterMaxRequests` int DEFAULT NULL
`master_users`.`rateLimiterSecondsWindow` int DEFAULT NULL
```

> DEFAULT NULL represents "no restrictions"

## HTTP Request

### GET Request

- [http://127.0.0.1:9501?r=/reload](http://127.0.0.1:9501?r=/reload)

- [http://127.0.0.1:9501?r=/tableName/1](http://127.0.0.1:9501?r=/tableName/1)

> One can clean the URL by making the necessary changes in the web server .conf file.

### Pagination in GET Request

Requires **countQuery** SQL in the configuration for GET request
```ini
defaultPerPage=10
maxPerPage=1000
```

- [http://127.0.0.1:9501?r=/tableName?page=1](http://127.0.0.1:9501?r=/tableName/1?page=1)
- [http://127.0.0.1:9501?r=/tableName?page=1&perPage=25](http://127.0.0.1:9501?r=/tableName/1?page=1&perPage=25)
- [http://127.0.0.1:9501?r=/tableName?page=1&perPage=25&orderBy={"field1":"ASC","field2":"DESC"}](http://127.0.0.1:9501?r=/tableName/1?page=1&perPage=25&orderBy={"field1":"ASC","field2":"DESC"})

>One need to urlencode orderBy value

### POST, PUT, PATCH, and DELETE Request

* Single

```javascript
var payload = {
    "key1": "value1",
    "key2": "value2",
    ...
};
```

* Multiple

```javascript
var payload = [
    {
        "key1": "value1",
        "key2": "value2",
        ...
    },
    {
        "key1": "value1",
        "key2": "value2",
        ...
    },
    ...
];
```

### HttpRequest Variables

- **$sess\['userDetails'\]** Session Data.
> This remains same for every request and contains keys like id, group\_id, client\_id

- **$sess\['uriParams'\]** Data passed in URI.
> Suppose our configured route is **/{table:string}/{id:int}** and we make an HTTP request for **/tableName/1** then $sess\['uriParams'\] will hold these dynamic values as below.

- **$sess\['payload'\]** Request data.
> For **GET** method, the **$\_GET** is the payload.

* **$sess\['__INSERT-IDs__'\]** Insert ids Data as per configuration.
>For **POST/PUT/PATCH/DELETE** we perform both INSERT as well as UPDATE operation. The insertId contains the insert ids of the executed INSERT queries.

* **$sess\['sqlResults'\]** Hierarchy data.
>For **GET** method, one can use previous query results if configured to use hierarchy.

## Hierarchy Data

- Config/Queries/ClientDB/GET/Category.php
>In this file one can confirm how previous select data is used recursively in subQuery select as indicated by useHierarchy flag.

```PHP
'parent_id' => ['sqlResults', 'return:id'],
```

- Config/Queries/ClientDB/POST/Category.php .Here a request can handle the hierarchy for write operations.

```PHP
return [
    '__QUERY__' => 'INSERT INTO `category` SET __SET__",
    '__SET__' => [
        'name' => ['payload', 'name'],
        'parent_id' => ['custom', 0],
    ],
    '__INSERT-IDs__' => 'category:id',
    '__SUB-QUERY__' => [
        'module1' => [
            '__QUERY__' => 'INSERT INTO `category` SET __SET__",
            '__SET__' => [
                'name' => ['payload', 'subname'],
                'parent_id' => ['__INSERT-IDs__', 'category:id'],
            ],
            '__INSERT-IDs__' => 'sub:id',
        ]
    ],
    'useHierarchy' => true
];
```

### Hierarchy Request

- Request - 1: Single object.

```javascript
var payload = {
    "name":"name",
    "module1": {
        "subname":"subname",
    }
}
```

- Request - 2: Array of module1

```javascript
var payload = {
    "name":"name",
    "module1":
    [
        {
            "subname":"subname1",
        },
        {
            "subname":"subname2",
        },
        ...
    ]
}
```

- Request - 3: Array of payload and arrays of module1

```javascript
var payload = [
    {
        "name":"name1",
        "module1":
        [
            {
                "subname":"subname1",
            },
            {
                "subname":"subname2",
            },
            ...
        ]
    },
    {
        "name":"name2",
        "module1":
        [
            {
                "subname":"subname21",
            },
            {
                "subname":"subname22",
            },
            ...
        ]
    },
    ...
]
```

## Configuration Route

* Appending route with **/config** returns the payload information that should be supplied; both necessary and optional with desired format.

Examples:

- r=/registration/config
- r=/category/config

One need to enable same in .env file as below

```ini
allowConfigRequest=1
configRequestUriKeyword='config' ;for appending /config at end of URI
```

>For controlling globally there is a flag in env file labled **allowConfigRequest**

### r=/routes

This lists down all allowed routes for HTTP methods respectively.

## Database

- Dedicated database for respective client can be configured
- This can also handle Master / Slave implementaion respectively

### fetchFrom

- **fetchFrom** is a SQL config feature where one can force the fetch from Master (Since usually it is Slave)

## Javascript HTTP request example

### Login

````javascript
var handlerUrl = "http://127.0.0.1:9501?r=/login";
var xmlhttp = new XMLHttpRequest();

xmlhttp . open( "POST", handlerUrl);
xmlhttp . setRequestHeader('X-API-Version', 'v1.0.0');
xmlhttp . setRequestHeader('Content-type', 'application/x-www-form-urlencoded; charset=utf-8');

xmlhttp . onreadystatechange = function() {
    if (this.readyState == 4 && this.status == 200) {
        var responseJson = this.responseText;
        var responseArr = JSON.parse(responseJson);
        console.log(responseArr);
        var token = responseArr['Output']['Results']['Token'];
        console.log(token);
    }
};

// Payload data which is to be made available on the server for the "/ajax-handler".
var payload = {
    "username":"client_1_user_1",
    "password":"shames11"
};

var jsonString = JSON.stringify(payload);
var urlencodeJsonString = encodeURIComponent(jsonString);
var params = "Payload="+urlencodeJsonString;

xmlhttp . send( params);
````

### For other API's

* GET Request

````javascript
var handlerUrl = "http://127.0.0.1:9501?r=/routes";
var xmlhttp = new XMLHttpRequest();

xmlhttp . open( "GET", handlerUrl);
xmlhttp . setRequestHeader('X-API-Version', 'v1.0.0');
xmlhttp . setRequestHeader('Content-type', 'text/plain; charset=utf-8');
xmlhttp . setRequestHeader('Authorization', 'Bearer <Token-from-login-api>');

xmlhttp . onreadystatechange = function() {
    if (this.readyState == 4 && this.status == 200) {
        var responseJson = this.responseText;
        var responseArr = JSON.parse(responseJson);
        console.log(responseArr);
    }
};

xmlhttp . send();
````

* POST Request

````javascript
var handlerUrl = "http://127.0.0.1:9501?r=/ajax-handler-route";
var xmlhttp = new XMLHttpRequest();

xmlhttp . open( "POST", handlerUrl);
xmlhttp . setRequestHeader('X-API-Version', 'v1.0.0');
xmlhttp . setRequestHeader('Content-type', 'application/x-www-form-urlencoded; charset=utf-8');
xmlhttp . setRequestHeader('Authorization', ‘Bearer <Token-from-login-api>');

xmlhttp . onreadystatechange = function() {
    if (this.readyState == 4 && this.status == 200) {
        var responseJson = this.responseText;
        var responseArr = JSON.parse(responseJson);
        console.log(responseArr);
    }
};

// Payload data which is to be made available on the server for the "/ajax-handler".
var payload = {
    "key1": "value1",
    "key2": "value2",
};

var jsonString = JSON.stringify(payload);
var urlencodeJsonString = encodeURIComponent(jsonString);
var params = "Payload="+urlencodeJsonString;

xmlhttp . send( params);
````

* PUT Request

````javascript
var handlerUrl = "http://127.0.0.1:9501?r=/custom/password";
var xmlhttp = new XMLHttpRequest();

xmlhttp . open( "PUT", handlerUrl);
xmlhttp . setRequestHeader('X-API-Version', 'v1.0.0');
xmlhttp . setRequestHeader('Content-type', 'application/x-www-form-urlencoded; charset=utf-8');
xmlhttp . setRequestHeader('Authorization', ‘Bearer <Token-from-login-api>');

xmlhttp . onreadystatechange = function() {
    if (this.readyState == 4 && this.status == 200) {
        var responseJson = this.responseText;
        var responseArr = JSON.parse(responseJson);
        console.log(responseArr);
    }
};

// Payload data which is to be made available on the server for the "/ajax-handler".
var payload = {
    "old_password": "shames11",
    "new_password": "ramesh",
};

var jsonString = JSON.stringify(payload);
var urlencodeJsonString = encodeURIComponent(jsonString);
var params = "Payload="+urlencodeJsonString;

xmlhttp . send( params);
````

* XML Request example

```javascript
var handlerUrl = "http://127.0.0.1:9501?r=/registration-with-address&iRepresentation=XML&oRepresentation=XML";

var xmlPayload = '<?xml version="1.0" encoding="UTF-8" ?>' +
'<Payload>' +
'    <Rows>' +
'        <Row>' +
'            <firstname>Ramesh-1</firstname>' +
'            <lastname>Jangid</lastname>' +
'            <email>ramesh@test.com</email>' +
'            <username>test</username>' +
'            <password>shames11</password>' +
'            <address>' +
'                <address>A-203</address>' +
'            </address>' +
'        </Row>' +
'        <Row>' +
'            <firstname>Ramesh-2</firstname>' +
'            <lastname>Jangid</lastname>' +
'            <email>ramesh@test.com</email>' +
'            <username>test</username>' +
'            <password>shames11</password>' +
'            <address>' +
'                <address>A-203</address>' +
'            </address>' +
'        </Row>' +
'    </Rows>' +
'</Payload>';

var xmlhttp = new XMLHttpRequest();

xmlhttp . open( "POST", handlerUrl);
xmlhttp . setRequestHeader('X-API-Version', 'v1.0.0');
xmlhttp . setRequestHeader('Content-type', 'text/plain; charset=utf-8');

xmlhttp . onreadystatechange = function() {
    if (this.readyState == 4 && this.status == 200) {
        console.log(this.responseText);
    }
};

var urlencodeJsonString = encodeURIComponent(xmlPayload);
var payload = "Payload="+urlencodeJsonString;

xmlhttp . send( payload);
```

## License

[MIT](LICENSE)
