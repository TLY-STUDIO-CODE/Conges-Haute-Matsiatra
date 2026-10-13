<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demande_conges', function (Blueprint $table) {
            $table->id('id_demande');
            $table->date('date_demande');
            $table->enum('type_periode', ['Fractionne_Jan_Juin', 'Complet_Juin_Dec']);
            $table->date('date_debut');
            $table->date('date_fin');
            $table->integer('nombre_jours_demandes');
            $table->text('motif')->nullable();

            // Aligné sur 'en_attente' par défaut pour correspondre au workflow hiérarchique
            $table->string('statut_global')->default('en_attente');

            // Chemins des pièces jointes (stockés sous forme de tableaux JSON)
            $table->text('fichier_demande')->nullable();
            $table->text('fichier_planning')->nullable();
            $table->text('fichier_decision')->nullable(); // Intégration automatique de la décision PDF

            $table->string('im');
            $table->foreign('im')->references('im')->on('personnels')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demande_conges');
    }
};
