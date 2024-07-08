<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('Souscategories', function (Blueprint $table) {
            $table->id();
            $table->string('nom');

            $table->unsignedBigInteger('categorie_id');

            $table->timestamps();

            // Clé étrangère pour lier à la table des catégories
            $table->foreign('categorie_id')->references('id')->on('categories')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sous_categories');
    }
}
;
