<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PlanningAnnuel;
use App\Models\LignePlanning;
use App\Models\Decision;
use App\Models\Personnel;
use Illuminate\Http\Request;

class PlanningController extends Controller
{
    public function index()
    {
        return response()->json([
            'success' => true,
            'data' => PlanningAnnuel::with(['lignes', 'personnel'])->get()
        ]);
    }

    public function verifierDecisionAgent($im)
    {
        $personnel = Personnel::where('im', $im)->first();
        if (!$personnel) {
            return response()->json([
                'success' => false,
                'message' => 'Agent introuvable.'
            ], 404);
        }

        $decisions = Decision::where('im', $im)->orderBy('annee_debut', 'asc')->get();

        if ($decisions->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => "Aucune décision officielle n'a été générée pour cet agent. Le planning est bloqué.",
                'annees_autorisees' => [],
                'decisions' => []
            ], 422);
        }

        $anneesAutorisees = [];
        foreach ($decisions as $dec) {
            for ($annee = $dec->annee_debut; $annee <= $dec->annee_fin; $annee++) {
                $anneesAutorisees[] = (int) $annee;
            }
        }
        $anneesAutorisees = array_unique($anneesAutorisees);
        sort($anneesAutorisees);

        return response()->json([
            'success' => true,
            'message' => "Décisions trouvées pour cet agent.",
            'annees_autorisees' => $anneesAutorisees,
            'decisions' => $decisions
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'annee' => 'required|integer',
            'im' => 'required|string|exists:personnels,im',
            'lignes' => 'required|array|min:1',
            'lignes.*.mois' => 'required|string',
            'lignes.*.nombre_jours_prevus' => 'required|integer|min:0',
        ]);

        $im = $request->im;
        $anneePlanning = (int) $request->annee;
        $anneeActuelle = (int) date('Y');
        $moisActuel = (int) date('n');

        $moisMap = [
            'Janvier' => 1,
            'Février' => 2,
            'Mars' => 3,
            'Avril' => 4,
            'Mai' => 5,
            'Juin' => 6,
            'Juillet' => 7,
            'Août' => 8,
            'Septembre' => 9,
            'Octobre' => 10,
            'Novembre' => 11,
            'Décembre' => 12
        ];

        if ($anneePlanning === $anneeActuelle) {
            foreach ($request->lignes as $ligne) {
                $numMois = $moisMap[$ligne['mois']] ?? 0;
                if ($numMois > 0 && $numMois < $moisActuel && $ligne['nombre_jours_prevus'] > 0) {
                    return response()->json([
                        'success' => false,
                        'message' => "Validation rejetée : Impossible d'attribuer des jours de congé sur un mois déjà écoulé ({$ligne['mois']})."
                    ], 422);
                }
            }
        }

        // Vérification de la décision couvrant l'année du planning
        $decisionActive = Decision::where('im', $im)
            ->where('annee_debut', '<=', $anneePlanning)
            ->where('annee_fin', '>=', $anneePlanning)
            ->first();

        if (!$decisionActive) {
            return response()->json([
                'success' => false,
                'message' => "Soumission rejetée : Aucune décision officielle active ne couvre l'année {$anneePlanning} pour cet agent."
            ], 422);
        }

        $totalJoursPrevus = collect($request->lignes)->sum('nombre_jours_prevus');

        if ($totalJoursPrevus <= 0) {
            return response()->json([
                'success' => false,
                'message' => "Le planning doit comporter au moins un jour de congé prévu."
            ], 422);
        }

        if ($totalJoursPrevus > 30) {
            return response()->json([
                'success' => false,
                'message' => "Le total des jours prévus ({$totalJoursPrevus} jours) dépasse le quota annuel autorisé de 30 jours."
            ], 422);
        }

        $existingPlanning = PlanningAnnuel::where('im', $im)->where('annee', $anneePlanning)->first();
        if ($existingPlanning) {
            $existingPlanning->lignes()->delete();
            $planning = $existingPlanning;
            $planning->update(['date_soumission' => now()]);
        } else {
            $planning = PlanningAnnuel::create([
                'annee' => $anneePlanning,
                'date_soumission' => now(),
                'im' => $im,
            ]);
        }

        foreach ($request->lignes as $ligne) {
            LignePlanning::create([
                'mois' => $ligne['mois'],
                'nombre_jours_prevus' => $ligne['nombre_jours_prevus'],
                'id_planning' => $planning->id_planning,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Planning annuel validé, synchronisé et enregistré avec succès.',
            'data' => $planning->load(['lignes', 'personnel'])
        ], 201);
    }

    public function exportCsv()
    {
        $plannings = PlanningAnnuel::with(['lignes', 'personnel'])->get();

        $filename = 'registre_plannings_annuels_' . date('Y-m-d') . '.csv';
        $headers = [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function () use ($plannings) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($file, ['ID Planning', 'IM Agent', 'Nom & Prénom', 'Année', 'Date Soumission', 'Total Jours Prévus', 'Détails Mensuels'], ';');

            foreach ($plannings as $p) {
                $nomAgent = $p->personnel ? ($p->personnel->nom . ' ' . $p->personnel->prenom) : 'N/A';
                $totalJours = $p->lignes->sum('nombre_jours_prevus');
                $details = $p->lignes->map(fn($l) => $l->mois . ': ' . $l->nombre_jours_prevus . 'j')->implode(' | ');

                fputcsv($file, [
                    $p->id_planning,
                    $p->im,
                    $nomAgent,
                    $p->annee,
                    $p->date_soumission,
                    $totalJours,
                    $details
                ], ';');
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
