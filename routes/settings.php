<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::middleware(['auth'])->group(function (): void {
    Route::redirect('profile', 'profile/edit');

    Route::livewire('profile/edit', 'pages::settings.profile')->name('profile.edit');
});

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::livewire('profile/appearance', 'pages::settings.appearance')->name('appearance.edit');

    Route::livewire('profile/security', 'pages::settings.security')
        ->middleware(
            when(
                Features::canManageTwoFactorAuthentication()
                    && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                ['password.confirm'],
                [],
            ),
        )
        ->name('security.edit');
});
