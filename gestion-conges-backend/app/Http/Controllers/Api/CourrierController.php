<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DossierTraitementCourrier;
use App\Models\DemandeConge;
use App\Models\Personnel;
use App\Models\Decision;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CourrierController extends Controller
{
    public function index()
    {
        try {
            $dossiers = DossierTraitementCourrier::with(['demande.personnel', 'demande.visas'])
                ->where('statut_dossier', 'en_attente')
                ->orWhere('statut_dossier', 'en_attente_courrier')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $dossiers
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id_traitement)
    {
        $traitement = DossierTraitementCourrier::with(['demande.personnel', 'demande.visas'])->findOrFail($id_traitement);

        return response()->json([
            'success' => true,
            'data' => $traitement
        ]);
    }

    public function traiterCourrier(Request $request, $id_traitement)
    {
        $request->validate([
            'reference_ampliation' => 'required|string|max:255',
            'date_verification_planning' => 'required|date',
        ]);

        $traitement = DossierTraitementCourrier::with('demande')->findOrFail($id_traitement);

        if ($traitement->statut_dossier === 'cloture') {
            return response()->json([
                'success' => false,
                'message' => 'Ce dossier a déjà été clôturé.'
            ], 400);
        }

        $demande = $traitement->demande;

        $traitement->update([
            'date_verification_planning' => $request->date_verification_planning,
            'reference_ampliation' => $request->reference_ampliation,
            'date_saisie_decision' => now(),
            'statut_dossier' => 'cloture'
        ]);

        if ($demande) {
            $demande->update([
                'statut_global' => 'cloture_et_archive'
            ]);

            $im = $demande->im;
            $personnel = Personnel::where('im', $im)->first();

            $decisionActive = Decision::where('im', $im)
                ->where('annee_debut', '<=', date('Y', strtotime($demande->date_debut)))
                ->where('annee_fin', '>=', date('Y', strtotime($demande->date_debut)))
                ->first()
                ?? Decision::where('im', $im)->orderBy('annee_fin', 'desc')->first();

            $totalDroits = $decisionActive ? $decisionActive->nombre_jours : 90;

            $joursPrisTotal = DemandeConge::where('im', $im)
                ->where('statut_global', '!=', 'rejete')
                ->sum('nombre_jours_demandes');

            $soldeRestantExact = max(0, $totalDroits - $joursPrisTotal); // <--- Correction du signe -

            Decision::firstOrCreate(
                ['reference_decision' => trim($request->reference_ampliation), 'im' => $im],
                [
                    'annee_debut' => $decisionActive->annee_debut ?? date('Y', strtotime($demande->date_debut)),
                    'annee_fin' => $decisionActive->annee_fin ?? date('Y', strtotime($demande->date_fin)),
                    'nombre_jours' => $totalDroits,
                    'imputation_budgetaire' => '60 601 6011',
                    'grade_emploi' => $personnel->grade ?? 'Encadreur',
                    'bureau_service' => 'Région Haute Matsiatra',
                    'fichier_decision' => $demande->fichier_decision ?? []
                ]
            );

            if ($personnel) {
                if (isset($personnel->solde_conge_actuel)) {
                    $personnel->solde_conge_actuel = $soldeRestantExact;
                    $personnel->save();
                } elseif (isset($personnel->solde_conge)) {
                    $personnel->solde_conge = $soldeRestantExact;
                    $personnel->save();
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Dossier clôturé, ampliation enregistrée et solde recalculé avec succès.',
            'data' => $traitement->load('demande.personnel')
        ]);
    }

    public function exportCsv(): StreamedResponse
    {
        $fileName = 'export_dossiers_courrier_' . date('Y-m-d_H-i-s') . '.csv';
        $dossiers = DossierTraitementCourrier::with(['demande.personnel'])->orderBy('created_at', 'desc')->get();

        $headers = [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function () use ($dossiers) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($file, ['ID Traitement', 'IM Agent', 'Nom & Prénom', 'Réf. Ampliation', 'Date Vérification', 'Statut Dossier', 'ID Demande'], ';');

            foreach ($dossiers as $dossier) {
                fputcsv($file, [
                    $dossier->id_traitement,
                    $dossier->demande->im ?? 'N/A',
                    trim(($dossier->demande->personnel->nom ?? '') . ' ' . ($dossier->demande->personnel->prenom ?? '')) ?: 'N/A',
                    $dossier->reference_ampliation ?? 'Non définie',
                    $dossier->date_verification_planning ?? 'N/A',
                    $dossier->statut_dossier,
                    $dossier->id_demande
                ], ';');
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
