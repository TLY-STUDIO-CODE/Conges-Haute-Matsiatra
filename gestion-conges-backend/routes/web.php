<?php

use Illuminate\Support\Facades\Route;

// Rediriger la racine du site vers le frontend Vue.js compilé
Route::get('/', function () {
    return file_get_contents(public_path('app/index.html'));
});

// Permettre au routeur Vue (Vue Router) de gérer toutes les sous-routes
Route::get('/app/{any}', function () {
    return file_get_contents(public_path('app/index.html'));
})->where('any', '.*');
