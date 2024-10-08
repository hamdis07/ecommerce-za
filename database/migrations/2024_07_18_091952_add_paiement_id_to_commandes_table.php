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
        Schema::table('commandes', function (Blueprint $table) {
            $table->unsignedBigInteger('paiement_id')->nullable();
            $table->foreign('paiement_id')->references('id')->on('paiements');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('commandes', function (Blueprint $table) {
            $table->dropForeign(['paiement_id']);
            $table->dropColumn('paiement_id');
        });
    }
};
