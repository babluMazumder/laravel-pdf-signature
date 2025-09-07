<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DocumentController;

Route::get('/', function () {
    return view('welcome');
});

// routes/web.php

Route::get('/documents/upload', fn() => view('documents.upload'))->name('documents.upload');
Route::post('/documents/upload', [DocumentController::class, 'upload'])->name('documents.store');
Route::get('/documents/{document}', [DocumentController::class, 'view'])->name('documents.view');
Route::post('/documents/{document}/sign', [DocumentController::class, 'sign'])->name('documents.sign');



Route::get('/documents-pdf/{document}/edit', [DocumentController::class, 'edit'])->name('documents.edit');
Route::post('/documents-pdf/{document}/sign', [DocumentController::class, 'saveEdited'])->name('documents.sign');



