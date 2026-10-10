<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fiche de la médiatrice : en-tête (ville, fonction, citation), À propos, repères
     * (années d'expérience calculées depuis experience_since, publics accompagnés)
     * et zones d'intervention (pays).
     */
    public function up(): void
    {
        Schema::table('member_profiles', function (Blueprint $table) {
            $table->string('city', 100)->nullable()->after('country_code');
            $table->string('job_title', 150)->nullable()->after('organization_type');
            $table->string('tagline', 200)->nullable()->after('job_title');
            $table->text('bio')->nullable()->after('tagline');
            $table->unsignedSmallInteger('experience_since')->nullable()->after('bio');
            $table->string('audiences', 200)->nullable()->after('experience_since');
        });

        Schema::create('member_zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_profile_id')->constrained()->cascadeOnDelete();
            $table->char('country_code', 2);
            // Ordre choisi par la médiatrice
            $table->unsignedTinyInteger('position')->default(0);

            $table->unique(['member_profile_id', 'country_code']);
            $table->index('country_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_zones');

        Schema::table('member_profiles', function (Blueprint $table) {
            $table->dropColumn(['city', 'job_title', 'tagline', 'bio', 'experience_since', 'audiences']);
        });
    }
};
