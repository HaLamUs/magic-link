<?php
require "../start.php";
use Src\Transaction;

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: OPTIONS,GET,POST,PUT,DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header('Access-Control-Allow-Origin: http://localhost:3000');

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = explode('/', $uri);

// endpoints starting with `/post` or `/posts` for GET shows all posts
// everything else results in a 404 Not Found
if ($uri[1] !== 'transaction') {
    if ($uri[1] !== 'transactions') {
        header("HTTP/1.1 404 Not Found");
        exit();
    }
}
$requestMethod = $_SERVER["REQUEST_METHOD"];

$controller = new Transaction($dbConnection, $requestMethod);
$controller->processRequest();
