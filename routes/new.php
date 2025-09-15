<?php

use App\Http\Controllers\Api\Admin\LoanStatusController;
use App\Http\Controllers\Api\Admin\UserController;
use Illuminate\Support\Facades\Route;


    Route::post('/add-user', [UserController::class, 'addUser']);
    Route::get('/single-loan-detail/{id}', [LoanStatusController::class, 'singleLoanDetail']);

    Route::get('/checking',function(){
        return "hello";
    });