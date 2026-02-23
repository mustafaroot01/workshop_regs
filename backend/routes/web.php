<?php

use Illuminate\Support\Facades\Route;

// Catch-all route to serve the Vue SPA
Route::get('/{any}', function () {
    $path = public_path('index.html');
    if (file_exists($path)) {
        return file_get_contents($path);
    }
    return view('welcome');
})->where('any', '.*');
