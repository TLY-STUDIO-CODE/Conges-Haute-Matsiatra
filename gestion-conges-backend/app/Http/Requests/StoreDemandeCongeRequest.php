<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDemandeCongeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'im' => 'required|exists:personnels,im',
            'type_periode' => 'required|in:Congé Annuel,Permission Exceptionnelle,Congé de Maladie,Fractionne_Jan_Juin,Complet_Juin_Dec',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after_or_equal:date_debut',
            'nombre_jours_demandes' => 'required|integer|min:1|max:60',
            'motif' => 'nullable|string'
        ];
    }
}
