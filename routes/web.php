<?php

use App\Http\Controllers\Api\EvaluationStatusController;
use App\Http\Controllers\CandidateController;
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
