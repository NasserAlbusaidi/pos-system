<?php

use App\Http\Controllers\HealthController;
use App\Http\Controllers\ImpersonationController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\ReportsExportController;
use App\Http\Controllers\StripeSubscriptionWebhookController;
use App\Http\Controllers\StripeWebhookController;
use App\Livewire\Admin\AuditLogs;
use App\Livewire\Admin\MenuBuilder;
use App\Livewire\Admin\MenuEngineering;
use App\Livewire\Admin\PricingRules;
use App\Livewire\Admin\ReportsDashboard;
use App\Livewire\BillingSettings;
use App\Livewire\CashReconciliation;
use App\Livewire\Guest\OrderTracker;
use App\Livewire\GuestMenu;
use App\Livewire\KitchenDisplay;
use App\Livewire\ModifierManager;
use App\Livewire\OnboardingWizard;
use App\Livewire\PinLogin;
use App\Livewire\PosDashboard;
use App\Livewire\ProductManager;
use App\Livewire\ShiftReport;
use App\Livewire\ShopDashboard;
use App\Livewire\ShopSettings;
use App\Livewire\SuperAdmin\Dashboard as SuperAdminDashboard;
use App\Livewire\SuperAdmin\Shops\Index as SuperAdminShopsIndex;
use App\Livewire\SuperAdmin\Shops\Manage as SuperAdminShopsManage;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class)->name('health')->withoutMiddleware([\App\Http\Middleware\SecurityHeaders::class]);

Route::view('/', 'welcome');
Route::view('/offline', 'offline');
Route::view('/privacy', 'legal.privacy')->name('legal.privacy');
Route::view('/terms', 'legal.terms')->name('legal.terms');

// Public Guest Routes (Path-Based Tenancy)
Route::get('/menu/{shop:slug}', GuestMenu::class)->name('guest.menu');
Route::get('/track/{trackingToken}', OrderTracker::class)->whereUuid('trackingToken')->name('guest.track');
Route::get('/pos/pin/{shop:slug}', PinLogin::class)->name('pos.pin');
Route::post('/webhooks/stripe', [StripeWebhookController::class, 'handle'])
    ->middleware('throttle:stripe-webhooks')
    ->name('webhooks.stripe');
Route::post('/webhooks/stripe/subscription', [StripeSubscriptionWebhookController::class, 'handle'])
    ->middleware('throttle:stripe-webhooks')
    ->name('webhooks.stripe.subscription');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', ShopDashboard::class)->name('dashboard');
    Route::view('profile', 'profile')->name('profile');
});

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('onboarding', OnboardingWizard::class)->name('onboarding');
});

Route::middleware(['auth', 'subscribed', 'shop.active', 'role:server,manager,admin'])->group(function () {
    Route::get('/pos', PosDashboard::class)->name('pos.dashboard');
    Route::get('/orders/{order}/invoice', [InvoiceController::class, 'show'])->name('admin.orders.invoice');
    Route::get('/receipt/{order}', [ReceiptController::class, 'show'])->name('receipt.print');
});

Route::middleware(['auth', 'subscribed', 'shop.active', 'role:kitchen,manager,admin'])->group(function () {
    Route::get('/kds', KitchenDisplay::class)->name('kds.view');
});

Route::middleware(['auth', 'subscribed', 'shop.active', 'role:manager,admin'])->group(function () {
    Route::get('/products', ProductManager::class)->name('admin.products');
    Route::get('/menu-builder', MenuBuilder::class)->name('admin.menu-builder');
    Route::get('/modifiers', ModifierManager::class)->name('admin.modifiers');
    Route::get('/reports', ReportsDashboard::class)->middleware('plan:reports')->name('admin.reports');
    Route::get('/reports/export', [ReportsExportController::class, 'orders'])->middleware('plan:reports')->name('admin.reports.export');
    Route::get('/audit-logs', AuditLogs::class)->name('admin.audit-logs');
    Route::get('/settings', ShopSettings::class)->name('admin.settings');
    Route::get('/shift-report', ShiftReport::class)->name('admin.shift-report');
    Route::get('/cash-reconciliation', CashReconciliation::class)->name('admin.cash-reconciliation');
    Route::get('/menu-engineering', MenuEngineering::class)->middleware('plan:menu_engineering')->name('admin.menu-engineering');
    Route::get('/pricing-rules', PricingRules::class)->middleware('plan:pricing_rules')->name('admin.pricing-rules');
});

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/billing', BillingSettings::class)->name('billing');
});

// Super Admin Routes
Route::middleware(['auth', 'super_admin'])->prefix('admin')->group(function () {
    Route::get('/', SuperAdminDashboard::class)->name('super-admin.dashboard');
    Route::get('/shops', SuperAdminShopsIndex::class)->name('super-admin.shops.index');
    Route::get('/shops/create', SuperAdminShopsManage::class)->name('super-admin.shops.create');
    Route::get('/shops/{shop}/edit', SuperAdminShopsManage::class)->name('super-admin.shops.edit');
    Route::post('/impersonate/{user}', [ImpersonationController::class, 'impersonate'])->name('super-admin.impersonate');
});

Route::get('/leave-impersonation', [ImpersonationController::class, 'leave'])->name('impersonation.leave')->middleware('auth');

require __DIR__.'/auth.php';
