<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanningAnnuel extends Model
{
    protected $table = 'planning_annuels';
    protected $primaryKey = 'id_planning';

    protected $fillable = ['annee', 'date_soumission', 'im'];

    public function personnel()
    {
        return $this->belongsTo(Personnel::class, 'im', 'im');
    }

    public function lignes()
    {
        return $this->hasMany(LignePlanning::class, 'id_planning', 'id_planning');
    }
}
