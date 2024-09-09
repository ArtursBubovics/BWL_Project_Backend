<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Http\Request;
use App\Http\Controllers\UserController;

Route::get('/', function () {
    return view('welcome');
});


Route::get('/csrf-token', function () {
    return response()->json(['csrf_token' => csrf_token()]);
});
Route::post('/new-access-token', [AuthController::class, 'new_access_token']);


Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);


Route::middleware('check.token.expiry')->group(function () {
    Route::put('/user/update', [UserController::class, 'user_data_update']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::delete('/user/delete', [UserController::class, 'delete_user']);
});