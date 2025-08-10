<?php

use App\Http\Controllers\FullRegistrationController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\RegistrationSACController;
use App\Http\Controllers\RegistrationSACPersController;
use App\Http\Controllers\RegistrationSACVipController;
use App\Http\Controllers\RegistrationSuccessController;
use App\Http\Controllers\VolunteerController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/registration');

Route::prefix('registration')->name('user.')->group(function () {
    Route::get('/', [RegistrationController::class, 'showRegistrationPage'])->name('registration');
    Route::post('/', [RegistrationController::class, 'submitRegistration'])->name('submit_registration');

    Route::get('/choose-seat', [RegistrationController::class, 'showChooseSeat'])->name('choose_seat');
    Route::post('/choose-seat', [RegistrationController::class, 'submitChosenSeat'])->name('submit_seat');

    Route::get('/{registration}/success', [RegistrationController::class, 'showRegistrationSuccess'])
        ->name('registration_success')
        ->middleware(['signed']);
});

Route::get('/full', FullRegistrationController::class)->name('full_registration');

Route::get('/registration/{registration}/success', RegistrationSuccessController::class)
    ->name('registration_success')
    ->middleware(['signed']);

Route::prefix('sac')->name('sac.')->group(function () {
    Route::get('/', [RegistrationSACController::class, 'showWelcome'])->name('welcome');
    Route::get('/registration', [RegistrationSACController::class, 'showForm'])->name('registration');
    Route::post('/registration', [RegistrationSACController::class, 'submitForm'])->name('submit_registration');
});

Route::prefix('sac-vip')->name('sac_vip.')->group(function () {
    Route::get('/', [RegistrationSACVipController::class, 'showWelcome'])->name('welcome');
    Route::get('/registration', [RegistrationSACVipController::class, 'showForm'])->name('registration');
    Route::post('/registration', [RegistrationSACVipController::class, 'submitForm'])->name('submit_registration');
});

Route::prefix('sac-pers')->name('sac_pers.')->group(function () {
    Route::get('/', [RegistrationSACPersController::class, 'showWelcome'])->name('welcome');
    Route::get('/registration', [RegistrationSACPersController::class, 'showForm'])->name('registration');
    Route::post('/registration', [RegistrationSACPersController::class, 'submitForm'])->name('submit_registration');
});

Route::prefix('volunteer')->name('volunteer.')->group(function () {
    Route::get('/', [VolunteerController::class, 'showWelcome'])->name('welcome');
    Route::get('/registration', [VolunteerController::class, 'showVolunteerRegistration'])->name('registration');
    Route::post('/registration', [VolunteerController::class, 'submitVolunteer'])->name('submit_registration');
});
