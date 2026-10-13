<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jours de présence d'une participante à un événement de plusieurs jours (« 2026-11-10 »…) ;
     * null = tous les jours. attendance_note devient sa note facultative.
     */
    public function up(): void
    {
        Schema::table('event_user', function (Blueprint $table) {
            $table->json('attendance_days')->nullable()->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('event_user', function (Blueprint $table) {
            $table->dropColumn('attendance_days');
        });
    }
};
