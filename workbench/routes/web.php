<?php

use App\Http\Controllers\AnonymousMiddlewareController;
use App\Http\Controllers\DomainController;
use App\Http\Controllers\InvokableController;
use App\Http\Controllers\InvokablePlusController;
use App\Http\Controllers\KeyController;
use App\Http\Controllers\ModelBindingController;
use App\Http\Controllers\OptionalController;
use App\Http\Controllers\ParameterNameController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\TwoRoutesSameActionController;
use App\Http\Controllers\UrlDefaultsController;
use App\Http\Middleware\UrlDefaultsMiddleware;
use Illuminate\Support\Facades\Route;

Route::get('/closure', fn () => 'ok');
Route::get('/invokable-controller', InvokableController::class);
Route::get('/invokable-plus-controller', InvokablePlusController::class);
Route::post('/invokable-plus-controller', [InvokablePlusController::class, 'store']);

Route::get('/posts', [PostController::class, 'index'])->name('posts.index');
Route::get('/posts/create', [PostController::class, 'create'])->name('posts.create');
Route::post('/posts', [PostController::class, 'store'])->name('posts.store');
Route::get('/posts/{post}', [PostController::class, 'show'])->name('posts.show');
Route::get('/posts/{post}/edit', [PostController::class, 'edit'])->name('posts.edit');
Route::patch('/posts/{post}', [PostController::class, 'update'])->name('posts.update');
Route::delete('/posts/{post}', [PostController::class, 'destroy'])->name('posts.destroy');

Route::get('/dashboard', function () {
    return 'Dashboard';
})->name('dashboard');

Route::post('/optional/{parameter?}', [OptionalController::class, 'optional']);
Route::post('/many-optional/{one?}/{two?}/{three?}', [OptionalController::class, 'manyOptional']);

Route::post('/users/{user}', [ModelBindingController::class, 'show']);

Route::middleware(UrlDefaultsMiddleware::class)->post('/with-defaults/{locale}', [UrlDefaultsController::class, 'onlyDefaults']);
Route::middleware(UrlDefaultsMiddleware::class)->post('/with-defaults/{locale}/also/{timezone}', [UrlDefaultsController::class, 'mixedDefaults']);

Route::get('/keys/{key}', [KeyController::class, 'show']);
Route::get('/keys/{key:uuid}/edit', [KeyController::class, 'edit']);

Route::get('/parameter-names/{camelCase}/camel', [ParameterNameController::class, 'camel']);
Route::get('/parameter-names/{StudlyCase}/studly', [ParameterNameController::class, 'studly']);
Route::get('/parameter-names/{snake_case}/snake', [ParameterNameController::class, 'snake']);
Route::get('/parameter-names/{SCREAMING_SNAKE_CASE}/screaming-snake', [ParameterNameController::class, 'screamingSnake']);

Route::domain('example.test')->get('/fixed-domain/{param}', [DomainController::class, 'fixedDomain']);
Route::domain('{domain}.au')->get('/dynamic-domain/{param}', [DomainController::class, 'dynamicDomain']);

Route::get('/two-routes-one-action-1', [TwoRoutesSameActionController::class, 'same']);
Route::get('/two-routes-one-action-2', [TwoRoutesSameActionController::class, 'same']);

Route::get('/disallowed/delete', [DisallowedMethodNameController::class, 'delete']);
Route::get('/disallowed/404', [DisallowedMethodNameController::class, '404']);

Route::get('/anonymous-middleware', [AnonymousMiddlewareController::class, 'show']);
