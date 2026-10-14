<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Page « Modifier mon profil » : nom libre de l'organisation (le type reste celui du filtre de l'annuaire)
     * et liens affichés sur la fiche (LinkedIn, site personnel, organisation, autre lien).
     */
    public function up(): void
    {
        Schema::table('member_profiles', function (Blueprint $table) {
            $table->string('organization_name', 150)->nullable()->after('organization_type');
            $table->json('links')->nullable()->after('audiences');
        });
    }

    public function down(): void
    {
        Schema::table('member_profiles', function (Blueprint $table) {
            $table->dropColumn(['organization_name', 'links']);
        });
    }
};
