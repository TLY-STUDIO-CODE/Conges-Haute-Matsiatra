<?php

use Illuminate\Support\Facades\Route;

// Rediriger la racine du site vers l'application Vue.js
Route::get('/', function () {
    return file_get_contents(public_path('app/index.html'));
});

// Permettre au routeur Vue.js (Vue Router) de gérer les sous-routes du frontend
Route::get('/app/{any}', function () {
    return file_get_contents(public_path('app/index.html'));
})->where('any', '.*');
