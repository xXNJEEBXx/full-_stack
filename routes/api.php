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

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::post('/register', 'ApiController@register');
Route::post('/login', 'ApiController@login');
Route::get('/product/{name}', 'ApiController@get_product');
Route::get('/get_home_products', 'ApiController@get_home_products');
Route::group(['middleware' => ['auth:api']], function () {
    // middleware routes here
    Route::post('/authCheck', 'ApiController@authCheck');
    Route::post('/update_user_profile', 'ApiController@update_user_profile');
    Route::post('/add_new_product', 'ApiController@add_new_product');
    Route::post('/update_product/{id}', 'ApiController@update_product');
    Route::post('/update_product_discount/{id}', 'ApiController@update_product_discount');
    Route::get('/get_user_profile_photo', 'ApiController@get_user_profile_photo');
    Route::get('/get_products_list', 'ApiController@get_products_list');
    Route::post('/logout', 'ApiController@logout');
});
