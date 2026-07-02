<?php
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\JwtMiddleware;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\StoreController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\BrandController;
use App\Http\Controllers\Api\PurchaseController;
use App\Http\Controllers\Api\PurchaseReturnController;
use App\Http\Controllers\Api\SaleController;
use App\Http\Controllers\Api\SaleReturnController;
use App\Http\Controllers\Api\ExpenseCategoryController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\StockAdjustmentController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\ActivityLogController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\DashboardController;

use App\Http\Controllers\Api\PasswordResetController;

Route::prefix('admin')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [PasswordResetController::class, 'forgotPassword']);
    Route::post('/validate-reset-token', [PasswordResetController::class, 'validateToken']);
    Route::post('/reset-password', [PasswordResetController::class, 'resetPassword']);
});

Route::middleware([JwtMiddleware::class])->prefix('admin')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/update-profile', [AuthController::class, 'updateProfile']);
    Route::get('/users', [UserController::class, 'getAllUsers']);
    Route::post('/create-user', [UserController::class, 'createUser']);
    Route::put('/update-user/{id}', [UserController::class, 'updateUser']);
    Route::delete('/delete-user/{id}', [UserController::class, 'deleteUser']);
    // Customer Routes
    Route::get('/customers', [CustomerController::class, 'getAllCustomer']);
    Route::get('/view-customers/{id}', [CustomerController::class, 'viewCustomer']);
    Route::post('/create-customer', [CustomerController::class, 'createCustomer']);
    Route::put('/update-customer/{id}', [CustomerController::class, 'updateCustomer']);
    Route::delete('/delete-customer/{id}', [CustomerController::class, 'deleteCustomer']);
    // Supplier Routes
    Route::get('/suppliers', [SupplierController::class, 'getAllSuppliers']);
    Route::get('/view-suppliers/{id}', [SupplierController::class, 'viewSupplier']);
    Route::post('/create-supplier', [SupplierController::class, 'createSupplier']);
    Route::put('/update-supplier/{id}', [SupplierController::class, 'updateSupplier']);
    Route::delete('/delete-supplier/{id}', [SupplierController::class, 'deleteSupplier']);
    // Store Routes
    Route::get('/stores', [StoreController::class, 'getAllStore']);
    Route::get('/view-stores/{id}', [StoreController::class, 'viewStore']);
    Route::post('/create-store', [StoreController::class, 'createStore']);
    Route::put('/update-store/{id}', [StoreController::class, 'updateStore']);
    Route::delete('/delete-store/{id}', [StoreController::class, 'deleteStore']);
    // Product Routes
    Route::get('/products', [ProductController::class, 'getAllProducts']);
    Route::get('/view-products/{id}', [ProductController::class, 'viewProduct']);
    Route::post('/create-product', [ProductController::class, 'createProduct']);
    Route::post('/update-product/{id}', [ProductController::class, 'updateProduct']);
    Route::delete('/delete-product/{id}', [ProductController::class, 'deleteProduct']);
    // Category Routes
    Route::get('/categories', [CategoryController::class, 'getAllCategories']);
    Route::get('/view-categories/{id}', [CategoryController::class, 'viewCategory']);
    Route::post('/create-category', [CategoryController::class, 'createCategory']);
    Route::put('/update-category/{id}', [CategoryController::class, 'updateCategory']);
    Route::delete('/delete-category/{id}', [CategoryController::class, 'deleteCategory']);
    // Brand Routes
    Route::get('/brands', [BrandController::class, 'getAllBrands']);
    Route::get('/view-brands/{id}', [BrandController::class, 'viewBrand']);
    Route::post('/create-brand', [BrandController::class, 'createBrand']);
    Route::post('/update-brand/{id}', [BrandController::class, 'updateBrand']);
    Route::delete('/delete-brand/{id}', [BrandController::class, 'deleteBrand']);
    // Purchase Routes
    Route::get('/purchases', [PurchaseController::class, 'getAllPurchases']);
    Route::get('/view-purchases/{id}', [PurchaseController::class, 'viewPurchase']);
    Route::post('/create-purchase', [PurchaseController::class, 'createPurchase']);
    Route::put('/update-purchase/{id}', [PurchaseController::class, 'updatePurchase']);
    Route::delete('/delete-purchase/{id}', [PurchaseController::class, 'deletePurchase']);
    // Purchase Return Routes
    Route::get('/purchase-returns', [PurchaseReturnController::class, 'getAllPurchaseReturns']);
    Route::get('/view-purchase-returns/{id}', [PurchaseReturnController::class, 'viewPurchaseReturn']);
    Route::post('/create-purchase-return', [PurchaseReturnController::class, 'createPurchaseReturn']);
    Route::put('/update-purchase-return/{id}', [PurchaseReturnController::class, 'updatePurchaseReturn']);
    Route::delete('/delete-purchase-return/{id}', [PurchaseReturnController::class, 'deletePurchaseReturn']);
    // Sale Routes
    Route::get('/sales', [SaleController::class, 'getAllSales']);
    Route::get('/view-sales/{id}', [SaleController::class, 'viewSale']);
    Route::post('/create-sale', [SaleController::class, 'createSale']);
    Route::put('/update-sale/{id}', [SaleController::class, 'updateSale']);
    Route::delete('/delete-sale/{id}', [SaleController::class, 'deleteSale']);
    // Sale Return Routes
    Route::get('/sale-returns', [SaleReturnController::class, 'getAllSaleReturns']);
    Route::get('/view-sale-returns/{id}', [SaleReturnController::class, 'viewSaleReturn']);
    Route::post('/create-sale-return', [SaleReturnController::class, 'createSaleReturn']);
    Route::put('/update-sale-return/{id}', [SaleReturnController::class, 'updateSaleReturn']);
    Route::delete('/delete-sale-return/{id}', [SaleReturnController::class, 'deleteSaleReturn']);
    // Expense Category Routes
    Route::get('/expense-categories', [ExpenseCategoryController::class, 'getAllExpenseCategories']);
    Route::get('/view-expense-categories/{id}', [ExpenseCategoryController::class, 'viewExpenseCategory']);
    Route::post('/create-expense-category', [ExpenseCategoryController::class, 'createExpenseCategory']);
    Route::put('/update-expense-category/{id}', [ExpenseCategoryController::class, 'updateExpenseCategory']);
    Route::delete('/delete-expense-category/{id}', [ExpenseCategoryController::class, 'deleteExpenseCategory']);
    // Expense Routes
    Route::get('/expenses', [ExpenseController::class, 'getAllExpenses']);
    Route::get('/view-expenses/{id}', [ExpenseController::class, 'viewExpense']);
    Route::post('/create-expense', [ExpenseController::class, 'createExpense']);
    Route::post('/update-expense/{id}', [ExpenseController::class, 'updateExpense']);
    Route::delete('/delete-expense/{id}', [ExpenseController::class, 'deleteExpense']);
    // Stock Adjustment Routes
    Route::get('/adjustments', [StockAdjustmentController::class, 'getAllAdjustments']);
    Route::get('/view-adjustments/{id}', [StockAdjustmentController::class, 'viewAdjustment']);
    Route::post('/create-adjustment', [StockAdjustmentController::class, 'createAdjustment']);
    Route::put('/update-adjustment/{id}', [StockAdjustmentController::class, 'updateAdjustment']);
    Route::delete('/delete-adjustment/{id}', [StockAdjustmentController::class, 'deleteAdjustment']);
    // Report Routes
    Route::get('/reports/sales',      [ReportController::class, 'salesReport']);
    Route::get('/reports/purchases',  [ReportController::class, 'purchaseReport']);
    Route::get('/reports/inventory',  [ReportController::class, 'inventoryReport']);
    Route::get('/reports/customers',  [ReportController::class, 'customerReport']);
    Route::get('/reports/suppliers',  [ReportController::class, 'supplierReport']);
    // Activity Log Routes
    Route::get('/activity-logs',   [ActivityLogController::class, 'getActivityLogs']);
    Route::delete('/activity-logs/clear', [ActivityLogController::class, 'clearActivityLogs']);
    // Settings Routes
    Route::get('/settings',              [SettingsController::class, 'getSettings']);
    Route::put('/settings',              [SettingsController::class, 'updateSettings']);
    Route::post('/settings/test-email',  [SettingsController::class, 'testEmailConnection']);
    // Dashboard Route
    Route::get('/dashboard', [DashboardController::class, 'getDashboardData']);
    // Notification Routes
    Route::get('/notifications', [NotificationController::class, 'getNotifications']);
    Route::post('/notifications/mark-read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);
});
