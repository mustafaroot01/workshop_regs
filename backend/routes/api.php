<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\PublicController;
use Illuminate\Support\Facades\Route;

// ── Public Routes (No Auth) ──────────────────────────────────────────────────
Route::prefix('forms')->group(function () {
    Route::get('{id}', [PublicController::class, 'getForm']);
    Route::post('{id}/register', [PublicController::class, 'register']);
});

// ── Admin Auth ────────────────────────────────────────────────────────────────
Route::post('admin/login', [AdminController::class, 'login']);

// ── Admin Protected Routes ────────────────────────────────────────────────────
Route::middleware('auth:sanctum')->prefix('admin')->group(function () {

    Route::post('logout', [AdminController::class, 'logout']);
    Route::get('me', [AdminController::class, 'me']);

    // Stats
    Route::get('stats/overview', [AdminController::class, 'getOverviewStats']);

    // Forms
    Route::get('forms', [AdminController::class, 'getForms']);
    Route::post('forms', [AdminController::class, 'createForm']);
    Route::put('forms/{id}', [AdminController::class, 'updateForm']);
    Route::delete('forms/{id}', [AdminController::class, 'deleteForm']);

    // Students
    Route::get('forms/{id}/students', [AdminController::class, 'getStudents']);
    Route::put('students/{id}', [AdminController::class, 'updateStudent']);
    Route::delete('students/{id}', [AdminController::class, 'deleteStudent']);
    Route::get('forms/{id}/stats', [AdminController::class, 'getStats']);

    // Attendance
    Route::post('attendance/scan', [AdminController::class, 'scanAttendance']);

    // Departments
    Route::get('departments', [AdminController::class, 'getDepartments']);
    Route::post('departments', [AdminController::class, 'createDepartment']);
    Route::delete('departments/{id}', [AdminController::class, 'deleteDepartment']);

    // Interests
    Route::get('interests', [AdminController::class, 'getInterests']);
    Route::post('interests', [AdminController::class, 'createInterest']);
    Route::delete('interests/{id}', [AdminController::class, 'deleteInterest']);

    // Admin Users (RBAC)
    Route::get('users', [AdminController::class, 'getUsers']);
    Route::post('users', [AdminController::class, 'createUser']);
    Route::delete('users/{id}', [AdminController::class, 'deleteUser']);
});
