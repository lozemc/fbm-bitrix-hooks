<?php

use App\Controllers\CreateTaskController;
use App\Controllers\UpdateTaskController;
use Pecee\SimpleRouter\SimpleRouter as Route;

Route::group(['prefix' => '/webhook'], function () {
    Route::post('/new-task', [CreateTaskController::class, 'execute']);
    Route::post('/update-task', [UpdateTaskController::class, 'execute']);
});
