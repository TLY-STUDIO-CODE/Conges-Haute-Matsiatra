<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VisaEtape;
use App\Models\DemandeConge;
use App\Models\DossierTraitementCourrier;
use App\Http\Requests\VisaDemandeRequest;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WorkflowController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = VisaEtape::with([
            'demande.personnel',
            'demande.visas',
            'demande.dossierCourrier'
        ])
            ->where('decision_visa', 'En_Attente');

        // Si ce n'est pas l'admin global et qu'il a un grade spécifique,
        // il ne voit que les visas correspondant à son grade / étape exacte.
        if ($user && $user->role !== 'admin' && !empty($user->grade)) {
            $query->where('etape', $user->grade);
        }

        $visas = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $visas
        ]);
    }

    public function show($id_visa)
    {
        $visa = VisaEtape::with([
            'demande.personnel',
            'demande.visas',
            'demande.dossierCourrier'
        ])->findOrFail($id_visa);

        return response()->json([
            'success' => true,
            'data' => $visa
        ]);
    }

    public function traiterVisa(VisaDemandeRequest $request, $id_visa)
    {
        $visaActuel = VisaEtape::with('demande')->findOrFail($id_visa);
        $user = $request->user();

        // CONTRÔLE DE SÉCURITÉ PAR GRADE :
        // Un utilisateur (non admin suprême) ne peut agir que si son grade correspond exactement à l'étape en cours.
        if ($user && $user->role !== 'admin') {
            $gradeUser = $user->grade ?? '';
            // Gérer les equivalences de libellés si besoin (ex: Chef_Hierarchique vs Chef_Service)
            $etapeActuelle = $visaActuel->etape;

            $estAutorise = ($gradeUser === $etapeActuelle) ||
                ($gradeUser === 'Chef_Service' && in_array($etapeActuelle, ['Chef_Service', 'Chef_Hierarchique']));

            if (!$estAutorise) {
                return response()->json([
                    'success' => false,
                    'message' => "Accès refusé : Votre grade ({$gradeUser}) ne vous autorise pas à valider l'étape en cours ({$etapeActuelle})."
                ], 403);
            }
        }

        if ($visaActuel->decision_visa !== 'En_Attente') {
            return response()->json([
                'success' => false,
                'message' => 'Cette étape a déjà été traitée.',
                'statut_actuel' => $visaActuel->decision_visa
            ], 200);
        }

        if ($request->filled('etape') && $request->etape !== $visaActuel->etape) {
            return response()->json([
                'success' => false,
                'message' => 'L\'étape transmise ne correspond pas à l\'étape actuelle du dossier.'
            ], 422);
        }

        // Enregistrement de la décision pour l'étape actuelle
        $visaActuel->update([
            'decision_visa' => $request->decision_visa,
            'date_visa' => now(),
            'commentaire' => $request->commentaire
        ]);

        $demande = $visaActuel->demande;

        // Si la demande est REFUSÉE à une étape quelconque
        if ($request->decision_visa === 'Refuse') {
            $etapeLibelle = str_replace('_', ' ', $visaActuel->etape);
            $demande->update([
                'statut_global' => 'refuse',
                'motif_rejet' => "Refusé par {$etapeLibelle}"
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Demande refusée à l\'étape ' . $visaActuel->etape . '. La validation est arrêtée.',
                'data' => $visaActuel
            ]);
        }

        // Séquence hiérarchique rigoureuse
        $prochaineEtape = null;
        if ($visaActuel->etape === 'Chef_Service' || $visaActuel->etape === 'Chef_Hierarchique') {
            $prochaineEtape = 'DAG';
        } elseif ($visaActuel->etape === 'DAG') {
            $prochaineEtape = 'SG';
        } elseif ($visaActuel->etape === 'SG') {
            $prochaineEtape = 'Chef_Region';
        }

        if ($prochaineEtape) {
            $existedeja = VisaEtape::where('id_demande', $demande->id_demande)
                ->where('etape', $prochaineEtape)
                ->exists();

            if (!$existedeja) {
                VisaEtape::create([
                    'etape' => $prochaineEtape,
                    'decision_visa' => 'En_Attente',
                    'id_demande' => $demande->id_demande
                ]);
            }

            // Statut intermédiaire de transmission hiérarchique
            $demande->update(['statut_global' => 'en_attente']);

            return response()->json([
                'success' => true,
                'message' => 'Étape validée avec succès. Transmis à : ' . $prochaineEtape,
            ]);
        } else {
            // Dernière étape franchie (Chef_Region) -> La demande est entièrement VALIDÉE
            $demande->update(['statut_global' => 'valide']);

            DossierTraitementCourrier::firstOrCreate(
                ['id_demande' => $demande->id_demande],
                ['statut_dossier' => 'en_attente_courrier']
            );

            return response()->json([
                'success' => true,
                'message' => 'Dossier entièrement validé par le Chef de Région et transmis au Service Courrier.',
                'demande' => $demande
            ]);
        }
    }

    public function exportCsv(): StreamedResponse
    {
        $fileName = 'export_visas_hierarchiques_' . date('Y-m-d_H-i-s') . '.csv';
        $visas = VisaEtape::with(['demande.personnel'])->orderBy('created_at', 'desc')->get();

        $headers = [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function () use ($visas) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($file, ['ID Visa', 'IM Agent', 'Nom Agent', 'Étape', 'Décision', 'Date Visa', 'Commentaire', 'ID Demande'], ';');

            foreach ($visas as $visa) {
                fputcsv($file, [
                    $visa->id_visa,
                    $visa->demande->im ?? 'N/A',
                    ($visa->demande->personnel->nom ?? '') . ' ' . ($visa->demande->personnel->prenom ?? ''),
                    $visa->etape,
                    $visa->decision_visa,
                    $visa->date_visa ? $visa->date_visa->format('Y-m-d H:i') : 'En attente',
                    $visa->commentaire,
                    $visa->id_demande
                ], ';');
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
