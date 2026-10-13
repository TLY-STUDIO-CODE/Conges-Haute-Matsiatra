<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Decision;
use App\Models\Personnel;
use App\Models\DemandeConge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class DecisionController extends Controller
{
    public function index()
    {
        $decisions = Decision::with('personnel')
            ->orderBy('created_at', 'desc')
            ->get();

        $decisions->transform(function ($decision) {
            if (is_string($decision->fichier_decision)) {
                $decoded = json_decode($decision->fichier_decision, true);
                $decision->fichier_decision = is_array($decoded) ? $decoded : [$decision->fichier_decision];
            }
            return $decision;
        });

        return response()->json([
            'success' => true,
            'data' => $decisions
        ]);
    }

    /**
     * Vérification de la décision active et du solde transparent de l'agent
     */
    public function verifierDecisionAgent(Request $request, $im)
    {
        $anneeCourante = (int) date('Y');

        $personnel = Personnel::where('im', $im)->first();
        if (!$personnel) {
            return response()->json([
                'success' => false,
                'message' => 'Agent introuvable.'
            ], 404);
        }

        $anneeContrat = $personnel->date_contrat ? (int) date('Y', strtotime($personnel->date_contrat)) : $anneeCourante;

        // 1. Recherche de la décision active couvrant l'année courante
        $decision = Decision::where('im', $im)
            ->where('annee_debut', '<=', $anneeCourante)
            ->where('annee_fin', '>=', $anneeCourante)
            ->first() ?? Decision::where('im', $im)->orderBy('annee_fin', 'desc')->first();

        if (!$decision) {
            return response()->json([
                'success' => false,
                'message' => 'Aucune décision officielle active n’a été trouvée ou générée pour cet agent pour l’année ' . $anneeCourante . '.'
            ], 404);
        }

        // 2. Calcul transparent exact basé sur les droits de la décision moins tous les jours pris
        $totalDroits = $decision->nombre_jours ?? 90;
        $joursPrisTotal = DemandeConge::where('im', $im)
            ->where('statut_global', '!=', 'rejete')
            ->sum('nombre_jours_demandes');

        $soldeRestant = max(0, $totalDroits - $joursPrisTotal);

        if ($soldeRestant <= 0) {
            return response()->json([
                'success' => false,
                'message' => "Solde insuffisant : Le solde disponible est épuisé pour cet exercice.",
                'decision' => $decision,
                'solde_restant' => 0
            ], 422);
        }

        return response()->json([
            'success' => true,
            'decision' => $decision,
            'solde_restant' => $soldeRestant,
            'message' => "Décision active trouvée ({$decision->reference_decision}). Solde global disponible : {$soldeRestant} jour(s)."
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'im' => 'required|string|exists:personnels,im',
            'reference_decision' => 'required|string|max:255',
            'imputation_budgetaire' => 'nullable|string|max:100',
            'grade_emploi' => 'nullable|string|max:100',
            'bureau_service' => 'nullable|string|max:255',
        ]);

        $personnel = Personnel::where('im', $validated['im'])->first();
        $anneeCourante = (int) date('Y');
        $anneeContrat = $personnel->date_contrat ? (int) date('Y', strtotime($personnel->date_contrat)) : $anneeCourante;

        // Période sur 3 ans
        $anneeDebut = max($anneeContrat, $anneeCourante - 2);
        $anneeFin = $anneeCourante;

        $nombreAnnees = ($anneeFin - $anneeDebut) + 1;
        $nombreJoursTotal = max(30, $nombreAnnees * 30);

        // 1. Génération du PDF avec la date dynamique de signature
        $pdfData = [
            'reference_decision' => trim($validated['reference_decision']),
            'im' => $validated['im'],
            'nom_prenom' => strtoupper($personnel->nom . ' ' . ($personnel->prenom ?? '')),
            'annee_debut' => $anneeDebut,
            'annee_fin' => $anneeFin,
            'nombre_jours' => $nombreJoursTotal,
            'imputation_budgetaire' => $validated['imputation_budgetaire'] ?? '60 601 6011',
            'grade_emploi' => $validated['grade_emploi'] ?? ($personnel->grade ?? 'Encadreur'),
            'bureau_service' => $validated['bureau_service'] ?? 'Région Haute Matsiatra',
            'date_generation' => now()->format('d/m/Y'),
            'date_decision' => now()->format('d/m/Y') // <--- Ajout de la date dynamique pour le pied de page
        ];

        $pdf = Pdf::loadView('pdf.decision', $pdfData);
        $fileName = 'decision_' . time() . '_' . $validated['im'] . '.pdf';
        $filePath = 'conges/decisions/' . $fileName;

        Storage::disk('public')->put($filePath, $pdf->output());

        // 2. Enregistrement en base
        $decision = Decision::create([
            'im' => $validated['im'],
            'annee_debut' => $anneeDebut,
            'annee_fin' => $anneeFin,
            'reference_decision' => trim($validated['reference_decision']),
            'nombre_jours' => $nombreJoursTotal,
            'imputation_budgetaire' => $validated['imputation_budgetaire'] ?? '60 601 6011',
            'grade_emploi' => $validated['grade_emploi'] ?? $personnel->grade ?? null,
            'bureau_service' => $validated['bureau_service'] ?? 'Région Haute Matsiatra',
            'fichier_decision' => [$filePath],
        ]);

        return response()->json([
            'success' => true,
            'message' => "Décision générée avec succès pour la période {$anneeDebut}-{$anneeFin} ({$nombreJoursTotal} jours attribués).",
            'data' => $decision->load('personnel')
        ], 201);
    }
}
