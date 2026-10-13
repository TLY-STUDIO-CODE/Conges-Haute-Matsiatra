<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Decision extends Model
{
    protected $table = 'decisions';
    protected $primaryKey = 'id_decision';

    protected $fillable = [
        'annee_debut',
        'annee_fin',
        'reference_decision',
        'nombre_jours',
        'imputation_budgetaire',
        'grade_emploi',
        'bureau_service',
        'fichier_decision',
        'im'
    ];

    protected $casts = [
        'fichier_decision' => 'array',
    ];

    public function personnel()
    {
        return $this->belongsTo(Personnel::class, 'im', 'im');
    }
}
