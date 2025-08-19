<?php

use App\Http\Controllers\ComingSoonController;
use App\Http\Controllers\FullRegistrationController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\RegistrationSACController;
use App\Http\Controllers\RegistrationSACOpeningCeremonyController;
use App\Http\Controllers\RegistrationSACPersConferenceController;
use App\Http\Controllers\RegistrationSACPersController;
use App\Http\Controllers\RegistrationSACVipController;
use App\Http\Controllers\RegistrationSuccessController;
use App\Http\Controllers\VolunteerController;
use Illuminate\Support\Facades\Route;

Route::get('/storage-link', function () {
    $targetFolder = $_SERVER['DOCUMENT_ROOT'].'/laravel/storage/app/public';
    $linkFolder = $_SERVER['DOCUMENT_ROOT'].'/storage';
    $success = symlink($targetFolder, $linkFolder);
    echo 'Symlink completed '. $success;
});

Route::get('/full', FullRegistrationController::class)->name('full_registration');

Route::get('/registration/{registration}/success', RegistrationSuccessController::class)
    ->name('registration_success')
    ->middleware(['signed']);

Route::prefix('sac')->name('sac.')->group(function () {
    Route::get('/pameran', [RegistrationSACController::class, 'showWelcome'])->name('welcome');
    Route::get('/pameran/registration', [RegistrationSACController::class, 'showForm'])->name('registration');
    Route::post('/pameran/registration', [RegistrationSACController::class, 'submitForm'])->name('submit_registration');
});
