<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Personnel extends Model
{
    use HasFactory;

    protected $table = 'personnels';
    protected $primaryKey = 'im';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'im',
        'nom',
        'prenom',
        'date_naissance',
        'categorie',
        'grade',
        'date_contrat',
        'solde_conge_actuel'
    ];

    public function decisions()
    {
        return $this->hasMany(Decision::class, 'im', 'im');
    }

    public function plannings()
    {
        return $this->hasMany(PlanningAnnuel::class, 'im', 'im');
    }

    public function demandes()
    {
        return $this->hasMany(DemandeConge::class, 'im', 'im');
    }
}
