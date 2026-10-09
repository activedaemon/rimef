<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Annuaire du réseau : profil de médiatrice (un par compte), expertises et langues.
     * Pays, régions et langues sont des codes ; leurs libellés sont dans config/directory.php.
     */
    public function up(): void
    {
        Schema::create('member_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->char('country_code', 2)->nullable()->index();
            $table->string('organization_type', 50)->nullable()->index();
            $table->boolean('is_available')->default(false);
            $table->timestamps();
        });

        Schema::create('expertises', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->timestamps();
        });

        Schema::create('expertise_member_profile', function (Blueprint $table) {
            $table->foreignId('member_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('expertise_id')->constrained()->cascadeOnDelete();
            // Ordre choisi par la médiatrice : les premières sont affichées sur sa carte.
            $table->unsignedTinyInteger('position')->default(0);

            $table->primary(['member_profile_id', 'expertise_id']);
            $table->index('expertise_id');
        });

        Schema::create('member_languages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_profile_id')->constrained()->cascadeOnDelete();
            $table->string('language_code', 3);
            $table->unsignedTinyInteger('position')->default(0);

            $table->unique(['member_profile_id', 'language_code']);
            $table->index('language_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_languages');
        Schema::dropIfExists('expertise_member_profile');
        Schema::dropIfExists('expertises');
        Schema::dropIfExists('member_profiles');
    }
};
