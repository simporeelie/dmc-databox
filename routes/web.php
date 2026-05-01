<?php

use App\Http\Controllers\ChunkUploadController;
use App\Http\Controllers\DocumentRequestController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\ExpiredPasswordController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TwoFactorController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EntityController;
use App\Http\Controllers\Admin\FilialeController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

// Redirection racine vers login
Route::get('/', fn () => redirect()->route('login'));

// Routes authentifiées
Route::middleware(['auth'])->group(function () {

    // Double authentification (2FA)
    Route::get('/two-factor/challenge', [TwoFactorController::class, 'challenge'])->name('two-factor.challenge');
    Route::post('/two-factor/verify',   [TwoFactorController::class, 'verify'])->name('two-factor.verify')->middleware('throttle:10,1');
    Route::get('/two-factor/setup',     [TwoFactorController::class, 'setup'])->name('two-factor.setup');
    Route::post('/two-factor/enable',   [TwoFactorController::class, 'enable'])->name('two-factor.enable');
    Route::post('/two-factor/disable',  [TwoFactorController::class, 'disable'])->name('two-factor.disable');

    // Mot de passe expiré
    Route::get('/password/expired',  [ExpiredPasswordController::class, 'show'])->name('password.expired');
    Route::put('/password/expired',  [ExpiredPasswordController::class, 'update'])->name('password.expired.update');


    // Bibliothèque (tous les utilisateurs connectés)
    Route::get('/library', [DocumentController::class, 'index'])->name('library.index');

    // Stream media/PDF — authentifié, filiale vérifiée dans le controller
    Route::get('/documents/{document}/stream', [DocumentController::class, 'stream'])
        ->name('documents.stream')
        ->middleware('throttle:120,1');

    // Miniatures images — authentifié
    Route::get('/documents/{document}/thumbnail', [DocumentController::class, 'thumbnail'])
        ->name('documents.thumbnail')
        ->middleware('throttle:120,1');

    // Téléchargement (visiteur exclu)
    Route::middleware('can:download')->group(function () {
        Route::get('/documents/export', [DocumentController::class, 'export'])
            ->name('documents.export')
            ->middleware('throttle:20,1');

        Route::post('/documents/download-zip', [DocumentController::class, 'downloadZip'])
            ->name('documents.download-zip')
            ->middleware('throttle:10,1');

        Route::get('/documents/{document}/download', [DocumentController::class, 'download'])
            ->name('documents.download')
            ->middleware('throttle:60,1');
    });

    // Upload, édition (admin + dmc uniquement)
    Route::middleware('can:upload')->group(function () {
        Route::post('/documents', [DocumentController::class, 'store'])
            ->name('documents.store')
            ->middleware('throttle:30,1');

        Route::post('/documents/chunk', [ChunkUploadController::class, 'upload'])
            ->name('documents.chunk')
            ->middleware('throttle:200,1');

        Route::put('/documents/{document}', [DocumentController::class, 'update'])
            ->name('documents.update');
    });

    // Suppression (admin + dmc + rmc — vérification filiale dans le controller)
    Route::delete('/documents/{document}', [DocumentController::class, 'destroy'])
        ->name('documents.destroy')
        ->middleware('can:delete-document');

    // Demandes de documents
    Route::post('/document-requests', [DocumentRequestController::class, 'store'])
        ->name('document-requests.store')
        ->middleware('throttle:5,1');

    // Profil
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // Administration (admin uniquement)
    Route::prefix('admin')->name('admin.')->middleware('can:admin')->group(function () {

        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        Route::post('/users/{user}/resend-invitation', [UserController::class, 'resendInvitation'])
            ->name('users.resend-invitation');

        Route::get('/entities', [EntityController::class, 'index'])->name('entities.index');
        Route::post('/entities', [EntityController::class, 'store'])->name('entities.store');
        Route::put('/entities/{entity}', [EntityController::class, 'update'])->name('entities.update');
        Route::delete('/entities/{entity}', [EntityController::class, 'destroy'])->name('entities.destroy');

        Route::get('/filiales', [FilialeController::class, 'index'])->name('filiales.index');
        Route::post('/filiales', [FilialeController::class, 'store'])->name('filiales.store');
        Route::put('/filiales/{filiale}', [FilialeController::class, 'update'])->name('filiales.update');
        Route::delete('/filiales/{filiale}', [FilialeController::class, 'destroy'])->name('filiales.destroy');

        Route::get('/document-requests', [DocumentRequestController::class, 'index'])
            ->name('document-requests.index');
        Route::put('/document-requests/{documentRequest}', [DocumentRequestController::class, 'update'])
            ->name('document-requests.update');

        Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
        Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');
        Route::post('/subcategories', [CategoryController::class, 'storeSubcategory'])->name('subcategories.store');
        Route::delete('/subcategories/{subcategory}', [CategoryController::class, 'destroySubcategory'])
            ->name('subcategories.destroy');
    });
});

require __DIR__.'/auth.php';
