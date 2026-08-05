<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GuestController;
use App\Http\Controllers\FormController;

/*
|--------------------------------------------------------------------------
| Web Routes Common LMS
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// The old "interview assessment" guest landing page isn't part of this brief —
// go straight to the form manager instead of an unrelated home page.
Route::redirect('/', '/forms');

/*
|--------------------------------------------------------------------------
| AI-Powered Form Builder
|--------------------------------------------------------------------------
*/

Route::prefix('forms')->group(function () {
    Route::get('/', [FormController::class, 'index'])->name('forms.index');
    Route::post('/', [FormController::class, 'create'])->name('forms.create');
    Route::get('/import', [FormController::class, 'importPage'])->name('forms.import');
    Route::get('/{form}/edit', [FormController::class, 'edit'])->name('forms.edit');
    Route::delete('/{form}', [FormController::class, 'destroy'])->name('forms.destroy');
    Route::get('/{form}/submissions', [FormController::class, 'submissions'])->name('forms.submissions');
    Route::get('/{form}/submissions/export', [FormController::class, 'exportSubmissions'])->name('forms.submissions.export');
});

// Public fill URL is throttled — Part D: basic rate limiting / spam protection
// against scripted mass-submission on an unauthenticated endpoint.
Route::get('/f/{slug}', [FormController::class, 'fill'])
    ->name('forms.fill')
    ->middleware('throttle:30,1');