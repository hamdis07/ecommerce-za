<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('livraisondetails', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('commandes_id')->nullable;
            $table->string('adresse');
            $table->string('ville');
            $table->string('code_postal');
            $table->string('telephone');
            $table->string('description');
            // Ajoutez d'autres colonnes au besoin
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('commandes_id')->references('id')->on('commandes')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('livraison_details');
    }
}
;
