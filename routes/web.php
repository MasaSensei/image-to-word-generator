<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WordController;

// routes/web.php
Route::get('/', [WordController::class, 'index'])->name('word.index');
Route::post('/generate', [WordController::class, 'generate'])->name('word.generate');
Route::get('/generate/status/{jobId}', [WordController::class, 'status'])->name('word.status');
Route::get('/generate/download/{jobId}', [WordController::class, 'jobDownload'])->name('word.job.download');
Route::get('/history/{id}/download', [WordController::class, 'downloadHistory'])->name('history.download');
Route::get('/history', [WordController::class, 'history'])->name('word.history');