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
        Schema::table('message_envoyers', function (Blueprint $table) {
            $table->text('attachments')->nullable(); // Ajoutez cette ligne pour ajouter la colonne
        });
    }

    public function down()
    {
        Schema::table('message_envoyers', function (Blueprint $table) {
            $table->dropColumn('attachments'); // Supprimer la colonne en cas de rollback
        });
    }
};
