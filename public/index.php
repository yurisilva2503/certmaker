<?php

require_once(__DIR__ . '/../vendor/autoload.php');

$url = $_SERVER['SERVER_NAME'] ?? '';
$parts = explode('.', $url);

if (count($parts) > 2) {
    array_shift($parts);
}

$dominio = implode('.', $parts);

if (mb_strtolower($dominio) == 'sectetpa.local') {
    require_once(__DIR__ . '/../config/environments/dev.php');
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else if (mb_strtolower($dominio) == 'code129.com.br') {
    require_once(__DIR__ . '/../config/environments/prod.php');
} else {
    require_once(__DIR__ . '/../config/environments/xampp.php');
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    
}

require_once(__DIR__ . '/../routes/web.php');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}