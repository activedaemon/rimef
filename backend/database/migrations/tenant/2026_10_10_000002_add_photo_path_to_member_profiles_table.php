<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Photo de la médiatrice : chemin dans le disque « local » du tenant (fichiers privés,
     * servis par GET /api/members/{user}/photo aux seuls comptes connectés).
     */
    public function up(): void
    {
        Schema::table('member_profiles', function (Blueprint $table) {
            $table->string('photo_path')->nullable()->after('is_available');
        });
    }

    public function down(): void
    {
        Schema::table('member_profiles', function (Blueprint $table) {
            $table->dropColumn('photo_path');
        });
    }
};
