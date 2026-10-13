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
        Schema::create('ligne_plannings', function (Blueprint $table) {
            $table->id('id_ligne_plan');
            $table->string('mois');
            $table->integer('nombre_jours_prevus');
            $table->unsignedBigInteger('id_planning');
            $table->foreign('id_planning')->references('id_planning')->on('planning_annuels')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ligne_plannings');
    }
};
