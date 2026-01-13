<?php

use App\Http\Controllers\Api\NewsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::get('/news/headlines', [NewsController::class, 'getHeadlines']);
Route::get('/news/logs', [NewsController::class, 'getLogs']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
