<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fiche d'une ressource : nombre de pages, présentation et site de l'organisation, lien avec
     * l'événement associé ; rôle et note des médiatrices liées.
     */
    public function up(): void
    {
        Schema::table('resources', function (Blueprint $table) {
            $table->unsignedSmallInteger('pages')->nullable()->after('duration_minutes');
            $table->string('source_detail', 300)->nullable()->after('source');
            $table->string('source_url', 500)->nullable()->after('source_detail');
            // « Présenté au panel… » : ce qui relie la ressource à son événement
            $table->string('event_note', 300)->nullable()->after('event_id');
        });

        Schema::table('resource_member', function (Blueprint $table) {
            // App\Enums\ResourceMemberRole
            $table->string('role', 30)->default('contributor');
            $table->string('note', 300)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('resource_member', function (Blueprint $table) {
            $table->dropColumn(['role', 'note']);
        });

        Schema::table('resources', function (Blueprint $table) {
            $table->dropColumn(['pages', 'source_detail', 'source_url', 'event_note']);
        });
    }
};
