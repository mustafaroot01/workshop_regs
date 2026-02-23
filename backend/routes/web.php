<?php

use Illuminate\Support\Facades\Route;

// Catch-all route to serve the Vue SPA
// Exclude /build/ paths so static assets are served directly by the web server
Route::get('/{any}', function () {
    $path = public_path('build/index.html');
    if (file_exists($path)) {
        return response(file_get_contents($path))->header('Content-Type', 'text/html; charset=utf-8');
    }
    return view('welcome');
})->where('any', '^(?!build/).*$');
