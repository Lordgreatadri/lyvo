<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\OperatorApprovalController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\PayoutController as AdminPayoutController;
use App\Http\Controllers\Admin\RoleController as AdminRoleController;
use App\Http\Controllers\Admin\SmsController as AdminSmsController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Auth\OperatorRegistrationController;
use App\Http\Controllers\Customer\CheckoutController;
use App\Http\Controllers\Customer\DashboardController as CustomerDashboardController;
use App\Http\Controllers\Customer\DeliveryAddressController;
use App\Http\Controllers\Customer\OrderController as CustomerOrderController;
use App\Http\Controllers\Customer\PaymentMethodController;
use App\Http\Controllers\DirectoryController;
use App\Http\Controllers\EscrowController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Operator\BrandingController;
use App\Http\Controllers\Operator\CustomerController as OperatorCustomerController;
use App\Http\Controllers\Operator\DashboardController as OperatorDashboardController;
use App\Http\Controllers\Operator\OrderController as OperatorOrderController;
use App\Http\Controllers\Operator\ProductController as OperatorProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StoreController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Authentication phase. Public marketing/discovery routes remain open; the
| customer, operator and admin workspaces are now gated by real auth, contact
| verification (email + phone via OTP) and account type. Operators additionally
| require admin approval before their dashboard unlocks. Records are resolved by
| uuid, never the auto-increment primary key.
|
*/

// ---------- Public ----------
Route::get('/', [HomeController::class, 'index'])->name('home');

// Verified operator directory + profiles (resolved by uuid)
Route::get('/operators', [DirectoryController::class, 'index'])->name('directory.index');
Route::get('/operators/{operator}', [DirectoryController::class, 'show'])->name('directory.show');

// Public marketplace (published items from approved operators)
Route::get('/store', [StoreController::class, 'index'])->name('store.index');
Route::get('/store/{product}', [StoreController::class, 'show'])->name('store.show');

// Guest access — browse without an account
Route::get('/guest', function () {
    session(['lyvo_guest' => true]);

    return redirect()->route('directory.index');
})->name('guest.enter');

// Operator onboarding wizard (public; creates a pending operator account)
Route::middleware('guest')->group(function () {
    Route::get('/become-an-operator', [OperatorRegistrationController::class, 'create'])->name('register.operator');
    Route::post('/become-an-operator', [OperatorRegistrationController::class, 'store'])->name('register.operator.store');
});

// ---------- Escrow desk (role-aware, real order data) ----------
Route::middleware(['auth', 'verified.contacts'])->group(function () {
    Route::get('/escrow', [EscrowController::class, 'index'])->name('escrow.index');
    Route::get('/escrow/{order}', [EscrowController::class, 'show'])->name('escrow.show');
});

// ---------- Customer workspace ----------
Route::middleware(['auth', 'verified.contacts', 'account:customer'])->group(function () {
    Route::get('/customer', [CustomerDashboardController::class, 'index'])->name('customer.dashboard');

    // Escrow orders — track lifecycle, confirm delivery, raise disputes
    Route::get('/customer/orders', [CustomerOrderController::class, 'index'])->name('customer.orders.index');
    Route::get('/customer/orders/{order}', [CustomerOrderController::class, 'show'])->name('customer.orders.show');
    Route::patch('/customer/orders/{order}/confirm', [CustomerOrderController::class, 'confirm'])->name('customer.orders.confirm');
    Route::patch('/customer/orders/{order}/dispute', [CustomerOrderController::class, 'dispute'])->name('customer.orders.dispute');
    Route::patch('/customer/orders/{order}/otp', [CustomerOrderController::class, 'submitOtp'])->name('customer.orders.otp');

    // Escrow checkout for a public product
    Route::get('/checkout/{product}', [CheckoutController::class, 'create'])->name('checkout.create');
    Route::post('/checkout/{product}', [CheckoutController::class, 'store'])->name('checkout.store');

    // Delivery address book (max 3, one default)
    Route::get('/customer/addresses', [DeliveryAddressController::class, 'index'])->name('customer.addresses.index');
    Route::post('/customer/addresses', [DeliveryAddressController::class, 'store'])->name('customer.addresses.store');
    Route::put('/customer/addresses/{address}', [DeliveryAddressController::class, 'update'])->name('customer.addresses.update');
    Route::patch('/customer/addresses/{address}/default', [DeliveryAddressController::class, 'setDefault'])->name('customer.addresses.default');
    Route::delete('/customer/addresses/{address}', [DeliveryAddressController::class, 'destroy'])->name('customer.addresses.destroy');

    // Saved payment methods
    Route::get('/customer/payment-methods', [PaymentMethodController::class, 'index'])->name('customer.payment-methods.index');
    Route::post('/customer/payment-methods', [PaymentMethodController::class, 'store'])->name('customer.payment-methods.store');
    Route::patch('/customer/payment-methods/{paymentMethod}/default', [PaymentMethodController::class, 'setDefault'])->name('customer.payment-methods.default');
    Route::delete('/customer/payment-methods/{paymentMethod}', [PaymentMethodController::class, 'destroy'])->name('customer.payment-methods.destroy');
});

// ---------- Operator workspace ----------
Route::middleware(['auth', 'verified.contacts', 'account:operator'])->group(function () {
    // Status page shown while awaiting (or after a rejected) admin review.
    Route::get('/operator/pending', [OperatorDashboardController::class, 'pending'])->name('operator.pending');

    // Dashboard unlocks only once approved.
    Route::middleware('operator.approved')->group(function () {
        Route::get('/operator', [OperatorDashboardController::class, 'index'])->name('operator.dashboard');
        Route::get('/operator/verification', [OperatorDashboardController::class, 'verification'])->name('operator.verification');

        // Order fulfilment queue + customers roll-up
        Route::get('/operator/orders', [OperatorOrderController::class, 'index'])->name('operator.orders.index');
        Route::get('/operator/orders/{order}', [OperatorOrderController::class, 'show'])->name('operator.orders.show');
        Route::patch('/operator/orders/{order}/processing', [OperatorOrderController::class, 'processing'])->name('operator.orders.processing');
        Route::patch('/operator/orders/{order}/delivered', [OperatorOrderController::class, 'delivered'])->name('operator.orders.delivered');
        Route::get('/operator/customers', [OperatorCustomerController::class, 'index'])->name('operator.customers.index');

        // Storefront branding (cover + logo uploads)
        Route::get('/operator/branding', [BrandingController::class, 'edit'])->name('operator.branding.edit');
        Route::post('/operator/branding', [BrandingController::class, 'update'])->name('operator.branding.update');

        // Catalogue management
        Route::resource('/operator/products', OperatorProductController::class)
            ->except(['show'])
            ->names('operator.products')
            ->parameters(['products' => 'product']);
        Route::patch('/operator/products/{product}/publish', [OperatorProductController::class, 'publish'])->name('operator.products.publish');
        Route::patch('/operator/products/{product}/unpublish', [OperatorProductController::class, 'unpublish'])->name('operator.products.unpublish');
    });
});

// ---------- Admin workspace ----------
Route::middleware(['auth', 'verified.contacts', 'account:admin'])->group(function () {
    Route::get('/admin', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('/admin/verification', [AdminDashboardController::class, 'verification'])->name('admin.verification');

    // Escrow oversight + dispute resolution
    Route::get('/admin/orders', [AdminOrderController::class, 'index'])->name('admin.orders.index');
    Route::get('/admin/orders/{order}', [AdminOrderController::class, 'show'])->name('admin.orders.show');
    Route::patch('/admin/orders/{order}/release', [AdminOrderController::class, 'release'])->name('admin.orders.release');
    Route::patch('/admin/orders/{order}/refund', [AdminOrderController::class, 'refund'])->name('admin.orders.refund');

    // Operator verification center
    Route::get('/admin/operators', [OperatorApprovalController::class, 'index'])->name('admin.operators.index');
    Route::get('/admin/operators/{operator}', [OperatorApprovalController::class, 'show'])->name('admin.operators.show');
    Route::patch('/admin/operators/{operator}/review', [OperatorApprovalController::class, 'markInReview'])->name('admin.operators.review');
    Route::patch('/admin/operators/{operator}/approve', [OperatorApprovalController::class, 'approve'])->name('admin.operators.approve');
    Route::patch('/admin/operators/{operator}/reject', [OperatorApprovalController::class, 'reject'])->name('admin.operators.reject');

    // User management
    Route::get('/admin/users', [AdminUserController::class, 'index'])->name('admin.users.index');
    Route::get('/admin/users/{user}', [AdminUserController::class, 'show'])->name('admin.users.show');
    Route::patch('/admin/users/{user}/approve', [AdminUserController::class, 'approve'])->name('admin.users.approve');
    Route::patch('/admin/users/{user}/freeze', [AdminUserController::class, 'freeze'])->name('admin.users.freeze');
    Route::patch('/admin/users/{user}/unfreeze', [AdminUserController::class, 'unfreeze'])->name('admin.users.unfreeze');
    Route::put('/admin/users/{user}/roles', [AdminUserController::class, 'updateRoles'])->name('admin.users.roles');

    // Roles & permissions
    Route::get('/admin/roles', [AdminRoleController::class, 'index'])->name('admin.roles.index');
    Route::put('/admin/roles/{role}', [AdminRoleController::class, 'update'])->name('admin.roles.update');

    // SMS gateway console
    Route::get('/admin/sms', [AdminSmsController::class, 'index'])->name('admin.sms.index');
    Route::put('/admin/sms/settings', [AdminSmsController::class, 'updateSettings'])->name('admin.sms.settings');
    Route::post('/admin/sms/balance', [AdminSmsController::class, 'refreshBalance'])->name('admin.sms.balance');
    Route::post('/admin/sms/test', [AdminSmsController::class, 'sendTest'])->name('admin.sms.test');

    // Payouts (disbursements) console — release escrow funds to operators
    Route::get('/admin/payouts', [AdminPayoutController::class, 'index'])->name('admin.payouts.index');
    Route::post('/admin/payouts', [AdminPayoutController::class, 'store'])->name('admin.payouts.store');
    Route::post('/admin/payouts/validate', [AdminPayoutController::class, 'validateName'])->name('admin.payouts.validate');
    Route::post('/admin/payouts/{payout}/status', [AdminPayoutController::class, 'refreshStatus'])->name('admin.payouts.status');
});

// ---------- Authenticated profile + generic dashboard redirect ----------
Route::get('/dashboard', function () {
    return redirect()->route(auth()->user()->homeRoute());
})->middleware(['auth', 'verified.contacts'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
