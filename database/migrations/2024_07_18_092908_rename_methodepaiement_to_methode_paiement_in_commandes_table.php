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
            $table->renameColumn('methodepaiement', 'methode_paiement');
        });
    }

    public function down()
    {
        Schema::table('commandes', function (Blueprint $table) {
            $table->renameColumn('methode_paiement', 'methodepaiement');
        });
    }
};
