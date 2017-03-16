<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::group(['prefix' => 'api'], function () {
    Route::get('login', 'UsersController@login');
});

Route::group(['prefix' => 'api', 'middleware' => ['jwt.auth', 'jwt.refresh']], function () {

    Route::get('logout', 'UsersController@logout');
});


Route::group(['prefix' => 'api/user', 'middleware' => 'jwt.auth'], function () {

    Route::get('get/{user_id}', 'UsersController@read');
    Route::delete('delete/{user_id}', 'UsersController@delete');
    Route::post('update/{user_id}', 'UsersController@update');
    Route::put('create', 'UsersController@create');
    Route::get('all', 'UsersController@getUsers');

});


Route::group(['prefix' => 'api/task', 'middleware' => 'jwt.auth'], function () {
    Route::get('get/{task_id}', 'TasksController@read');
    Route::get('all', 'TasksController@readAllTasks');
    Route::get('get_new', 'TasksController@readAllNewTask');
    Route::get('user_at_day/{user_id}/{day}', 'TasksController@readTasksForUserAtDay');
    Route::post('set_task', 'TasksController@setTaskToUser');

});
