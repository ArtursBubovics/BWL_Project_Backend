<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Middleware\CheckTokenExpiry;

Route::middleware(['auth:sanctum', CheckTokenExpiry::class])->group(function () {
    Route::delete('/user/delete', [UserController::class, 'delete_user']);
    Route::put('/user/update', [UserController::class, 'user_data_update']);
});
?>