<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VisaDemandeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'decision_visa' => 'required|in:Valide,Refuse',
            'commentaire' => 'nullable|string|max:1000',
            // Étapes mises à jour avec SG et Chef_Region séparés
            'etape' => 'nullable|in:Chef_Hierarchique,DAG,SG,Chef_Region'
        ];
    }
}
