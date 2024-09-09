<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Http\Request;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/xsrf-token', function (Request $request) {
    $xsrfToken = $request->cookie('XSRF-TOKEN');
    return response()->json(['xsrf_token' => $xsrfToken]);
});

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::post('/new-access-token', [AuthController::class, 'new_access_token']);