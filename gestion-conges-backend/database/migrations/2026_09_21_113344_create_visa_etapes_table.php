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
        Schema::create('visa_etapes', function (Blueprint $table) {
            $table->id('id_visa');

            // On utilise string/text pour éviter les contraintes strictes bloquantes d'enum
            $table->string('etape');

            // Vous pouvez garder decision_visa en enum ou string selon votre préférence
            $table->enum('decision_visa', ['Valide', 'Refuse', 'En_Attente'])->default('En_Attente');

            $table->timestamp('date_visa')->nullable(); // Changé en timestamp pour correspondre à votre contrôleur ($visaActuel->update)
            $table->text('commentaire')->nullable();

            $table->unsignedBigInteger('id_demande');
            $table->foreign('id_demande')->references('id_demande')->on('demande_conges')->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visa_etapes');
    }
};
