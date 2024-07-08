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
        Schema::table('produits', function (Blueprint $table) {
            $table->unsignedBigInteger('souscategories_id')->nullable();
            $table->unsignedBigInteger('genre_id')->nullable();
            $table->foreign('souscategories_id')->references('id')->on('souscategories');
            $table->foreign('genre_id')->references('id')->on('genre');


        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('produits', function (Blueprint $table) {
            //
        });
    }
};
