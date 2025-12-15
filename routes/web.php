<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::any('{any}', function (Request $request) {
    return response()->json([
        'message' => 'Ruta no encontrada. Esta es una API Backend-only.',
        'error' => 'Es probable que hayas olvidado el prefijo /api en tu URL.',
        'suggested_url' => $request->root() . '/api/' . $request->path(),
        'status' => 404
    ], 404);
})->where('any', '.*');
