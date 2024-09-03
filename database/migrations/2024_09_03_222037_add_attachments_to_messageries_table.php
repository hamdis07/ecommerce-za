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
        Schema::table('messageries', function (Blueprint $table) {
            $table->json('attachments')->nullable(); // Ajoute une colonne JSON pour les fichiers joints
        });
    }

    public function down()
    {
        Schema::table('messageries', function (Blueprint $table) {
            $table->dropColumn('attachments');
        });
    }

};
