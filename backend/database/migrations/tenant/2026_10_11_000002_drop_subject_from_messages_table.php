<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * L'objet de la prise de contact disparaît : il ne servait qu'au premier message d'une
     * conversation, la suite se faisant en messages directs.
     */
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn('subject');
        });
    }

    /**
     * Colonne recréée vide : les objets supprimés ne sont pas restaurés.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->string('subject', 30)->nullable()->after('user_id');
        });
    }
};
