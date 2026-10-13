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
        Schema::create('personnels', function (Blueprint $table) {
            $table->string('im')->primary(); // IM comme Clé Primaire
            $table->string('nom');
            $table->string('prenom');
            $table->date('date_naissance');
            $table->string('categorie');
            $table->string('grade');
            $table->date('date_contrat');
            $table->integer('solde_conge_actuel')->default(30);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personnels');
    }
};
