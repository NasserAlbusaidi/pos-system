<?php

use App\Http\Controllers\ImpersonationController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ReportsExportController;
use App\Http\Controllers\StripeWebhookController;
use App\Livewire\Admin\AuditLogs;
use App\Livewire\Admin\InventoryManager;
use App\Livewire\Admin\MenuBuilder;
use App\Livewire\Admin\ReportsDashboard;
use App\Livewire\Guest\OrderTracker;
use App\Livewire\GuestMenu;
use App\Livewire\KitchenDisplay;
use App\Livewire\ModifierManager;
use App\Livewire\PinLogin;
use App\Livewire\PosDashboard;
use App\Livewire\ProductManager;
use App\Livewire\ShopDashboard;
use App\Livewire\ShopSettings;
use App\Livewire\SuperAdmin\Dashboard as SuperAdminDashboard;
use App\Livewire\SuperAdmin\Shops\Index as SuperAdminShopsIndex;
use App\Livewire\SuperAdmin\Shops\Manage as SuperAdminShopsManage;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');
Route::view('/offline', 'offline');

// Public Guest Routes (Path-Based Tenancy)
Route::get('/menu/{shop:slug}', GuestMenu::class)->name('guest.menu');
Route::get('/track/{trackingToken}', OrderTracker::class)->whereUuid('trackingToken')->name('guest.track');
Route::get('/pos/pin/{shop:slug}', PinLogin::class)->name('pos.pin');
Route::post('/webhooks/stripe', [StripeWebhookController::class, 'handle'])->name('webhooks.stripe');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', ShopDashboard::class)->name('dashboard');
    Route::view('profile', 'profile')->name('profile');
});

Route::middleware(['auth', 'verified', 'role:server,manager,admin'])->group(function () {
    Route::get('/pos', PosDashboard::class)->name('pos.dashboard');
    Route::get('/orders/{order}/invoice', [InvoiceController::class, 'show'])->name('admin.orders.invoice');
});

Route::middleware(['auth', 'verified', 'role:kitchen,manager,admin'])->group(function () {
    Route::get('/kds', KitchenDisplay::class)->name('kds.view');
});

Route::middleware(['auth', 'verified', 'role:manager,admin'])->group(function () {
    Route::get('/products', ProductManager::class)->name('admin.products');
    Route::get('/menu-builder', MenuBuilder::class)->name('admin.menu-builder');
    Route::get('/modifiers', ModifierManager::class)->name('admin.modifiers');
    Route::get('/inventory', InventoryManager::class)->name('admin.inventory');
    Route::get('/reports', ReportsDashboard::class)->name('admin.reports');
    Route::get('/reports/export', [ReportsExportController::class, 'orders'])->name('admin.reports.export');
    Route::get('/audit-logs', AuditLogs::class)->name('admin.audit-logs');
    Route::get('/settings', ShopSettings::class)->name('admin.settings');
});

// Super Admin Routes
Route::middleware(['auth', 'super_admin'])->prefix('admin')->group(function () {
    Route::get('/', SuperAdminDashboard::class)->name('super-admin.dashboard');
    Route::get('/shops', SuperAdminShopsIndex::class)->name('super-admin.shops.index');
    Route::get('/shops/create', SuperAdminShopsManage::class)->name('super-admin.shops.create');
    Route::get('/shops/{shop}/edit', SuperAdminShopsManage::class)->name('super-admin.shops.edit');
    Route::get('/impersonate/{user}', [ImpersonationController::class, 'impersonate'])->name('super-admin.impersonate');
});

Route::get('/leave-impersonation', [ImpersonationController::class, 'leave'])->name('impersonation.leave')->middleware('auth');

require __DIR__.'/auth.php';
