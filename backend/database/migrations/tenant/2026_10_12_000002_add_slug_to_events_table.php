<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Adresse lisible de la fiche d'un événement : /agenda/paris-peace-forum-2026 (titre et année,
     * suffixe -2, -3… pour un même titre la même année). Les événements existants reçoivent la leur.
     */
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('title');
        });

        $taken = [];
        foreach (DB::table('events')->orderBy('id')->get(['id', 'title', 'starts_at']) as $event) {
            $year = Carbon::parse($event->starts_at)->year;
            $base = Str::slug("{$event->title} {$year}") ?: 'evenement';
            $slug = $base;
            for ($suffix = 2; isset($taken[$slug]); $suffix++) {
                $slug = "{$base}-{$suffix}";
            }
            $taken[$slug] = true;

            DB::table('events')->where('id', $event->id)->update(['slug' => $slug]);
        }

        Schema::table('events', function (Blueprint $table) {
            $table->string('slug')->nullable(false)->change();
            $table->unique('slug');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
