<?php

use App\Http\Controllers\Account;
use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\AccountVerificationController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\SessionController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\MedicineController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Medimatch BD web routes
|--------------------------------------------------------------------------
| Access is enforced here (middleware) AND in controllers (policies/gates).
| Nothing relies on hidden buttons or values sent by the browser.
*/

/* ---------------------------------------------------------------- Public */
Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/how-it-works', [PageController::class, 'howItWorks'])->name('how-it-works');
Route::get('/safety', [PageController::class, 'safety'])->name('safety');
Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/privacy', [PageController::class, 'privacy'])->name('privacy');
Route::get('/terms', [PageController::class, 'terms'])->name('terms');
Route::get('/contact', [ContactController::class, 'create'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:5,10')->name('contact.send');

Route::get('/medicines', [MedicineController::class, 'index'])->name('medicines.index');
Route::get('/search', [MedicineController::class, 'search'])->name('medicines.search');
Route::get('/medicines/{listing}', [MedicineController::class, 'show'])->whereNumber('listing')->name('medicines.show');
Route::get('/members/{member}', [MemberController::class, 'show'])->whereNumber('member')->name('members.show');

/* ---------------------------------------------------------- Authentication */
Route::middleware('guest')->group(function () {
    Route::get('/login', [SessionController::class, 'create'])->name('login');
    Route::post('/login', [SessionController::class, 'store'])->name('login.attempt');
    Route::get('/admin/login', [SessionController::class, 'adminCreate'])->name('admin.login');
    Route::post('/admin/login', [SessionController::class, 'adminStore'])->name('admin.login.attempt');
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->middleware('throttle:10,60')->name('register.store');
    Route::get('/forgot-password', [PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'email'])->middleware('throttle:5,10')->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'reset'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'update'])->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::get('/verify-account', [AccountVerificationController::class, 'notice'])->name('verification.notice');
    Route::post('/verify-account', [AccountVerificationController::class, 'verify'])->middleware('throttle:10,1')->name('verification.verify');
    Route::post('/verify-account/resend', [AccountVerificationController::class, 'resend'])->name('verification.resend');
    Route::post('/logout', [SessionController::class, 'destroy'])->name('logout');
});

/* ------------------------------------------- Member area: signed in + verified */
Route::middleware(['auth', 'verified.account'])->group(function () {
    Route::get('/dashboard', Account\DashboardController::class)->name('dashboard');

    Route::get('/profile', [Account\ProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/edit', [Account\ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [Account\ProfileController::class, 'update'])->name('profile.update');

    Route::prefix('my/listings')->name('listings.')->controller(Account\ListingController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/{listing}/edit', 'edit')->whereNumber('listing')->name('edit');
        Route::patch('/{listing}', 'update')->whereNumber('listing')->name('update');
        Route::patch('/{listing}/status', 'status')->whereNumber('listing')->name('status');
    });

    Route::prefix('my/requests')->name('requests.')->controller(Account\RequestController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/{medicineRequest}', 'show')->whereNumber('medicineRequest')->name('show');
        Route::get('/{medicineRequest}/edit', 'edit')->whereNumber('medicineRequest')->name('edit');
        Route::patch('/{medicineRequest}', 'update')->whereNumber('medicineRequest')->name('update');
        Route::patch('/{medicineRequest}/cancel', 'cancel')->whereNumber('medicineRequest')->name('cancel');
    });

    Route::controller(Account\ListingActionController::class)->group(function () {
        Route::post('/medicines/{listing}/request', 'request')->whereNumber('listing')->name('medicines.request');
        Route::post('/medicines/{listing}/chat', 'chat')->whereNumber('listing')->name('messages.start');
        Route::post('/medicines/{listing}/save', 'save')->whereNumber('listing')->name('saved.store');
        Route::delete('/saved/{listing}', 'unsave')->whereNumber('listing')->name('saved.destroy');
    });
    Route::get('/saved', Account\SavedController::class)->name('saved');
    Route::post('/reports', [Account\ReportController::class, 'store'])->middleware('throttle:10,60')->name('reports.store');

    Route::get('/messages', [Account\MessageController::class, 'index'])->name('messages.index');
    Route::get('/messages/{conversation}', [Account\MessageController::class, 'show'])->whereNumber('conversation')->name('messages.show');
    Route::post('/messages/{conversation}', [Account\MessageController::class, 'send'])->whereNumber('conversation')->middleware('throttle:30,1')->name('messages.send');
    Route::patch('/interactions/{interaction}', [Account\InteractionController::class, 'update'])->whereNumber('interaction')->name('interactions.update');

    Route::get('/notifications', [Account\NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/{notification}/open', [Account\NotificationController::class, 'open'])->name('notifications.open');
    Route::patch('/notifications/read-all', [Account\NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::patch('/notifications/{notification}/read', [Account\NotificationController::class, 'read'])->name('notifications.read');

    Route::get('/history', Account\HistoryController::class)->name('history');
    Route::get('/verification', [Account\VerificationController::class, 'index'])->name('verification.index');
    Route::post('/verification', [Account\VerificationController::class, 'store'])->middleware('throttle:10,60')->name('verification.store');

    Route::controller(Account\SettingsController::class)->group(function () {
        Route::get('/settings/privacy', 'privacy')->name('settings.privacy');
        Route::patch('/settings/privacy', 'updatePrivacy')->name('settings.privacy.update');
        Route::get('/settings/security', 'security')->name('settings.security');
        Route::patch('/settings/password', 'updatePassword')->name('settings.password.update');
        Route::delete('/settings/sessions', 'destroySessions')->name('settings.sessions.destroy');
        Route::delete('/account', 'destroyAccount')->name('account.destroy');
    });
});

/* -------------------------------------------- Administration: staff accounts only */
Route::prefix('admin')->name('admin.')->middleware(['auth', 'can:access-admin'])->group(function () {
    Route::get('/', Admin\DashboardController::class)->name('dashboard');

    // Sensitive documents: authorised reviewers and admins.
    Route::middleware('can:review-verifications')->controller(Admin\VerificationController::class)->group(function () {
        Route::get('/verifications', 'index')->name('verifications.index');
        Route::get('/verifications/{verification}', 'show')->whereNumber('verification')->name('verifications.show');
        Route::get('/verifications/{verification}/document', 'document')->whereNumber('verification')->name('verifications.document');
        Route::patch('/verifications/{verification}/decision', 'decide')->whereNumber('verification')->name('verifications.decision');
    });

    // Everything else: administrators only.
    Route::middleware('can:admin')->group(function () {
        Route::get('/users', [Admin\UserController::class, 'index'])->name('users.index');
        Route::get('/users/{user}', [Admin\UserController::class, 'show'])->whereNumber('user')->name('users.show');
        Route::patch('/users/{user}/status', [Admin\UserController::class, 'status'])->whereNumber('user')->name('users.status');
        Route::patch('/users/{user}/role', [Admin\UserController::class, 'role'])->whereNumber('user')->name('users.role');

        Route::get('/medicines', [Admin\MedicineController::class, 'index'])->name('medicines.index');
        Route::post('/medicines', [Admin\MedicineController::class, 'store'])->name('medicines.store');
        Route::patch('/medicines/{medicine}', [Admin\MedicineController::class, 'update'])->whereNumber('medicine')->name('medicines.update');

        Route::get('/listings', [Admin\ListingController::class, 'index'])->name('listings.index');
        Route::get('/listings/{listing}', [Admin\ListingController::class, 'show'])->whereNumber('listing')->name('listings.show');
        Route::patch('/listings/{listing}/decision', [Admin\ListingController::class, 'decide'])->whereNumber('listing')->name('listings.decision');

        Route::get('/requests', Admin\RequestController::class)->name('requests.index');

        Route::get('/reports', [Admin\ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/{report}', [Admin\ReportController::class, 'show'])->whereNumber('report')->name('reports.show');
        Route::patch('/reports/{report}/decision', [Admin\ReportController::class, 'decide'])->whereNumber('report')->name('reports.decision');

        Route::get('/interactions', Admin\InteractionController::class)->name('interactions.index');
        Route::get('/partners', [Admin\PartnerController::class, 'index'])->name('partners.index');
        Route::post('/partners', [Admin\PartnerController::class, 'store'])->name('partners.store');
        Route::patch('/partners/{partner}', [Admin\PartnerController::class, 'update'])->whereNumber('partner')->name('partners.update');
        Route::get('/announcements', [Admin\AnnouncementController::class, 'index'])->name('announcements.index');
        Route::post('/announcements', [Admin\AnnouncementController::class, 'store'])->name('announcements.store');
        Route::get('/audit-logs', Admin\AuditLogController::class)->name('audit-logs.index');
        Route::get('/settings', [Admin\SettingsController::class, 'edit'])->name('settings');
        Route::patch('/settings', [Admin\SettingsController::class, 'update'])->name('settings.update');
    });
});
