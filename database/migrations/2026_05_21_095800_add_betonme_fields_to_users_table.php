<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Monnaie fictive : entier pour éviter les erreurs d'arrondi des flottants
            $table->unsignedInteger('betcoins')->default(100)->after('password');
            $table->string('role', 20)->default('user')->after('betcoins');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['betcoins', 'role']);
        });
    }
};
