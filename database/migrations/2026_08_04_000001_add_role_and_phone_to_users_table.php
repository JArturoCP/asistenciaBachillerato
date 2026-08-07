<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('name')->nullable()->change();
            $table->string('email')->nullable()->change();
            $table->string('password')->nullable()->change();

            $table->string('nombre')->nullable()->after('id');
            $table->string('apellido_paterno')->nullable()->after('nombre');
            $table->string('apellido_materno')->nullable()->after('apellido_paterno');
            $table->string('role', 30)->default('admin')->after('email');
            $table->string('phone')->nullable()->after('role');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn(['nombre', 'apellido_paterno', 'apellido_materno', 'role', 'phone']);
        });
    }
};
