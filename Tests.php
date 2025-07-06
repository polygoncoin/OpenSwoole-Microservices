<?php
if (!function_exists('genXmlPayload')) {
    function genXmlPayload(&$params, &$payload, $rowsFlag = false)
    {
        if (empty($params)) {
            return;
        }

        $rows = false;

        $isAssoc = (isset($params[0])) ? false : true;

        if (!$isAssoc && count($params) === 1) {
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
            if ($isAssoc) {$payload .= "<{$key}>";}
            if (is_array($value)) {
                genXmlPayload($value, $payload, $rows);
            } else {
                $payload .= htmlspecialchars($value);
            }
            if ($isAssoc) {$payload .= "</{$key}>";}
        }
        if ($rowsFlag) {
            $payload .= "</Row>";
        }
        if (!$isAssoc) {
            $payload .= "</Rows>";
        }
    }
}

if (!function_exists('http_parse_headers')) {
    function http_parse_headers($raw_headers) {
        $headers = array();
        $key = '';

        foreach(explode("\n", $raw_headers) as $i => $h) {
            $h = explode(':', $h, 2);

            if (isset($h[1])) {
                if (!isset($headers[$h[0]]))
                    $headers[$h[0]] = trim($h[1]);
                elseif (is_array($headers[$h[0]])) {
                    $headers[$h[0]] = array_merge($headers[$h[0]], array(trim($h[1])));
                }
                else {
                    $headers[$h[0]] = array_merge(array($headers[$h[0]]), array(trim($h[1])));
                }

                $key = $h[0];
            }
            else {
                if (substr($h[0], 0, 1) == "\t")
                    $headers[$key] .= "\r\n\t".trim($h[0]);
                elseif (!$key)
                    $headers[0] = trim($h[0]);
            }
        }

        return $headers;
    }
}

if (!function_exists('getCurlConfig')) {
    function getCurlConfig($method, $route, $header = [], $payload = '')
    {
        $homeURL = 'http://127.0.0.1:9501';

        $curlConfig[CURLOPT_URL] = "{$homeURL}?r={$route}";
        $curlConfig[CURLOPT_HTTPHEADER] = $header;
        $curlConfig[CURLOPT_HTTPHEADER][] = "X-API-Version: v1.0.0";
        $curlConfig[CURLOPT_HTTPHEADER][] = "Cache-Control: no-cache";
        $curlConfig[CURLOPT_HEADER] = 1;

        $payload = http_build_query([
            "Payload" => $payload
        ]);

        switch ($method) {
            case 'GET':
                $curlConfig[CURLOPT_HTTPHEADER][] = 'Content-Type: text/plain; charset=utf-8';
                break;
            case 'POST':
                $curlConfig[CURLOPT_HTTPHEADER][] = 'Content-Type: application/x-www-form-urlencoded; charset=utf-8';
                $curlConfig[CURLOPT_POST] = true;
                $curlConfig[CURLOPT_POSTFIELDS] = $payload;
                break;
            case 'PUT':
                $curlConfig[CURLOPT_HTTPHEADER][] = 'Content-Type: application/x-www-form-urlencoded; charset=utf-8';
                $curlConfig[CURLOPT_CUSTOMREQUEST] = 'PUT';
                $curlConfig[CURLOPT_POSTFIELDS] = $payload;
                break;
            case 'PATCH':
                $curlConfig[CURLOPT_HTTPHEADER][] = 'Content-Type: application/x-www-form-urlencoded; charset=utf-8';
                $curlConfig[CURLOPT_CUSTOMREQUEST] = 'PATCH';
                $curlConfig[CURLOPT_POSTFIELDS] = $payload;
                break;
            case 'DELETE':
                $curlConfig[CURLOPT_HTTPHEADER][] = 'Content-Type: application/x-www-form-urlencoded; charset=utf-8';
                $curlConfig[CURLOPT_CUSTOMREQUEST] = 'DELETE';
                $curlConfig[CURLOPT_POSTFIELDS] = $payload;
                break;
        }
        $curlConfig[CURLOPT_RETURNTRANSFER] = true;

        return $curlConfig;
    }
}

if (!function_exists('trigger')) {
    function trigger($method, $route, $header = [], $payload = '')
    {
        $curl = curl_init();
        $curlConfig = getCurlConfig($method, $route, $header, $payload);
        curl_setopt_array($curl, $curlConfig);
        $curlResponse = curl_exec($curl);

        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($curl, CURLINFO_CONTENT_TYPE);

        $headerSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $responseHeaders = http_parse_headers(substr($curlResponse, 0, $headerSize));
        $responseBody = substr($curlResponse, $headerSize);

        $error = curl_error($curl);
        curl_close($curl);

        if ($error) {
            $response = 'cURL Error #:' . $error;
            echo 'Failed:'.$route . PHP_EOL;
            echo 'O/P:' . htmlspecialchars($responseBody) . PHP_EOL . PHP_EOL;
            die;
        } else {
            $response = $responseBody;
        }

        return [
            'httpCode' => $httpCode,
            'contentType' => $contentType,
            'headers' => $responseHeaders,
            'body' => $response
        ];
    }
}

if (!function_exists('processAuth')) {
    function processAuth()
    {
        $response = [];

        $response[] = trigger('GET', '/reload', [], $payload = '');

        // Client User
        $params = [
            'username' => 'client_1_group_1_user_1',
            'password' => 'shames11'
        ];
        $res = trigger('POST', '/login', [], $payload = json_encode($params));
        if ($res) {
            $response[] = $res;
            $token = json_decode($res['body'], true)['Results']['Token'];
            $header = ["Authorization: Bearer {$token}"];

            $response[] = trigger('GET', '/routes', $header, $payload = '');

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
            $response[] = trigger('POST', '/category', $header, $payload = json_encode($params));

            $params = [
                'firstname' => 'Ramesh',
                'lastname' => 'Jangid',
                'email' => 'ramesh@test.com',
                'username' => 'test',
                'password' => 'shames11'
            ];
            $response[] = trigger('POST', '/registration', $header, $payload = json_encode($params));

            $params = [
                'user_id' => 1,
                'address' => '203'
            ];
            $response[] = trigger('POST', '/address', $header, $payload = json_encode($params));

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
            $response[] = trigger('POST', '/registration-with-address', $header, $payload = json_encode($params));
        
            $response[] = trigger('GET', '/category', $header, $payload = '');
            // $response[] = trigger('GET', '/category/search', $header, $payload = '');
            $response[] = trigger('GET', '/category/1', $header, $payload = '');
            $response[] = trigger('GET', '/category&orderBy={"id":"DESC"}', $header, $payload = '');
        
            $response[] = trigger('GET', '/registration/1', $header, $payload = '');
            $response[] = trigger('GET', '/address/1', $header, $payload = '');
            $response[] = trigger('GET', '/registration-with-address/1', $header, $payload = '');
        
            $params = [
                'firstname' => 'Ramesh',
                'lastname' => 'Jangid',
                'email' => 'ramesh@test.com',
                'username' => 'test',
                'password' => 'shames11'
            ];
            $response[] = trigger('PUT', '/registration/1', $header, $payload = json_encode($params));

            $params = [
                'user_id' => 1,
                'address' => '203'
            ];
            $response[] = trigger('PUT', '/address/1', $header, $payload = json_encode($params));

            $params = [
                'firstname' => 'Ramesh',
                'lastname' => 'Jangid',
                'email' => 'ramesh_test@test.com'
            ];
            $response[] = trigger('PATCH', '/registration/1', $header, $payload = json_encode($params));

            $params = [
                'address' => '203'
            ];
            $response[] = trigger('PATCH', '/address/1', $header, $payload = json_encode($params));
        
            $response[] = trigger('DELETE', '/registration/1', $header, $payload = '');
            $response[] = trigger('DELETE', '/address/1', $header, $payload = '');
        
            $response[] = trigger('POST', '/category/config', $header, $payload = '');
        }

        // Admin User
        $res = trigger('POST', '/login', [], $payload = '{"username":"client_1_admin_1", "password":"shames11"}');
        if ($res) {
            $response[] = $res;
            $token = json_decode($res['body'], true)['Results']['Token'];
            $header = ["Authorization: Bearer {$token}"];

            $response[] = trigger('GET', '/routes', $header, $payload = '');

            $response[] = trigger('DELETE', '/category/truncate', $header, $payload = '');
            
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
            $response[] = trigger('POST', '/category', $header, $payload = json_encode($params));
// return '<pre>'.print_r($response, true);

            $params = [
                'firstname' => 'Ramesh',
                'lastname' => 'Jangid',
                'email' => 'ramesh@test.com',
                'username' => 'test',
                'password' => 'shames11'
            ];
            $response[] = trigger('POST', '/registration', $header, $payload = json_encode($params));

            $params = [
                'user_id' => 1,
                'address' => '203'
            ];
            $response[] = trigger('POST', '/address', $header, $payload = json_encode($params));

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
            $response[] = trigger('POST', '/registration-with-address', $header, $payload = json_encode($params));
            
            $response[] = trigger('GET', '/category', $header, $payload = '');
            // $response[] = trigger('GET', '/category/search', $header, $payload = '');
            $response[] = trigger('GET', '/category/1', $header, $payload = '');
            $response[] = trigger('GET', '/category&orderBy={"id":"DESC"}', $header, $payload = '');

            $response[] = trigger('GET', '/registration', $header, $payload = '');
            $response[] = trigger('GET', '/registration/1', $header, $payload = '');

            $response[] = trigger('GET', '/address', $header, $payload = '');
            $response[] = trigger('GET', '/address/1', $header, $payload = '');

            $response[] = trigger('GET', '/registration-with-address', $header, $payload = '');
            $response[] = trigger('GET', '/registration-with-address/1', $header, $payload = '');

            $params = [
                'firstname' => 'Ramesh',
                'lastname' => 'Jangid',
                'email' => 'ramesh@test.com',
                'username' => 'test',
                'password' => 'shames11'
            ];
            $response[] = trigger('PUT', '/registration/1', $header, $payload = json_encode($params));

            $params = [
                'user_id' => 1,
                'address' => '203'
            ];
            $response[] = trigger('PUT', '/address/1', $header, $payload = json_encode($params));

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
            $response[] = trigger('PUT', '/registration-with-address/1', $header, $payload = json_encode($params));

            $params = [
                'firstname' => 'Ramesh',
                'lastname' => 'Jangid',
                'email' => 'ramesh_test@test.com'
            ];
            $response[] = trigger('PATCH', '/registration/1', $header, $payload = json_encode($params));

            $params = [
                'address' => '203'
            ];
            $response[] = trigger('PATCH', '/address/1', $header, $payload = json_encode($params));

            $params = [
                'firstname' => 'Ramesh',
                'lastname' => 'Jangid',
                'email' => 'ramesh@test.com',
                'address' => [
                    'id' => 1,
                    'address' => 'a-203'
                ]
            ];
            $response[] = trigger('PATCH', '/registration-with-address/1', $header, $payload = json_encode($params));

            $response[] = trigger('DELETE', '/registration/1', $header, $payload = '');
            $response[] = trigger('DELETE', '/address/1', $header, $payload = '');

            $params = [
                'address' => [
                    'user_id' => 1
                ]
            ];
            $response[] = trigger('DELETE', '/registration-with-address/1', $header, $payload = json_encode($params));

            $response[] = trigger('POST', '/category/config', $header, $payload = '');
        }

        return '<pre>'.print_r($response, true);
    }
}

if (!function_exists('processOpen')) {
    function processOpen()
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
        $response[] = trigger('POST', '/registration', $header, $payload = json_encode($params));

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
        $response[] = trigger('POST', '/registration-with-address', $header, $payload = json_encode($params));

        $response[] = trigger('GET', '/category/1', $header, $payload = '');
        // $response[] = trigger('GET', '/category/search', $header, $payload = '');
        $response[] = trigger('GET', '/category', $header, $payload = '');
        $response[] = trigger('GET', '/category&orderBy={"id":"DESC"}', $header, $payload = '');

        return '<pre>'.print_r($response, true);
    }
}

if (!function_exists('processAuth')) {
    function processAuth()
    {
        $response = [];

        $response[] = trigger('GET', '/reload', [], $payload = '');

        // Client User
        $params = [
            'username' => 'client_1_group_1_user_1',
            'password' => 'shames11'
        ];
        $res = trigger('POST', '/login', [], $payload = json_encode($params));
        if ($res) {
            $response[] = $res;
            $token = $res['Results']['Token'];
            $header = ["Authorization: Bearer {$token}"];

            $response[] = trigger('GET', '/routes', $header, $payload = '');

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
            $response[] = trigger('POST', '/category', $header, $payload = json_encode($params));

            $params = [
                'firstname' => 'Ramesh',
                'lastname' => 'Jangid',
                'email' => 'ramesh@test.com',
                'username' => 'test',
                'password' => 'shames11'
            ];
            $response[] = trigger('POST', '/registration', $header, $payload = json_encode($params));

            $params = [
                'user_id' => 1,
                'address' => '203'
            ];
            $response[] = trigger('POST', '/address', $header, $payload = json_encode($params));

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
            $response[] = trigger('POST', '/registration-with-address', $header, $payload = json_encode($params));
        
            $response[] = trigger('GET', '/category', $header, $payload = '');
            // $response[] = trigger('GET', '/category/search', $header, $payload = '');
            $response[] = trigger('GET', '/category/1', $header, $payload = '');
            $response[] = trigger('GET', '/category&orderBy={"id":"DESC"}', $header, $payload = '');
        
            $response[] = trigger('GET', '/registration/1', $header, $payload = '');
            $response[] = trigger('GET', '/address/1', $header, $payload = '');
            $response[] = trigger('GET', '/registration-with-address/1', $header, $payload = '');
        
            $params = [
                'firstname' => 'Ramesh',
                'lastname' => 'Jangid',
                'email' => 'ramesh@test.com',
                'username' => 'test',
                'password' => 'shames11'
            ];
            $response[] = trigger('PUT', '/registration/1', $header, $payload = json_encode($params));

            $params = [
                'user_id' => 1,
                'address' => '203'
            ];
            $response[] = trigger('PUT', '/address/1', $header, $payload = json_encode($params));

            $params = [
                'firstname' => 'Ramesh',
                'lastname' => 'Jangid',
                'email' => 'ramesh_test@test.com'
            ];
            $response[] = trigger('PATCH', '/registration/1', $header, $payload = json_encode($params));

            $params = [
                'address' => '203'
            ];
            $response[] = trigger('PATCH', '/address/1', $header, $payload = json_encode($params));
        
            $response[] = trigger('DELETE', '/registration/1', $header, $payload = '');
            $response[] = trigger('DELETE', '/address/1', $header, $payload = '');
        
            $response[] = trigger('POST', '/category/config', $header, $payload = '');
        }

        // Admin User
        $res = trigger('POST', '/login', [], $payload = '{"username":"client_1_admin_1", "password":"shames11"}');
        if ($res) {
            $response[] = $res;
            $token = $res['Results']['Token'];
            $header = ["Authorization: Bearer {$token}"];

            $response[] = trigger('GET', '/routes', $header, $payload = '');

            $response[] = trigger('DELETE', '/category/truncate', $header, $payload = '');
            
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
            $response[] = trigger('POST', '/category', $header, $payload = json_encode($params));

            $params = [
                'firstname' => 'Ramesh',
                'lastname' => 'Jangid',
                'email' => 'ramesh@test.com',
                'username' => 'test',
                'password' => 'shames11'
            ];
            $response[] = trigger('POST', '/registration', $header, $payload = json_encode($params));

            $params = [
                'user_id' => 1,
                'address' => '203'
            ];
            $response[] = trigger('POST', '/address', $header, $payload = json_encode($params));

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
            $response[] = trigger('POST', '/registration-with-address', $header, $payload = json_encode($params));
            
            $response[] = trigger('GET', '/category', $header, $payload = '');
            // $response[] = trigger('GET', '/category/search', $header, $payload = '');
            $response[] = trigger('GET', '/category/1', $header, $payload = '');
            $response[] = trigger('GET', '/category&orderBy={"id":"DESC"}', $header, $payload = '');

            $response[] = trigger('GET', '/registration', $header, $payload = '');
            $response[] = trigger('GET', '/registration/1', $header, $payload = '');

            $response[] = trigger('GET', '/address', $header, $payload = '');
            $response[] = trigger('GET', '/address/1', $header, $payload = '');

            $response[] = trigger('GET', '/registration-with-address', $header, $payload = '');
            $response[] = trigger('GET', '/registration-with-address/1', $header, $payload = '');

            $params = [
                'firstname' => 'Ramesh',
                'lastname' => 'Jangid',
                'email' => 'ramesh@test.com',
                'username' => 'test',
                'password' => 'shames11'
            ];
            $response[] = trigger('PUT', '/registration/1', $header, $payload = json_encode($params));

            $params = [
                'user_id' => 1,
                'address' => '203'
            ];
            $response[] = trigger('PUT', '/address/1', $header, $payload = json_encode($params));

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
            $response[] = trigger('PUT', '/registration-with-address/1', $header, $payload = json_encode($params));

            $params = [
                'firstname' => 'Ramesh',
                'lastname' => 'Jangid',
                'email' => 'ramesh_test@test.com'
            ];
            $response[] = trigger('PATCH', '/registration/1', $header, $payload = json_encode($params));

            $params = [
                'address' => '203'
            ];
            $response[] = trigger('PATCH', '/address/1', $header, $payload = json_encode($params));

            $params = [
                'firstname' => 'Ramesh',
                'lastname' => 'Jangid',
                'email' => 'ramesh@test.com',
                'address' => [
                    'id' => 1,
                    'address' => 'a-203'
                ]
            ];
            $response[] = trigger('PATCH', '/registration-with-address/1', $header, $payload = json_encode($params));

            $response[] = trigger('DELETE', '/registration/1', $header, $payload = '');
            $response[] = trigger('DELETE', '/address/1', $header, $payload = '');

            $params = [
                'address' => [
                    'user_id' => 1
                ]
            ];
            $response[] = trigger('DELETE', '/registration-with-address/1', $header, $payload = json_encode($params));

            $response[] = trigger('POST', '/category/config', $header, $payload = '');
        }

        return '<pre>'.print_r($response, true);
    }
}

if (!function_exists('processXml')) {
    function processXml()
    {
        $response = [];
        $header = [];

        $params = [
            'Paylaod' => [
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
        genXmlPayload($params, $payload);

        $response[] = trigger('POST', '/registration-with-address&inputDataRepresentation=Xml&outputDataRepresentation=Xml', $header, $payload);

        return '<pre>'.print_r($response, true);
    }
}
