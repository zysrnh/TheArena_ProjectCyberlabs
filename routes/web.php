<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MatchController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\LiveController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\EquipmentBookingController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\AboutController; // ✅ Tambahkan ini

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/storage-link', function () {
    $targetFolder = $_SERVER['DOCUMENT_ROOT'] . '/laravel/storage/app/public';
    $linkFolder = $_SERVER['DOCUMENT_ROOT'] . '/storage';
    $success = symlink($targetFolder, $linkFolder);
    echo 'Symlink completed ' . $success;
});

// Route untuk HomePage
Route::get('/', [HomeController::class, 'index'])->name('home');

// ✅ Route untuk About/Tentang - GUNAKAN CONTROLLER
Route::get('/tentang', [AboutController::class, 'index'])->name('about');

// ============================================
// BOOKING LAPANGAN ROUTES
// ============================================
Route::get('/booking', [BookingController::class, 'index'])->name('booking.index');

// API Routes untuk Booking Lapangan (AJAX requests - NO CSRF)
Route::prefix('api/booking')->group(function () {
    Route::get('/time-slots', [BookingController::class, 'getTimeSlots']);
    Route::post('/process', [BookingController::class, 'processBooking']);
});

// ============================================
// BOOKING PERALATAN ROUTES
// ============================================
Route::get('/booking-peralatan', [EquipmentBookingController::class, 'index'])->name('equipment.booking.index');
Route::get('/booking-peralatan/{id}', [EquipmentBookingController::class, 'show'])->name('equipment.booking.show');

// API Routes untuk Booking Peralatan
Route::prefix('api/equipment-booking')->group(function () {
    Route::get('/equipments', [EquipmentBookingController::class, 'getEquipments']);
    Route::post('/process', [EquipmentBookingController::class, 'processBooking']);
});

// ============================================
// JADWAL & HASIL PERTANDINGAN ROUTES
// ============================================
Route::get('/jadwal-hasil', [MatchController::class, 'index'])->name('match.index');
Route::get('/jadwal-hasil/{id}', [MatchController::class, 'show'])->name('match.show');

// API Routes untuk Match
Route::prefix('api/match')->group(function () {
    Route::get('/by-date', [MatchController::class, 'getMatchesByDate']);
    Route::get('/search', [MatchController::class, 'search']);
    Route::get('/{id}/stats', [MatchController::class, 'getStats']);
});

// ============================================
// AUTH ROUTES
// ============================================
Route::middleware('guest:client')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:client')->name('logout');

// ============================================
// PROTECTED ROUTES (memerlukan login)
// ============================================
Route::middleware('auth:client')->group(function () {
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile');
    Route::post('/profile/update', [ProfileController::class, 'update'])->name('profile.update');

    // Cancel Booking Route
    Route::post('/profile/booking/{id}/cancel', [ProfileController::class, 'cancelBooking'])->name('profile.booking.cancel');
});

// ============================================
// SIARAN LANGSUNG ROUTES
// ============================================
Route::get('/siaran-langsung', [LiveController::class, 'index'])->name('live.index');

// API Routes untuk Live
Route::prefix('api/live')->group(function () {
    Route::get('/filter', [LiveController::class, 'filter']);
    Route::get('/search', [LiveController::class, 'search']);
});

// ============================================
// KONTAK ROUTES
// ============================================
Route::get('/kontak', [ContactController::class, 'index'])->name('contact.index');
Route::post('/kontak', [ContactController::class, 'submit'])->name('contact.submit');

// ============================================
// BERITA/NEWS ROUTES
// ============================================
Route::get('/berita', [NewsController::class, 'index'])->name('news.index');
Route::get('/berita/{id}', [NewsController::class, 'show'])->name('news.show');

// Review routes
Route::post('/api/reviews/store', [BookingController::class, 'storeReview'])->middleware('web');
Route::get('/api/reviews', [BookingController::class, 'getReviews']);

// ============================================
// PAYMENT ROUTES (FASPAY)
// ============================================

// ✅ Protected routes (user must be logged in)
Route::middleware('auth:client')->group(function () {
    // Process payment (redirect to Faspay)
    Route::post('/payment/process/{booking}', [PaymentController::class, 'process'])
        ->name('payment.process');

    // ✅ Return URL (user comes back from Faspay)
    Route::get('/payment/faspay/return', [PaymentController::class, 'return'])
        ->name('payment.faspay.return');


    Route::get('/payment/test-callback', function () {
        return view('test-callback');
    })->name('payment.test-callback');
});

// ✅ Callback from Faspay (server-to-server, NO AUTH)
Route::post('/api/payment/faspay/callback', [PaymentController::class, 'callback'])
    ->name('payment.faspay.callback');

    // ✅ Check payment status (NO AUTH required - for UAT testing)
Route::post('/api/payment/check-status', [PaymentController::class, 'checkStatus'])
    ->name('payment.check-status');