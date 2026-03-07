# HTTP Request Payload

## GET Request

- [http://localhost/Microservices/public\_html/index.php?route=/reload](http://localhost/Microservices/public_html/index.php?route=/reload)

- [http://localhost/Microservices/public\_html/index.php?route=/tableName/1](http://localhost/Microservices/public_html/index.php?route=/tableName/1)

One can clean the URL by making the required changes in the web server .conf file.

## Pagination in GET Request

Requires **countQuery** SQL in the configuration for GET request
```ini
defaultPerpage=10
maxResultsPerPage=1000
```

- [http://localhost/Microservices/public\_html/index.php?route=/tableName?page=1](http://localhost/Microservices/public_html/index.php?route=/tableName/1?page=1)
- [http://localhost/Microservices/public\_html/index.php?route=/tableName?page=1&perpage=25](http://localhost/Microservices/public_html/index.php?route=/tableName/1?page=1&perpage=25)
- [http://localhost/Microservices/public\_html/index.php?route=/tableName?page=1&perpage=25&orderBy={"field1":"ASC","field2":"DESC"}](http://localhost/Microservices/public_html/index.php?route=/tableName/1?page=1&perpage=25&orderBy={"field1":"ASC","field2":"DESC"})

>One need to urlencode orderBy value

## POST, PUT, PATCH, and DELETE Request

- Single

```javascript
var payload = {
	"key1": "value1",
	"key2": "value2",
	...
};
```

- Multiple

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

## HttpRequest Variables

- **$session\['uDetails'\]** Session Data.
This remains same for every request and contains keys like id, group\_id, client\_id

- **$session\['routeParams'\]** Data passed in URI.
Suppose our configured route is **/{table:string}/{id:int}** and we make an HTTP request for **/tableName/1** then $session\['routeParams'\] will hold these dynamic values as below.

- **$session\['payload'\]** Request data.
For **GET** method, the **$\_GET** is the payload.

- **$session\['__INSERT-IDs__'\]** Insert ids Data as per configuration.
>For **POST/PUT/PATCH/DELETE** we perform both INSERT as well as UPDATE operation. The insertId contains the insert ids of the executed INSERT queries.

- **$session\['sqlResults'\]** Hierarchy data.
>For **GET** method, one can use previous query results if configured to use hierarchy.

## Hierarchy Configs

- Config/Queries/ClientDB/GET/Category.php
>In this file one can confirm how previous select data is used recursively in subQuery select as indicated by useHierarchy flag.

```PHP
[
	'column' => 'parent_id',
	'fetchFrom' => 'sqlResults',
	'fetchFromValue' => 'return:id'
],
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

## 🤝 Contributing

Issues and feature request are welcome.<br />
Feel free to share them on [issues page](https://github.com/polygoncoin/Microservices/issues)

## Author

- **Ramesh N. Jangid (Sharma)**

Github: [@polygoncoin](https://github.com/polygoncoin)

## 📝 License

Copyright © 2026 [Ramesh N. Jangid (Sharma)](https://github.com/polygoncoin).<br />
This project is [MIT](License) licensed.
