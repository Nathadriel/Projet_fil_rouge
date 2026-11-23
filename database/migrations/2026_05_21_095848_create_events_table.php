<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            // Créateur de l'événement (null si son compte est supprimé : l'événement reste)
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title', 150);
            $table->text('description');
            $table->string('category', 30);
            $table->dateTime('closes_at');
            $table->string('status', 20)->default('pending')->index();
            // Cotes fixées par l'administrateur lors de la validation
            $table->decimal('odds_yes', 5, 2)->default(2.00);
            $table->decimal('odds_no', 5, 2)->default(2.00);
            $table->string('result', 3)->nullable();
            $table->dateTime('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
