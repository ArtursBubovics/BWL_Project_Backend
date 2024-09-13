<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;

Route::middleware(['auth:sanctum', 'check.token.expiry'])->group(function () {
    Route::delete('/user/delete', [UserController::class, 'delete_user']);
    Route::put('/user/update', [UserController::class, 'user_data_update']);
});

?>