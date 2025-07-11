<?php
/**
 * File to conduct test on editing Configs or code
 * php version 8.3
 *
 * @category  Tests
 * @package   Openswoole_Microservices
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Openswoole-Microservices
 * @since     Class available since Release 1.0.0
 */
if (!function_exists(function: 'genXmlPayload')) {
    /**
     * Defining function genXmlPayload
     * to generate Xml for use as payload
     *
     * @param mixed  $params   Payload whose XML format is requested
     * @param string $payload  Reference to which generated XML is appended
     * @param bool   $rowsFlag true or false
     *
     * @return void
     */
    function genXmlPayload(&$params, &$payload, $rowsFlag = false): void
    {
        if (empty($params)) {
            return;
        }

        $rows = false;
        $isAssoc = (isset($params[0])) ? false : true;
        if (!$isAssoc && count(value: $params) === 1) {
            $params = $params[0];
            if (empty($params)) {
                return;
            }
            $isAssoc = true;
        }
        if (!$isAssoc) {
            $payload .= "<Rows>";
            $rows = true;
        }
        if ($rowsFlag) {
            $payload .= "<Row>";
        }
        foreach ($params as $key => &$value) {
            if ($isAssoc) {
                $payload .= "<{$key}>";
            }
            if (is_array(value: $value)) {
                genXmlPayload(params: $value, payload: $payload, rowsFlag: $rows);
            } else {
                $payload .= htmlspecialchars(string: $value);
            }
            if ($isAssoc) {
                $payload .= "</{$key}>";
            }
        }
        if ($rowsFlag) {
            $payload .= "</Row>";
        }
        if (!$isAssoc) {
            $payload .= "</Rows>";
        }
    }
}

if (!function_exists(function: '_httpParseHeaders')) {
    /**
     * Converts raw headers in array format
     *
     * @param string $rawHeaders Raw headers from cURL response
     *
     * @return array
     */
    function _httpParseHeaders($rawHeaders): array
    {
        $headers = array();
        $key = '';

        foreach (explode(separator: "\n", string: $rawHeaders) as $i => $h) {
            $h = explode(separator: ':', string: $h, limit: 2);

            if (isset($h[1])) {
                if (!isset($headers[$h[0]])) {
                    $headers[$h[0]] = trim(string: $h[1]);
                } elseif (is_array(value: $headers[$h[0]])) {
                    $headers[$h[0]] = array_merge(
                        $headers[$h[0]],
                        array(trim(string: $h[1]))
                    );
                } else {
                    $headers[$h[0]] = array_merge(
                        array($headers[$h[0]]),
                        array(trim(string: $h[1]))
                    );
                }

                $key = $h[0];
            } else {
                if (substr(string: $h[0], offset: 0, length: 1) == "\t") {
                    $headers[$key] .= "\r\n\t".trim(string: $h[0]);
                } elseif (!$key) {
                    $headers[0] = trim(string: $h[0]);
                }
            }
        }

        return $headers;
    }
}

if (!function_exists(function: 'getCurlConfig')) {
    /**
     * Function to get cURL configuration as per params
     *
     * @param string $method  HTTP method
     * @param string $route   Route
     * @param array  $header  HTTP Headers
     * @param string $payload Payload
     *
     * @return array
     */
    function getCurlConfig($method, $route, $header = [], $payload = ''): array
    {
        $curlConfig[CURLOPT_URL] = $route;
        $curlConfig[CURLOPT_HTTPHEADER] = $header;
        $curlConfig[CURLOPT_HTTPHEADER][] = "x-api-version: v1.0.0";
        $curlConfig[CURLOPT_HTTPHEADER][] = "Cache-Control: no-cache";
        $curlConfig[CURLOPT_HEADER] = 1;

        $payload = http_build_query(
            data: [
            "Payload" => $payload
            ]
        );

        $contentType = [
            'Content-Type: text/plain; charset=utf-8',
            'Content-Type: application/x-www-form-urlencoded; charset=utf-8',
        ];

        switch ($method) {
        case 'GET':
            $curlConfig[CURLOPT_HTTPHEADER][] = $contentType[0];
            break;
        case 'POST':
            $curlConfig[CURLOPT_HTTPHEADER][] = $contentType[1];
            $curlConfig[CURLOPT_POST] = true;
            $curlConfig[CURLOPT_POSTFIELDS] = $payload;
            break;
        case 'PUT':
            $curlConfig[CURLOPT_HTTPHEADER][] = $contentType[1];
            $curlConfig[CURLOPT_CUSTOMREQUEST] = 'PUT';
            $curlConfig[CURLOPT_POSTFIELDS] = $payload;
            break;
        case 'PATCH':
            $curlConfig[CURLOPT_HTTPHEADER][] = $contentType[1];
            $curlConfig[CURLOPT_CUSTOMREQUEST] = 'PATCH';
            $curlConfig[CURLOPT_POSTFIELDS] = $payload;
            break;
        case 'DELETE':
            $curlConfig[CURLOPT_HTTPHEADER][] = $contentType[1];
            $curlConfig[CURLOPT_CUSTOMREQUEST] = 'DELETE';
            $curlConfig[CURLOPT_POSTFIELDS] = $payload;
            break;
        }
        $curlConfig[CURLOPT_RETURNTRANSFER] = true;

        return $curlConfig;
    }
}

if (!function_exists(function: 'trigger')) {
    /**
     * Triggers cURL request
     *
     * @param string $method  HTTP method
     * @param string $route   Route
     * @param array  $header  HTTP Headers
     * @param string $payload Payload
     *
     * @return array
     */
    function trigger($method, $route, $header = [], $payload = ''): array
    {
        $homeURL = 'http://127.0.0.1:9501';
        $route = "{$homeURL}?r={$route}";

        $curl = curl_init();
        $curlConfig = getCurlConfig(
            method: $method,
            route: $route,
            header: $header,
            payload: $payload
        );
        curl_setopt_array(handle: $curl, options: $curlConfig);
        $curlResponse = curl_exec(handle: $curl);

        $responseHttpCode = curl_getinfo(
            handle: $curl,
            option: CURLINFO_HTTP_CODE
        );
        $responseContentType = curl_getinfo(
            handle: $curl,
            option: CURLINFO_CONTENT_TYPE
        );

        $headerSize = curl_getinfo(handle: $curl, option: CURLINFO_HEADER_SIZE);
        $responseHeaders = _httpParseHeaders(
            rawHeaders: substr(
                string: $curlResponse,
                offset: 0,
                length: $headerSize
            )
        );
        $responseBody = substr(string: $curlResponse, offset: $headerSize);

        $error = curl_error(handle: $curl);
        curl_close(handle: $curl);

        if ($error) {
            $response = 'cURL Error #:' . $error;
        } else {
            $response = $responseBody;
        }

        return [
            'route' => $route,
            'httpMethod' => $method,
            'requestHeaders' => $curlConfig[CURLOPT_HTTPHEADER],
            'requestPayload' => $payload,
            'responseHttpCode' => $responseHttpCode,
            'responseHeaders' => $responseHeaders,
            'responseContentType' => $responseContentType,
            'responseBody' => $response
        ];
    }
}

if (!function_exists(function: 'processAuth')) {
    /**
     * Process Auth based requests
     *
     * @return string
     */
    function processAuth(): string
    {
        $response = [];

        $response[] = trigger(
            method: 'GET',
            route: '/reload',
            header: [],
            payload: ''
        );

        // Client User
        $params = [
            'username' => 'client_1_group_1_user_1',
            'password' => 'shames11'
        ];
        $res = trigger(
            method: 'POST',
            route: '/login',
            header: [],
            payload: json_encode(value: $params)
        );
        if ($res) {
            $response[] = $res;
            $arr = json_decode(
                json: $res['responseBody'],
                associative: true
            );
            $token = $arr['Results']['Token'];
            $header = ["Authorization: Bearer {$token}"];

            $response[] = trigger(
                method: 'GET',
                route: '/routes',
                header: $header,
                payload: ''
            );

            $params = [
                [
                    'name' => 'ramesh0',
                    'sub' => [
                        'subname' => 'ramesh1',
                        'subsub' => [
                            [
                                'subsubname' => 'ramesh'
                            ],
                            [
                                'subsubname' => 'ramesh'
                            ]
                        ]
                    ]
                ],
                [
                    'name' => 'ramesh1',
                    'sub' => [
                        'subname' => 'ramesh1',
                        'subsub' => [
                            'subsubname' => 'ramesh'
                        ]
                    ]
                ]
            ];
            $response[] = trigger(
                method: 'POST',
                route: '/category',
                header: $header,
                payload: json_encode(value: $params)
            );

            $params = [
                'firstname' => 'Ramesh',
                'lastname' => 'Jangid',
                'email' => 'ramesh@test.com',
                'username' => 'test',
                'password' => 'shames11'
            ];
            $response[] = trigger(
                method: 'POST',
                route: '/registration',
                header: $header,
                payload: json_encode(value: $params)
            );

            $params = [
                'user_id' => 1,
                'address' => '203'
            ];
            $response[] = trigger(
                method: 'POST',
                route: '/address',
                header: $header,
                payload: json_encode(value: $params)
            );

            $params = [
                'firstname' => 'Ramesh',
                'lastname' => 'Jangid',
                'email' => 'ramesh@test.com',
                'username' => 'test',
                'password' => 'shames11',
                'address' => [
                    'address' => 'A-203'
                ]
            ];
            $response[] = trigger(
                method: 'POST',
                route: '/registration-with-address',
                header: $header,
                payload: json_encode(value: $params)
            );

            $response[] = trigger(
                method: 'GET',
                route: '/category',
                header: $header,
                payload: ''
            );

            // $response[] = trigger(method: 'GET', route: '/category/search',
            // header: $header, payload: '');

            $response[] = trigger(
                method: 'GET',
                route: '/category/1',
                header: $header,
                payload: ''
            );

            $response[] = trigger(
                method: 'GET',
                route: '/category&orderBy={"id":"DESC"}',
                header: $header,
                payload: ''
            );

            $response[] = trigger(
                method: 'GET',
                route: '/registration/1',
                header: $header,
                payload: ''
            );

            $response[] = trigger(
                method: 'GET',
                route: '/address/1',
                header: $header,
                payload: ''
            );

            $response[] = trigger(
                method: 'GET',
                route: '/registration-with-address/1',
                header: $header,
                payload: ''
            );

            $params = [
                'firstname' => 'Ramesh',
                'lastname' => 'Jangid',
                'email' => 'ramesh@test.com',
                'username' => 'test',
                'password' => 'shames11'
            ];
            $response[] = trigger(
                method: 'PUT',
                route: '/registration/1',
                header: $header,
                payload: json_encode(value: $params)
            );

            $params = [
                'user_id' => 1,
                'address' => '203'
            ];
            $response[] = trigger(
                method: 'PUT',
                route: '/address/1',
                header: $header,
                payload: json_encode(value: $params)
            );

            $params = [
                'firstname' => 'Ramesh',
                'lastname' => 'Jangid',
                'email' => 'ramesh_test@test.com'
            ];
            $response[] = trigger(
                method: 'PATCH',
                route: '/registration/1',
                header: $header,
                payload: json_encode(value: $params)
            );

            $params = [
                'address' => '203'
            ];
            $response[] = trigger(
                method: 'PATCH',
                route: '/address/1',
                header: $header,
                payload: json_encode(value: $params)
            );

            $response[] = trigger(
                method: 'DELETE',
                route: '/registration/1',
                header: $header,
                payload: ''
            );

            $response[] = trigger(
                method: 'DELETE',
                route: '/address/1',
                header: $header,
                payload: ''
            );

            $response[] = trigger(
                method: 'POST',
                route: '/category/config',
                header: $header,
                payload: ''
            );
        }

        // Admin User
        $res = trigger(
            method: 'POST',
            route: '/login',
            header: [],
            payload: '{"username":"client_1_admin_1", "password":"shames11"}'
        );
        if ($res) {
            $response[] = $res;
            $arr = json_decode(
                json: $res['responseBody'],
                associative: true
            );
            $token = $arr['Results']['Token'];
            $header = ["Authorization: Bearer {$token}"];

            $response[] = trigger(
                method: 'GET',
                route: '/routes',
                header: $header,
                payload: ''
            );

            $response[] = trigger(
                method: 'DELETE',
                route: '/category/truncate',
                header: $header,
                payload: ''
            );

            $params = [
                [
                    'name' => 'ramesh0',
                    'sub' => [
                        'subname' => 'ramesh1',
                        'subsub' => [
                            [
                                'subsubname' => 'ramesh'
                            ],
                            [
                                'subsubname' => 'ramesh'
                            ]
                        ]
                    ]
                ],
                [
                    'name' => 'ramesh1',
                    'sub' => [
                        'subname' => 'ramesh1',
                        'subsub' => [
                            'subsubname' => 'ramesh'
                        ]
                    ]
                ]
            ];
            $response[] = trigger(
                method: 'POST',
                route: '/category',
                header: $header,
                payload: json_encode(value: $params)
            );
            // return '<pre>'.print_r($response, true);

            $params = [
                'firstname' => 'Ramesh',
                'lastname' => 'Jangid',
                'email' => 'ramesh@test.com',
                'username' => 'test',
                'password' => 'shames11'
            ];
            $response[] = trigger(
                method: 'POST',
                route: '/registration',
                header: $header,
                payload: json_encode(value: $params)
            );

            $params = [
                'user_id' => 1,
                'address' => '203'
            ];
            $response[] = trigger(
                method: 'POST',
                route: '/address',
                header: $header,
                payload: json_encode(value: $params)
            );

            $params = [
                'firstname' => 'Ramesh',
                'lastname' => 'Jangid',
                'email' => 'ramesh@test.com',
                'username' => 'test',
                'password' => 'shames11',
                'address' => [
                    'address' => 'A-203'
                ]
            ];
            $response[] = trigger(
                method: 'POST',
                route: '/registration-with-address',
                header: $header,
                payload: json_encode(value: $params)
            );

            $response[] = trigger(
                method: 'GET',
                route: '/category',
                header: $header,
                payload: ''
            );

            // $response[] = trigger(method: 'GET', route: '/category/search',
            // header: $header, payload: '');

            $response[] = trigger(
                method: 'GET',
                route: '/category/1',
                header: $header,
                payload: ''
            );

            $response[] = trigger(
                method: 'GET',
                route: '/category&orderBy={"id":"DESC"}',
                header: $header,
                payload: ''
            );

            $response[] = trigger(
                method: 'GET',
                route: '/registration',
                header: $header,
                payload: ''
            );

            $response[] = trigger(
                method: 'GET',
                route: '/registration/1',
                header: $header,
                payload: ''
            );

            $response[] = trigger(
                method: 'GET',
                route: '/address',
                header: $header,
                payload: ''
            );

            $response[] = trigger(
                method: 'GET',
                route: '/address/1',
                header: $header,
                payload: ''
            );

            $response[] = trigger(
                method: 'GET',
                route: '/registration-with-address',
                header: $header,
                payload: ''
            );

            $response[] = trigger(
                method: 'GET',
                route: '/registration-with-address/1',
                header: $header,
                payload: ''
            );

            $params = [
                'firstname' => 'Ramesh',
                'lastname' => 'Jangid',
                'email' => 'ramesh@test.com',
                'username' => 'test',
                'password' => 'shames11'
            ];
            $response[] = trigger(
                method: 'PUT',
                route: '/registration/1',
                header: $header,
                payload: json_encode(value: $params)
            );

            $params = [
                'user_id' => 1,
                'address' => '203'
            ];
            $response[] = trigger(
                method: 'PUT',
                route: '/address/1',
                header: $header,
                payload: json_encode(value: $params)
            );

            $params = [
                'firstname' => 'Ramesh',
                'lastname' => 'Jangid',
                'email' => 'ramesh@test.com',
                'username' => 'test',
                'password' => 'shames11',
                'address' => [
                    'id' => 1,
                    'address' => 'a-203'
                ]
            ];
            $response[] = trigger(
                method: 'PUT',
                route: '/registration-with-address/1',
                header: $header,
                payload: json_encode(value: $params)
            );

            $params = [
                'firstname' => 'Ramesh',
                'lastname' => 'Jangid',
                'email' => 'ramesh_test@test.com'
            ];
            $response[] = trigger(
                method: 'PATCH',
                route: '/registration/1',
                header: $header,
                payload: json_encode(value: $params)
            );

            $params = [
                'address' => '203'
            ];
            $response[] = trigger(
                method: 'PATCH',
                route: '/address/1',
                header: $header,
                payload: json_encode(value: $params)
            );

            $params = [
                'firstname' => 'Ramesh',
                'lastname' => 'Jangid',
                'email' => 'ramesh@test.com',
                'address' => [
                    'id' => 1,
                    'address' => 'a-203'
                ]
            ];
            $response[] = trigger(
                method: 'PATCH',
                route: '/registration-with-address/1',
                header: $header,
                payload: json_encode(value: $params)
            );

            $response[] = trigger(
                method: 'DELETE',
                route: '/registration/1',
                header: $header,
                payload: ''
            );

            $response[] = trigger(
                method: 'DELETE',
                route: '/address/1',
                header: $header,
                payload: ''
            );

            $params = [
                'address' => [
                    'user_id' => 1
                ]
            ];
            $response[] = trigger(
                method: 'DELETE',
                route: '/registration-with-address/1',
                header: $header,
                payload: json_encode(value: $params)
            );

            $response[] = trigger(
                method: 'POST',
                route: '/category/config',
                header: $header,
                payload: ''
            );
        }

        return '<pre>'.print_r(value: $response, return: true);
    }
}

if (!function_exists(function: 'processOpen')) {
    /**
     * Process Open to web api requests
     *
     * @return string
     */
    function processOpen(): string
    {
        $response = [];
        $header = [];

        $params = [
            'firstname' => 'Ramesh',
            'lastname' => 'Jangid',
            'email' => 'ramesh@test.com',
            'username' => 'test',
            'password' => 'shames11'
        ];
        $response[] = trigger(
            method: 'POST',
            route: '/registration',
            header: $header,
            payload: json_encode(value: $params)
        );

        $params = [
            'firstname' => 'Ramesh',
            'lastname' => 'Jangid',
            'email' => 'ramesh@test.com',
            'username' => 'test',
            'password' => 'shames11',
            'address' => [
                'address' => 'A-203'
            ]
        ];
        $response[] = trigger(
            method: 'POST',
            route: '/registration-with-address',
            header: $header,
            payload: json_encode(value: $params)
        );

        $response[] = trigger(
            method: 'GET',
            route: '/category/1',
            header: $header,
            payload: ''
        );

        // $response[] = trigger(method: 'GET', route: '/category/search',
        // header: $header, payload: '');

        $response[] = trigger(
            method: 'GET',
            route: '/category',
            header: $header,
            payload: ''
        );

        $response[] = trigger(
            method: 'GET',
            route: '/category&orderBy={"id":"DESC"}',
            header: $header,
            payload: ''
        );

        return '<pre>'.print_r(value: $response, return: true);
    }
}

if (!function_exists(function: 'processXml')) {
    /**
     * Process Open to web api requests - Request/Response are in XML format
     *
     * @return string
     */
    function processXml(): string
    {
        $response = [];
        $header = [];

        $params = [
            'Payload' => [
                'firstname' => 'Ramesh1',
                'lastname' => 'Jangid',
                'email' => 'ramesh@test.com',
                'username' => 'test',
                'password' => 'shames11',
                'address' => [
                    'address' => 'A-203'
                ]
            ]
        ];

        $payload = '<?xml version="1.0" encoding="UTF-8" ?>';
        genXmlPayload(params: $params, payload: $payload);

        $response[] = trigger(
            method: 'POST',
            route: '/registration-with-address' .
                '&inputRepresentation=Xml&outputRepresentation=Xml',
            header: $header,
            payload: $payload
        );

        return '<pre>'.print_r(value: $response, return: true);
    }
}
