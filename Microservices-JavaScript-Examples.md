# Javascript HTTP request example

## Login

```javascript
var handlerUrl = "http://127.0.0.1:9501??route=/login";
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

## For other API's

* GET Request

```javascript
var handlerUrl = "http://127.0.0.1:9501??route=/routes";
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
var handlerUrl = "http://127.0.0.1:9501??route=/ajax-handler-route";
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
var handlerUrl = "http://127.0.0.1:9501??route=/custom/password";
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
var handlerUrl = "http://127.0.0.1:9501??route=/registration-with-address&iRepresentation=XML&oRepresentation=XML";

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

## 🤝 Contributing

Issues and feature requests are welcome.<br />
Feel free to share them on [issues page](https://github.com/polygoncoin/Openswoole-Microservices/issues)

## Author

👤 **Ramesh N Jangid (Sharma)**

- Github: [@polygoncoin](https://github.com/polygoncoin)

## 📝 License

Copyright © 2026 [Ramesh N Jangid (Sharma)](https://github.com/polygoncoin).<br />
This project is [MIT](License) licensed.
