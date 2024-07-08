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
        Schema::create('Produits', function (Blueprint $table) {
            $table->id();
            $table->string('references');
            $table->string('nom_produit');
            $table->string('description');
            $table->decimal('prix_initial', 10, 2);

            $table->decimal('prix');

            $table->string('composition');
            $table->string('entretien');
            $table->string('mots_cles')->nullable();

            


            $table->string('image_url');



            $table->timestamps();


        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('produits');

        //
    }
};
