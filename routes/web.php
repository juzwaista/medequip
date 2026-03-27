<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Controllers
|--------------------------------------------------------------------------
*/
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\Owner\DashboardController as OwnerDashboardController;
use App\Http\Controllers\Owner\OrderController;
use App\Http\Controllers\Owner\DistributorController;
use App\Http\Controllers\Owner\LicenseController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\CourierController;
use App\Http\Controllers\SuperAdmin\AdminManagementController;
use App\Http\Controllers\Courier\DashboardController as CourierDashboardController;
use App\Http\Controllers\Courier\DeliveryController as CourierDeliveryController;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return redirect('/products');
})->name('landing');

// Static Pages
Route::get('/about', function () { return \Inertia\Inertia::render('Static/About'); })->name('about');
Route::get('/contact', function () { return \Inertia\Inertia::render('Static/Contact'); })->name('contact');
Route::get('/help', function () { return \Inertia\Inertia::render('Static/Help'); })->name('help');


// Product Routes (Public) - search must come before the {id} wildcard
Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/search', [ProductController::class, 'search'])->name('products.search');
Route::get('/products/{id}', [ProductController::class, 'show'])->name('products.show');
Route::get('/category/{category:slug}', [ProductController::class, 'byCategory'])->name('products.category');

// Cart Routes
Route::get('/cart', [\App\Http\Controllers\CartController::class, 'index'])->name('cart.index');
Route::post('/cart/add', [\App\Http\Controllers\CartController::class, 'add'])->name('cart.add');
Route::patch('/cart/{lineKey}', [\App\Http\Controllers\CartController::class, 'update'])->name('cart.update');
Route::delete('/cart/{lineKey}', [\App\Http\Controllers\CartController::class, 'remove'])->name('cart.remove');
Route::delete('/cart', [\App\Http\Controllers\CartController::class, 'clear'])->name('cart.clear');
Route::get('/cart/count', [\App\Http\Controllers\CartController::class, 'count'])->name('cart.count');

// Wallet (Auth required)
Route::middleware('auth')->group(function () {
    Route::get('/wallet', [\App\Http\Controllers\WalletController::class, 'index'])->name('wallet.index');
    Route::post('/wallet/topup', [\App\Http\Controllers\WalletController::class, 'topup'])->name('wallet.topup');
    Route::get('/wallet/topup/success', [\App\Http\Controllers\WalletController::class, 'topupSuccess'])->name('wallet.topup.success');
    Route::get('/wallet/topup/cancel', [\App\Http\Controllers\WalletController::class, 'topupCancel'])->name('wallet.topup.cancel');
    Route::post('/wallet/withdraw', [\App\Http\Controllers\WalletController::class, 'withdraw'])->name('wallet.withdraw');
});

// Checkout and Orders (Auth required)
Route::middleware('auth')->group(function () {
    Route::get('/checkout', [\App\Http\Controllers\OrderController::class, 'checkout'])->name('checkout');
    Route::post('/orders', [\App\Http\Controllers\OrderController::class, 'placeOrder'])->name('orders.place');
    Route::get('/orders/confirmation/{order}', [\App\Http\Controllers\OrderController::class, 'confirmation'])->name('orders.confirmation');
    Route::get('/orders/{order}/prescription', [\App\Http\Controllers\OrderController::class, 'prescriptionUploadForm'])->name('orders.prescription.show');
    Route::post('/orders/{order}/prescription', [\App\Http\Controllers\OrderController::class, 'prescriptionUpload'])->name('orders.prescription.store');

    // Customer order tracking
    Route::get('/my-orders', [\App\Http\Controllers\CustomerOrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [\App\Http\Controllers\CustomerOrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{order}/cancel', [\App\Http\Controllers\CustomerOrderController::class, 'cancel'])->name('orders.cancel');
    Route::post('/orders/{order}/confirm-received', [\App\Http\Controllers\OrderController::class, 'confirmReceived'])->name('orders.confirmReceived');
    Route::post('/orders/{order}/pay-now', [\App\Http\Controllers\OrderController::class, 'payNow'])->name('orders.payNow');

    // Payment callbacks (PayMongo redirects here)
    Route::get('/invoices/{invoice}/success', [\App\Http\Controllers\PaymentController::class, 'success'])->name('payment.success');
    Route::get('/invoices/{invoice}/cancel', [\App\Http\Controllers\PaymentController::class, 'cancel'])->name('payment.cancel');
    Route::get('/payments/checkout/{batch}/success', [\App\Http\Controllers\PaymentController::class, 'batchSuccess'])->name('payment.batch.success');
    Route::get('/payments/checkout/{batch}/cancel', [\App\Http\Controllers\PaymentController::class, 'batchCancel'])->name('payment.batch.cancel');

    // Saved Addresses
    Route::get('/addresses', [\App\Http\Controllers\CustomerAddressController::class, 'index'])->name('addresses.index');
    Route::post('/addresses', [\App\Http\Controllers\CustomerAddressController::class, 'store'])->name('addresses.store');
    Route::put('/addresses/{address}', [\App\Http\Controllers\CustomerAddressController::class, 'update'])->name('addresses.update');
    Route::delete('/addresses/{address}', [\App\Http\Controllers\CustomerAddressController::class, 'destroy'])->name('addresses.destroy');
    Route::post('/addresses/{address}/default', [\App\Http\Controllers\CustomerAddressController::class, 'setDefault'])->name('addresses.setDefault');
});

// PayMongo webhook — NO auth, NO CSRF (exempted in bootstrap/app.php)
Route::post('/payments/webhook', [\App\Http\Controllers\PaymentController::class, 'webhook'])->name('payment.webhook');

// Public Seller Profile
Route::get('/seller/{slug}', [\App\Http\Controllers\DistributorProfileController::class, 'show'])->name('seller.profile');

/*
|--------------------------------------------------------------------------
| Default Auth Dashboard (Breeze default)
|--------------------------------------------------------------------------
*/
Route::get('/dashboard', function () {
    return redirect('/products');
})->middleware(['auth'])->name('dashboard');

// Public distributor profile page
Route::get('/shop/{slug}', [\App\Http\Controllers\DistributorProfileController::class, 'show'])
    ->name('distributor.profile');

/*
|--------------------------------------------------------------------------
| Profile Routes (Breeze)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');

    Route::put('/password', [ProfileController::class, 'updatePassword'])
        ->name('password.update');

    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->name('profile.destroy');

    // Account Settings
    Route::get('/settings', [ProfileController::class, 'edit'])
        ->name('settings');

    // Privacy Settings (placeholder)
    Route::get('/privacy', function () {
        return \Inertia\Inertia::render('Settings/Privacy');
    })->name('privacy');
});

/*
|--------------------------------------------------------------------------
| DISTRIBUTOR SETUP ROUTES (No Verification Required Yet)
|--------------------------------------------------------------------------
*/
// These are extracted from the verified middleware so new/pending users can access them!
Route::middleware(['auth', 'role:distributor'])
    ->prefix('owner')
    ->name('owner.')
    ->group(function () {
        Route::get('/distributor/create', [\App\Http\Controllers\Owner\DistributorController::class, 'create'])
            ->name('distributors.create');
            
        Route::post('/distributor/store', [\App\Http\Controllers\Owner\DistributorController::class, 'store'])
            ->name('distributors.store');
            
        Route::get('/distributor/pending', [\App\Http\Controllers\Owner\DistributorController::class, 'pending'])
            ->name('distributors.pending');
    });

/*
|--------------------------------------------------------------------------
| STRICT SHOP OPERATIONS (Verified Owner & Staff Only)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:distributor,staff', \App\Http\Middleware\EnsureDistributorVerified::class])
    ->prefix('owner')
    ->name('owner.')
    ->group(function () {

        // ==========================================
        // SHARED SHOP OPERATIONS (Owner & Staff)
        // ==========================================
        
        // Owner Dashboard
        Route::get('/dashboard', [\App\Http\Controllers\Owner\DashboardController::class, 'index'])
            ->name('dashboard');
        Route::get('/dashboard/pulse', [\App\Http\Controllers\Owner\DashboardController::class, 'pulse'])
            ->name('dashboard.pulse');

        // Point of Sale (POS)
        Route::get('/pos', [\App\Http\Controllers\Owner\POSController::class, 'index'])
            ->name('pos.index');
        Route::post('/pos/checkout', [\App\Http\Controllers\Owner\POSController::class, 'checkout'])
            ->name('pos.checkout');
        Route::get('/pos/invoices/{invoice}/success', [\App\Http\Controllers\Owner\POSController::class, 'paymentSuccess'])
            ->name('pos.success');
        Route::get('/pos/invoices/{invoice}/cancel', [\App\Http\Controllers\Owner\POSController::class, 'paymentCancel'])
            ->name('pos.cancel');

        // Orders
        Route::get('/orders', [\App\Http\Controllers\Owner\OrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [\App\Http\Controllers\Owner\OrderController::class, 'show'])->name('orders.show');
        Route::post('/orders/{order}/prescription/approve', [\App\Http\Controllers\Owner\OrderController::class, 'approvePrescription'])->name('orders.prescription.approve');
        Route::post('/orders/{order}/prescription/reject', [\App\Http\Controllers\Owner\OrderController::class, 'rejectPrescription'])->name('orders.prescription.reject');
        Route::patch('/orders/{order}/status', [\App\Http\Controllers\Owner\OrderController::class, 'updateStatus'])->name('orders.updateStatus');
        Route::post('/orders/{order}/note', [\App\Http\Controllers\Owner\OrderController::class, 'addNote'])->name('orders.addNote');
        Route::post('/orders/{order}/confirm-cod-remittance', [\App\Http\Controllers\Owner\OrderController::class, 'confirmCodRemittance'])->name('orders.confirmCodRemittance');

        // Inventory Management (Unified Product + Stock)
        Route::resource('inventory', \App\Http\Controllers\Owner\InventoryController::class, [
            'names' => [
                'index' => 'inventory.index',
                'create' => 'inventory.create',
                'store' => 'inventory.store',
                'edit' => 'inventory.edit',
                'update' => 'inventory.update',
                'destroy' => 'inventory.destroy',
            ]
        ]);
        Route::post('/inventory/{id}/adjust', [\App\Http\Controllers\Owner\InventoryController::class, 'adjustStock'])
            ->name('inventory.adjust');

        // Legacy /products/* URLs → unified inventory (single add/edit flow)
        Route::get('/products', fn () => redirect()->route('owner.inventory.index'))->name('products.index');
        Route::get('/products/create', fn () => redirect()->route('owner.inventory.create'))->name('products.create');
        Route::get('/products/{product}/edit', function (\App\Models\Product $product) {
            return redirect()->route('owner.inventory.edit', $product);
        })->name('products.edit');

        // ==========================================
        // OWNER-ONLY MANAGEMENT
        // ==========================================
        Route::middleware(['role:distributor'])->group(function () {
            
            // Distributor Profile & Licensing
            Route::get('/distributors', [\App\Http\Controllers\Owner\DistributorController::class, 'index'])
                ->name('distributors.index');
                
            Route::get('/distributor/{distributor}/license/create', [\App\Http\Controllers\Owner\LicenseController::class, 'create'])
                ->name('licenses.create');
                
            Route::post('/distributor/{distributor}/license/store', [\App\Http\Controllers\Owner\LicenseController::class, 'store'])
                ->name('licenses.store');

            // Profile Management
            Route::get('/profile/edit', [\App\Http\Controllers\Owner\ProfileController::class, 'edit'])
                ->name('profile.edit');
            Route::put('/profile', [\App\Http\Controllers\Owner\ProfileController::class, 'update'])
                ->name('profile.update');
            Route::post('/profile/check-slug', [\App\Http\Controllers\Owner\ProfileController::class, 'checkSlug'])
                ->name('profile.checkSlug');

            // Branch Management
            Route::get('/distributor/{distributor}/branches', [\App\Http\Controllers\Owner\BranchController::class, 'index'])
                ->name('distributors.branches.index');
            Route::get('/distributor/{distributor}/branches/create', [\App\Http\Controllers\Owner\BranchController::class, 'create'])
                ->name('distributors.branches.create');
            Route::post('/distributor/{distributor}/branches', [\App\Http\Controllers\Owner\BranchController::class, 'store'])
                ->name('distributors.branches.store');

            // Financial & Payments
            Route::get('/payments', [\App\Http\Controllers\Owner\PaymentController::class, 'index'])
                ->name('payments.index');
            Route::post('/payments/{payment}/verify', [\App\Http\Controllers\Owner\PaymentController::class, 'verify'])
                ->name('payments.verify');
            Route::post('/payments/{payment}/reject', [\App\Http\Controllers\Owner\PaymentController::class, 'reject'])
                ->name('payments.reject');
            Route::get('/sales', [\App\Http\Controllers\Owner\SalesController::class, 'index'])
                ->name('sales.index');

            // DSS
            Route::get('/dss', [\App\Http\Controllers\Owner\DssController::class, 'index'])
                ->name('dss.index');
            Route::patch('/dss/settings', [\App\Http\Controllers\Owner\DssController::class, 'updateSettings'])
                ->name('dss.settings.update');
            Route::post('/dss/alerts/{alert}/read', [\App\Http\Controllers\Owner\DssController::class, 'markAlertRead'])
                ->name('dss.alerts.read');
            Route::post('/dss/recommendations/{recommendation}/action', [\App\Http\Controllers\Owner\DssController::class, 'actionRecommendation'])
                ->name('dss.recommendations.action');

            // Staff Management
            Route::get('/staff', [\App\Http\Controllers\Owner\StaffController::class, 'index'])
                ->name('staff.index');
            Route::post('/staff', [\App\Http\Controllers\Owner\StaffController::class, 'store'])
                ->name('staff.store');
            Route::delete('/staff/{id}', [\App\Http\Controllers\Owner\StaffController::class, 'destroy'])
                ->name('staff.destroy');
        });
        
    });

/*
|--------------------------------------------------------------------------
| ADMIN ROUTES (For Normal Admins & Super Admins)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:admin,super_admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');
        
        // Distributors Management
        Route::post('/distributors/{id}/approve', [\App\Http\Controllers\Admin\DashboardController::class, 'approveDistributor'])->name('distributors.approve');
        Route::post('/distributors/{id}/reject', [\App\Http\Controllers\Admin\DashboardController::class, 'rejectDistributor'])->name('distributors.reject');
        
        // Secure Document Viewing
        Route::get('/documents/{path}', [\App\Http\Controllers\Admin\DashboardController::class, 'viewDocument'])
            ->where('path', '.*')
            ->name('documents.view');
        
        // Users Management
        Route::get('/users', [\App\Http\Controllers\Admin\UserManagementController::class, 'index'])->name('users.index');
        Route::patch('/users/{user}/role', [\App\Http\Controllers\Admin\UserManagementController::class, 'updateRole'])->name('users.updateRole');
        
        // Courier Management
        Route::get('/couriers', [CourierController::class, 'index'])->name('couriers.index');
        Route::post('/couriers', [CourierController::class, 'store'])->name('couriers.store');
    });

/*
|--------------------------------------------------------------------------
| SUPER ADMIN ROUTES (Exclusive)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:super_admin'])
    ->prefix('superadmin')
    ->name('superadmin.')
    ->group(function () {
        Route::get('/staff', [\App\Http\Controllers\SuperAdmin\AdminManagementController::class, 'index'])->name('staff.index');
        Route::post('/staff', [\App\Http\Controllers\SuperAdmin\AdminManagementController::class, 'store'])->name('staff.store');

        // Courier Governance
        Route::get('/couriers', [\App\Http\Controllers\Admin\CourierManagementController::class, 'index'])->name('couriers.index');
        Route::get('/couriers/deliveries', [\App\Http\Controllers\Admin\CourierManagementController::class, 'deliveries'])->name('couriers.deliveries');

        // Global Settings (Super Admin Only)
        Route::get('/settings', [\App\Http\Controllers\Admin\SettingsController::class, 'index'])->name('settings.index');
        Route::post('/settings', [\App\Http\Controllers\Admin\SettingsController::class, 'update'])->name('settings.update');
    });

/*
|--------------------------------------------------------------------------
| Courier Fleet App Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:courier'])->prefix('courier')->name('courier.')->group(function () {
    Route::get('/dashboard', [CourierDashboardController::class, 'index'])->name('dashboard');
    Route::get('/scanner', [\App\Http\Controllers\CourierController::class, 'scanner'])->name('scanner');
    Route::post('/scan', [\App\Http\Controllers\CourierController::class, 'processScan'])->name('scan');
    Route::get('/lookup-order', [\App\Http\Controllers\CourierController::class, 'lookupOrder'])->name('lookup-order');
    Route::post('/deliveries/{delivery}/accept', [CourierDeliveryController::class, 'accept'])->name('deliveries.accept');
    Route::post('/deliveries/{delivery}/status', [CourierDeliveryController::class, 'updateStatus'])->name('deliveries.status');
});

/*
|--------------------------------------------------------------------------
| AUTH ROUTES (Breeze)
|--------------------------------------------------------------------------
*/
require __DIR__ . '/auth.php';