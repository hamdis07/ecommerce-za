<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('produits', function (Blueprint $table) {
            $table->boolean('is_featured')->default(false)->after('nom_produit'); // Remplacez 'nom' par la colonne après laquelle vous voulez ajouter ces nouvelles colonnes
            $table->boolean('is_hidden')->default(false)->after('is_featured');
        });
    }

    /**
     * Annuler les migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('produits', function (Blueprint $table) {
            $table->dropColumn('is_featured');
            $table->dropColumn('is_hidden');
        });
    }
};
