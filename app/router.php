<?php

use App\controllers\CreateTaskController;
use App\Controllers\UpdateTaskController;
use Pecee\SimpleRouter\SimpleRouter as Route;
use Pecee\Http\Request;
use Pecee\SimpleRouter\Exceptions\NotFoundHttpException;

Route::group(['prefix' => '/webhook'], function() {

    Route::form('/update-task', [UpdateTaskController::class, 'execute']);

//    Route::form('/new-task', [CreateTaskController::class, 'execute']);

});

Route::error(function(Request $request, \Exception $exception) {
    if ($exception instanceof NotFoundHttpException) {
        http_response_code(404);
        return Route::response()->json([
            'error' => 'Route not found',
            'path'  => $request->getUrl()->getPath(),
        ]);
    }

    http_response_code(500);
    return Route::response()->json([
        'error'   => 'Server error',
        'message' => $exception->getMessage(),
    ]);
});
