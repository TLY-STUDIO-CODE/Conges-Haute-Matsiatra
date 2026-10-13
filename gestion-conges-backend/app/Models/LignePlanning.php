<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LignePlanning extends Model
{
    protected $table = 'ligne_plannings';
    protected $primaryKey = 'id_ligne_plan';

    protected $fillable = ['mois', 'nombre_jours_prevus', 'id_planning'];

    public function planning()
    {
        return $this->belongsTo(PlanningAnnuel::class, 'id_planning', 'id_planning');
    }
}
