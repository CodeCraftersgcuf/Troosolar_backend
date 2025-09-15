<?php

use App\Http\Controllers\Api\Admin\LoanStatusController;
use App\Http\Controllers\Api\Admin\NotificationController;
use App\Http\Controllers\Api\Admin\UserController;
use App\Http\Controllers\Api\Website\LoanApplicationController;
use Illuminate\Support\Facades\Route;


Route::get('user-notifications', [NotificationController::class, 'userNotifications']);

//adming routes

    Route::post('/add-user', [UserController::class, 'addUser']);
    Route::get('/single-loan-detail/{id}', [LoanStatusController::class, 'singleLoanDetail']);
    Route::get('loan-kyc-details/{userId}', [LoanApplicationController::class, 'loanKycDetails']);
    Route::get('/checking',function(){
        return "hello";
    });