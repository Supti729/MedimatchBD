<?php

use App\Http\Controllers\Account;
use App\Http\Controllers\Admin;
use App\Http\Controllers\AuthPageController;
use App\Http\Controllers\MedicineController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PreviewActionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Medimatch BD web routes
|--------------------------------------------------------------------------
| PART 1 (frontend preview): every GET route renders a real Blade page using
| App\Support\SampleData. Every form action (POST/PATCH/DELETE) is already
| named, but is handled by PreviewActionController, which saves nothing and
| redirects back with an honest "preview" message.
|
| PART 2 replaces PreviewActionController with real controllers, adds the
| `auth`, `verified` and role middleware, and swaps SampleData for Eloquent.
*/

$preview = PreviewActionController::class;

/* ---------------------------------------------------------------- Public */
Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/how-it-works', [PageController::class, 'howItWorks'])->name('how-it-works');
Route::get('/safety', [PageController::class, 'safety'])->name('safety');
Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/contact', [PageController::class, 'contact'])->name('contact');
Route::post('/contact', $preview)->name('contact.send');
Route::get('/privacy', [PageController::class, 'privacy'])->name('privacy');
Route::get('/terms', [PageController::class, 'terms'])->name('terms');

Route::get('/medicines', [MedicineController::class, 'index'])->name('medicines.index');
Route::get('/search', [MedicineController::class, 'search'])->name('medicines.search');
Route::get('/medicines/{listing}', [MedicineController::class, 'show'])->whereNumber('listing')->name('medicines.show');
Route::get('/members/{member}', [MemberController::class, 'show'])->whereNumber('member')->name('members.show');

/* ---------------------------------------------------------- Authentication */
Route::get('/login', [AuthPageController::class, 'login'])->name('login');
Route::post('/login', $preview)->name('login.attempt');
Route::get('/register', [AuthPageController::class, 'register'])->name('register');
Route::post('/register', $preview)->name('register.store');
Route::get('/forgot-password', [AuthPageController::class, 'forgotPassword'])->name('password.request');
Route::post('/forgot-password', $preview)->name('password.email');
Route::get('/reset-password/{token}', [AuthPageController::class, 'resetPassword'])->name('password.reset');
Route::post('/reset-password', $preview)->name('password.update');
Route::get('/verify-account', [AuthPageController::class, 'verifyAccount'])->name('verification.notice');
Route::post('/verify-account', $preview)->name('verification.verify');
Route::post('/logout', $preview)->name('logout');

/* ------------------------------------------------- Member area (Part 2: auth) */
Route::get('/dashboard', Account\DashboardController::class)->name('dashboard');

Route::get('/profile', [Account\ProfileController::class, 'show'])->name('profile.show');
Route::get('/profile/edit', [Account\ProfileController::class, 'edit'])->name('profile.edit');
Route::patch('/profile', $preview)->name('profile.update');

Route::prefix('my/listings')->name('listings.')->group(function () use ($preview) {
    Route::get('/', [Account\ListingController::class, 'index'])->name('index');
    Route::get('/create', [Account\ListingController::class, 'create'])->name('create');
    Route::post('/', $preview)->name('store');
    Route::get('/{listing}/edit', [Account\ListingController::class, 'edit'])->whereNumber('listing')->name('edit');
    Route::patch('/{listing}', $preview)->whereNumber('listing')->name('update');
    Route::patch('/{listing}/status', $preview)->whereNumber('listing')->name('status'); // pause / reactivate / mark unavailable / cancel
});

Route::prefix('my/requests')->name('requests.')->group(function () use ($preview) {
    Route::get('/', [Account\RequestController::class, 'index'])->name('index');
    Route::get('/create', [Account\RequestController::class, 'create'])->name('create');
    Route::post('/', $preview)->name('store');
    Route::get('/{medicineRequest}', [Account\RequestController::class, 'show'])->whereNumber('medicineRequest')->name('show');
    Route::get('/{medicineRequest}/edit', [Account\RequestController::class, 'edit'])->whereNumber('medicineRequest')->name('edit');
    Route::patch('/{medicineRequest}', $preview)->whereNumber('medicineRequest')->name('update');
    Route::patch('/{medicineRequest}/cancel', $preview)->whereNumber('medicineRequest')->name('cancel');
});

Route::post('/medicines/{listing}/request', $preview)->whereNumber('listing')->name('medicines.request');
Route::post('/medicines/{listing}/save', $preview)->whereNumber('listing')->name('saved.store');
Route::delete('/saved/{listing}', $preview)->whereNumber('listing')->name('saved.destroy');
Route::get('/saved', Account\SavedController::class)->name('saved');
Route::post('/reports', $preview)->name('reports.store');

Route::get('/messages', [Account\MessageController::class, 'index'])->name('messages.index');
Route::get('/messages/{conversation}', [Account\MessageController::class, 'show'])->whereNumber('conversation')->name('messages.show');
Route::post('/messages/{conversation}', $preview)->whereNumber('conversation')->name('messages.send');
Route::post('/medicines/{listing}/chat', $preview)->whereNumber('listing')->name('messages.start');
Route::patch('/interactions/{interaction}', $preview)->whereNumber('interaction')->name('interactions.update');

Route::get('/notifications', [Account\NotificationController::class, 'index'])->name('notifications.index');
Route::patch('/notifications/read-all', $preview)->name('notifications.read-all');
Route::patch('/notifications/{notification}/read', $preview)->name('notifications.read');

Route::get('/history', Account\HistoryController::class)->name('history');
Route::get('/verification', [Account\VerificationController::class, 'index'])->name('verification.index');
Route::post('/verification', $preview)->name('verification.store');

Route::get('/settings/privacy', [Account\SettingsController::class, 'privacy'])->name('settings.privacy');
Route::patch('/settings/privacy', $preview)->name('settings.privacy.update');
Route::get('/settings/security', [Account\SettingsController::class, 'security'])->name('settings.security');
Route::patch('/settings/password', $preview)->name('settings.password.update');
Route::delete('/settings/sessions', $preview)->name('settings.sessions.destroy');
Route::delete('/account', $preview)->name('account.destroy');

/* ------------------------------------------- Administration (Part 2: admin role) */
Route::get('/admin/login', [AuthPageController::class, 'adminLogin'])->name('admin.login');
Route::post('/admin/login', $preview)->name('admin.login.attempt');

Route::prefix('admin')->name('admin.')->group(function () use ($preview) {
    Route::get('/', Admin\DashboardController::class)->name('dashboard');

    Route::get('/users', [Admin\UserController::class, 'index'])->name('users.index');
    Route::get('/users/{user}', [Admin\UserController::class, 'show'])->whereNumber('user')->name('users.show');
    Route::patch('/users/{user}/status', $preview)->whereNumber('user')->name('users.status');

    Route::get('/medicines', Admin\MedicineController::class)->name('medicines.index');
    Route::post('/medicines', $preview)->name('medicines.store');
    Route::patch('/medicines/{medicine}', $preview)->whereNumber('medicine')->name('medicines.update');

    Route::get('/listings', [Admin\ListingController::class, 'index'])->name('listings.index');
    Route::get('/listings/{listing}', [Admin\ListingController::class, 'show'])->whereNumber('listing')->name('listings.show');
    Route::patch('/listings/{listing}/decision', $preview)->whereNumber('listing')->name('listings.decision');

    Route::get('/requests', Admin\RequestController::class)->name('requests.index');

    Route::get('/verifications', [Admin\VerificationController::class, 'index'])->name('verifications.index');
    Route::get('/verifications/{verification}', [Admin\VerificationController::class, 'show'])->whereNumber('verification')->name('verifications.show');
    Route::patch('/verifications/{verification}/decision', $preview)->whereNumber('verification')->name('verifications.decision');

    Route::get('/reports', [Admin\ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/{report}', [Admin\ReportController::class, 'show'])->whereNumber('report')->name('reports.show');
    Route::patch('/reports/{report}/decision', $preview)->whereNumber('report')->name('reports.decision');

    Route::get('/interactions', Admin\InteractionController::class)->name('interactions.index');
    Route::get('/partners', Admin\PartnerController::class)->name('partners.index');
    Route::get('/announcements', Admin\AnnouncementController::class)->name('announcements.index');
    Route::post('/announcements', $preview)->name('announcements.store');
    Route::get('/audit-logs', Admin\AuditLogController::class)->name('audit-logs.index');
    Route::get('/settings', Admin\SettingsController::class)->name('settings');
    Route::patch('/settings', $preview)->name('settings.update');
});
