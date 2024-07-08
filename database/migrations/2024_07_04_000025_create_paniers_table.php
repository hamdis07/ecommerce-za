<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up()
    {
        Schema::create('paniers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

   // public function up() :void
   // {
    //    Schema::create('paniers', function (Blueprint $table) {
     ////       $table->id();
     //       $table->unsignedBigInteger('user_id')->nullable();
      //      $table->unsignedBigInteger('produit_id')->nullable();
      //      $table->integer('quantite')->required; // Ajoutez une colonne pour stocker la quantité du produit dans le panier
        //  $table->string('taille')->required;
          //  $table->string('couleur')->required;
          //  $table->decimal('prix');

          //  $table->timestamps();

           // $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
           // $table->foreign('produit_id')->references('id')->on('produits')->onDelete('cascade');

       // });
   // }


    public function down()
    {
        Schema::dropIfExists('paniers');
    }}
;

