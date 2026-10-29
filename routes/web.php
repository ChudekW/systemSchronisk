<?php

use App\Http\Controllers\AnimalController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\TenantOrganizationController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

Route::middleware(['auth', 'verified', 'can:manage-organizations'])->group(function () {
    Route::resource('organizations', OrganizationController::class)->only(['index', 'create', 'store', 'edit', 'update']);
});
Route::middleware(['auth', 'verified', 'can:manage-own-organization'])->group(function () {
    Route::get('organization', [TenantOrganizationController::class, 'edit'])->name('tenant.organization.edit');
    Route::put('organization', [TenantOrganizationController::class, 'update'])->name('tenant.organization.update');
});

require __DIR__.'/settings.php';

Route::middleware(['auth', 'verified', 'can:manage-animals'])->group(function () {
    Route::get('animals/{animal}/photo', [AnimalController::class, 'photo'])->name('animals.photo');
    Route::resource('animals', AnimalController::class)->except('destroy');
});
