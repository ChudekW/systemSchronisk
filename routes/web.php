<?php

use App\Http\Controllers\AdoptionAftercareController;
use App\Http\Controllers\AdoptionController;
use App\Http\Controllers\AnimalController;
use App\Http\Controllers\CareTaskController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\MedicalRecordController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\TeamController;
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

Route::middleware(['auth', 'verified', 'can:manage-team'])->group(function () {
    Route::get('team', [TeamController::class, 'index'])->name('team.index');
    Route::post('team', [TeamController::class, 'store'])->middleware('throttle:10,1')->name('team.store');
    Route::put('team/{member}', [TeamController::class, 'update'])->whereNumber('member')->name('team.update');
    Route::post('team/{member}/invitation', [TeamController::class, 'resend'])->whereNumber('member')->middleware('throttle:10,1')->name('team.resend');
    Route::delete('team/{member}/invitation', [TeamController::class, 'cancel'])->whereNumber('member')->name('team.cancel');
});

Route::get('invitations/{token}', [InvitationController::class, 'show'])->where('token', '[a-zA-Z0-9]{64}')->middleware('throttle:60,1')->name('invitations.show');
Route::post('invitations/{token}', [InvitationController::class, 'store'])->where('token', '[a-zA-Z0-9]{64}')->middleware(['guest', 'throttle:10,1'])->name('invitations.store');

Route::middleware(['auth', 'verified', 'can:manage-animals'])->group(function () {
    Route::get('animals/{animal}/photo', [AnimalController::class, 'photo'])->name('animals.photo');
    Route::resource('animals', AnimalController::class)->except('destroy');
});

Route::middleware(['auth', 'verified', 'can:manage-animals'])->group(function () {
    Route::get('tasks', [CareTaskController::class, 'index'])->name('tasks.index');
    Route::get('animals/{animal}/tasks', [CareTaskController::class, 'index'])->name('animals.tasks.index');
    Route::post('animals/{animal}/tasks', [CareTaskController::class, 'store'])->name('animals.tasks.store');
    Route::get('animals/{animal}/tasks/{task}', [CareTaskController::class, 'show'])->name('animals.tasks.show');
    Route::put('animals/{animal}/tasks/{task}', [CareTaskController::class, 'update'])->name('animals.tasks.update');
});

Route::middleware(['auth', 'verified', 'can:manage-animals'])->group(function () {
    Route::get('animals/{animal}/health', [MedicalRecordController::class, 'index'])->name('animals.health.index');
    Route::post('animals/{animal}/health', [MedicalRecordController::class, 'store'])->name('animals.health.store');
    Route::get('animals/{animal}/health/attachments/{attachment}', [MedicalRecordController::class, 'download'])->name('animals.health.download');
});

Route::middleware(['auth', 'verified', 'can:manage-animals'])->group(function () {
    Route::get('adoptions', [AdoptionController::class, 'index'])->name('adoptions.index');
    Route::get('animals/{animal}/adoptions', [AdoptionController::class, 'index'])->name('animals.adoptions.index');
    Route::post('animals/{animal}/adoptions', [AdoptionController::class, 'store'])->name('animals.adoptions.store');
    Route::get('adoptions/{adoption}', [AdoptionController::class, 'show'])->name('adoptions.show');
    Route::put('adoptions/{adoption}', [AdoptionController::class, 'update'])->name('adoptions.update');
    Route::get('adoption-documents/{attachment}', [AdoptionController::class, 'download'])->name('adoptions.download');
});

Route::middleware(['auth', 'verified', 'can:manage-animals'])->group(function () {
    Route::post('adoptions/{adoption}/aftercare', [AdoptionAftercareController::class, 'store'])->name('adoptions.aftercare');
    Route::post('adoptions/{adoption}/return', [AdoptionAftercareController::class, 'store'])->name('adoptions.return');
});
