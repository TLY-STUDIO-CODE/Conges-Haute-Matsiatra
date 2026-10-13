<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('decisions', function (Blueprint $table) {
            $table->id('id_decision');
            $table->year('annee_debut');
            $table->year('annee_fin');
            $table->string('reference_decision');
            $table->integer('nombre_jours')->default(30); // Ex: 90 jours (30*3)
            $table->string('imputation_budgetaire')->nullable(); // Ex: 60 601 6011
            $table->string('grade_emploi')->nullable();
            $table->string('bureau_service')->nullable();
            $table->string('fichier_decision')->nullable(); // Fichier scanné de la décision
            $table->string('im');
            $table->foreign('im')->references('im')->on('personnels')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('decisions');
    }
};
