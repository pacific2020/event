<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\excelController;

Route::get('/', function () {
    return view('welcome');
});


// Route::get('/addExcel', excelController::class, 'createGuest');
