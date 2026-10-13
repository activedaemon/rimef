<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bibliothèque de ressources (écran Ressources) : rapports, analyses, podcasts… avec leurs
     * thématiques, les médiatrices du réseau qui y sont liées et les signets des membres.
     */
    public function up(): void
    {
        Schema::create('resources', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 190)->unique();
            $table->string('type', 30)->index();
            $table->string('title', 200);
            $table->text('summary')->nullable();
            // Organisation éditrice, ou autrice médiatrice du réseau
            $table->string('source', 150)->nullable();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('published_on')->index();
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            // Région du monde (App\Enums\Region) ; null = portée mondiale
            $table->string('region', 30)->nullable()->index();
            // Codes ISO 639-1 (« fr », « en »)
            $table->json('languages');
            $table->string('url', 500)->nullable();
            $table->foreignId('event_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('expertise_resource', function (Blueprint $table) {
            $table->foreignId('resource_id')->constrained()->cascadeOnDelete();
            $table->foreignId('expertise_id')->constrained()->cascadeOnDelete();

            $table->primary(['resource_id', 'expertise_id']);
            $table->index('expertise_id');
        });

        // Médiatrices liées : autrices, contributrices ou recommandations
        Schema::create('resource_member', function (Blueprint $table) {
            $table->foreignId('resource_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->primary(['resource_id', 'user_id']);
            $table->index('user_id');
        });

        Schema::create('resource_saves', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('resource_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['user_id', 'resource_id']);
            $table->index('resource_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resource_saves');
        Schema::dropIfExists('resource_member');
        Schema::dropIfExists('expertise_resource');
        Schema::dropIfExists('resources');
    }
};
