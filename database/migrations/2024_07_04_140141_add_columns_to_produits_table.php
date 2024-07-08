<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToProduitsTable extends Migration
{
    public function up()
    {
        Schema::table('produits', function (Blueprint $table) {
            // Ajouter les colonnes promo_id, souscategories_id, categorie_id, genre_id
            $table->unsignedBigInteger('promo_id')->nullable();
            $table->unsignedBigInteger('categorie_id')->nullable();

           // $table->unsignedBigInteger('souscategories_id')->nullable();
          //  $table->unsignedBigInteger('genre_id')->nullable();

            // Ajouter les clés étrangères si nécessaire
            $table->foreign('promo_id')->references('id')->on('promos')->onDelete('cascade');
            $table->foreign('categorie_id')->references('id')->on('categories')->onDelete('cascade');

           // $table->foreign('souscategories_id')->references('id')->on('souscategories')->onDelete('cascade');
           // $table->foreign('genre_id')->references('id')->on('genres')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('produits', function (Blueprint $table) {
            // Supprimer les clés étrangères d'abord si nécessaire
            $table->dropForeign(['promo_id']);
            $table->dropForeign(['souscategories_id']);
            $table->dropForeign(['categorie_id']);
            $table->dropForeign(['genre_id']);

            // Supprimer les colonnes ajoutées dans la méthode up
            $table->dropColumn('promo_id');
            $table->dropColumn('souscategories_id');
            $table->dropColumn('categorie_id');
            $table->dropColumn('genre_id');
        });
    }
}
