<?php

use App\Http\Controllers\site\SiteController;
use Illuminate\Support\Facades\Route;


Route::get('/', function () {
    return redirect()->route('cloud-project.index');
});

Route::prefix('cloud-project')->name('cloud-project.')->controller(SiteController::class)->middleware('auth')->group(function () {
    Route::get('/', 'index')->name('index')->withoutMiddleware('auth');
    Route::post('upload-files', 'upload_files')->name('upload-files');
    Route::delete('delete-file/{id}', 'delete_file')->name('delete-file');
    Route::get('view-file/{id}', 'view_file')->name('view-file');
    Route::get('search-documents', 'search_doucments')->name('search-documents');
    Route::get('classify-documents', 'classify_doucments')->name('classify-doucments');
    Route::get('test', function () {

         function formatFileSize($bytes, $precision = 2)
        {
            $units = ['B', 'KB', 'MB', 'GB'];
            $pow = $bytes > 0 ? floor(log($bytes, 1024)) : 0;
            return round($bytes / pow(1024, $pow), $precision) . ' ' . $units[$pow];
        }

        $filePath = public_path('uploads\files\pdf\ppt9_Marketing.pdf');
        $size = filesize($filePath);
        return dd(formatFileSize($size));
    });
});
