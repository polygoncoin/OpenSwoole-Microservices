# Javascript HTTP request example

## Login

```javascript
var handlerUrl = "http://127.0.0.1:9501?route=/login.json";
var xmlhttp = new XMLHttpRequest();

xmlhttp . open( "POST", handlerUrl );
xmlhttp . setRequestHeader('Content-type', 'text/plain; charset=utf-8');

xmlhttp . onreadystatechange = function() {
	if (this.readyState == 4 && this.status == 200) {
		var responseJson = this.responseText;
		var responseArray = JSON.parse(responseJson);
		console.log(responseArray);
		var token = responseArray['Output']['Results']['authId'];
		console.log(token);
	}
};

var payload = {
	"username":"customer_user_1",
	"password":"shames11"
};

xmlhttp . send( JSON.stringify(payload) );
```

## For other API's

- GET request

```javascript
var handlerUrl = "http://127.0.0.1:9501?route=/routes.json";
var xmlhttp = new XMLHttpRequest();

xmlhttp . open( "GET", handlerUrl );
xmlhttp . setRequestHeader('Content-type', 'text/plain; charset=utf-8');
xmlhttp . setRequestHeader('Authorization', 'Bearer <Token-from-login-api>');

xmlhttp . onreadystatechange = function() {
	if (this.readyState == 4 && this.status == 200) {
		var responseJson = this.responseText;
		var responseArray = JSON.parse(responseJson);
		console.log(responseArray);
	}
};

xmlhttp . send();
```

- POST request

```javascript
var handlerUrl = "http://127.0.0.1:9501?route=/ajax-handler-route.json";
var xmlhttp = new XMLHttpRequest();

xmlhttp . open( "POST", handlerUrl );
xmlhttp . setRequestHeader('Content-type', 'text/plain; charset=utf-8');
xmlhttp . setRequestHeader('Authorization', ‘Bearer <Token-from-login-api>');

xmlhttp . onreadystatechange = function() {
	if (this.readyState == 4 && this.status == 200) {
		var responseJson = this.responseText;
		var responseArray = JSON.parse(responseJson);
		console.log(responseArray);
	}
};

var payload = {
	"key1": "value1",
	"key2": "value2",
};

xmlhttp . send( JSON.stringify(payload) );
```

- PUT request

```javascript
var handlerUrl = "http://127.0.0.1:9501?route=/custom/password.json";
var xmlhttp = new XMLHttpRequest();

xmlhttp . open( "PUT", handlerUrl );
xmlhttp . setRequestHeader('Content-type', 'text/plain; charset=utf-8');
xmlhttp . setRequestHeader('Authorization', ‘Bearer <Token-from-login-api>');

xmlhttp . onreadystatechange = function() {
	if (this.readyState == 4 && this.status == 200) {
		var responseJson = this.responseText;
		var responseArray = JSON.parse(responseJson);
		console.log(responseArray);
	}
};

var payload = {
	"old_password": "shames11",
	"new_password": "ramesh",
};

xmlhttp . send( JSON.stringify(payload) );
```

- XML request example

```javascript
var handlerUrl = "http://127.0.0.1:9501?route=/registration-with-address.json";

var payload = '<?xml version="1.0" encoding="UTF-8" ?>' +
'<Payload>' +
'	<Records>' +
'		<Record>' +
'			<firstname>Ramesh-1</firstname>' +
'			<lastname>Jangid</lastname>' +
'			<email>ramesh@test.com</email>' +
'			<username>test</username>' +
'			<password>shames11</password>' +
'			<address>' +
'				<address>A-203</address>' +
'			</address>' +
'		</Record>' +
'		<Record>' +
'			<firstname>Ramesh-2</firstname>' +
'			<lastname>Jangid</lastname>' +
'			<email>ramesh@test.com</email>' +
'			<username>test</username>' +
'			<password>shames11</password>' +
'			<address>' +
'				<address>A-203</address>' +
'			</address>' +
'		</Record>' +
'	</Records>' +
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

## Contributing

Issues and feature request are welcome.<br />
Feel free to share them on [issues page](https://github.com/polygoncoin/Openswoole-Microservices/issues)

## Author

- **Ramesh N. Jangid (Sharma)**

Github: [@polygoncoin](https://github.com/polygoncoin)

## License

Copyright © 2026 [Ramesh N. Jangid (Sharma)](https://github.com/polygoncoin).<br />
This project is [MIT](License) licensed.
