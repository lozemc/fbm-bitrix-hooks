<?php

use App\Controllers\CreateTaskController;
use App\Controllers\UpdateTaskController;
use Pecee\Http\Request;
use Pecee\SimpleRouter\Exceptions\NotFoundHttpException;
use Pecee\SimpleRouter\SimpleRouter as Route;

Route::group(['prefix' => '/webhook'], function () {
    Route::post('/new-task', [CreateTaskController::class, 'execute']);
    Route::post('/update-task', [UpdateTaskController::class, 'execute']);
});

Route::error(static function (Request $request, \Exception $exception) {
    if ($exception instanceof NotFoundHttpException) {
        http_response_code(404);
        return Route::response()->json([
            'error' => 'Route not found',
            'path' => $request->getUrl()->getPath(),
        ]);
    }

    http_response_code(500);
    return Route::response()->json([
        'error' => 'Server error',
        'message' => $exception->getMessage(),
    ]);
});
