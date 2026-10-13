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
        Schema::create('dossier_traitement_courriers', function (Blueprint $table) {
            $table->id('id_traitement');
            $table->date('date_verification_planning')->nullable();
            $table->string('reference_ampliation')->nullable();
            $table->text('fichier_ampliation')->nullable(); // <--- AJOUT POUR ENREGISTRER L'AMPLIATION
            $table->date('date_saisie_decision')->nullable();
            $table->string('statut_dossier')->default('en_attente');
            $table->unsignedBigInteger('id_demande')->unique();
            $table->foreign('id_demande')->references('id_demande')->on('demande_conges')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dossier_traitement_courriers');
    }
};
