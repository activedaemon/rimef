<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Prénom et nom séparés (tri et recherche dans l'annuaire) et compte désactivable.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name', 100)->after('id');
            $table->string('last_name', 100)->after('first_name');
            $table->boolean('is_active')->default(true)->after('password');

            $table->index(['last_name', 'first_name']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('name');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('name')->after('id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['last_name', 'first_name']);
            $table->dropColumn(['first_name', 'last_name', 'is_active']);
        });
    }
};
