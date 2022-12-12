<?php
require 'vendor/autoload.php';
error_reporting(0);

use Src\Database;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

$dbConnection = (new Database())->connet();
