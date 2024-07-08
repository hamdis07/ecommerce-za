<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up()
    {
        Schema::create('paniersproduits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produit_id')->constrained('produits')->onDelete('cascade');

            $table->foreignId('panier_id')->constrained('paniers')->onDelete('cascade');
            $table->integer('quantite')->default(1);
            $table->string('taille');
            $table->string('couleur');
            $table->decimal('prix_total', 10, 2)->nullable();

            $table->timestamps();
        });
    }
    /**
     * Run the migrations.
     */
   // public function up(): void
  //  {




      //  Schema::create('paniers_produits', function (Blueprint $table) {
      //      $table->id();
      //      $table->unsignedBigInteger('user_id')->nullable();
       //     $table->unsignedBigInteger('panier_id');
       //     $table->unsignedBigInteger('produit_id')->nullable();
        //    $table->string('taille');
        //    $table->string('couleur');
       //     $table->integer('quantite');
       //     $table->decimal('prix', 8, 2);
       //     $table->timestamps();

       //     $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

       //     $table->foreign('panier_id')->references('id')->on('paniers')->onDelete('cascade');
        //    $table->foreign('produit_id')->references('id')->on('produits')->onDelete('cascade');
       // });

   // }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
