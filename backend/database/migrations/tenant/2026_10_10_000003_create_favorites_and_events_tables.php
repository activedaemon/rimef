<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Favoris de l'annuaire (marque-page d'une médiatrice sur une autre) et base minimale des
     * événements (futur Agenda) : « Prochainement : … » sur les cartes de l'annuaire.
     */
    public function up(): void
    {
        Schema::create('favorites', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['user_id', 'member_id']);
            $table->index('member_id');
        });

        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('title', 150);
            // UTC (MySQL), converti dans le fuseau de l'utilisatrice côté application
            $table->dateTime('starts_at')->index();
            $table->string('place', 150)->nullable();
            $table->timestamps();
        });

        Schema::create('event_user', function (Blueprint $table) {
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->primary(['event_id', 'user_id']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_user');
        Schema::dropIfExists('events');
        Schema::dropIfExists('favorites');
    }
};
