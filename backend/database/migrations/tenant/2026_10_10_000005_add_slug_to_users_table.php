<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Prénom-nom dans l'URL de la fiche (/reseau/aminata-diallo) ; homonymes : aminata-diallo-2, -3…
     * Ensuite tenu à jour par le modèle User.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('last_name');
        });

        $taken = [];
        foreach (DB::table('users')->orderBy('id')->get(['id', 'first_name', 'last_name']) as $user) {
            $base = Str::slug("{$user->first_name} {$user->last_name}") ?: 'membre';
            $slug = $base;
            for ($suffix = 2; isset($taken[$slug]); $suffix++) {
                $slug = "{$base}-{$suffix}";
            }
            $taken[$slug] = true;

            DB::table('users')->where('id', $user->id)->update(['slug' => $slug]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('slug')->nullable(false)->change();
            $table->unique('slug');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
