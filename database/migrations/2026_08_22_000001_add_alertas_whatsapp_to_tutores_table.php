<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tutores') && !Schema::hasColumn('tutores', 'alertas_whatsapp_activadas')) {
            Schema::table('tutores', function (Blueprint $table) {
                $table->boolean('alertas_whatsapp_activadas')->default(true)->after('alertas_correo_activadas');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tutores') && Schema::hasColumn('tutores', 'alertas_whatsapp_activadas')) {
            Schema::table('tutores', function (Blueprint $table) {
                $table->dropColumn('alertas_whatsapp_activadas');
            });
        }
    }
};
