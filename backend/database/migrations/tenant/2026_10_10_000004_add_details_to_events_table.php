<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Détails affichés par l'accueil : fin (événements sur plusieurs jours), description, et
     * événement « à la une » (choisi par l'équipe, distinct des simples rendez-vous).
     */
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dateTime('ends_at')->nullable()->after('starts_at');
            $table->text('description')->nullable()->after('place');
            $table->boolean('is_featured')->default(false)->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['ends_at', 'description', 'is_featured']);
        });
    }
};
