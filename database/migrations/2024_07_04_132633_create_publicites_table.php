<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('publicites', function (Blueprint $table) {
            $table->id();
            $table->string('nom');

            $table->text('detail')->nullable();
            $table->dateTime('date_lancement');
            $table->dateTime('date_fin');
            $table->decimal('montant_paye', 8, 2);
            $table->string('image')->nullable(); // Pour le chemin de l'image
            $table->string('video')->nullable(); // Pour le chemin de la vidéo
            $table->string('affiche')->nullable(); // Pour le chemin de l'affiche
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
