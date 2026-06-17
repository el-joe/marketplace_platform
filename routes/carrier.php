<?php

use App\Http\Controllers\CarrierPortal\AgentController;
use App\Http\Controllers\CarrierPortal\AssignmentController;
use App\Http\Controllers\CarrierPortal\AuthController;
use App\Http\Controllers\CarrierPortal\DashboardController;
use App\Http\Controllers\CarrierPortal\SupervisorController;
use Illuminate\Support\Facades\Route;

Route::domain('carrier.' . env('APP_DOMAIN', 'localhost'))
    ->name('carrier.')
    ->group(function () {

        // ── Guest ──────────────────────────────────────────────────────────────
        Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [AuthController::class, 'login'])->name('login.post');

        // ── Authenticated ──────────────────────────────────────────────────────
        Route::middleware(['auth.carrier'])->group(function () {

            Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

            // Dashboard
            Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

            // Agents (unlimited — no cap)
            Route::get('/agents', [AgentController::class, 'index'])->name('agents.index');
            Route::get('/agents/create', [AgentController::class, 'create'])->name('agents.create');
            Route::post('/agents', [AgentController::class, 'store'])->name('agents.store');
            Route::patch('/agents/{id}/suspend', [AgentController::class, 'suspend'])->name('agents.suspend');
            Route::patch('/agents/{id}/activate', [AgentController::class, 'activate'])->name('agents.activate');

            // Supervisors (owner-level only, gated inside controller)
            Route::resource('supervisors', SupervisorController::class)
                ->except(['show']);

            // Assignments — supervisor sees ALL including unaccepted
            Route::get('/assignments', [AssignmentController::class, 'index'])->name('assignments.index');
            Route::post('/assignments/{assignment}/reassign', [AssignmentController::class, 'reassign'])
                ->name('assignments.reassign');
        });
    });
