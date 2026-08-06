<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\AdminGroupController;
use App\Http\Controllers\Admin\AdminStudentController;
use App\Http\Controllers\Admin\AdminTeacherController;
use App\Http\Controllers\Admin\AdminGuardianController;
use App\Http\Controllers\ScanController;
use App\Http\Controllers\Teacher\TeacherAttendanceController;
use App\Http\Controllers\Parent\ParentPortalController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Kiosk Scanner Station Routes
    Route::get('/scan', [ScanController::class, 'index'])->name('scan.index');
    Route::post('/scan/process', [ScanController::class, 'process'])->name('scan.process');

    // Teacher Panel Routes (Roles: teacher, admin)
    Route::middleware(['role:teacher,admin'])->prefix('teacher')->name('teacher.')->group(function () {
        Route::get('attendance', [TeacherAttendanceController::class, 'index'])->name('attendance.index');
        Route::post('attendance/update', [TeacherAttendanceController::class, 'updateStatus'])->name('attendance.update');
        Route::get('attendance/export', [TeacherAttendanceController::class, 'exportCsv'])->name('attendance.export');
    });

    // Parent Portal Routes (Roles: parent, admin)
    Route::middleware(['role:parent,admin'])->prefix('parent')->name('parent.')->group(function () {
        Route::get('dashboard', [ParentPortalController::class, 'index'])->name('dashboard');
        Route::post('alerts/update', [ParentPortalController::class, 'updateAlerts'])->name('alerts.update');
        Route::post('arco/submit', [ParentPortalController::class, 'submitArcoRequest'])->name('arco.submit');
    });
});

// Admin Routes (RBAC Role: admin)
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    // Groups CRUD
    Route::resource('groups', AdminGroupController::class)->except(['create', 'edit', 'show']);

    // Students CRUD & QR Credential
    Route::resource('students', AdminStudentController::class)->except(['create', 'edit', 'show']);
    Route::get('students/{student}/credential', [AdminStudentController::class, 'showCredential'])->name('students.credential');

    // Teachers Management & Assignments
    Route::get('teachers', [AdminTeacherController::class, 'index'])->name('teachers.index');
    Route::post('teachers/store', [AdminTeacherController::class, 'storeTeacher'])->name('teachers.store');
    Route::post('teachers/assign', [AdminTeacherController::class, 'assignGroup'])->name('teachers.assignGroup');
    Route::delete('teachers/assignments/{teacherGroup}', [AdminTeacherController::class, 'removeAssignment'])->name('teachers.removeAssignment');

    // Guardians Management & LFPDPPP Consent
    Route::get('guardians', [AdminGuardianController::class, 'index'])->name('guardians.index');
    Route::post('guardians/store', [AdminGuardianController::class, 'storeGuardian'])->name('guardians.store');
    Route::post('guardians/link', [AdminGuardianController::class, 'linkStudent'])->name('guardians.linkStudent');
});

require __DIR__.'/auth.php';
