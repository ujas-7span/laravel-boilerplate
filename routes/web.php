<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Convenient redirects to developer suite paths
Route::redirect('telescope', 'developer/telescope');
Route::redirect('horizon', 'developer/horizon');
Route::redirect('log-viewer', 'developer/log-viewer');
Route::redirect('docs/api', 'developer/docs/api');
Route::redirect('docs/api.json', 'developer/docs/api.json');
