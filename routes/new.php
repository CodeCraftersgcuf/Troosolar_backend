<?php

use App\Http\Controllers\Api\Admin\LoanStatusController;
use App\Http\Controllers\Api\Admin\NotificationController;
use App\Http\Controllers\Api\Admin\PartnerController;
use App\Http\Controllers\Api\Admin\UserController;
use App\Http\Controllers\Api\Website\LoanApplicationController;
use Illuminate\Support\Facades\Route;


Route::get('user-notifications', [NotificationController::class, 'userNotifications']);

//adming routes

    Route::post('/add-user', [UserController::class, 'addUser']);
    Route::get('/single-loan-detail/{id}', [LoanStatusController::class, 'singleLoanDetail']);
    Route::get('loan-kyc-details/{loan_application_id}', [LoanApplicationController::class, 'loanKycDetails']);
    Route::get('/admin/send-to-partner/{userId}',[PartnerController::class, 'sendToPartner']);
    Route::get('/checking',function(){
        return "hello";
    });