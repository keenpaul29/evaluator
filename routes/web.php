<?php

use App\Http\Controllers\Api\EvaluationProgressController;
use App\Http\Controllers\Api\EvaluationStatusController;
use App\Http\Controllers\BatchController;
use App\Http\Controllers\CandidateController;
use App\Http\Controllers\ComparisonController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PublicApplyController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

Route::resource('candidates', CandidateController::class)->except(['edit', 'update', 'destroy']);
Route::post('candidates/{candidate}/comment', [CandidateController::class, 'addComment'])->name('candidates.comment');
Route::post('candidates/{candidate}/shortlist', [CandidateController::class, 'shortlist'])->name('candidates.shortlist');
Route::post('candidates/{candidate}/reject', [CandidateController::class, 'reject'])->name('candidates.reject');

Route::get('/apply', [PublicApplyController::class, 'show'])->name('apply.show');
Route::post('/apply', [PublicApplyController::class, 'store'])->name('apply.store');
Route::get('/apply/success', [PublicApplyController::class, 'success'])->name('apply.success');

Route::get('/api/evaluation-status/{candidate}', [EvaluationStatusController::class, 'show'])->name('api.evaluation-status');
Route::get('/api/evaluations/{candidate}/progress', [EvaluationProgressController::class, 'show'])->name('api.evaluation-progress');

Route::get('/comparisons', [ComparisonController::class, 'index'])->name('comparisons.index');
Route::get('/comparisons/{comparison}', [ComparisonController::class, 'show'])->name('comparisons.show');
Route::post('/comparisons', [ComparisonController::class, 'store'])->name('comparisons.store');
Route::delete('/comparisons/{comparison}', [ComparisonController::class, 'destroy'])->name('comparisons.destroy');

Route::get('/batches', [BatchController::class, 'index'])->name('batches.index');
Route::get('/batches/create', [BatchController::class, 'create'])->name('batches.create');
Route::post('/batches', [BatchController::class, 'store'])->name('batches.store');
Route::get('/batches/{batch}', [BatchController::class, 'show'])->name('batches.show');
