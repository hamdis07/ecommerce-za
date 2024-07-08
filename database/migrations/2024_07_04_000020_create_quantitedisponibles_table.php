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
        Schema::create('quantite', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('produits_id');
            $table->unsignedBigInteger('tailles_id');
            $table->unsignedBigInteger('couleurs_id');
            $table->integer('quantite');
            $table->timestamps();

            $table->foreign('produits_id')->references('id')->on('Produits')->onDelete('cascade');
            $table->foreign('tailles_id')->references('id')->on('Tailles')->onDelete('cascade');
            $table->foreign('couleurs_id')->references('id')->on('Couleurs')->onDelete('cascade');

            $table->unsignedBigInteger('magasin_id');
            $table->foreign('magasin_id')->references('id')->on('magasins')->onDelete('cascade');

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
