<?php

use App\Http\Controllers\ChunkUploadController;
use App\Http\Controllers\DocumentRequestController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EntityController;
use App\Http\Controllers\Admin\FilialeController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Redirection racine vers login
Route::get('/', function () {
    return redirect()->route('login');
});

// Routes authentifiées
Route::middleware(['auth'])->group(function () {

    // Bibliothèque (tous les utilisateurs connectés)
    Route::get('/library', [DocumentController::class, 'index'])->name('library.index');
    Route::get('/documents/export', [DocumentController::class, 'export'])->name('documents.export');
    Route::post('/documents/download-zip', [DocumentController::class, 'downloadZip'])->name('documents.download-zip');
    Route::get('/documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
    Route::get('/documents/{document}/stream', [DocumentController::class, 'stream'])->name('documents.stream');

    // Upload, édition et suppression (admin + dmc + rmc)
    Route::middleware('can:upload')->group(function () {
        Route::post('/documents', [DocumentController::class, 'store'])->name('documents.store');
        Route::post('/documents/chunk', [ChunkUploadController::class, 'upload'])->name('documents.chunk');
        Route::put('/documents/{document}', [DocumentController::class, 'update'])->name('documents.update');
    });
    Route::delete('/documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');

    // Demandes de documents
    Route::post('/document-requests', [DocumentRequestController::class, 'store'])->name('document-requests.store');

    // Profil
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Administration (admin uniquement)
    Route::prefix('admin')->name('admin.')->middleware('can:admin')->group(function () {

        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        // Utilisateurs
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        Route::post('/users/{user}/resend-invitation', [UserController::class, 'resendInvitation'])->name('users.resend-invitation');

        // Entités
        Route::get('/entities', [EntityController::class, 'index'])->name('entities.index');
        Route::post('/entities', [EntityController::class, 'store'])->name('entities.store');
        Route::put('/entities/{entity}', [EntityController::class, 'update'])->name('entities.update');
        Route::delete('/entities/{entity}', [EntityController::class, 'destroy'])->name('entities.destroy');

        // Filiales
        Route::get('/filiales', [FilialeController::class, 'index'])->name('filiales.index');
        Route::post('/filiales', [FilialeController::class, 'store'])->name('filiales.store');
        Route::put('/filiales/{filiale}', [FilialeController::class, 'update'])->name('filiales.update');
        Route::delete('/filiales/{filiale}', [FilialeController::class, 'destroy'])->name('filiales.destroy');

        // Demandes de documents (admin)
        Route::get('/document-requests', [DocumentRequestController::class, 'index'])->name('document-requests.index');
        Route::put('/document-requests/{documentRequest}', [DocumentRequestController::class, 'update'])->name('document-requests.update');

        // Catégories
        Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
        Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');
        Route::post('/subcategories', [CategoryController::class, 'storeSubcategory'])->name('subcategories.store');
        Route::delete('/subcategories/{subcategory}', [CategoryController::class, 'destroySubcategory'])->name('subcategories.destroy');
    });
});

require __DIR__.'/auth.php';
