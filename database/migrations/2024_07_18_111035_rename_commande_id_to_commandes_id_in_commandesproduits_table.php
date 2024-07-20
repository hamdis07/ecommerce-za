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
        Schema::table('commandesproduits', function (Blueprint $table) {
            $table->renameColumn('commande_id', 'commandes_id');
        });
    }

    public function down()
    {
        Schema::table('commandesproduits', function (Blueprint $table) {
            $table->renameColumn('commandes_id', 'commande_id');
        });
    }
};
