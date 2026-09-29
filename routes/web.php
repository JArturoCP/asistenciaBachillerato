<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\{AdminGroupController, AdminStudentController, AdminTeacherController, AdminGuardianController, AdminPendingRegistrationController, AdminTeacherAttendanceController, AccessUserController, AccessRoleController, StudentCsvImportController};
use App\Http\Controllers\{ScanController, GeneralAttendanceController, WhatsappController};
use App\Http\Controllers\Teacher\TeacherAttendanceController;
use App\Http\Controllers\Parent\ParentPortalController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));
Route::get('/dashboard', function () {
    $u = auth()->user();
    if ($u->isAdmin()) return view('dashboard');
    if ($u->hasPermission('attendance.students.view') || $u->hasPermission('attendance.teachers.view')) return redirect()->route('attendance.overview.index');
    if ($u->hasPermission('students.view')) return redirect()->route('admin.students.index');
    if ($u->hasPermission('scan.use')) return redirect()->route('scan.index');
    if ($u->hasPermission('teacher.attendance.view')) return redirect()->route('teacher.attendance.index');
    if ($u->hasPermission('parent.portal')) return redirect()->route('parent.dashboard');
    abort(403, 'Su cuenta no tiene módulos habilitados. Contacte al administrador.');
})->middleware(['auth','verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('attendance/overview', [GeneralAttendanceController::class, 'index'])
        ->name('attendance.overview.index');
    Route::post('attendance/update-student', [GeneralAttendanceController::class, 'updateStudentStatus'])->middleware('can:attendance.students.manage')->name('attendance.overview.update-student');
    Route::post('attendance/update-teacher', [GeneralAttendanceController::class, 'updateTeacherStatus'])->middleware('can:attendance.teachers.manage')->name('attendance.overview.update-teacher');
    Route::get('attendance/export-students', [GeneralAttendanceController::class, 'exportStudentCsv'])->middleware(['can:attendance.students.view','can:attendance.export'])->name('attendance.overview.export-students');
    Route::get('attendance/export-teachers', [GeneralAttendanceController::class, 'exportTeacherCsv'])->middleware(['can:attendance.teachers.view','can:attendance.export'])->name('attendance.overview.export-teachers');

    Route::middleware('can:scan.use')->group(function () {
        Route::get('/scan', [ScanController::class, 'index'])->name('scan.index');
        Route::post('/scan/process', [ScanController::class, 'process'])->name('scan.process');
    });
    Route::get('teacher/attendance', [TeacherAttendanceController::class, 'index'])->middleware('can:teacher.attendance.view')->name('teacher.attendance.index');
    Route::post('teacher/attendance/update', [TeacherAttendanceController::class, 'updateStatus'])->middleware('can:teacher.attendance.manage')->name('teacher.attendance.update');
    Route::get('teacher/attendance/export', [TeacherAttendanceController::class, 'exportCsv'])->middleware('can:teacher.attendance.export')->name('teacher.attendance.export');
    Route::middleware('can:parent.portal')->prefix('parent')->name('parent.')->group(function () {
        Route::get('dashboard', [ParentPortalController::class, 'index'])->name('dashboard');
        Route::post('alerts/update', [ParentPortalController::class, 'updateAlerts'])->name('alerts.update');
        Route::post('arco/submit', [ParentPortalController::class, 'submitArcoRequest'])->name('arco.submit');
    });
});

Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::middleware('can:whatsapp.manage')->group(function () {
        Route::get('whatsapp', [WhatsappController::class, 'index'])->name('whatsapp.index');
        Route::get('whatsapp/status', [WhatsappController::class, 'status'])->name('whatsapp.status');
        Route::post('whatsapp/send', [WhatsappController::class, 'store'])->name('whatsapp.send');
        Route::post('whatsapp/logout', [WhatsappController::class, 'logout'])->name('whatsapp.logout');
    });
    Route::get('pending-registrations', [AdminPendingRegistrationController::class, 'index'])->middleware('can:registrations.view')->name('pending-registrations.index');
    Route::post('pending-registrations/{user}/approve', [AdminPendingRegistrationController::class, 'approve'])->middleware('can:registrations.manage')->name('pending-registrations.approve');
    Route::delete('pending-registrations/{user}/reject', [AdminPendingRegistrationController::class, 'reject'])->middleware('can:registrations.manage')->name('pending-registrations.reject');

    Route::get('groups', [AdminGroupController::class, 'index'])->middleware('can:groups.view')->name('groups.index');
    Route::post('groups', [AdminGroupController::class, 'store'])->middleware('can:groups.create')->name('groups.store');
    Route::put('groups/{group}', [AdminGroupController::class, 'update'])->middleware('can:groups.edit')->name('groups.update');
    Route::delete('groups/{group}', [AdminGroupController::class, 'destroy'])->middleware('can:groups.delete')->name('groups.destroy');

    Route::get('students', [AdminStudentController::class, 'index'])->middleware('can:students.view')->name('students.index');
    Route::post('students', [AdminStudentController::class, 'store'])->middleware('can:students.create')->name('students.store');
    Route::put('students/{student}', [AdminStudentController::class, 'update'])->middleware('can:students.edit')->name('students.update');
    Route::delete('students/{student}', [AdminStudentController::class, 'destroy'])->middleware('can:students.delete')->name('students.destroy');
    Route::get('students/{student}/credential', [AdminStudentController::class, 'showCredential'])->middleware('can:students.view')->name('students.credential');
    Route::middleware('can:students.import')->prefix('students/import')->name('students.import.')->group(function () {
        Route::get('/', [StudentCsvImportController::class, 'index'])->name('index');
        Route::get('template', [StudentCsvImportController::class, 'template'])->name('template');
        Route::post('preview', [StudentCsvImportController::class, 'preview'])->name('preview');
        Route::post('commit', [StudentCsvImportController::class, 'commit'])->name('commit');
    });

    Route::get('teachers', [AdminTeacherController::class, 'index'])->middleware('can:teachers.view')->name('teachers.index');
    Route::get('teachers/{teacher}/credential', [AdminTeacherController::class, 'showCredential'])->middleware('can:teachers.view')->name('teachers.credential');
    Route::middleware('can:teachers.manage')->group(function () {
        Route::post('teachers/store', [AdminTeacherController::class, 'storeTeacher'])->name('teachers.store');
        Route::put('teachers/{teacher}', [AdminTeacherController::class, 'updateTeacher'])->name('teachers.updateTeacher');
        Route::delete('teachers/{teacher}', [AdminTeacherController::class, 'destroyTeacher'])->name('teachers.destroyTeacher');
        Route::post('teachers/materias', [AdminTeacherController::class, 'storeMateria'])->name('teachers.storeMateria');
        Route::post('teachers/assign', [AdminTeacherController::class, 'assignGroup'])->name('teachers.assignGroup');
        Route::put('teachers/assignments/{teacherGroup}', [AdminTeacherController::class, 'updateAssignment'])->name('teachers.updateAssignment');
        Route::delete('teachers/assignments/{teacherGroup}', [AdminTeacherController::class, 'removeAssignment'])->name('teachers.removeAssignment');
    });
    Route::get('guardians', [AdminGuardianController::class, 'index'])->middleware('can:guardians.view')->name('guardians.index');
    Route::middleware('can:guardians.manage')->group(function () {
        Route::post('guardians/store', [AdminGuardianController::class, 'storeGuardian'])->name('guardians.store');
        Route::put('guardians/{guardian}', [AdminGuardianController::class, 'updateGuardian'])->name('guardians.updateGuardian');
        Route::delete('guardians/{guardian}', [AdminGuardianController::class, 'destroyGuardian'])->name('guardians.destroyGuardian');
        Route::post('guardians/link', [AdminGuardianController::class, 'linkStudent'])->name('guardians.linkStudent');
        Route::delete('guardians/{guardian}/unlink/{student}', [AdminGuardianController::class, 'unlinkStudent'])->name('guardians.unlinkStudent');
    });
    Route::get('teacher-attendance', [AdminTeacherAttendanceController::class, 'index'])->middleware('can:attendance.teachers.view')->name('teacher-attendance.index');
    Route::post('teacher-attendance/update', [AdminTeacherAttendanceController::class, 'updateStatus'])->middleware('can:attendance.teachers.manage')->name('teacher-attendance.update');
    Route::get('teacher-attendance/export', [AdminTeacherAttendanceController::class, 'exportCsv'])->middleware(['can:attendance.teachers.view','can:attendance.export'])->name('teacher-attendance.export');

    Route::middleware('can:users.manage')->group(function () {
        Route::get('users', [AccessUserController::class, 'index'])->name('users.index');
        Route::post('users', [AccessUserController::class, 'store'])->name('users.store');
        Route::put('users/{user}', [AccessUserController::class, 'update'])->name('users.update');
        Route::patch('users/{user}/status', [AccessUserController::class, 'status'])->name('users.status');
    });
    Route::middleware('can:roles.manage')->group(function () {
        Route::get('roles', [AccessRoleController::class, 'index'])->name('roles.index');
        Route::post('roles', [AccessRoleController::class, 'store'])->name('roles.store');
        Route::put('roles/{accessRole}', [AccessRoleController::class, 'update'])->name('roles.update');
        Route::delete('roles/{accessRole}', [AccessRoleController::class, 'destroy'])->name('roles.destroy');
    });
});
require __DIR__.'/auth.php';
