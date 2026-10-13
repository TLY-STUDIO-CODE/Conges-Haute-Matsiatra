<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DemandeConge extends Model
{
    protected $table = 'demande_conges';
    protected $primaryKey = 'id_demande';

    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'date_demande',
        'type_periode',
        'date_debut',
        'date_fin',
        'nombre_jours_demandes',
        'motif',
        'statut_global',
        'im',
        'fichier_demande',
        'fichier_planning',
        'fichier_decision'
    ];

    protected $casts = [
        'fichier_demande' => 'array',
        'fichier_planning' => 'array',
        'fichier_decision' => 'array',
    ];

    // Permet d'inclure automatiquement le statut visuel dans les serialisations JSON/API si besoin
    protected $appends = ['statut_visuel'];

    public function personnel()
    {
        return $this->belongsTo(Personnel::class, 'im', 'im');
    }

    public function visas()
    {
        return $this->hasMany(VisaEtape::class, 'id_demande', 'id_demande');
    }

    public function dossierCourrier()
    {
        return $this->hasOne(DossierTraitementCourrier::class, 'id_demande', 'id_demande');
    }

    /**
     * Accesseur dynamique pour obtenir le statut exact selon les visas hiérarchiques.
     */
    public function getStatutVisuelAttribute()
    {
        $visas = $this->visas;
        $refuseVisa = $visas->firstWhere('decision_visa', 'Refuse');

        if ($refuseVisa) {
            $etapeNom = str_replace('_', ' ', $refuseVisa->etape);
            return "Refusé par {$etapeNom}";
        }

        $tousValides = $visas->isNotEmpty() && $visas->every(fn($v) => $v->decision_visa === 'Valide');

        if ($tousValides || in_array($this->statut_global, ['valide', 'valide_par_autorite', 'cloture_et_archive'])) {
            return "Validé";
        }

        return "En attente";
    }
}
