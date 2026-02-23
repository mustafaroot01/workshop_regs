<?php

use Illuminate\Support\Facades\Route;

// Catch-all route to serve the Vue SPA
Route::get('/{any}', function () {
    $path = public_path('index.html');
    if (file_exists($path)) {
        return response(file_get_contents($path))->header('Content-Type', 'text/html; charset=utf-8');
    }
    return view('welcome');
})->where('any', '.*');

