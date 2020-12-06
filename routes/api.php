<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::prefix('user')->middleware(['auth:api','checkUser'])->group(function () {

        Route::post('/post', 'App\Http\Controllers\PostController@storePost');
        Route::delete('/post/{post_id}', 'App\Http\Controllers\PostController@deletePost');
        Route::get('/post', 'App\Http\Controllers\PostController@getPosts');
        Route::get('/post/{post_id}', 'App\Http\Controllers\PostController@getOnePost');

});

Route::prefix('admin')->middleware(['auth:api','checkAdmin'])->group(function () {

        Route::post('/user/edit/{user_id}', 'App\Http\Controllers\AdminRequestController@editUser');
        Route::post('/post/create/{user_id}', 'App\Http\Controllers\AdminRequestController@createPost');
        Route::post('/post/delete/{user_id}', 'App\Http\Controllers\AdminRequestController@deletePost');
        Route::post('/post/edit/{user_id}', 'App\Http\Controllers\AdminRequestController@editPost');

});


Route::prefix('superadmin')->middleware(['auth:api','checkSuperAdmin'])->group(function () {

        Route::get('/requests', 'App\Http\Controllers\SuperAdminController@adminRequests');
        Route::post('/approve/request/{request_id}', 'App\Http\Controllers\SuperAdminController@approveRequest');
        Route::post('/decline/request/{request_id}', 'App\Http\Controllers\SuperAdminController@declineRequest');
        Route::get('/insights/post_freuency/user/{user_id}','App\Http\Controllers\PostController@getPostFrequency');
        Route::get('/insights/request_freuency/user/{user_id}','App\Http\Controllers\AdminRequestController@getRequestFrequency');

});





Route::post('/register', 'App\Http\Controllers\API\AuthController@register');
Route::post('/login', 'App\Http\Controllers\API\AuthController@login');
Route::get('/login', function(){
        return 'Please login';
})->name('login');
