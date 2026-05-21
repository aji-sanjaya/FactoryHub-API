<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AndroidAuthController;

Route::get('/auth/clients', [AndroidAuthController::class, 'getClients']);
Route::get('/auth/roles', [AndroidAuthController::class, 'getRoles']);
Route::get('/auth/orgs', [AndroidAuthController::class, 'getOrgs']);
Route::get('/auth/warehouses', [AndroidAuthController::class, 'getWarehouses']);
