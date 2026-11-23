<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pari_id')->nullable()->constrained('paris')->nullOnDelete();
            $table->string('type', 20);
            // Montant signé : positif = crédit, négatif = débit
            $table->integer('amount');
            $table->string('description');
            $table->timestamps();

            // Accélère le classement hebdomadaire (somme des gains par période)
            $table->index(['type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
