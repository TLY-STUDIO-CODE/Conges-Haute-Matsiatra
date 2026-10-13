<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisaEtape extends Model
{
    protected $table = 'visa_etapes';
    protected $primaryKey = 'id_visa';

    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = ['etape', 'decision_visa', 'date_visa', 'commentaire', 'id_demande'];

    protected $casts = [
        'date_visa' => 'datetime',
    ];

    public function demande()
    {
        return $this->belongsTo(DemandeConge::class, 'id_demande', 'id_demande');
    }
}
