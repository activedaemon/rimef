<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Avis des médiatrices sur les ressources (bandeau « Lues et partagées par les médiatrices ») :
     * une phrase par médiatrice et par ressource.
     */
    public function up(): void
    {
        Schema::create('resource_recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resource_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('quote', 200);
            $table->timestamps();

            $table->unique(['resource_id', 'user_id']);
            $table->index('user_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resource_recommendations');
    }
};
