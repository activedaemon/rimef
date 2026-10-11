<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Conversation supprimée « pour moi » : les messages jusqu'à ce dernier message me sont
     * masqués (l'autre participante les garde, tant qu'elle ne la supprime pas aussi).
     */
    public function up(): void
    {
        Schema::table('conversation_user', function (Blueprint $table) {
            $table->unsignedBigInteger('cleared_message_id')->nullable()->after('emailed_at');
        });
    }

    public function down(): void
    {
        Schema::table('conversation_user', function (Blueprint $table) {
            $table->dropColumn('cleared_message_id');
        });
    }
};
