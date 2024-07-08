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
        Schema::create('paiements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('commandes_id');
            $table->unsignedBigInteger('livraisondetails_id'); // Ajout de la clé étrangère de la livraison
            $table->string('methode_paiement', 50)->default('carte_bancaire');
            $table->string('numero_carte');
            $table->string('nom_detenteur_carte');
            $table->string('mois_validite');
            $table->string('annee_validite');
            $table->string('code_secret');
            $table->text('adresse_facturation');

            $table->decimal('prix_total', 8, 2);
            $table->timestamps();


            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->foreign('commandes_id')->references('id')->on('commandes')->onDelete('cascade');
            $table->foreign('livraisondetails_id')->references('id')->on('livraisondetails')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('paiemnts');
    }
};
