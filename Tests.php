<?php

if (!function_exists('getCurlConfig')) {
    function getCurlConfig($method, $route, $header = [], $json = '')
    {
        $homeURL = 'http://127.0.0.1:9501';

        $curlConfig[CURLOPT_URL] = "{$homeURL}?r={$route}";
        $curlConfig[CURLOPT_HTTPHEADER] = $header;
        $curlConfig[CURLOPT_HTTPHEADER][] = "X-API-Version: v1.0.0";
        $curlConfig[CURLOPT_HTTPHEADER][] = "Cache-Control: no-cache";

        $payload = http_build_query([
            "Payload" => $json
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
    function trigger(&$strArr, $method, $route, $header = [], $json = '')
    {
        $curl = curl_init();
        $curlConfig = getCurlConfig($method, $route, $header, $json);
        curl_setopt_array($curl, $curlConfig);
        $responseJSON = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
            $strArr[] = "cURL Error #:" . $err;
        } else {
            $response = json_decode($responseJSON, true);
            if (
                !empty($response)
                && (
                    (isset($response['Status']) && $response['Status'] == 200)
                    || (isset($response['Results']['Status']) && $response['Results']['Status'] == 200)
                )
            ) {
                $strArr[] = 'Sucess:'.$route . PHP_EOL . PHP_EOL;
            } else {
                $strArr[] = 'Failed:'.$route . PHP_EOL;
                $strArr[] = 'O/P:' . $responseJSON . PHP_EOL . PHP_EOL;
                $response = false;
            }
        }
        return $response;
    }
}

if (!function_exists('processAuth')) {
    function processAuth()
    {
        $strArr = [];
        $response = [];

        $response[] = trigger($strArr, 'GET', '/reload', [], $jsonPayload = '');

        $res = trigger($strArr, 'POST', '/login', [], $jsonPayload = '{"username":"client_1_group_1_user_1", "password":"shames11"}');
        if ($res) {
            $response[] = $res;
            $token = $res['Results']['Token'];
            $header = ["Authorization: Bearer {$token}"];

            $response[] = trigger($strArr, 'GET', '/routes', $header, $jsonPayload = '');
            $response[] = trigger($strArr, 'POST', '/category', $header, $jsonPayload = '[{"name":"ramesh0","sub":{"subname":"ramesh1","subsub":[{"subsubname":"ramesh"},{"subsubname":"ramesh"}]}},{"name":"ramesh1","sub":{"subname":"ramesh1","subsub":{"subsubname":"ramesh"}}}]');
            $response[] = trigger($strArr, 'GET', '/category/1', $header, $jsonPayload = '');
            $response[] = trigger($strArr, 'GET', '/category', $header, $jsonPayload = '');
            $response[] = trigger($strArr, 'GET', '/category&orderBy={"id":"DESC"}', $header, $jsonPayload = '');
            $response[] = trigger($strArr, 'POST', '/category/config', $header, $jsonPayload = '');
        }
        return '<pre>'.print_r($strArr, true).print_r($response, true);
    }
}

if (!function_exists('processOpen')) {
    function processOpen()
    {
        $strArr = [];
        $response = [];
        $header = [];

        $response[] = trigger($strArr, 'GET', '/category/1', $header, $jsonPayload = '');
        $response[] = trigger($strArr, 'GET', '/category', $header, $jsonPayload = '');
        $response[] = trigger($strArr, 'GET', '/category&orderBy={"id":"DESC"}', $header, $jsonPayload = '');

        return '<pre>'.print_r($strArr, true).print_r($response, true);
    }
}
