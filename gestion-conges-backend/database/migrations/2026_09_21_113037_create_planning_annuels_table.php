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
        Schema::create('planning_annuels', function (Blueprint $table) {
            $table->id('id_planning');
            $table->year('annee');
            $table->date('date_soumission');
            $table->string('im');
            $table->foreign('im')->references('im')->on('personnels')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('planning_annuels');
    }
};
