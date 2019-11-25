<?php

use Illuminate\Http\Request;

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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('register', 'UserController@store');

Route::group(['middleware' => ['auth.user']], function(){
    Route::get('logout', 'UserController@logout');
    Route::post('login', 'UserController@login');
    Route::resource('reward', 'RewardController')->only(['index','show', 'store']);
    Route::get('reward/{id}/done', 'RewardController@update');
    Route::resource('profile', 'UserController')->only(['index']);
});
