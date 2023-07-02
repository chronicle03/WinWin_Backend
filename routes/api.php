<?php

use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

// Route::middleware('auth:sanctum')->get('/logout', [UserController::class, 'logout']);

// Auth::routes(['verify' => true]);

Route::middleware('auth:sanctum')->group(function() {
    Route::get('logout', [UserController::class, 'logout']);
    Route::post('users/update', [UserController::class, "updateProfile"]);
});

Route::get('users', [UserController::class, "getAllUsers"]);

Route::post('register', [UserController::class, "register"]);
Route::post('login', [UserController::class, "login"]);
Route::get('users/{id}', [UserController::class, "getUserById"]);
Route::get('email/verify/{id}', [UserController::class, 'verify'])->name('verification.verify');
Route::get('email/verify', [UserController::class, 'notice'])->name('verification.notice');
Route::post('email/resend', [UserController::class, 'resend'])->name('verification.resend');
Route::post('/forget-password', [UserController::class,'forgetPassword']);
Route::get('/favorites', [UserController::class, 'getFavorites']);
Route::post('/favorites', [UserController::class, 'createFavorite']);



