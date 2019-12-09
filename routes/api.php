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
    Route::resource('reward', 'RewardController')->only(['index','store']);
    Route::post('reward/{id}', 'RewardController@hunt');
    Route::get('reward/{id}', 'RewardController@show');
    Route::post('reward/{id}/report', 'RewardController@update');
    Route::post('reward/{id}/done', 'RewardController@done');
    Route::post('reward/{id}/choose', 'RewardController@choose');
    Route::resource('profile', 'UserController')->only(['index']);
    Route::get('history', 'UserController@history');
    Route::get('shop', 'UserController@shop');
    Route::get('bought', 'UserController@bought');
    Route::post('earn', 'UserController@earn');
    Route::post('buy', 'UserController@buy');
    Route::post('goods/{id}', 'UserController@goods'); //發送貨物 用sparta帳號
    Route::get('goodlist/{id}', 'UserController@goodlist');//瀏覽陣營貨品
    Route::post('avatar/{id}', 'UserController@avatar');
    
});
