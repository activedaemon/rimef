<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Résumé d'une ligne d'un événement, affiché sur l'accueil (événement à la une).
     */
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('summary', 140)->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('summary');
        });
    }
};
