<?php

use App\Http\Controllers\ImageComparisonController;
use Illuminate\Support\Facades\Route;

Route::post('/image-comparison', [ImageComparisonController::class, 'store'])->name('api.image-comparison');
