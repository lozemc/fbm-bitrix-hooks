<?php

use Dotenv\Dotenv;
use Illuminate\Database\Capsule\Manager as Capsule;
use Pecee\SimpleRouter\SimpleRouter as Route;

# Env
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

# Database
$capsule = new Capsule;
$capsule->addConnection([
    'driver' => 'pgsql',
    'host' => env('DB_HOST', 'localhost'),
    'port' => 5432,
    'database' => env('DB_DATABASE', 'forge'),
    'username' => env('DB_USERNAME', 'forge'),
    'password' => env('DB_PASSWORD', ''),
    'charset' => 'utf8',
    'prefix' => '',
    'schema' => 'public', // для PostgreSQL важно!
]);

// Делаем Capsule доступным глобально
$capsule->setAsGlobal();
$capsule->bootEloquent();

# Routing
require_once __DIR__ . '/routes.php';
Route::start();
