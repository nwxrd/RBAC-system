<?php

use App\Http\Controllers\RoleController;
use Illuminate\Support\Facades\Route;

Route::resource('roles', RoleController::class)
    ->except(['show'])
    ->middlewareFor(['index'], 'module.permission:roles,can_view')
    ->middlewareFor(['create', 'store'], 'module.permission:roles,can_create')
    ->middlewareFor(['edit', 'update'], 'module.permission:roles,can_update')
    ->middlewareFor(['destroy'], 'module.permission:roles,can_delete');
