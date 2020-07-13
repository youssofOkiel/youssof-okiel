<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::group(['prefix'=>'api/v1'] , function () {

    Route::resource('meeting' , 'MeetingController');

//        ,[
//        'only' => ['store', 'update']
//    ]);

//to be able to create and delete registration
    Route::resource('meeting/registration' , 'RegistrationController' ,[
        'only' => ['store', 'destroy']
    ]);

    Route::post('user', [
        'uses' =>'AuthController@signup'
    ]);

    Route::post('user/signin', [
        'uses' =>'AuthController@signin'
    ]);

});


//Route::get('/', function () {
//    return view('welcome');
//});
