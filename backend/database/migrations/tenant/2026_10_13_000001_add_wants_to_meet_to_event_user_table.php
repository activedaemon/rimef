<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * « Disponible pour se retrouver » : la participante propose aux autres médiatrices présentes
     * de se retrouver sur place (fiche d'un événement).
     */
    public function up(): void
    {
        Schema::table('event_user', function (Blueprint $table) {
            $table->boolean('wants_to_meet')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('event_user', function (Blueprint $table) {
            $table->dropColumn('wants_to_meet');
        });
    }
};
