<?php

use App\Http\Controllers\WIthdrawController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Admin\{
    TermController,
    UserController,
    BrandController,
    BannerController,
    BundleController,
    ReportController,
    BalanceController,
    PartnerController,
    ProductController,
    CategoryController,
    DashboardController,
    LoanStatusController,
    AdminTicketController,
    NotificationController,
    LoanDistributedController,
    InterestPercentageController,
    AnalyticController,
    BNPLAdminController
};
use App\Http\Controllers\Api\Website\{
    CartController,
    OrderController,
    TicketController,
    LoanWalletController,
    LinkAccountController,
    LoanHistoryController,
    TransactionController,
    LoanRepaymentController,
    ProductReviewController,
    DeliveryAddressController,
    LoanApplicationController,
    LoanCalculationController,
    LoanInstallmentController,
    MonoLoanCalculationController,
    ConfigurationController,
    BNPLController,
    CalendarController,
    AuditController
};
use App\Http\Controllers\InstallmentController;
use App\Http\Controllers\KycController;
use App\Http\Controllers\ReferralController;
use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/
require __DIR__.'/new.php';    

Route::get('/optimize-app', function () {
    Artisan::call('optimize:clear'); // Clears cache, config, route, and view caches
    Artisan::call('cache:clear');    // Clears application cache
    Artisan::call('config:clear');   // Clears configuration cache
    Artisan::call('route:clear');    // Clears route cache
    Artisan::call('view:clear');     // Clears compiled Blade views
    Artisan::call('config:cache');   // Rebuilds configuration cache
    Artisan::call('route:cache');    // Rebuilds route cache
    Artisan::call('view:cache');     // Precompiles Blade templates
    Artisan::call('optimize');       // Optimizes class loading

    return "Application optimized and caches cleared successfully!";
});
Route::get('/migrate', function () {
    Artisan::call('migrate');
    return response()->json(['message' => 'Migration successful'], 200);
});
Route::get('/migrate/rollback', function () {
    Artisan::call('migrate:rollback');
    return response()->json(['message' => 'Migration rollback successfully'], 200);
});

Route::post('/register', [UserController::class, 'register']);
Route::post('/verify-otp/{user_id}', [UserController::class, 'verifyOtp']);
Route::post('/login', [UserController::class, 'login']);
Route::post('/admin-login', [UserController::class, 'adminLogin']);
Route::post('/forget-password', [UserController::class, 'forgetPassword']);  //requirec. email
Route::post('/verify-reset-password-otp', [UserController::class, 'verifyResetPasswordOtp']);
Route::post('/reset-password', [UserController::class, 'resetPassword']);

// Configuration endpoints (public)
Route::get('/config/customer-types', [ConfigurationController::class, 'getCustomerTypes']);
Route::get('/config/audit-types', [ConfigurationController::class, 'getAuditTypes']);
Route::get('/config/states', [ConfigurationController::class, 'getStates']);
Route::get('/config/loan-configuration', [ConfigurationController::class, 'getLoanConfiguration']);
Route::get('/config/add-ons', [ConfigurationController::class, 'getAddOns']);
Route::get('/config/delivery-locations', [ConfigurationController::class, 'getDeliveryLocations']);

// Public bundles endpoint (for Buy Now flow)
Route::get('/bundles', [\App\Http\Controllers\Api\Website\BundleController::class, 'index']);

// Public cart access via token (from email links - allows access before login)
Route::get('/cart/access/{token}', [\App\Http\Controllers\Api\Website\CartController::class, 'accessCartViaToken']);

// ================= PROTECTED ROUTES =================
Route::middleware('auth:sanctum')->group(function () {

    // Dashboard
    Route::prefix('admin')->group(function () {
        Route::get('dashboard', [DashboardController::class, 'index']);
    });

    // Categories, Brands, Products, Bundles
    Route::apiResource('categories', CategoryController::class);
    Route::post('categories/{category}/update', [CategoryController::class, 'update']);
    Route::get('/categories/{id}/brands', [BrandController::class, 'getByCategory']);
    Route::get('/categories/{id}/products', [CategoryController::class, 'getProducts']);
    Route::get('/brands/{id}/products', [ProductController::class, 'getProductsByBrand']);
    Route::get('/brands/{ids}/products', [ProductController::class, 'getProductsByBrand']);
    Route::get('/brands/{id}/products/{productId}', [ProductController::class, 'showProductByBrand']);
    Route::get('/brands/{ids}/products/{productId}', [ProductController::class, 'showProductByBrand']);
    Route::get('/categories/{category}/brands/{brand}', [BrandController::class, 'showBrandByCategory']);
    Route::get('/categories/{category}/products/{product}', [ProductController::class, 'showProductByCategoryBrand']);
    Route::get('/categories/{category}/brands/{brand}/products/{product}', [ProductController::class, 'showProductByCategory']);
    Route::get('/cart/checkout-summary', [CartController::class, 'checkoutSummary']);
    // categories/{categoryId}/brands/{brandIds}/product/{productId}
    Route::apiResource('brands', BrandController::class);
    Route::apiResource('products', ProductController::class);
    Route::post('products/{product}/update', [ProductController::class, 'update'])
     ->name('products.update.post');

    // Route::get('/products/top-products')
    Route::apiResource('bundles', BundleController::class);
    // Accept POST /bundles/{bundle}/update as an alternative to PUT
Route::post('bundles/{bundle}/update', [BundleController::class, 'update'])
     ->name('bundles.update.post');


    // Delivery address
    Route::prefix('delivery-address')->group(function () {
        Route::get('index', [DeliveryAddressController::class, 'index']);
        Route::get('show/{id}', [DeliveryAddressController::class, 'show']);
        Route::post('store', [DeliveryAddressController::class, 'store']);
        Route::put('update/{id}', [DeliveryAddressController::class, 'update']);
        Route::delete('delete/{id}', [DeliveryAddressController::class, 'destroy']);
    });

    // Reviews
    Route::prefix('product-reviews')->group(function () {
        Route::post('/', [ProductReviewController::class, 'store']);
        Route::put('/{id}', [ProductReviewController::class, 'update']);
    });

    // Linked Accounts
    Route::prefix('link-accounts')->group(function () {
        Route::get('/', [LinkAccountController::class, 'index']);
        Route::post('/', [LinkAccountController::class, 'store']);
        Route::get('{id}', [LinkAccountController::class, 'show']);
        Route::put('{id}', [LinkAccountController::class, 'update']);
        Route::delete('{id}', [LinkAccountController::class, 'destroy']);
    });

    // User
    Route::post('/logout', [UserController::class, 'logout']);


    //wallet routes
    Route::post('/fund-wallet', [LoanWalletController::class, 'fundWallet']);
    // Loan Wallet
    Route::get('/loan-wallet', [LoanWalletController::class, 'show']);

    // Loan new routes

    //loan calculation on click of proeec call the finalized and than eveyertime user open call the status and check if status is offered than go to next page and call the offeredloancalculation
    Route::post('/loan-calculation', action: [LoanCalculationController::class, 'store']);
    Route::post('/loan-calculation-finalized/{id}', [LoanCalculationController::class, 'finalized']); //calculated ,pending,offered,finalized
    Route::get('/loan-calculation-stauts', [LoanCalculationController::class, 'status']);
    Route::get('/offered-loan-calculation', [LoanCalculationController::class, 'offeredLoanCalculation']);
    Route::post('/loan-application/{monoLoanCalculationId}', [LoanApplicationController::class, 'documents']);
    
    // BNPL Flow endpoints
    Route::get('/bnpl/applications', [BNPLController::class, 'getApplications']); // List all user's BNPL applications
    Route::post('/bnpl/apply', [BNPLController::class, 'apply']);
    Route::get('/bnpl/status/{application_id}', [BNPLController::class, 'getStatus']); // Get single application details
    Route::get('/bnpl/applications/{application_id}/repayment-schedule', [BNPLController::class, 'getRepaymentSchedule']); // Get repayment schedule for application
    Route::post('/bnpl/guarantor/invite', [BNPLController::class, 'inviteGuarantor']);
    Route::post('/bnpl/guarantor/upload', [BNPLController::class, 'uploadGuarantorForm']);
    Route::post('/bnpl/counteroffer/accept', [BNPLController::class, 'acceptCounterOffer']);
    
    // BNPL Orders endpoints
    Route::get('/bnpl/orders', [BNPLController::class, 'getOrders']); // List all user's BNPL orders
    Route::get('/bnpl/orders/{order_id}', [BNPLController::class, 'getOrderDetails']); // Get single BNPL order with repayment details
    
    // Audit Request endpoints
    Route::post('/audit/request', [AuditController::class, 'submit']);
    Route::get('/audit/request/{id}', [AuditController::class, 'getStatus']);
    Route::get('/audit/requests', [AuditController::class, 'index']);
    
    // Buy Now Flow endpoints
    Route::post('/orders/checkout', [OrderController::class, 'checkout']);
    Route::get('/orders/{id}/summary', [OrderController::class, 'getOrderSummary']);
    Route::get('/orders/{id}/invoice-details', [OrderController::class, 'getInvoiceDetails']);
    
    // Calendar/Scheduling endpoints
    Route::get('/calendar/slots', [CalendarController::class, 'getSlots']);
    // Route::get('/get-currentmonth-installment', [InstallmentController::class, 'currentMonthInstallment'])
    Route::get('/installments/with-history', action: [InstallmentController::class, 'historyWithCurrentMonth']);
    Route::post('/installments/{installmentId}/pay',    [InstallmentController::class, 'payInstallment']);;
    //for admin  for offering the loan
    Route::post('/mono-loan/{loanCalculationId}', [MonoLoanCalculationController::class, 'store']);
    Route::post('/loan-application-grant/{id}', [MonoLoanCalculationController::class, 'grant']);

    //for accepting use following route
    //kyc

    //tools 
    Route::post('/loan-calculator-tool', [LoanCalculationController::class, 'tool']);
    // User
    Route::post('/kyc', [KycController::class, 'store']);
    Route::get('/kyc/status', [KycController::class, 'myStatus']);
    Route::post('/kyc/{kyc}/replace-file', [KycController::class, 'replaceFile'])->whereNumber('kyc');

    // Admin (manual role check inside controller)
    Route::get('/admin/kyc', [KycController::class, 'index']);
    Route::post('/admin/kyc/{kyc}/review', [KycController::class, 'review'])->whereNumber('kyc');


    //loan old routes
    Route::apiResource('/interest-percentage', InterestPercentageController::class);
    Route::post('/beneficiary-detail/{monoLoanCalculationId}', [LoanApplicationController::class, 'beneficiary']);
    Route::post('/loan-details/{monoLoanCalculationId}', [LoanApplicationController::class, 'loanDetail']);
    Route::post('/mono-loan/edit/{loanCalculationId}', [MonoLoanCalculationController::class, 'edit']);
    Route::get('/loan-dashboard', [LoanWalletController::class, 'loanDashboard']);
    Route::get('/all-loan-application', [LoanApplicationController::class, 'allLoanApplication']);
    Route::get('/single-loan-application/{id}', [LoanApplicationController::class, 'singleLoanApplication']);
    Route::delete('/delete-loan-application/{loanApplicationId}', [LoanApplicationController::class, 'destory']);

    Route::get('/all-loan-status', [LoanStatusController::class, 'allLoansStatus']);



    Route::post('/send-to-partner/{userId}', [PartnerController::class, 'sendToPartner']);
    Route::post('/loan-distributed/{loanCalculationId}', [LoanDistributedController::class, 'store']);
    Route::get('/all-loan-distributed', [LoanDistributedController::class, 'allLoansDistributed']);
    Route::post('/loan-history/{loanApplicatioId}', [LoanHistoryController::class, 'show']);
    Route::post('/loan-installment/{monoLoanCalculationId}', [LoanInstallmentController::class, 'loanInstallment']);
    Route::post('/loan-repayment/{monoLoanCalculationId}', [LoanRepaymentController::class, 'store']);
    Route::get('/show-loan-installment/{monoCalculationId}', [LoanInstallmentController::class, 'show']);
    Route::get('/single-document/{mono_loan_calculation_id}', [LoanApplicationController::class, 'singleDocument']);
    Route::get('/single-beneficiary/{mono_loan_calculation_id}', [LoanApplicationController::class, 'singleBeneficiary']);
    Route::get('/single-loan-detail/{mono_loan_calculation_id}', [LoanApplicationController::class, 'singleLoanDetail']);

    // Terms

    // Website Tickets
    Route::apiResource('website/tickets', TicketController::class)->names('website.tickets');

    // Admin Tickets

    // Orders
    Route::prefix('orders')->group(function () {
        Route::get('/', [OrderController::class, 'index']);
        Route::post('/', [OrderController::class, 'store']);
        Route::get('/{id}', [OrderController::class, 'show']);
        Route::delete('/{id}', [OrderController::class, 'destroy']);
        Route::get('/user/{userId}', [OrderController::class, 'forUser']);
    });
    //route for payment confirmation


    // Cart
    Route::prefix('cart')->group(function () {
        Route::get('/', [CartController::class, 'index']);
        Route::post('/', [CartController::class, 'store']);
        Route::put('/{id}', [CartController::class, 'update']);
        Route::delete('/{id}', [CartController::class, 'destroy']);
        Route::delete('/', [CartController::class, 'clear']);
    });
    Route::post('order/payment-confirmation', [OrderController::class, 'paymentConfirmation']);
    Route::post('order/pay-by-loan', [OrderController::class, 'payByLoan']);
    // Transactions

    // Transactions  -----------------------------------x-----------------
    // current user (or ALL if admin)
    Route::get('/single-trancastion', [TransactionController::class, 'singleTranscation']);
    Route::get('/transactions/user/{userId}', [TransactionController::class, 'forUser'])
        ->whereNumber('userId');
    Route::get('/transactions/{id}', [TransactionController::class, 'show'])
        ->whereNumber('id');
    Route::post('withdraw', [WIthdrawController::class, 'store']);
    Route::get('/withdraw/get', [WIthdrawController::class, 'getWithdrawRequest']);
    // Admin: use /api/admin/users to show the TRANSACTIONS table (not the raw users list)
    // Banners
    //common routes
    Route::get('/transactions', [TransactionController::class, 'index']);

    //admin routes
    Route::get('/transactions-for-user', [TransactionController::class, 'getforUser']);
    Route::get('/all-balances', [BalanceController::class, 'index']);
    Route::apiResource('admin/banners', BannerController::class);
    Route::prefix('admin')->group(function () {
        Route::get('/users', [TransactionController::class, 'index']); // ⬅ REPOINTED to transactions
        Route::get('/users/{userId}', [TransactionController::class, 'forUser'])->whereNumber('userId'); // ⬅ REPOINTED
    });


    Route::apiResource('/terms', TermController::class);

    // Partners
    Route::post('admin/add-partner', [PartnerController::class, 'add_partner']);
    Route::get('admin/all-partners', [PartnerController::class, 'all_partners']);
    Route::post('admin/update-partner/{partner_id}', [PartnerController::class, 'update_partner']);
    Route::get('admin/delete_partner/{partner_id}', [PartnerController::class, 'delete_partner']);
    // Notifications
    Route::apiResource('admin/notifications', NotificationController::class);
    Route::prefix('admin')->group(function () {
        Route::apiResource('tickets', AdminTicketController::class)->names([
            'index'   => 'admin.tickets.index',
            'store'   => 'admin.tickets.store',
            'show'    => 'admin.tickets.show',
            'update'  => 'admin.tickets.update',
            'destroy' => 'admin.tickets.destroy',
        ]);
     Route::post('tickets/{ticketId}/status', [AdminTicketController::class, 'status'])->name('admin.tickets.status');
        Route::post('tickets/{ticket}/reply', [AdminTicketController::class, 'reply'])->name('admin.tickets.reply');
    });
    Route::get('admin/analytics', [AnalyticController::class, 'index']);
    Route::get('/all-users', [UserController::class, 'allUsers']);

    // ================= REFERRAL ADMIN ROUTES =================
    Route::prefix('admin/referral')->group(function () {
        Route::get('settings', [\App\Http\Controllers\Api\Admin\ReferralAdminController::class, 'getSettings']);
        Route::put('settings', [\App\Http\Controllers\Api\Admin\ReferralAdminController::class, 'updateSettings']);
        Route::get('list', [\App\Http\Controllers\Api\Admin\ReferralAdminController::class, 'getReferralList']);
        Route::get('user/{userId}', [\App\Http\Controllers\Api\Admin\ReferralAdminController::class, 'getUserReferralDetails']);
    });

    //new user creations

    Route::get('admin/users/with-loans', [UserController::class, 'usersWithLoans']);
    Route::get('/total-users', [UserController::class, 'totalUser']);
    Route::post('/update-user', [UserController::class, 'updateUser']);
    Route::get('/send-otp', [UserController::class, 'sendOtp']);

    Route::delete('/delete-user/{user_id}', [UserController::class, 'deleteUser']);
    Route::get('/single-user/{user_id}', [UserController::class, 'singleUser']);
    //refferal routes
    Route::get('/get-referral-details', [ReferralController::class, 'getBalance']);
    //for admin
    Route::get('/withdraw/approve/{id}', [WIthdrawController::class, 'approveRequest']);


    Route::get('/full-loan-detail/{loanStatusId}', [LoanStatusController::class, 'fullLoanDetails']);
    //use the following for the single loan detail modal

    // ================= BNPL ADMIN ROUTES =================
    Route::prefix('admin/bnpl')->group(function () {
        Route::get('/applications', [BNPLAdminController::class, 'index']);
        Route::get('/applications/{id}', [BNPLAdminController::class, 'show']);
        Route::put('/applications/{id}/status', [BNPLAdminController::class, 'updateStatus']);
        Route::get('/guarantors', [BNPLAdminController::class, 'getGuarantors']);
        Route::put('/guarantors/{id}/status', [BNPLAdminController::class, 'updateGuarantorStatus']);
    });

    // ================= AUDIT ADMIN ROUTES =================
    Route::prefix('admin/audit')->group(function () {
        Route::get('/users-with-requests', [\App\Http\Controllers\Api\Admin\AuditAdminController::class, 'getUsersWithAuditRequests']);
        Route::get('/requests', [\App\Http\Controllers\Api\Admin\AuditAdminController::class, 'index']);
        Route::get('/requests/{id}', [\App\Http\Controllers\Api\Admin\AuditAdminController::class, 'show']);
        Route::put('/requests/{id}/status', [\App\Http\Controllers\Api\Admin\AuditAdminController::class, 'updateStatus']);
    });

    // ================= BUY NOW ADMIN ROUTES =================
    Route::prefix('admin/orders')->group(function () {
        Route::get('/buy-now', [OrderController::class, 'getBuyNowOrders']);
        Route::get('/buy-now/{id}', [OrderController::class, 'getBuyNowOrder']);
        Route::put('/buy-now/{id}/status', [OrderController::class, 'updateBuyNowOrderStatus']);
        Route::get('/bnpl', [OrderController::class, 'getBnplOrders']);
        Route::get('/bnpl/{id}', [OrderController::class, 'getBnplOrder']);
    });

    // ================= ADMIN CUSTOM ORDER ROUTES =================
    Route::prefix('admin/cart')->group(function () {
        Route::get('/users-with-carts', [\App\Http\Controllers\Api\Admin\AdminCartController::class, 'getUsersWithCarts']);
        Route::post('/create-custom-order', [\App\Http\Controllers\Api\Admin\AdminCartController::class, 'createCustomOrder']);
        Route::get('/products', [\App\Http\Controllers\Api\Admin\AdminCartController::class, 'getProducts']);
        Route::get('/user/{userId}', [\App\Http\Controllers\Api\Admin\AdminCartController::class, 'getUserCart']);
        Route::delete('/user/{userId}/item/{itemId}', [\App\Http\Controllers\Api\Admin\AdminCartController::class, 'removeCartItem']);
        Route::delete('/user/{userId}/clear', [\App\Http\Controllers\Api\Admin\AdminCartController::class, 'clearUserCart']);
        Route::post('/resend-email/{userId}', [\App\Http\Controllers\Api\Admin\AdminCartController::class, 'resendCartEmail']);
    });

    // ================= USER CART ACCESS VIA TOKEN =================
    // Note: This route is inside auth middleware but should allow public access
    // Consider moving outside auth middleware if users need to access before login
});
