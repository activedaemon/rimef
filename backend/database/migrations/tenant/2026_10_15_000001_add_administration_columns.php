<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rubrique Administration : comptes créés par invitation, dernière connexion,
     * jetons d'invitation (7 jours, séparés des jetons de réinitialisation) et
     * participations des comptes supprimés, gardées en nombre seulement.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Pas de mot de passe tant que l'invitée n'a pas activé son compte
            $table->string('password')->nullable()->change();
            $table->boolean('invitation_pending')->default(false)->after('is_active');
            $table->timestamp('invited_at')->nullable()->after('invitation_pending');
            $table->foreignId('invited_by_id')->nullable()->after('invited_at')->constrained('users')->nullOnDelete();
            $table->timestamp('last_login_at')->nullable()->after('invited_by_id');
        });

        Schema::create('invitation_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::table('events', function (Blueprint $table) {
            $table->unsignedInteger('former_attendees_count')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('former_attendees_count');
        });

        Schema::dropIfExists('invitation_tokens');

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('invited_by_id');
            $table->dropColumn(['invitation_pending', 'invited_at', 'last_login_at']);
        });
    }
};
