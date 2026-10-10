<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Messagerie interne : une conversation par paire de membres.
     */
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            // « plus petit id-plus grand id » : une seule conversation par paire, même en cas d'envois simultanés
            $table->string('pair_key', 50)->unique();
            $table->timestamp('last_message_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('conversation_user', function (Blueprint $table) {
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // Dernier message lu : les messages reçus après sont « non lus »
            $table->unsignedBigInteger('last_read_message_id')->nullable();
            // Email déjà envoyé pour des messages non lus : pas d'autre email avant la lecture
            $table->timestamp('emailed_at')->nullable();

            $table->primary(['conversation_id', 'user_id']);
            $table->index('user_id');
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            // Expéditrice ; le message reste si son compte est supprimé
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            // Objet de la prise de contact (premier message envoyé depuis une fiche)
            $table->string('subject', 30)->nullable();
            $table->text('body');
            $table->timestamps();

            $table->index(['conversation_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversation_user');
        Schema::dropIfExists('conversations');
    }
};
