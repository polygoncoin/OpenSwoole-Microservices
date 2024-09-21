# OpenSwoole based Microservices
 
This is a light & easy Openswoole based Microservices framework. It can be used to create APIs in a very short time once you are done with your database.


## Important Files
 
**.env.example** Create a copy of this file as **.env**
 
**global.sql** Import this SQL file on your **MySQL global** instance
 
**client\_master.sql** Import this SQL file on your **MySQL client** instance
 
**cache.sql** Import this SQL file for cache in **MySQL cache** instance if Redis is not the choice (To be configured in .env)
 
**Start.php** Start your application with a command on your console

```
$ php Start.php
```

> **Note**: One can import all three sql's in a single database to start with. Just configure the same details in the .env file.
 

## .env.example
 
Below are the configuration settings details in .env
 

| Variable | Description | Value Example |
| -------- | ----------- | ------------- |
| defaultDbHostname | Default MySQL hostname | 127.0.0.1 |
| defaultDbUsername | Default MySQL username | root |
| defaultDbPassword | Default MySQL password | password |
| defaultDbDatabase | Default MySQL database | global |
| clientMasterDbName | Client Master Database | client\_master - Complete Database with least required data to replicate if client demand a separate database for his data. |
| dbHostnameClient001 | Client MySQL hostname | This can be the dedicated host domain / IP |
| dbUsernameClient001 | Client MySQL username | username |
| dbPasswordClient001 | Client MySQL password | password |
| dbDatabaseClient001 | Client MySQL database | client\_001 / as\_per\_your\_use |
|  | The Client details can be same as all Default MySQL settings also depending on situation |  |

## Folders
 
*    **App** Basic Microservices application folder
 
*    **Config** Basic Microservices configuration folder
 
*    **Crons** Contains classes for cron API's

*    **Custom** Contains classes for custom API's

*    **Dropbox** Folder for uploaded files.
 
*    **ThirdParty** Contains classes for third-party API's

*    **Upload** Contains classes for upload file API's
  
*    **Validation** Contains validation classes.
 

## Route
 

### Files
 
*    **Config/Routes/&lt;GroupName&gt;/GETroutes.php** for all GET method routes configuration.
 
*    **Config/Routes/&lt;GroupName&gt;/POSTroutes.php** for all POST method routes configuration.
 
*    **Config/Routes/&lt;GroupName&gt;/PUTroutes.php** for all PUT method routes configuration.
 
*    **Config/Routes/&lt;GroupName&gt;/PATCHroutes.php** for all PATCH method routes configuration.
 
*    **Config/Routes/&lt;GroupName&gt;/DELETEroutes.php** for all DELETE method routes configuration.
 
**&lt;GroupName&gt;** These are corresponding to the assigned group to a user for accessing the API's
 

### Example
 
*    For configuring route **/tableName/parts** GET method
 

    return [
      'tableName' => [
        'parts' => [
          '__file__' => 'SQL file location'
        ]
      ]
    ];

*    For configuring route **/tableName/{id}** where id is dynamic **integer** value to be collected.
 

    return [
      'tableName' => [
        '{id:int}' => [
          '__file__' => 'SQL file location'
        ]
      ]
    ];

*    Same dynamic variable but with a different data type, for e.g. **{id}** will be treated differently for **string** and **integer** values to be collected.
 

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

*    To restrict dynamic values to a certain set of values. One can do the same by appending comma-separated values after OR key.
 

    return [
      '{tableName:string|admin,group,client,routes}' => [
        '{id:int}' => [
          '__file__' => 'SQL file location'
        ]
      ]
    ];

## SQLs
 

### Folder
 
*    **Config/Queries/GlobalDB** for global database.
 
*    **Config/Queries/ClientDB** for Clients (including all hosts and their databases).
 

### Files - GlobalDB
 
*    **Config/Queries/GlobalDB/GET/&lt;filenames&gt;.php** GET method SQL.
 
*    **Config/Queries/GlobalDB/POST/&lt;filenames&gt;;.php** POST method SQL.
 
*    **Config/Queries/GlobalDB/PUT/&lt;filenames&gt;.php** PUT method SQL.
 
*    **Config/Queries/GlobalDB/PATCH/&lt;filenames&gt;.php** PATCH method SQL.
 
*    **Config/Queries/GlobalDB/DELETE/&lt;filenames&gt;.php** DELETE method SQL.
 

### Files - ClientDB
 
*    **Config/Queries/ClientDB/GET/&lt;filenames&gt;.php** GET method SQL.
 
*    **Config/Queries/ClientDB/POST/&lt;filenames&gt;.php** POST method SQL.
 
*    **Config/Queries/ClientDB/PUT/&lt;filenames&gt;.php** PUT method SQL.
 
*    **Config/Queries/ClientDB/PATCH/&lt;filenames&gt;.php** PATCH method SQL.
 
*    **Config/Queries/ClientDB/DELETE/&lt;filenames&gt;.php** DELETE method SQL.
 

> One can replace **&lt;filenames&gt;** tag with desired name as per functionality.
 

### Configuration
 

*    GET method.
 

    <?php
    return [
      'query' => "SELECT * FROM {$this->globalDB}.TableName WHERE id = ? AND group_id = ? AND client_id = ?",
      '__WHERE__' => [//column => [uriParams|payload|function|readOnlySession|{custom}, key|{value}]    
        'id' => ['uriParams', 'id'],
        'group_id' => ['payload', 'group_id'],
        'client_id' => ['readOnlySession', 'client_id']
      ],
      'mode' => 'singleRowFormat',// Single row returned.
      'subQuery' => [
        'Clients' => [
          'query' => "MySQL Query here",
          '__WHERE__' => [],
          'mode' => 'multipleRowFormat'// Multiple rows returned.
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

> Here **query & mode** keys are required keys
 

*    For POST/PUT/PATCH/DELETE method.
 

    <?php
    return [
      'query' => "INSERT {$this->globalDB}.TableName SET SET WHERE WHERE ",
      // Fields present in below __CONFIG__ shall be supported for DB operation. Both Required and Optional
      '__CONFIG__' => [// Set your payload/uriParams fields config here.
        ['payload', 'group_id', Constants::$REQUIRED], // Required field
        ['payload', 'password'], // Optional field
        ['payload', 'client_id'], // Optional field
      ],
      '__SET__' => [
        //column => [uriParams|payload|readOnlySession|insertIdParams|{custom}|function, key|{value}|function()],
        'group_id' => ['payload', 'group_id'],
        'password' => ['function', function() {
          return password_hash(httpRequest->input['payload']['password'], PASSWORD_DEFAULT);
        }],
        'client_id' => ['readOnlySession', 'client_id']
      ],
      '__WHERE__' => [// column => [uriParams|payload|function|readOnlySession|insertIdParams|{custom}, key|{value}
        'id' => ['uriParams', 'id']
      ],
      'insertId' => 'tablename1:id',// Last insert id key name in $input['insertIdParams'][<tableName>:id];
      'subQuery' => [
        'module1' => [
          'query' => "MySQL Query here",
          '__SET__' => [
            'previous_table_id' => ['insertIdParams', '<tableName>:id'],
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

> **Note**: If there are modules or configurations repeated. One can reuse them by palcing them in a separate file and including as below.
 

      'subQuery' => [
        //Here the module1 properties are reused for write operation.
        'module1' => include DOC_ROOT . 'Config/Queries/ClientDB/Common/reusefilename.php',
      ]

> **Note**: For POST, PUT, PATCH, and DELETE methods we can configure both INSERT as well as UPDATE queries.
 

## HTTP Request
 

### GET Request
 
*    [http://localhost:9501?r=/reload](http://localhost:9501?r=/reload) 
 
*    [http://localhost:9501?r=/tableName/1](http://localhost:9501?r=/tableName/1) 
 
> One can clean the URL by making the required changes in the web server .conf file.
 

### POST, PUT, PATCH, and DELETE Request
 

*   Single Payload
     
```
      {
        "key1": "value1",
        "key2": "value2",
        ...
      }
```
*   Multiple
     
```
      [
        {
          "key1": "value1",
          "key2": "value2",
          ...
        },
        {
          "key1": "value21",
          "key2": "value22",
          ...
        }
        ...
      ]
```
## Variables
 
*    **httpRequest->input\['readOnlySession'\]** Session Data.
 
This remains same for every request and contains keys like id, group\_id, client\_id
 
*    **httpRequest->input\['uriParams'\]** Data passed in URI.
 
Suppose our configured route is **/{table:string}/{id:int}** and we make an HTTP request for **/tableName/1** then $input\['uriParams'\] will hold these dynamic values as below.
 
*    **httpRequest->input\['payload'\]** Request data.
 
For **GET** method, the **$\_GET** is the payload.
 
*    **httpRequest->input\['insertIdParams'\]** Insert ids Data as per configuration.
 
For **POST/PUT/PATCH/DELETE** we perform both INSERT as well as UPDATE operation. The insertIdParams contains the insert ids of the executed INSERT queries.

*    **httpRequest->input\['hierarchyData'\]** Hierarchy data.
 
For **GET** method, one can use previous query results if configured to use hierarchy.
 

## Hierarchy
 

*   Config/Queries/ClientDB/GET/Category.php
     

In this file one can confirm how previous select data is used recursively in subQuery select as indicated by useHierarchy flag.
 
```
    'parent_id' => ['hierarchyData', 'return:id'],
```
*   Config/Queries/ClientDB/POST/Category.php .Here a request can handle the hierarchy for write operations.
     
```
        // Configuration
        return [
          'query' => "INSERT INTO {$this->clientDB}.`category` SET SET",
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
                'query' => "INSERT INTO {$this->clientDB}.`category` SET SET",
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

*   Request - 1: Single object.
     
```
      {
        "name":"name",
        "module1":{
          "subname":"subname",
        }
      }
```
*   Request - 2: Array of module1
     
```
      {
        "name":"name",
        "module1":[
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
*   Request - 3: Array of payload and arrays of module1
     
```
      [
        {
          "name":"name1",
          "module1":[
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
          "module1":[
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
## Route ending with /config
 
*    Adding keyword **config** at the end of route after a slash returns the payload information that should be supplied; both required and optional with desired format.
 
Examples:
 
*    r=/registration/config
 
*    r=/category/config
 
One need to configure for same in route with a flag as **config => true**
 
Only these configured routes will be supported the config feature.
 
*    For controlling globally there is a flag in env file labled **allowConfigRequest**
 

### r=/routes
 
This lists down all allowed routes for HTTP methods respectively.


### r=/check
 
Perform basic checks on Config folder.


## Javascript - HTTP request example

### Login
```
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
var Payload = {
    "username":"client_1_user_1",
    "password":"shames11"
};

var jsonString = JSON.stringify(Payload);
var urlencodeJsonString = encodeURIComponent(jsonString);
var params = "Payload="+urlencodeJsonString;

xmlhttp . send( params );
```

### For other API's

- GET Request
```
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

```

- POST Request
```
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
var Payload = {
    "key1": "value1",
    "key2": "value2",
};

var jsonString = JSON.stringify(Payload);
var urlencodeJsonString = encodeURIComponent(jsonString);
var params = "Payload="+urlencodeJsonString;

xmlhttp . send( params );
```

- PUT Request
```
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
var Payload = {
    "old_password": "shames11",
    "new_password": "ramesh",
};

var jsonString = JSON.stringify(Payload);
var urlencodeJsonString = encodeURIComponent(jsonString);
var params = "Payload="+urlencodeJsonString;

xmlhttp . send( params );
```