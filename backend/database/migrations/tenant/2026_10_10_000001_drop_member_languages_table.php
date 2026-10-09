<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Les langues des médiatrices sont abandonnées : toutes sont francophones.
     */
    public function up(): void
    {
        Schema::dropIfExists('member_languages');
    }

    public function down(): void
    {
        Schema::create('member_languages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_profile_id')->constrained()->cascadeOnDelete();
            $table->string('language_code', 3);
            $table->unsignedTinyInteger('position')->default(0);

            $table->unique(['member_profile_id', 'language_code']);
            $table->index('language_code');
        });
    }
};
