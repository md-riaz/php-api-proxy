<?php

// Path: index.php
// create a proxy server for api requests for both get and post request
// this is a simple proxy server that will forward the request to the api server
// and return the response
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: X-Auth-Token');

class ProxyServer
{
    // request method
    private $method;
    // request url
    public $req_uri;
    // request body
    public $body;
    // request headers
    public $headers;
    // api server url
    public $api_url;
    // api server response
    private $api_response;

    // get error
    private $error;

    // constructor
    public function __construct()
    {
        $this->method = $_SERVER['REQUEST_METHOD'];
    }

    // send request to api server
    public function sendRequest()
    {
        // create a curl handle
        $ch = curl_init();
        // set the url
        curl_setopt($ch, CURLOPT_URL, $this->api_url . $this->req_uri);
        // set the method
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $this->method);
        // set the headers
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
        // set the body
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->body);
        // set the response as a string
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // set the timeout
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        // execute the request
        $this->api_response = curl_exec($ch);

        // get the error msg
        $this->error = curl_error($ch);

        // close the handle
        curl_close($ch);
    }

    // get the api server response
    public function getResponse()
    {
        return $this->api_response;
    }

    // get the error msg
    public function getError()
    {
        return $this->error;
    }
}

// sub folder to remove from url path
$sub_folder_path = '';
// get the actual api uri to forward the request to
$req_uri = str_replace($sub_folder_path, '',  $_SERVER['REQUEST_URI']);

// get the request body
$body = file_get_contents('php://input');
// json decode body
$body = json_decode($body, true);

// get the request headers
$headers = getallheaders();

// check if the request is /user/login
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $req_uri == '/user/login') {
    // add x-forwarded-for header
    if (!isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $headers['X-Forwarded-For'] = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    // add origin header if not present
    if (!isset($headers['HTTP_ORIGIN'])) {
        $headers['HTTP_ORIGIN'] = $_SERVER['HTTP_HOST'];
    }

    $body['client_ip'] = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $body['api_key'] = ''; //todo add api key here
    $body['origin'] = parse_url($_SERVER['HTTP_ORIGIN'] ?? '', PHP_URL_HOST) ?? $_SERVER['HTTP_HOST'];
}

// create a new proxy server
$proxy_server = new ProxyServer();
// set the api url
$proxy_server->api_url = 'http://api.dev.alpha.net.bd';
// set the request uri
$proxy_server->req_uri = $req_uri;
// set the request headers
$proxy_server->headers = $headers;
// set the request body
$proxy_server->body = $body;

// send the request to the api server
$proxy_server->sendRequest();
// get the api server response
$api_response = $proxy_server->getResponse();

if (empty($api_response)) {
    $api_response = json_encode(array('error' => 500, 'msg' => $proxy_server->getError()));
}

echo $api_response;
