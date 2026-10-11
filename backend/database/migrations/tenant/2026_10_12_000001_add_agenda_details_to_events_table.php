<?php

use App\Support\DirectoryCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agenda : type, lieu structuré (ville, pays → région, ou en ligne) et thématiques
     * (vocabulaire des expertises). Le lieu libre « Ville, Pays » est converti puis supprimé.
     */
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('type', 30)->nullable()->after('title');
            $table->string('city', 100)->nullable()->after('place');
            $table->char('country_code', 2)->nullable()->after('city')->index();
            $table->boolean('is_online')->default(false)->after('country_code');
        });

        $codes = array_flip(DirectoryCatalog::countryNames());
        foreach (DB::table('events')->whereNotNull('place')->get(['id', 'place']) as $event) {
            [$city, $country] = array_pad(array_map('trim', explode(',', $event->place, 2)), 2, null);
            DB::table('events')->where('id', $event->id)->update(
                $event->place === 'En ligne'
                    ? ['is_online' => true]
                    : ['city' => $city, 'country_code' => $codes[$country] ?? null],
            );
        }

        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('place');
        });

        Schema::create('event_expertise', function (Blueprint $table) {
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('expertise_id')->constrained()->cascadeOnDelete();

            $table->primary(['event_id', 'expertise_id']);
            $table->index('expertise_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_expertise');

        Schema::table('events', function (Blueprint $table) {
            $table->string('place', 150)->nullable()->after('starts_at');
        });

        foreach (DB::table('events')->get(['id', 'city', 'country_code', 'is_online']) as $event) {
            $place = $event->is_online
                ? 'En ligne'
                : implode(', ', array_filter([$event->city, DirectoryCatalog::countryName($event->country_code)]));
            DB::table('events')->where('id', $event->id)->update(['place' => $place ?: null]);
        }

        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex(['country_code']);
            $table->dropColumn(['type', 'city', 'country_code', 'is_online']);
        });
    }
};
