<?php

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::resource('users', UserController::class)
    ->except(['show'])
    ->middlewareFor(['index'], 'module.permission:users,can_view')
    ->middlewareFor(['create', 'store'], 'module.permission:users,can_create')
    ->middlewareFor(['edit', 'update'], 'module.permission:users,can_update')
    ->middlewareFor(['destroy'], 'module.permission:users,can_delete');
