<?php

use App\Http\Controllers\RegistrationController;
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

Route::prefix('volunteer')->name('volunteer.')->group(function () {
    Route::get('/', [VolunteerController::class, 'showVolunteerRegistration'])->name('registration');
    Route::post('/', [VolunteerController::class, 'submitVolunteer'])->name('submit_registration');
});