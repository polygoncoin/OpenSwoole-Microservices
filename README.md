# Openswoole based Low code API generator

This is a light & easy Openswoole based low code API generator using configuration arrays. It can be used to create APIs in a very short time once you are done with your database.

## Contents

- [Important Files](#important-files)
- [Environment File](#environment-file)
- [Folders](#folders)
- [Configuration Rules](#configuration-rules)
- [HTTP Request](#http-request)
- [Hierarchy Configs](#hierarchy-configs)
- [Special Routes](#special-routes)
- [Javascript HTTP request example](#javascript-http-request-example)
- [License](#license)

***

## Important Files

- **.env.example** Create a copy of this file as **.env**
- **.session.env.example** Create a copy of this file as **.session.env**
- **Sql/global.sql** Import this SQL file on your **MySql global** instance
- **Sql/client\_master.sql** Import this SQL file on your **MySql client** instance

> **Note**: One can import all three sql's in a single database to start with. Just configure the same details in the .env file.

## Environment File

Below are the configuration settings details in .env

```ini
ENVIRONMENT=0                       ;Environment PRODUCTION = 1 / DEVELOPMENT = 0
OUTPUT_PERFORMANCE_STATS=1          ;Add Performance Stats in JSON output: 1 = true / 0 = false
DISABLE_REQUESTS_VIA_PROXIES=1      ; 1 = true / 0 = false

; API authentication modes - Token / Session (Cookie based Sessions)
authMode='Token'
sessionMode='File'                  ; For Cookie based Session - 'File', 'MySql', 'PostgreSql', 'MongoDb', 'Redis', 'Memcached', 'Cookie'

; Allow particular route config request (global flag) - 1 = true / 0 = false
; Useful to get details of the payload for the API
enableConfigRequest=1
configRequestRouteKeyword='config' ; to append /config at the end of route

; Allow import CSV - 1 = true / 0 = false
enableImportRequest=1
importRequestRouteKeyword='import' ; to append /import at the end of route
importSampleRouteKeyword='import-sample'

; Data Representation: JSON/XML/HTML
iRepresentation='JSON'          ; JSON/XML - Input Data Representation
oRepresentation='JSON'          ; JSON/XML/HTML - Output Data Representation
enableRepresentationAsQueryParam=1        ; Allow iRepresentation / oRepresentation as GET query params
```

### Cache Server Details (Redis)
```ini
gCacheServerType='Redis'
gCacheServerHostname='127.0.0.1'
gCacheServerPort=6379
gCacheServerUsername=''
gCacheServerPassword=''
gCacheServerDatabase=0
```

### Global Database details - global.sql
```ini
gDbServerType='MySql'
gDbServerHostname='127.0.0.1'
gDbServerPort=3306
gDbServerUsername='username'
gDbServerPassword='password'
gDbServerDatabase='global'

; Tables
groupsTable='groups'
clientsTable='clients'
```

### Default database shared by most of the clients
```ini
cDbServerType='MySql'
cDbServerHostname='127.0.0.1'
cDbServerPort=3306
cDbServerUsername='username'
cDbServerPassword='password'
cDbServerDatabase='common'
```

### Example of seperate database for client 1 on Default database server
```ini
cDbServerDatabase001='client_001'
```

### Example of a dedicated database server for client 1
```ini
cDbServerHostname001='127.0.0.1'
cDbServerUsername001='username'
cDbServerPassword001='password'
cDbServerDatabase001='client_001'
```

### Additional table details for database server
```ini
masterDatabase='master'  ;contains all entities required for a new client.
clientUsersTable='users'         ;Table in client database containing user details.
```

These DB/Cache configurations can be set in below columns respectively for each client.
```SQL
`clients`.`master_db_server_type` varchar(255) NOT NULL,
`clients`.`master_db_hostname` varchar(255) NOT NULL,
`clients`.`master_db_port` varchar(255) NOT NULL,
`clients`.`master_db_username` varchar(255) NOT NULL,
`clients`.`master_db_password` varchar(255) NOT NULL,
`clients`.`master_db_database` varchar(255) NOT NULL,
`clients`.`master_query_placeholder` varchar(255) NOT NULL,
`clients`.`slave_db_server_type` varchar(255) NOT NULL,
`clients`.`slave_db_hostname` varchar(255) NOT NULL,
`clients`.`slave_db_port` varchar(255) NOT NULL,
`clients`.`slave_db_username` varchar(255) NOT NULL,
`clients`.`slave_db_password` varchar(255) NOT NULL,
`clients`.`slave_db_database` varchar(255) NOT NULL,
`clients`.`slave_query_placeholder` varchar(255) NOT NULL,
`clients`.`master_cache_server_type` varchar(255) NOT NULL,
`clients`.`master_cache_hostname` varchar(255) NOT NULL,
`clients`.`master_cache_port` varchar(255) NOT NULL,
`clients`.`master_cache_username` varchar(255) NOT NULL,
`clients`.`master_cache_password` varchar(255) NOT NULL,
`clients`.`master_cache_database` varchar(255) NOT NULL,
`clients`.`master_cache_table` varchar(255) NOT NULL,
`clients`.`slave_cache_server_type` varchar(255) NOT NULL,
`clients`.`slave_cache_hostname` varchar(255) NOT NULL,
`clients`.`slave_cache_port` varchar(255) NOT NULL,
`clients`.`slave_cache_username` varchar(255) NOT NULL,
`clients`.`slave_cache_password` varchar(255) NOT NULL,
`clients`.`slave_cache_database` varchar(255) NOT NULL,
`clients`.`slave_cache_table` varchar(255) NOT NULL,
```

### Allowed IPs

Classless Inter-Domain Routing (CIDR) is a method for assigning IP addresses to devices on the internet. Multiple CIDR separated by comma can be set in tables.

```SQL
# Client level
`clients`.`allowed_cidrs` text DEFAULT '0.0.0.0/0',

# Group level
`groups`.`allowed_cidrs` text DEFAULT '0.0.0.0/0',

# User level
`users`.`allowed_cidrs` text DEFAULT '0.0.0.0/0',
```

### The Rate Limiting configurations can be set as below.

```ini
; ---- Rate Limiting enable/disable Config
enableRateLimiting=1                ; 1 = true / 0 = false
enableRateLimitAtIpLevel=0          ; 1 = true / 0 = false
enableRateLimitAtClientLevel=0      ; 1 = true / 0 = false
enableRateLimitAtGroupLevel=0       ; 1 = true / 0 = false
enableRateLimitAtUserLevel=0        ; 1 = true / 0 = false
enableRateLimitAtRouteLevel=0       ; 1 = true / 0 = false
enableRateLimitAtUsersPerIpLevel=0  ; 1 = true / 0 = false
enableRateLimitAtUsersRequestLevel=0; 1 = true / 0 = false

; ---- Rate Limit Server Details (Redis)
; Supported Containers - Memcached / Redis without AUTH
rateLimitServerType='Memcached'     ; Redis/Memcached host dealing for Rate limit
rateLimitServerHostname='127.0.0.1' ; Redis host dealing with Rate limit
rateLimitServerPort=11211           ; Redis-6379 / Memcached-11211

; Rate Limiting Key Prefix
rateLimitIPPrefix='IPRL:'           ; Rate limit open traffic (not limited by allowed IPs/CIDR and allowed Rate Limits to users)
rateLimitClientPrefix='CRL:'        ; Client based Rate Limitng (GRL) key prefix used in Redis
rateLimitGroupPrefix='GRL:'         ; Group based Rate Limitng (GRL) key prefix used in Redis
rateLimitUserPrefix='URL:'          ; User based Rate Limitng (URL) key prefix used in Redis
rateLimitRoutePrefix='RRL:'         ; Route based Rate Limiting (RRL) key prefix used in Redis
rateLimitUsersPerIpPrefix='UIRL:'   ; User Per IP based Rate Limiting (UIRL) key prefix used in Redis
rateLimitUsersRequestPrefix='URRL:' ; User Per IP based Rate Limiting (UIRL) key prefix used in Redis

; Rate Limiting No. of Requests per IP ('IPRL:')
rateLimitIPMaxRequests=600          ; Max request allowed per IP
rateLimitIPSecondsWindow=300        ; Window in seconds of Max request allowed per IP

; Rate Limiting No. of User Per IP ('UIRL:')
rateLimitUsersPerIpMaxUsers=10      ; Max Users allowed per IP
rateLimitUsersPerIpSecondsWindow=300; Window in seconds of Max Users allowed per IP

; Rate Limiting No. of Requests per User ('URRL:')
; Delay Between Consecutive Requests (allow n requests only for seconds configured for each user)
rateLimitUsersMaxRequests=1         ; Max one request allowed for 10 seconds
rateLimitUsersMaxRequestsWindow=10  ; Max one request allowed for 10 seconds
```

#### Client/Group/User based Rate Limiting

##### Configure these in tables below
```SQL
# Client level
`clients`.`rateLimitMaxRequests` int DEFAULT NULL,
`clients`.`rateLimitSecondsWindow` int DEFAULT NULL,

# Group level
`groups`.`rateLimitMaxRequests` int DEFAULT NULL,
`groups`.`rateLimitSecondsWindow` int DEFAULT NULL,

# User level
`users`.`rateLimitMaxRequests` int DEFAULT NULL,
`users`.`rateLimitSecondsWindow` int DEFAULT NULL,
```

#### Route based Rate Limiting
```ini
rateLimitRoutePrefix='RRL:'   ; Route based Rate Limiting (RRL) key prefix used in Redis
```

##### Configure these in SQL configuration as below
```PHP
return [
    [...]
    'rateLimitMaxRequests' => 1, // Allowed number of requests
    'rateLimitSecondsWindow' => 3600, // Window in Seconds for allowed number of requests
    [...]
];
```

### For Cache hits configurations can be set as below.

```ini
; Supported Containers - Redis / Memcached / MySql / PostgreSql / MongoDb
sqlResultsCacheServerType='Redis'
sqlResultsCacheServerHostname='127.0.0.1'
sqlResultsCacheServerPort=6379
sqlResultsCacheServerUsername='username'
sqlResultsCacheServerPassword='password'
sqlResultsCacheServerDatabase=0
sqlResultsCacheServerTable='api_cache' ; For MySql / PostgreSql / MongoDb
```

## Folders

### Files Folder

- **Files** Folder for uploaded files.
- **Logs** Folder for application Logs.
- **Config** Basic configuration folder
- **Hooks** Hooks.
- **Supplement** Customised coding for APIs
- **Validation** Contains validation classes.

#### Supplement Folder

- **Crons** Contains classes for cron API's
- **Custom** Contains classes for custom API's
- **ThirdParty** Contains classes for third-party API's
- **Upload** Contains classes for upload file API's

### Routes Folder

#### Config/Routes

- **/Config/Routes/Auth/&lt;GroupName&gt;**
- **/Config/Routes/Open**

**&lt;GroupName&gt;** is the group user belongs to for accessing the API's

#### Files

- **/GETroutes.php** for all GET method routes configuration.
- **/POSTroutes.php** for all POST method routes configuration.
- **/PUTroutes.php** for all PUT method routes configuration.
- **/PATCHroutes.php** for all PATCH method routes configuration.
- **/DELETEroutes.php** for all DELETE method routes configuration.

#### Routes Configuration Rules

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

* For configuring route **/tableName/{id}** where id is dynamic **integer** value to be collected.
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

* Same dynamic variable but with a different data type, for e.g. **{id}** will be treated differently for **string** and **integer** values to be collected.
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

* To restrict dynamic values to a certain set of values. One can do the same by defining its data type.
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

> Hooks
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

> This '{id:int|!0}' means id is integer but can't be zero.

### Queries Folder

#### Config/Queries

- **/Config/Queries/Auth/GlobalDB** for global database.
- **/Config/Queries/Auth/ClientDB** for clients (including all hosts and their databases).
- **/Config/Queries/Open** for Open to Web API's (No Authentication).

#### Files

- **/GET/&lt;filenames&gt;.php** GET method SQL.
- **/POST/&lt;filenames&gt;;.php** POST method SQL.
- **/PUT/&lt;filenames&gt;.php** PUT method SQL.
- **/PATCH/&lt;filenames&gt;.php** PATCH method SQL.
- **/DELETE/&lt;filenames&gt;.php** DELETE method SQL.

> One can replace **&lt;filenames&gt;** tag with desired name as per functionality.

## Configuration Rules

### Defining Custom DataTypes

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
        [
            'column' => 'id',
            'fetchFrom' => 'routeParams', // Fetch value from parsed route
            // 'fetchFrom' => 'queryParams', // Fetch value from query string
            // 'fetchFrom' => 'payload', // Fetch value from payload
            // 'fetchFrom' => 'function', // Fetch value from function
            // 'fetchFrom' => 'cDetails', // Fetch value from client Details session
            // 'fetchFrom' => 'uDetails', // Fetch value from user Details session
            // 'fetchFrom' => 'custom', // Static values
            'fetchFromValue' => 'id',                       // key (id)
            'dataType' => DatabaseDataTypes::$PrimaryKey,   // key data type
            'required' => Constants::$REQUIRED              // Represents required field
        ],
        [ // Fetch value from function
            'column' => 'password',
            'fetchFrom' => 'function',                       // function
            'fetchFromValue' => function($session) {        // execute a function and return value
                return 'value';
            }
        ],
        [ // Fetch value of last insert ids
            'column' => 'is_deleted',
            'fetchFrom' => 'custom',                        // custom
            'fetchFromValue' => 'No'                        // Static values
        ]
    ],

    // Where clause of the Query to perform task
    '__WHERE__' => [
        [
            'column' => 'id',
            'fetchFrom' => 'routeParams', // Fetch value from parsed route
            // 'fetchFrom' => 'queryParams', // Fetch value from query string
            // 'fetchFrom' => 'payload', // Fetch value from payload
            // 'fetchFrom' => 'function', // Fetch value from function
            // 'fetchFrom' => 'cDetails', // Fetch value from client Details session
            // 'fetchFrom' => 'uDetails', // Fetch value from user Details session
            // 'fetchFrom' => 'custom', // Static values
            'fetchFromValue' => 'id',                       // key (id)
            'dataType' => DatabaseDataTypes::$PrimaryKey,   // key data type
            'required' => Constants::$REQUIRED              // Represents required field
        ],
        [...]
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
                [
                    'column' => 'id',
                    'fetchFrom' => 'routeParams', // Fetch value from parsed route
                    // 'fetchFrom' => 'queryParams', // Fetch value from query string
                    // 'fetchFrom' => 'payload', // Fetch value from payload
                    // 'fetchFrom' => 'function', // Fetch value from function
                    // 'fetchFrom' => 'cDetails', // Fetch value from client Details session
                    // 'fetchFrom' => 'uDetails', // Fetch value from user Details session
                    // 'fetchFrom' => 'custom', // Static values
                    'fetchFromValue' => 'id',                       // key (id)
                    'dataType' => DatabaseDataTypes::$PrimaryKey,   // key data type
                    'required' => Constants::$REQUIRED              // Represents required field
                ],
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
                    'fetchFrom' => 'routeParams', // Fetch value from parsed route
                    // 'fetchFrom' => 'queryParams', // Fetch value from query string
                    // 'fetchFrom' => 'payload', // Fetch value from payload
                    // 'fetchFrom' => 'function', // Fetch value from function
                    // 'fetchFrom' => 'cDetails', // Fetch value from client Details session
                    // 'fetchFrom' => 'uDetails', // Fetch value from user Details session
                    // 'fetchFrom' => 'custom', // Static values
                    // 'fetchFrom' => '__INSERT-IDs__', // Sql Insert Ids
                    'fetchFromValue' => 'address'
                ],
                [ // Sql Insert Ids
                    'fetchFrom' => '__INSERT-IDs__',
                    'fetchFromValue' => 'address:id'
                ]
            ],
            '__QUERY-STRING__' => [
                [
                    'column' => 'param-1',
                    'fetchFrom' => 'routeParams', // Fetch value from parsed route
                    // 'fetchFrom' => 'queryParams', // Fetch value from query string
                    // 'fetchFrom' => 'payload', // Fetch value from payload
                    // 'fetchFrom' => 'function', // Fetch value from function
                    // 'fetchFrom' => 'cDetails', // Fetch value from client Details session
                    // 'fetchFrom' => 'uDetails', // Fetch value from user Details session
                    // 'fetchFrom' => 'custom', // Static values
                    // 'fetchFrom' => '__INSERT-IDs__', // Sql Insert Ids
                    'fetchFromValue' => 'address'
                ],
                [...]
            ],
            '__METHOD__' => 'PATCH',
            '__PAYLOAD__' => [
                [
                    'column' => 'param-1',
                    'fetchFrom' => 'routeParams', // Fetch value from parsed route
                    // 'fetchFrom' => 'queryParams', // Fetch value from query string
                    // 'fetchFrom' => 'payload', // Fetch value from payload
                    // 'fetchFrom' => 'function', // Fetch value from function
                    // 'fetchFrom' => 'cDetails', // Fetch value from client Details
                    // 'fetchFrom' => 'uDetails', // Fetch value from user Details session
                    // 'fetchFrom' => 'custom', // Static values
                    // 'fetchFrom' => '__INSERT-IDs__', // Sql Insert Ids
                    'fetchFromValue' => 'address'
                ],
                [...]
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

#### Available configuration options for Download CSV

```PHP
return [
    // Query to perform task
    '__SQL-COMMENT__' => 'Comment prepended to query for monitoring queries in logs',
    '__DOWNLOAD__' => 'SELECT columns FROM TableName WHERE __WHERE__',
    // Where clause of the Query to perform task
    '__WHERE__' => [
        [
            'column' => 'id',
            'fetchFrom' => 'routeParams', // Fetch value from parsed route
            // 'fetchFrom' => 'queryParams', // Fetch value from query string
            // 'fetchFrom' => 'payload', // Fetch value from payload
            // 'fetchFrom' => 'function', // Fetch value from function
            // 'fetchFrom' => 'cDetails', // Fetch value from client Details
            // 'fetchFrom' => 'uDetails', // Fetch value from user Details session
            // 'fetchFrom' => 'custom', // Static values
            'fetchFromValue' => 'id',                       // key (id)
            'dataType' => DatabaseDataTypes::$PrimaryKey,   // key data type
            'required' => Constants::$REQUIRED              // Represents required field
        ],
        [...]
    ]
];
```

#### Available configuration options for Supplement

> Here one can configure and collect payload to perform customized operations

```PHP
//return represents root for sqlResults
return [
    // Details of data to perform task
    '__PAYLOAD__' => [
        [
            'column' => 'id',
            'fetchFrom' => 'routeParams', // Fetch value from parsed route
            // 'fetchFrom' => 'queryParams', // Fetch value from query string
            // 'fetchFrom' => 'payload', // Fetch value from payload
            // 'fetchFrom' => 'function', // Fetch value from function
            // 'fetchFrom' => 'cDetails', // Fetch value from client Details
            // 'fetchFrom' => 'uDetails', // Fetch value from user Details session
            // 'fetchFrom' => 'custom', // Static values
            'fetchFromValue' => 'id',                       // key (id)
            'dataType' => DatabaseDataTypes::$PrimaryKey,   // key data type
            'required' => Constants::$REQUIRED              // Represents required field
        ],
        [...]
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
                [
                    'column' => 'id',
                    'fetchFrom' => 'routeParams', // Fetch value from parsed route
                    // 'fetchFrom' => 'queryParams', // Fetch value from query string
                    // 'fetchFrom' => 'payload', // Fetch value from payload
                    // 'fetchFrom' => 'function', // Fetch value from function
                    // 'fetchFrom' => 'cDetails', // Fetch value from client Details
                    // 'fetchFrom' => 'uDetails', // Fetch value from user Details session
                    // 'fetchFrom' => 'custom', // Static values
                    'fetchFromValue' => 'id',                       // key (id)
                    'dataType' => DatabaseDataTypes::$PrimaryKey,   // key data type
                    'required' => Constants::$REQUIRED              // Represents required field
                ],
                // Database DataTypes settings required when useHierarchy is true
                // to validate each data set before procedding forward
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
                    'fetchFrom' => 'routeParams', // Fetch value from parsed route
                    // 'fetchFrom' => 'queryParams', // Fetch value from query string
                    // 'fetchFrom' => 'payload', // Fetch value from payload
                    // 'fetchFrom' => 'function', // Fetch value from function
                    // 'fetchFrom' => 'cDetails', // Fetch value from client Details
                    // 'fetchFrom' => 'uDetails', // Fetch value from user Details session
                    // 'fetchFrom' => 'custom', // Static values
                    'fetchFromValue' => 'address'
                ],
                [...]
            ],
            '__QUERY-STRING__' => [
                [
                    'column' => 'param-1',
                    'fetchFrom' => 'routeParams', // Fetch value from parsed route
                    // 'fetchFrom' => 'queryParams', // Fetch value from query string
                    // 'fetchFrom' => 'payload', // Fetch value from payload
                    // 'fetchFrom' => 'function', // Fetch value from function
                    // 'fetchFrom' => 'cDetails', // Fetch value from client Details
                    // 'fetchFrom' => 'uDetails', // Fetch value from user Details session
                    // 'fetchFrom' => 'custom', // Static values
                    'fetchFromValue' => 'address'
                ],
                [...]
            ],
            '__METHOD__' => 'PATCH',
            '__PAYLOAD__' => [
                [
                    'column' => 'param-1',
                    'fetchFrom' => 'routeParams', // Fetch value from parsed route
                    // 'fetchFrom' => 'queryParams', // Fetch value from query string
                    // 'fetchFrom' => 'payload', // Fetch value from payload
                    // 'fetchFrom' => 'function', // Fetch value from function
                    // 'fetchFrom' => 'cDetails', // Fetch value from client Details
                    // 'fetchFrom' => 'uDetails', // Fetch value from user Details session
                    // 'fetchFrom' => 'custom', // Static values
                    'fetchFromValue' => 'address'
                ],
                [...]
            ]
        ],
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

#### Database

- Dedicated database for respective client can be configured
- This can also handle Master / Slave implementaion respectively

###### fetchFrom

- **fetchFrom** is a SQL config feature where one can force the fetch from Master (Since usually it is Slave)

## HTTP Request

### GET Request

- [http://127.0.0.1:9501?route=/reload](http://127.0.0.1:9501?route=/reload)

- [http://127.0.0.1:9501?route=/tableName/1](http://127.0.0.1:9501?route=/tableName/1)

> One can clean the URL by making the necessary changes in the web server .conf file.

### Pagination in GET Request

Requires **countQuery** SQL in the configuration for GET request
```ini
defaultPerPage=10
maxResultsPerPage=1000
```

- [http://127.0.0.1:9501?route=/tableName?page=1](http://127.0.0.1:9501?route=/tableName/1?page=1)
- [http://127.0.0.1:9501?route=/tableName?page=1&perPage=25](http://127.0.0.1:9501?route=/tableName/1?page=1&perPage=25)
- [http://127.0.0.1:9501?route=/tableName?page=1&perPage=25&orderBy={"field1":"ASC","field2":"DESC"}](http://127.0.0.1:9501?route=/tableName/1?page=1&perPage=25&orderBy={"field1":"ASC","field2":"DESC"})

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

- **$sess\['uDetails'\]** Session Data.
> This remains same for every request and contains keys like id, group\_id, client\_id

- **$sess\['routeParams'\]** Data passed in URI.
> Suppose our configured route is **/{table:string}/{id:int}** and we make an HTTP request for **/tableName/1** then $sess\['routeParams'\] will hold these dynamic values as below.

- **$sess\['payload'\]** Request data.
> For **GET** method, the **$\_GET** is the payload.

* **$sess\['__INSERT-IDs__'\]** Insert ids Data as per configuration.
>For **POST/PUT/PATCH/DELETE** we perform both INSERT as well as UPDATE operation. The insertId contains the insert ids of the executed INSERT queries.

* **$sess\['sqlResults'\]** Hierarchy data.
>For **GET** method, one can use previous query results if configured to use hierarchy.

## Hierarchy Configs

- Config/Queries/ClientDB/GET/Category.php
>In this file one can confirm how previous select data is used recursively in subQuery select as indicated by useHierarchy flag.

```PHP
'parent_id' => ['sqlResults', 'return:id'],
```

- Config/Queries/ClientDB/POST/Category.php .Here a request can handle the hierarchy for write operations.

```PHP
return [
    '__QUERY__' => 'INSERT INTO `category` SET __SET__',
    '__SET__' => [
        'name' => ['payload', 'name'],
        'parent_id' => ['custom', 0],
    ],
    '__INSERT-IDs__' => 'category:id',
    '__SUB-QUERY__' => [
        'module1' => [
            '__QUERY__' => 'INSERT INTO `category` SET __SET__',
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
        "subname":"subname-value",
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

## Special Routes

* Appending route with **/config** returns the payload information that should be supplied; both necessary and optional with desired format.

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
importSampleRouteKeyword='import-sample'
```

## Javascript HTTP request example

### Login

```javascript
var handlerUrl = "http://127.0.0.1:9501?route=/login";
var xmlhttp = new XMLHttpRequest();

xmlhttp . open( "POST", handlerUrl );
xmlhttp . setRequestHeader('Content-type', 'text/plain; charset=utf-8');

xmlhttp . onreadystatechange = function() {
    if (this.readyState == 4 && this.status == 200) {
        var responseJson = this.responseText;
        var responseArr = JSON.parse(responseJson);
        console.log(responseArr);
        var token = responseArr['Output']['Results']['Token'];
        console.log(token);
    }
};

var payload = {
    "username":"client_1_user_1",
    "password":"shames11"
};

xmlhttp . send( JSON.stringify(payload) );
```

### For other API's

* GET Request

```javascript
var handlerUrl = "http://127.0.0.1:9501?route=/routes";
var xmlhttp = new XMLHttpRequest();

xmlhttp . open( "GET", handlerUrl );
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
```

* POST Request

```javascript
var handlerUrl = "http://127.0.0.1:9501?route=/ajax-handler-route";
var xmlhttp = new XMLHttpRequest();

xmlhttp . open( "POST", handlerUrl );
xmlhttp . setRequestHeader('Content-type', 'text/plain; charset=utf-8');
xmlhttp . setRequestHeader('Authorization', ‘Bearer <Token-from-login-api>');

xmlhttp . onreadystatechange = function() {
    if (this.readyState == 4 && this.status == 200) {
        var responseJson = this.responseText;
        var responseArr = JSON.parse(responseJson);
        console.log(responseArr);
    }
};

var payload = {
    "key1": "value1",
    "key2": "value2",
};

xmlhttp . send( JSON.stringify(payload) );
```

* PUT Request

```javascript
var handlerUrl = "http://127.0.0.1:9501?route=/custom/password";
var xmlhttp = new XMLHttpRequest();

xmlhttp . open( "PUT", handlerUrl );
xmlhttp . setRequestHeader('Content-type', 'text/plain; charset=utf-8');
xmlhttp . setRequestHeader('Authorization', ‘Bearer <Token-from-login-api>');

xmlhttp . onreadystatechange = function() {
    if (this.readyState == 4 && this.status == 200) {
        var responseJson = this.responseText;
        var responseArr = JSON.parse(responseJson);
        console.log(responseArr);
    }
};

var payload = {
    "old_password": "shames11",
    "new_password": "ramesh",
};

xmlhttp . send( JSON.stringify(payload) );
```

* XML Request example

```javascript
var handlerUrl = "http://127.0.0.1:9501?route=/registration-with-address&iRepresentation=XML&oRepresentation=XML";

var payload = '<?xml version="1.0" encoding="UTF-8" ?>' +
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

xmlhttp . open( "POST", handlerUrl );
xmlhttp . setRequestHeader('Content-type', 'text/plain; charset=utf-8');

xmlhttp . onreadystatechange = function() {
    if (this.readyState == 4 && this.status == 200) {
        console.log(this.responseText);
    }
};

xmlhttp . send( payload );
```

## License

[MIT](LICENSE)
