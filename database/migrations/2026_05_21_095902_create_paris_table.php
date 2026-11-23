<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paris', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('choice', 3);
            $table->unsignedInteger('amount');
            // Cote figée au moment du pari (elle ne change pas si l'admin la modifie ensuite)
            $table->decimal('odds', 5, 2);
            $table->string('status', 20)->default('pending');
            $table->unsignedInteger('payout')->default(0);
            $table->timestamps();

            // Règle de gestion : un seul pari par utilisateur et par événement
            $table->unique(['user_id', 'event_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paris');
    }
};
