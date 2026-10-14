<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ressources mises en favoris (marque-page), comme les médiatrices de l'annuaire : même mot,
     * même nom de table.
     */
    public function up(): void
    {
        Schema::rename('resource_saves', 'resource_favorites');
    }

    public function down(): void
    {
        Schema::rename('resource_favorites', 'resource_saves');
    }
};
