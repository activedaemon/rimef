<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fiche d'un événement : photo, salle, organisateur, langues et liens utiles ; rôle et présence
     * de chaque médiatrice inscrite ; programme en créneaux.
     */
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            // Chemin dans le stockage privé du tenant (events/<id>.webp)
            $table->string('photo_path')->nullable()->after('description');
            $table->string('venue', 150)->nullable()->after('city');
            $table->string('organizer_name', 150)->nullable()->after('is_featured');
            $table->string('organizer_detail', 150)->nullable()->after('organizer_name');
            $table->string('languages', 150)->nullable()->after('organizer_detail');
            // Liste de { label, url }
            $table->json('links')->nullable()->after('languages');
        });

        Schema::table('event_user', function (Blueprint $table) {
            $table->string('role', 20)->default('participant');
            $table->string('attendance_note', 150)->nullable();
        });

        Schema::create('event_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            // UTC (MySQL), converti dans le fuseau de l'utilisatrice côté application
            $table->dateTime('starts_at');
            $table->string('title', 200);
            $table->string('detail', 200)->nullable();
            $table->foreignId('speaker_id')->nullable()->constrained('users')->nullOnDelete();
            // Créneau propre au réseau (intervention ou rencontre RIMeF), mis en avant
            $table->boolean('is_network')->default(false);
            $table->timestamps();

            $table->index(['event_id', 'starts_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_sessions');

        Schema::table('event_user', function (Blueprint $table) {
            $table->dropColumn(['role', 'attendance_note']);
        });

        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['photo_path', 'venue', 'organizer_name', 'organizer_detail', 'languages', 'links']);
        });
    }
};
