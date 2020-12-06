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

use App\Http\Controllers\AdminController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PageController;

Route::get('/', [HomeController::class, 'index']);

Route::prefix('/admin')->group(function(){
	Route::get('/login', [AdminController::class, 'login'])->name('login'); //Rota de login
	Route::post('/login', [AdminController::class, 'loginAction']); //Rota do submit do form de login

	Route::get('/register', [AdminController::class, 'register'])->name('register'); //Rota de registro
	Route::post('/register', [AdminController::class, 'registerAction']); //Rota do submit do form de registro

	Route::get('/logout', [AdminController::class, 'logout'])->name('logout'); //Rota de logout

	Route::get('/', [AdminController::class, 'index']);

	Route::get('/{slug}/links', [AdminController::class, 'pageLinks']); //Rota dos links
	Route::get('/{slug}/design', [AdminController::class, 'pageDesign']); //Rota do design
	Route::get('/{slug}/stats', [AdminController::class, 'pageStats']); //Rota das estatísticas


	Route::get('/linkorder/{linkId}/{pos}', [AdminController::class, 'linkOrderUpdate']); //Rota que faz a troca do order dos links

	Route::get('/{slug}/newlink', [AdminController::class, 'newLink']); //Rota que renderiza a view com o formulário de criar link
	Route::post('/{slug}/newlink', [AdminController::class, 'newLinkAction']); //Rota que adiciona um novo link


	Route::get('/{slug}/editlink/{linkId}', [AdminController::class, 'editLink']); //Rota que renderiza a view com o formulário de editar link
	Route::post('/{slug}/editlink/{linkId}', [AdminController::class, 'editLinkAction']); //Rota que edita um link

	Route::get('/{slug}/dellink/{linkId}', [AdminController::class, 'delLink']); //Rota de deletar um link
});

Route::get('/{slug}',  [PageController::class, 'index']);

