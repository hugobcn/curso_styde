<?php

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

Route::get('/', function () {
    return view('welcome');
});

Route::get('/usuarios', 'UserController@index')
    ->name('users.index');
Route::get('/usuarios/{user}', 'UserController@show')
    ->where('user', '[0-9]+')
    ->name('users.show');

Route::get('/usuarios/nuevo', 'UserController@create')
    ->name('users.create');

Route::post('/usuarios', 'UserController@store')
    ->name('users.store');

Route::get('usuarios/{user}/editar', 'UserController@edit')
    ->name('users.edit');

Route::put('/usuarios/{user}', 'UserController@update')
    ->name('users.update');

Route::delete('/usuarios/{user}', 'UserController@destroy')
    ->name('users.destroy');


Route::get('/saludo/{name}/{nickname?}', 'WelcomeUserController');


/*Route::get('/usuarios/{user_id}/edit', function ($user_id){
   return "Editamos el usuario {$user_id}";
})->where('user_id',  '[\d+]');*/
Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');
