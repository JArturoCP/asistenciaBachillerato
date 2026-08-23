<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\AdminGroupController;
use App\Http\Controllers\Admin\AdminStudentController;
use App\Http\Controllers\Admin\AdminTeacherController;
use App\Http\Controllers\Admin\AdminGuardianController;
use App\Http\Controllers\Admin\AdminPendingRegistrationController;
use App\Http\Controllers\ScanController;
use App\Http\Controllers\Teacher\TeacherAttendanceController;
use App\Http\Controllers\Parent\ParentPortalController;
use App\Http\Controllers\WhatsAppController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

// Main Admin Dashboard (Restricted to Admin and Superadmin; throws 403 for Parents and Teachers)
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified', 'role:admin,superadmin'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Kiosk Scanner Station Routes (Visible to Superadmin, Admin & Teacher; restricted for Parent)
    Route::middleware(['role:superadmin,admin,teacher'])->group(function () {
        Route::get('/scan', [ScanController::class, 'index'])->name('scan.index');
        Route::post('/scan/process', [ScanController::class, 'process'])->name('scan.process');
    });

    // Teacher Panel Routes (Roles: teacher, admin, superadmin)
    Route::middleware(['role:teacher,admin,superadmin'])->prefix('teacher')->name('teacher.')->group(function () {
        Route::get('attendance', [TeacherAttendanceController::class, 'index'])->name('attendance.index');
        Route::post('attendance/update', [TeacherAttendanceController::class, 'updateStatus'])->name('attendance.update');
        Route::get('attendance/export', [TeacherAttendanceController::class, 'exportCsv'])->name('attendance.export');
    });

    // Parent Portal Routes (Roles: parent, admin, superadmin)
    Route::middleware(['role:parent,admin,superadmin'])->prefix('parent')->name('parent.')->group(function () {
        Route::get('dashboard', [ParentPortalController::class, 'index'])->name('dashboard');
        Route::post('alerts/update', [ParentPortalController::class, 'updateAlerts'])->name('alerts.update');
        Route::post('arco/submit', [ParentPortalController::class, 'submitArcoRequest'])->name('arco.submit');
    });
});

// Admin Routes (RBAC Roles: admin, superadmin)
Route::middleware(['auth', 'role:admin,superadmin'])->prefix('admin')->name('admin.')->group(function () {
    // Pending Registration Approval Panel
    Route::get('pending-registrations', [AdminPendingRegistrationController::class, 'index'])->name('pending-registrations.index');
    Route::post('pending-registrations/{user}/approve', [AdminPendingRegistrationController::class, 'approve'])->name('pending-registrations.approve');
    Route::delete('pending-registrations/{user}/reject', [AdminPendingRegistrationController::class, 'reject'])->name('pending-registrations.reject');

    // Groups CRUD
    Route::resource('groups', AdminGroupController::class)->except(['create', 'edit', 'show']);

    // Students CRUD & QR Credential
    Route::resource('students', AdminStudentController::class)->except(['create', 'edit', 'show']);
    Route::get('students/{student}/credential', [AdminStudentController::class, 'showCredential'])->name('students.credential');

    // Teachers Management & Assignments CRUD
    Route::get('teachers', [AdminTeacherController::class, 'index'])->name('teachers.index');
    Route::post('teachers/store', [AdminTeacherController::class, 'storeTeacher'])->name('teachers.store');
    Route::put('teachers/{teacher}', [AdminTeacherController::class, 'updateTeacher'])->name('teachers.updateTeacher');
    Route::delete('teachers/{teacher}', [AdminTeacherController::class, 'destroyTeacher'])->name('teachers.destroyTeacher');
    Route::post('teachers/materias', [AdminTeacherController::class, 'storeMateria'])->name('teachers.storeMateria');
    Route::post('teachers/assign', [AdminTeacherController::class, 'assignGroup'])->name('teachers.assignGroup');
    Route::put('teachers/assignments/{teacherGroup}', [AdminTeacherController::class, 'updateAssignment'])->name('teachers.updateAssignment');
    Route::delete('teachers/assignments/{teacherGroup}', [AdminTeacherController::class, 'removeAssignment'])->name('teachers.removeAssignment');

    // Guardians Management & LFPDPPP Consent CRUD
    Route::get('guardians', [AdminGuardianController::class, 'index'])->name('guardians.index');
    Route::post('guardians/store', [AdminGuardianController::class, 'storeGuardian'])->name('guardians.store');
    Route::put('guardians/{guardian}', [AdminGuardianController::class, 'updateGuardian'])->name('guardians.updateGuardian');
    Route::delete('guardians/{guardian}', [AdminGuardianController::class, 'destroyGuardian'])->name('guardians.destroyGuardian');
    Route::post('guardians/link', [AdminGuardianController::class, 'linkStudent'])->name('guardians.linkStudent');
    Route::delete('guardians/{guardian}/unlink/{student}', [AdminGuardianController::class, 'unlinkStudent'])->name('guardians.unlinkStudent');
});


require __DIR__.'/auth.php';
