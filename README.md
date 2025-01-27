# OpenSwoole based Microservices

This is a light & easy Openswoole based Microservices framework. It can be used to create APIs in a very short time once you are done with your database.

## Contents

- [Important Files](#important-files)
- [Environment File](#environment-file)
- [Folders](#folders)
- [Routes Folder](#routes-folder)
- [Queries Folder](#queries-folder)
- [HTTP Request](#http-request)
- [Hierarchy Data](#hierarchy-data)
- [Configuration Route](#configuration-route)
- [Database](#database)
- [Javascript HTTP request example](#javascript-http-request-example)
- [License](#license)

***

## Important Files

- **.env.example** Create a copy of this file as **.env**
- **global.sql** Import this SQL file on your **MySql global** instance
- **client\_master.sql** Import this SQL file on your **MySql client** instance
- **cache.sql** Import this SQL file for cache in **MySql cache** instance if Redis is not the choice (To be configured in .env)

> **Note**: One can import all three sql's in a single database to start with. Just configure the same details in the .env file.

## Environment File

Below are the configuration settings details in .env

- ENVIRONMENT=0
>Environment PRODUCTION = 1 / DEVELOPMENT = 0

- OUTPUT_PERFORMANCE_STATS=1
>Add Performance Stats in Json output: 1 = true / 0 = false

- allowConfigRequest=1
>Allow config request (global flag): 1 = true / 0 = false

- cronRestrictedIp='127.0.0.1'
>Crons Details

- maxPerpage=10000
>Maximum value of perpage (records per page)

#### Cache Server Details (Redis)
- cacheType='Redis'
- cacheHostname='127.0.0.1'
- cachePort=6379
- cacheUsername='ramesh'
- cachePassword='shames11'
- cacheDatabase=0

#### Global Database details - global.sql
- globalType='MySql'
- globalHostname='127.0.0.1'
- globalPort=3306
- globalUsername='root'
- globalPassword='shames11'
- globalDatabase='global'

#### global Database tables details
- groups='m002_master_groups'
- clients='m001_master_clients'

#### Default application database/server for clients
- defaultDbType='MySql'
- defaultDbHostname='127.0.0.1'
- defaultDbPort=3306
- defaultDbUsername='root'
- defaultDbPassword='shames11'
- defaultDbDatabase='global'

####  Dedicated application database/server for client 1
- dbHostnameClient001='127.0.0.1'
- dbUsernameClient001='root'
- dbPasswordClient001='shames11'
- dbDatabaseClient001='client_001'

####  Dedicated application database/server for client 2
- dbHostnameClient002='127.0.0.1'
- dbUsernameClient002='root'
- dbPasswordClient002='shames11'
- dbDatabaseClient002='client_002'

#### Additional client database details
- clientMasterDbName='client_master'
>contains all entities required for a new client.

- client_users='master_users'
>Table in client database containing user details.

## Folders

- **App** Basic Microservices application folder
- **Config** Basic Microservices configuration folder
- **Crons** Contains classes for cron API's
- **Custom** Contains classes for custom API's
- **Dropbox** Folder for uploaded files.
- **public\_html** Microservices doc root folder
- **ThirdParty** Contains classes for third-party API's
- **Upload** Contains classes for upload file API's
- **Validation** Contains validation classes.

## Route Folder

- **/Config/Routes/&lt;GroupName&gt;**

### Files

- **/GETroutes.php** for all GET method routes configuration.
- **/POSTroutes.php** for all POST method routes configuration.
- **/PUTroutes.php** for all PUT method routes configuration.
- **/PATCHroutes.php** for all PATCH method routes configuration.
- **/DELETEroutes.php** for all DELETE method routes configuration.

**&lt;GroupName&gt;** assigned group to a user for accessing the API's

### Example

* For configuring route **/tableName/parts** GET method
```PHP
return [
    'tableName' => [
        'parts' => [
            '__file__' => 'SQL file location'
        ]
    ]
];
```
* For configuring route **/tableName/{id}** where id is dynamic **integer** value to be collected.
```PHP
return [
    'tableName' => [
        '{id:int}' => [
            '__file__' => 'SQL file location'
        ]
    ]
];
```
* Same dynamic variable but with a different data type, for e.g. **{id}** will be treated differently for **string** and **integer** values to be collected.
```PHP
return [
    'tableName' => [
        '{id:int}' => [
            '__file__' => 'SQL file location for integer data type'
        ],
        '{id:string}' => [
            '__file__' => 'SQL file location for string data type'
        ]
    ]
];
```
* To restrict dynamic values to a certain set of values. One can do the same by appending comma-separated values after OR key.
```PHP
return [
    '{tableName:string|admin,group,client,routes}' => [
        '{id:int}' => [
            '__file__' => 'SQL file location'
        ]
    ]
];
```

* On other side; to exclude dynamic values. One can do the same by prefixing NOT(!) synbol to comma-separated values.
```PHP
return [
    '{tableName:string}' => [
        '{id:int|!0}' => [
            '__file__' => 'SQL file location'
        ]
    ]
];
```
> This '{id:int|!0}' means id is integer but can't be zero.

## Queries Folder

- **/Config/Queries/GlobalDB** for global database.
- **/Config/Queries/ClientDB** for Clients (including all hosts and their databases).

### Files

- **/GET/&lt;filenames&gt;.php** GET method SQL.
- **/POST/&lt;filenames&gt;;.php** POST method SQL.
- **/PUT/&lt;filenames&gt;.php** PUT method SQL.
- **/PATCH/&lt;filenames&gt;.php** PATCH method SQL.
- **/DELETE/&lt;filenames&gt;.php** DELETE method SQL.

> One can replace **&lt;filenames&gt;** tag with desired name as per functionality.

### Example

####GET method.

* For Single row

```PHP
return [
    'query' => "SELECT * FROM TableName WHERE id = ? AND group_id = ? AND client_id = ?",
    '__WHERE__' => [
        //column => [uriParams|payload|function|userInfo|{custom}, key|{value}]
        'id' => ['uriParams', 'id'],
        'group_id' => ['payload', 'group_id'],
        'client_id' => ['userInfo', 'client_id'],
    ],
    'mode' => 'singleRowFormat'                // Single row is returned.
];
```
* For Multiple rows

```PHP
return [
    'query' => "SELECT * FROM TableName WHERE client_id = ?",
    '__WHERE__' => [
        //column => [uriParams|payload|function|userInfo|{custom}, key|{value}]
        'client_id' => ['userInfo', 'client_id'],
    ],
    'mode' => 'multipleRowFormat'            // Multiple rows are returned.
];
```

* For Mixed

```PHP
return [
    'query' => "SELECT * FROM TableName WHERE id = ? AND group_id = ? AND client_id = ?",
    '__WHERE__' => [
        //column => [uriParams|payload|function|userInfo|{custom}, key|{value}]
        'id' => ['uriParams', 'id'],
        'group_id' => ['payload', 'group_id'],
        'client_id' => ['userInfo', 'client_id'],
    ],
    'mode' => 'singleRowFormat',            // Single row is returned.
    'subQuery' => [
        'Clients' => [
            'query' => "MySql Query here",
            '__WHERE__' => [],
            'mode' => 'multipleRowFormat'    // Multiple rows are returned.
        ],
        ...
    ],
    'validate' => [
        [
            'fn' => 'validateGroupId',
            'fnArgs' => [
                'group_id' => ['payload', 'group_id']
            ],
            'errorMessage' => 'Invalid Group Id'
        ]
    ],
    ...
];
```
> Here **query & mode** keys are required keys

* For POST/PUT/PATCH/DELETE method.

```PHP
return [
    'query' => "INSERT TableName SET SET WHERE WHERE ",
    // Fields present in below __CONFIG__ shall be supported for DB operation. Both Required and Optional
    '__CONFIG__' => [
        // Set your payload/uriParams fields config here.
        ['payload', 'group_id', Constants::$REQUIRED],    // Required field
        ['payload', 'password'],                        // Optional field
        ['payload', 'client_id'],                        // Optional field
    ],
    '__SET__' => [
        //column => [uriParams|payload|userInfo|insertIdParams|{custom}|function, key|{value}|function($session)],
        'group_id' => ['payload', 'group_id'],
        'password' => ['function', function($session) {
            return password_hash($session['payload']['password'], PASSWORD_DEFAULT);
        }],
        'client_id' => ['userInfo', 'client_id']
    ],
    '__WHERE__' => [
        // column => [uriParams|payload|function|userInfo|insertIdParams|{custom}, key|{value}
        'id' => ['uriParams', 'id']
    ],
    // Last insert id to be made available as $session['insertIdParams'][uniqueParamString];
    'insertId' => '<keyName>:id',
    'subQuery' => [
        'module1' => [
            'query' => "MySql Query here",
            '__SET__' => [
                'previous_table_id' => ['insertIdParams', '<keyName>:id'],
            ],
            '__WHERE__' => [],
        ],
        ...
    ],
    'validate' => [
        [
            'fn' => 'validateGroupId',
            'fnArgs' => [
                'group_id' => ['payload', 'group_id']
            ],
            'errorMessage' => 'Invalid Group Id'
        ],
        ...
    ]
];
```
> **Note**: If there are modules or configurations repeated. One can reuse them by palcing them in a separate file and including as below.
```PHP
'subQuery' => [
    //Here the module1 properties are reused for write operation.
    'module1' => include DOC_ROOT . 'Config/Queries/ClientDB/Common/reusefilename.php',
]
```
> **Note**: For POST, PUT, PATCH, and DELETE methods we can configure both INSERT as well as UPDATE queries.

## HTTP Request

### GET Request

- [http://127.0.0.1:9501?r=/reload](http://127.0.0.1:9501?r=/reload)

- [http://127.0.0.1:9501?r=/tableName/1](http://127.0.0.1:9501?r=/tableName/1)

> One can clean the URL by making the required changes in the web server .conf file.

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

- **$session\['userInfo'\]** Session Data.
> This remains same for every request and contains keys like id, group\_id, client\_id

- **$session\['uriParams'\]** Data passed in URI.
> Suppose our configured route is **/{table:string}/{id:int}** and we make an HTTP request for **/tableName/1** then $session\['uriParams'\] will hold these dynamic values as below.

- **$session\['payload'\]** Request data.
> For **GET** method, the **$\_GET** is the payload.

* **$session\['insertIdParams'\]** Insert ids Data as per configuration.
>For **POST/PUT/PATCH/DELETE** we perform both INSERT as well as UPDATE operation. The insertIdParams contains the insert ids of the executed INSERT queries.

* **$session\['hierarchyData'\]** Hierarchy data.
>For **GET** method, one can use previous query results if configured to use hierarchy.

## Hierarchy Data

- Config/Queries/ClientDB/GET/Category.php
>In this file one can confirm how previous select data is used recursively in subQuery select as indicated by useHierarchy flag.

```PHP
'parent_id' => ['hierarchyData', 'return:id'],
```

- Config/Queries/ClientDB/POST/Category.php .Here a request can handle the hierarchy for write operations.

```PHP
return [
    'query' => "INSERT INTO `category` SET SET",
    '__CONFIG__' => [
        ['payload', 'name', Constants::$REQUIRED],
    ],
    '__SET__' => [
        'name' => ['payload', 'name'],
        'parent_id' => ['custom', 0],
    ],
    'insertId' => 'category:id',
    'subQuery' => [
        'module1' => [
            'query' => "INSERT INTO `category` SET SET",
            '__CONFIG__' => [
                ['payload', 'subname', Constants::$REQUIRED],
            ],
            '__SET__' => [
                'name' => ['payload', 'subname'],
                'parent_id' => ['insertIdParams', 'category:id'],
            ],
            'insertId' => 'sub:id',
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

* Appending route with **/config** returns the payload information that should be supplied; both required and optional with desired format.

Examples:

- r=/registration/config
- r=/category/config

One need to enable same in .env file as below

```
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

````
var handlerUrl = "http://127.0.0.1:9501?r=/login";
var xmlhttp = new XMLHttpRequest();

xmlhttp . open( "POST", handlerUrl );
xmlhttp . setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

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

xmlhttp . send( params );
````

### For other API's

* GET Request

````
  var handlerUrl = "http://127.0.0.1:9501?r=/routes";
  var xmlhttp = new XMLHttpRequest();

  xmlhttp . open( "GET", handlerUrl );
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

````
var handlerUrl = "http://127.0.0.1:9501?r=/ajax-handler-route";
var xmlhttp = new XMLHttpRequest();

xmlhttp . open( "POST", handlerUrl );
xmlhttp . setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
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

xmlhttp . send( params );
````

* PUT Request

````
var handlerUrl = "http://127.0.0.1:9501?r=/custom/password";
var xmlhttp = new XMLHttpRequest();

xmlhttp . open( "PUT", handlerUrl );
xmlhttp . setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
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

xmlhttp . send( params );
````

## License

[MIT](LICENSE)
