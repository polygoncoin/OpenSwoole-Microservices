# Test Cases

## Defining Test Cases in file

To start with one can create a new file eg. <some>Tests.php containing a sample code

```PHP
// $apiVersion = 'X-API-Version: v1.0.0';
$cacheControl = 'Cache-Control: no-cache';
// $contentType = 'Content-Type: application/x-www-form-urlencoded; charset=utf-8';
// $contentType = 'Content-Type: multipart/form-data; charset=utf-8';
$contentType = 'Content-Type: text/plain; charset=utf-8';

$curlFile = __DIR__ . '/category.csv';

$defaultHeaders = [];
// $defaultHeaders[] = $apiVersion;
$defaultHeaders[] = $cacheControl;

$response = [];

$homeURL = 'http://api.client001.localhost/Microservices/public_html/index.php';
```

Add, Comment or Uncomment depending on the requirement.

## Adding Test Cases

- GET Request

```PHP
$response[] = include GET . DIRECTORY_SEPARATOR . '<get-route-file-1>.php';
$response[] = include GET . DIRECTORY_SEPARATOR . '<get-route-file-2>.php';

$response[] = include POST . DIRECTORY_SEPARATOR . '<post-route-file-1>.php';
$response[] = include POST . DIRECTORY_SEPARATOR . '<post-route-file-2>.php';

$response[] = include PUT . DIRECTORY_SEPARATOR . '<put-route-file-1>.php';
$response[] = include PUT . DIRECTORY_SEPARATOR . '<put-route-file-2>.php';

$response[] = include PATCH . DIRECTORY_SEPARATOR . '<patch-route-file-1>.php';
$response[] = include PATCH . DIRECTORY_SEPARATOR . '<patch-route-file-2>.php';

$response[] = include DELETE . DIRECTORY_SEPARATOR . '<delete-route-file-1>.php';
$response[] = include DELETE . DIRECTORY_SEPARATOR . '<delete-route-file-2>.php';
```

- POST / PUT / PATCH / DELETE Request

These contain payload

```PHP
$payload = [
	'payload-var-1' => 'payload-val-1',
	'payload-var-2' => 'payload-val-2'
];
$response[] = include POST . DIRECTORY_SEPARATOR . '<dml-route-file>.php';
```

## Adding Test Cases route-file

Depending on HTTP method create a file &lt;get-route-file&gt;.php / &lt;dml-route-file&gt;.php in respective HTTP method folder. Or one can change the path (the same path needs to be configured in the above code)<br/>

- Sample of code a file may contain is as below

```PHP
$header = $defaultHeaders;
return Web::trigger(
	homeURL: $homeURL,
	method: 'GET',
	route: '/routes',
	header: $header,
	payload: ''
);
```

If the code requires token; this can be done as below

```PHP
$header = $defaultHeaders;
if (isset($token)) {
	$header[] = "Authorization: Bearer {$token}";

	return Web::trigger(
		homeURL: $homeURL,
		method: 'GET',
		route: '/routes',
		header: $header,
		payload: ''
	);
}
```

## Henceforth

One can add any number of files and respective route file entries.

To access these files one can define resepctive route and access them in browser.<br/>

index.php/?route=/&lt;some&gt; Tests (to be configured in index.php)

If you have multiple test cases files for the project and want to check for all of them; one can configure same in Tests.php and acceess via browser.

index.php/?route=/tests (configured in index.php)

## 🤝 Contributing

Issues and feature request are welcome.<br />
Feel free to share them on [issues page](https://github.com/polygoncoin/Microservices/issues)

## Author

- **Ramesh N. Jangid (Sharma)**

Github: [@polygoncoin](https://github.com/polygoncoin)

## 📝 License

Copyright © 2026 [Ramesh N. Jangid (Sharma)](https://github.com/polygoncoin).<br />
This project is [MIT](License) licensed.
