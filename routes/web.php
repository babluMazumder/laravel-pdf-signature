<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\PdfTemplateController;

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



// PDF Editor (HR side)
Route::get('/pdf/editor', function () {
    return view('pdf.pdf_editor'); // HR upload + add fields
});

Route::get('/pdf-test/assignment-data/{id}', [PdfTemplateController::class, 'getAssignmentData']);


// Employee Fill Form
Route::get('/pdf/assignment/{assignment}', function ($assignmentId) {
    return view('pdf.pdf_fill', ['assignmentId' => $assignmentId]);
});

Route::post('/pdf/upload', [PdfTemplateController::class, 'upload']);
Route::post('/pdf/{template}/fields', [PdfTemplateController::class, 'saveFields']);

Route::get('/pdf/{template}/assign/{user}', [PdfTemplateController::class, 'assignToUser']);

Route::post('/pdf/assignment/{assignment}/submit', [PdfTemplateController::class, 'submit']);
Route::get('/pdf/assignment/{id}/view', [PdfTemplateController::class, 'viewSignedPdf']);






