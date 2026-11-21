<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\Dashboard;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\PublicController;
use App\Models\Business;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('landing', [
    'demos' => Business::whereNotNull('activated_at')->where('status', 'active')->orderBy('name')->get(),
]))->name('landing');

Route::view('privacy', 'legal.privacy')->name('legal.privacy');
Route::view('terms', 'legal.terms')->name('legal.terms');

// ------------------------------------------------------------------ Auth
Route::middleware('guest')->group(function () {
    Route::get('login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:20,1');
    Route::get('register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('register', [AuthController::class, 'register'])->middleware('throttle:10,1');
    Route::get('forgot-password', [PasswordResetController::class, 'request'])->name('password.request');
    Route::post('forgot-password', [PasswordResetController::class, 'email'])->name('password.email')->middleware(['throttle:6,1', 'demo.lock']);
    Route::get('reset-password/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('reset-password', [PasswordResetController::class, 'update'])->name('password.update')->middleware(['throttle:6,1', 'demo.lock']);
});
Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('mfa/setup', [AuthController::class, 'showMfaSetup'])->name('mfa.setup');
    Route::post('mfa/setup', [AuthController::class, 'confirmMfaSetup']);
    Route::get('mfa', [AuthController::class, 'showMfaChallenge'])->name('mfa.challenge');
    Route::post('mfa', [AuthController::class, 'verifyMfa']);
    Route::get('email/verify', [AccountController::class, 'verifyNotice'])->name('verification.notice');
    Route::get('email/verify/{id}/{hash}', [AccountController::class, 'verify'])->name('verification.verify')->middleware(['signed', 'throttle:6,1']);
    Route::post('email/verification-notification', [AccountController::class, 'resendVerification'])->name('verification.send')->middleware('throttle:6,1');
    Route::get('start', [OnboardingController::class, 'showBusiness'])->name('onboarding.business');
    Route::post('start', [OnboardingController::class, 'storeBusiness'])->middleware('mfa');
});

// ------------------------------------------------------------------ Customer page
Route::prefix('b/{business}')->name('public.')->group(function () {
    Route::get('/', [PublicController::class, 'home'])->name('home');
    Route::get('book', [PublicController::class, 'book'])->name('book');
    Route::get('slots', [PublicController::class, 'slots'])->name('slots')->middleware('throttle:60,1');
    Route::post('book/review', [PublicController::class, 'reviewBooking'])->name('book.review');
    Route::post('book', [PublicController::class, 'storeBooking'])->name('book.store')->middleware('throttle:10,1');
    Route::get('order', [PublicController::class, 'order'])->name('order');
    Route::post('order/review', [PublicController::class, 'reviewOrder'])->name('order.review');
    Route::post('order', [PublicController::class, 'storeOrder'])->name('order.store')->middleware('throttle:10,1');
    Route::get('quote', [PublicController::class, 'quote'])->name('quote');
    Route::post('quote/review', [PublicController::class, 'reviewQuote'])->name('quote.review');
    Route::post('quote', [PublicController::class, 'storeQuote'])->name('quote.store')->middleware('throttle:10,1');
    Route::get('request', [PublicController::class, 'serviceRequest'])->name('request');
    Route::post('request/review', [PublicController::class, 'reviewServiceRequest'])->name('request.review');
    Route::post('request', [PublicController::class, 'storeServiceRequest'])->name('request.store')->middleware('throttle:10,1');
    Route::get('r/{reference}', [PublicController::class, 'result'])->name('result');
    Route::get('track', [PublicController::class, 'track'])->name('track');
    Route::post('track', [PublicController::class, 'sendTrackLink'])->name('track.send');
    Route::get('access/{token}', [PublicController::class, 'access'])->name('access');
    Route::get('my', [PublicController::class, 'my'])->name('my');
    Route::post('my/appointments/{reference}/cancel', [PublicController::class, 'cancelAppointment'])->name('my.appointment.cancel');
    Route::post('my/orders/{reference}/cancel', [PublicController::class, 'cancelOrder'])->name('my.order.cancel');
    Route::post('my/quotations/{reference}/respond', [PublicController::class, 'respondQuote'])->name('my.quote.respond');
    Route::get('my/files/{attachment}', [PublicController::class, 'attachment'])->name('my.file');
    Route::get('my/data', [PublicController::class, 'myData'])->name('my.data')->middleware('throttle:10,1');
    Route::post('my/privacy-request', [PublicController::class, 'storePrivacyRequest'])->name('my.privacy')->middleware('throttle:5,1');
    Route::get('privacy', [PublicController::class, 'privacy'])->name('privacy');
    Route::get('chat', [ChatController::class, 'history'])->name('chat.history');
    Route::post('chat', [ChatController::class, 'send'])->name('chat.send')->middleware('throttle:20,1');
    Route::post('chat/handoff', [ChatController::class, 'handoff'])->name('chat.handoff');
});

// ------------------------------------------------------------------ Business dashboard
Route::prefix('app')->name('dashboard.')->middleware(['auth', 'mfa', 'business'])->group(function () {
    Route::get('/', [Dashboard\OverviewController::class, 'index'])->name('overview');
    Route::post('switch', [Dashboard\OverviewController::class, 'switch'])->name('switch');
    Route::post('notifications/read', [Dashboard\OverviewController::class, 'readNotifications'])->name('notifications.read');
    Route::get('setup', [Dashboard\OverviewController::class, 'onboarding'])->name('onboarding');
    Route::post('setup/activate', [Dashboard\OverviewController::class, 'activate'])->name('activate');

    Route::controller(Dashboard\ConversationController::class)->prefix('conversations')->name('conversations.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('{id}', 'show')->name('show');
        Route::post('{id}/reply', 'reply')->name('reply');
        Route::post('{id}/take-over', 'takeOver')->name('takeover');
        Route::post('{id}/resume', 'resume')->name('resume');
        Route::post('{id}/assign', 'assign')->name('assign');
        Route::post('{id}/close', 'close')->name('close');
    });

    Route::get('customers', [Dashboard\CustomerController::class, 'index'])->name('customers.index');
    Route::get('customers/{id}', [Dashboard\CustomerController::class, 'show'])->name('customers.show');
    Route::post('customers/{id}/anonymize', [Dashboard\CustomerController::class, 'anonymize'])->name('customers.anonymize')->middleware('demo.lock');
    Route::post('customers/{id}/privacy/{requestId}', [Dashboard\CustomerController::class, 'resolvePrivacy'])->name('customers.privacy');

    Route::controller(Dashboard\CatalogController::class)->prefix('catalog')->name('catalog.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('new', 'create')->name('create');
        Route::post('/', 'store')->name('store')->middleware('demo.lock');
        Route::get('{id}/edit', 'edit')->name('edit');
        Route::put('{id}', 'update')->name('update')->middleware('demo.lock');
        Route::post('{id}/stock', 'adjustStock')->name('stock');
    });

    Route::controller(Dashboard\AppointmentController::class)->prefix('appointments')->name('appointments.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('new', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('{id}', 'show')->name('show');
        Route::post('{id}/action', 'action')->name('action');
        Route::post('{id}/reschedule', 'reschedule')->name('reschedule');
        Route::post('{id}/assign', 'assign')->name('assign');
    });

    Route::controller(Dashboard\OrderController::class)->prefix('orders')->name('orders.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('new', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('{id}', 'show')->name('show');
        Route::post('{id}/action', 'action')->name('action');
        Route::post('{id}/delivery-fee', 'deliveryFee')->name('fee');
        Route::post('{id}/verify-payment', 'verifyPayment')->name('verify');
        Route::post('{id}/assign', 'assign')->name('assign');
    });

    Route::controller(Dashboard\QuotationController::class)->prefix('quotations')->name('quotations.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::get('{id}', 'show')->name('show');
        Route::post('{id}/versions', 'draft')->name('draft');
        Route::post('{id}/approve', 'approve')->name('approve');
        Route::post('{id}/status', 'status')->name('status');
        Route::post('{id}/assign', 'assign')->name('assign');
        Route::post('{id}/convert', 'convert')->name('convert');
        Route::get('{id}/files/{attachment}', 'attachment')->name('file');
    });

    Route::controller(Dashboard\ServiceRequestController::class)->prefix('service-requests')->name('service-requests.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::get('{id}', 'show')->name('show');
        Route::post('{id}/action', 'action')->name('action');
        Route::post('{id}/assign', 'assign')->name('assign');
    });

    Route::get('reports', [Dashboard\ReportController::class, 'index'])->name('reports');
    Route::get('activity', [Dashboard\ReportController::class, 'logs'])->name('logs');

    Route::get('account', [AccountController::class, 'edit'])->name('account');
    Route::put('account', [AccountController::class, 'update'])->name('account.update')->middleware('demo.lock');
    Route::put('account/password', [AccountController::class, 'updatePassword'])->name('account.password')->middleware(['throttle:6,1', 'demo.lock']);

    Route::get('team', [Dashboard\TeamController::class, 'index'])->name('team.index');
    Route::post('team', [Dashboard\TeamController::class, 'store'])->name('team.store')->middleware('demo.lock');
    Route::put('team/{id}', [Dashboard\TeamController::class, 'update'])->name('team.update')->middleware('demo.lock');
    Route::post('team/{userId}/schedule', [Dashboard\TeamController::class, 'schedule'])->name('team.schedule')->middleware('demo.lock');

    Route::controller(Dashboard\SettingsController::class)->prefix('settings')->name('settings.')->group(function () {
        Route::get('profile', 'profile')->name('profile');
        Route::put('profile', 'updateProfile')->middleware('demo.lock');
        Route::get('workflow', 'workflow')->name('workflow');
        Route::put('workflow', 'updateWorkflow')->middleware('demo.lock');
        Route::get('hours', 'hours')->name('hours');
        Route::put('hours', 'updateHours')->middleware('demo.lock');
        Route::get('knowledge', 'knowledge')->name('knowledge');
        Route::post('knowledge', 'storeKnowledge')->middleware('demo.lock');
        Route::put('knowledge/{id}', 'updateKnowledge')->name('knowledge.update')->middleware('demo.lock');
        Route::get('assistant', 'assistant')->name('assistant');
        Route::put('assistant', 'updateAssistant')->middleware('demo.lock');
        Route::post('assistant/test', 'testAssistant')->name('assistant.test')->middleware('throttle:20,1');
        Route::get('data', 'data')->name('data');
        Route::post('support', 'grantSupport')->name('support.grant')->middleware('demo.lock');
        Route::post('support/{id}/revoke', 'revokeSupport')->name('support.revoke');
        Route::get('export/{type}', 'export')->name('export');
        Route::post('close', 'close')->name('close')->middleware('demo.lock');
    });
});

// ------------------------------------------------------------------ Platform administration
Route::prefix('admin')->name('admin.')->middleware(['auth', 'mfa', 'platform'])->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('index');
    Route::get('logs', [AdminController::class, 'logs'])->name('logs');
    Route::get('businesses/{business}', [AdminController::class, 'show'])->name('show');
    Route::put('businesses/{business}/subscription', [AdminController::class, 'updateSubscription'])->name('subscription')->middleware('demo.lock');
    Route::post('businesses/{business}/payments', [AdminController::class, 'recordPayment'])->name('payment')->middleware('demo.lock');
    Route::post('businesses/{business}/status', [AdminController::class, 'setStatus'])->name('status')->middleware('demo.lock');
});
