<?php

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
    InterestPercentageController
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
    MonoLoanCalculationController
};

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ================= PUBLIC ROUTES =================
Route::post('/register', [UserController::class, 'register']);
Route::post('/verify-otp/{user_id}', [UserController::class, 'verifyOtp']);
Route::post('/login', [UserController::class, 'login']);

// ================= PROTECTED ROUTES =================
Route::middleware('auth:sanctum')->group(function () {

    // Dashboard
    Route::prefix('admin')->group(function () {
        Route::get('dashboard', [DashboardController::class, 'index']);
    });

    // Categories, Brands, Products, Bundles
    Route::apiResource('categories', CategoryController::class);
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
    Route::apiResource('bundles', BundleController::class);

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
    Route::get('/all-users', [UserController::class, 'allUsers']);
    Route::get('admin/users/with-loans', [UserController::class, 'usersWithLoans']);
    Route::get('/total-users', [UserController::class, 'totalUser']);
    Route::post('/update-user', [UserController::class, 'updateUser']);
    Route::get('/send-otp', [UserController::class, 'sendOtp']);
    Route::delete('/delete-user/{user_id}', [UserController::class, 'deleteUser']);
    Route::get('/single-user/{user_id}', [UserController::class, 'singleUser']);

    // Loan Wallet
    Route::get('/loan-wallet', [LoanWalletController::class, 'show']);

    // Loan
    Route::post('/loan-calculation', [LoanCalculationController::class, 'store']);
    Route::apiResource('/interest-percentage', InterestPercentageController::class);
    Route::get('/mono-loan/{loanCalculationId}', [MonoLoanCalculationController::class, 'store']);
    Route::post('/mono-loan/edit/{loanCalculationId}', [MonoLoanCalculationController::class, 'edit']);
    Route::post('/loan-application/{monoLoanCalculationId}', [LoanApplicationController::class, 'documents']);
    Route::post('/beneficiary-detail/{monoLoanCalculationId}', [LoanApplicationController::class, 'beneficiary']);
    Route::post('/loan-details/{monoLoanCalculationId}', [LoanApplicationController::class, 'loanDetail']);
    Route::get('/loan-dashboard', [LoanWalletController::class, 'loanDashboard']);
    Route::get('/all-loan-application', [LoanApplicationController::class, 'allLoanApplication']);
    Route::get('/single-loan-application/{id}', [LoanApplicationController::class, 'singleLoanApplication']);
    Route::delete('/delete-loan-application/{loanApplicationId}', [LoanApplicationController::class, 'destory']);
    Route::get('/all-loan-status', [LoanStatusController::class, 'allLoansStatus']);
    Route::get('/full-loan-detail/{loanStatusId}', [LoanStatusController::class, 'fullLoanDetails']);
    Route::post('/send-to-partner/{userId}', [PartnerController::class, 'sendToPartner']);
    Route::post('/loan-distributed/{loanCalculationId}', [LoanDistributedController::class, 'store']);
    Route::get('/all-loan-distributed', [LoanDistributedController::class, 'allLoansDistributed']);
    Route::post('/loan-history/{loanApplicatioId}', [LoanHistoryController::class, 'show']);
    Route::post('/loan-installment/{monoLoanCalculationId}', [LoanInstallmentController::class, 'loanInstallment']);
    Route::post('/loan-repayment/{monoLoanCalculationId}', [LoanRepaymentController::class, 'store']);
    Route::get('/show-loan-installment/{monoCalculationId}', [LoanInstallmentController::class, 'show']);
    Route::get('/all-balances', [BalanceController::class, 'index']);
    Route::get('/single-document/{mono_loan_calculation_id}', [LoanApplicationController::class, 'singleDocument']);
    Route::get('/single-beneficiary/{mono_loan_calculation_id}', [LoanApplicationController::class, 'singleBeneficiary']);
    Route::get('/single-loan-detail/{mono_loan_calculation_id}', [LoanApplicationController::class, 'singleLoanDetail']);

    // Terms
    Route::apiResource('/terms', TermController::class);

    // Partners
    Route::post('/add-partner', [PartnerController::class, 'add_partner']);
    Route::get('/all-partners', [PartnerController::class, 'all_partners']);
    Route::post('/update-partner/{partner_id}', [PartnerController::class, 'update_partner']);
    Route::get('/delete_partner/{partner_id}', [PartnerController::class, 'delete_partner']);

    // Notifications
    Route::apiResource('admin/notifications', NotificationController::class);

    // Website Tickets
    Route::apiResource('website/tickets', TicketController::class)->names('website.tickets');

    // Admin Tickets
    Route::prefix('admin')->group(function () {
        Route::apiResource('tickets', AdminTicketController::class)->names([
            'index'   => 'admin.tickets.index',
            'store'   => 'admin.tickets.store',
            'show'    => 'admin.tickets.show',
            'update'  => 'admin.tickets.update',
            'destroy' => 'admin.tickets.destroy',
        ]);
        Route::post('tickets/{ticket}/reply', [AdminTicketController::class, 'reply'])->name('admin.tickets.reply');
    });

    // Orders
    Route::prefix('orders')->group(function () {
        Route::get('/', [OrderController::class, 'index']);
        Route::post('/', [OrderController::class, 'store']);
        Route::get('/{id}', [OrderController::class, 'show']);
        Route::delete('/{id}', [OrderController::class, 'destroy']);
    });

    // Cart
    Route::prefix('cart')->group(function () {
        Route::get('/', [CartController::class, 'index']);
        Route::post('/', [CartController::class, 'store']);
        Route::put('/{id}', [CartController::class, 'update']);
        Route::delete('/{id}', [CartController::class, 'destroy']);
        Route::delete('/', [CartController::class, 'clear']);
    });

    // Transactions
    Route::get('/transactions', [TransactionController::class, 'index']);
    Route::get('/single-trancastion', [TransactionController::class, 'singleTranscation']);
    Route::get('/transactions/{id}', [TransactionController::class, 'show']);

    // Banners
    Route::apiResource('admin/banners', BannerController::class);
});
