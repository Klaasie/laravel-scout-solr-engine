<?php

use App\Http\Controllers;
use Illuminate\Routing\Redirector;
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

Route::get('/', static fn(Redirector $redirector) => $redirector->route('users.index'));

Route::resources([
    'users' => Controllers\UserController::class,
    'books' => Controllers\BookController::class,
]);
