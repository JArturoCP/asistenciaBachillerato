<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('access_roles', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 30)->unique();
            $table->string('name', 100);
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });
        Schema::create('access_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('code', 80)->unique();
            $table->string('group_name', 80);
            $table->string('name', 160);
        });
        Schema::create('access_permission_role', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained('access_roles')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained('access_permissions')->cascadeOnDelete();
            $table->primary(['role_id','permission_id']);
        });
        $now = now();
        foreach (config('access.permissions') as $code => [$group, $name]) {
            DB::table('access_permissions')->insert(['code'=>$code, 'group_name'=>$group, 'name'=>$name]);
        }
        $permissions = DB::table('access_permissions')->pluck('id','code');
        foreach (array_merge(config('access.system_roles'), config('access.starter_roles')) as $slug => [$name, $codes]) {
            $id = DB::table('access_roles')->insertGetId([
                'slug'=>$slug, 'name'=>$name,
                'is_system'=>array_key_exists($slug, config('access.system_roles')),
                'created_at'=>$now, 'updated_at'=>$now,
            ]);
            foreach (($codes === ['*'] ? array_keys(config('access.permissions')) : ($codes === ['*except_roles'] ? array_values(array_diff(array_keys(config('access.permissions')), ['roles.manage'])) : $codes)) as $code) {
                DB::table('access_permission_role')->insert(['role_id'=>$id, 'permission_id'=>$permissions[$code]]);
            }
        }
    }
    public function down(): void
    {
        Schema::dropIfExists('access_permission_role');
        Schema::dropIfExists('access_permissions');
        Schema::dropIfExists('access_roles');
    }
};
