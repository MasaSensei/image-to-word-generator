<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WordController;

Route::get('/', [WordController::class, 'index'])->name('home');
Route::post('/generate', [WordController::class, 'generate'])->name('generate');
Route::get('/history/download/{id}', [WordController::class, 'downloadHistory'])->name('history.download');
