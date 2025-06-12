<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;


Auth::routes();

Route::get('/debug-files', function () {
    $files = File::files(public_path('uploads'));
    return response()->json($files);
});

