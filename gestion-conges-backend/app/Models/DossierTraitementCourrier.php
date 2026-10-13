<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DossierTraitementCourrier extends Model
{
    protected $table = 'dossier_traitement_courriers';
    protected $primaryKey = 'id_traitement';

    protected $fillable = [
        'date_verification_planning',
        'reference_ampliation',
        'fichier_ampliation', // <--- AJOUT INDISPENSABLE ICI
        'date_saisie_decision',
        'statut_dossier',
        'id_demande'
    ];

    public function demande()
    {
        return $this->belongsTo(DemandeConge::class, 'id_demande', 'id_demande');
    }
}
