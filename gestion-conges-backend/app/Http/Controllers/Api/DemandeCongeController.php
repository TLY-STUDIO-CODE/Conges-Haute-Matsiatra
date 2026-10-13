<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDemandeCongeRequest;
use App\Models\DemandeConge;
use App\Models\Decision;
use App\Models\PlanningAnnuel;
use App\Models\VisaEtape;
use App\Models\DossierTraitementCourrier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DemandeCongeController extends Controller
{
    public function index()
    {
        $demandes = DemandeConge::with(['personnel', 'visas.demande', 'dossierCourrier'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Harmonisation dynamique du statut global selon les visas hiérarchiques pour l'affichage
        $demandes->transform(function ($demande) {
            $visas = $demande->visas;
            $refuseVisa = $visas->firstWhere('decision_visa', 'Refuse');
            $tousValides = $visas->isNotEmpty() && $visas->every(fn($v) => $v->decision_visa === 'Valide') && $demande->statut_global === 'valide';

            if ($refuseVisa) {
                $etapeRefus = str_replace('_', ' ', $refuseVisa->etape);
                $demande->statut_global_affiche = "Refusé par {$etapeRefus}";
                $demande->badge_statut = 'refuse';
            } elseif ($tousValides || $demande->statut_global === 'valide_par_autorite' || $demande->statut_global === 'cloture_et_archive') {
                $demande->statut_global_affiche = "Validé";
                $demande->badge_statut = 'valide';
            } else {
                $demande->statut_global_affiche = "En attente";
                $demande->badge_statut = 'en_attente';
            }

            return $demande;
        });

        return response()->json([
            'success' => true,
            'data' => $demandes
        ]);
    }

    /**
     * Vérification croisée (Décision + Planning + Solde) pour l'agent
     */
    public function verifierDecisionAgent(Request $request, $im)
    {
        $anneeCourante = (int) $request->input('annee', date('Y'));

        // 1. Recherche d'une décision active couvrant l'année ou la plus récente pour l'agent
        $decision = Decision::where('im', $im)
            ->where(function ($query) use ($anneeCourante) {
                $query->where('annee_debut', '<=', $anneeCourante)
                    ->where('annee_fin', '>=', $anneeCourante);
            })
            ->first() ?? Decision::where('im', $im)->orderBy('annee_fin', 'desc')->first();

        if (!$decision) {
            return response()->json([
                'success' => false,
                'message' => "Validation bloquée : Aucune décision officielle active n’a été trouvée pour cet agent."
            ], 404);
        }

        $anneeCible = $decision->annee_debut;

        // 2. Vérification du Planning Annuel
        $planningExistant = PlanningAnnuel::where('im', $im)
            ->where(function ($query) use ($anneeCourante, $anneeCible) {
                $query->where('annee', $anneeCourante)
                    ->orWhere('annee', $anneeCible);
            })
            ->first();

        if (!$planningExistant) {
            return response()->json([
                'success' => false,
                'message' => "Validation bloquée : Aucun planning annuel n'a été validé pour cet agent (Vérifiez l'année {$anneeCourante} ou {$anneeCible})."
            ], 422);
        }

        // 3. Calcul transparent et global du solde de congé disponible basé sur la décision (ex: 90 jours)
        $totalDroits = $decision->nombre_jours ?? 90;
        $joursPrisTotal = DemandeConge::where('im', $im)
            ->where('statut_global', '!=', 'rejete')
            ->sum('nombre_jours_demandes');

        $soldeRestant = max(0, $totalDroits - $joursPrisTotal);

        if ($soldeRestant <= 0) {
            return response()->json([
                'success' => false,
                'message' => "Solde insuffisant : Le solde disponible est épuisé (0 jour restant sur {$totalDroits}j).",
                'decision' => $decision,
                'solde_restant' => 0
            ], 422);
        }

        return response()->json([
            'success' => true,
            'decision' => $decision,
            'planning' => $planningExistant,
            'solde_restant' => $soldeRestant,
            'message' => "Décision active ({$decision->reference_decision}) et planning validés. Solde global restant : {$soldeRestant} jour(s)."
        ]);
    }

    public function store(StoreDemandeCongeRequest $request)
    {
        $im = $request->im;
        $nombreJoursDemandes = $request->nombre_jours_demandes;
        $anneeDemande = $request->date_debut ? (int) date('Y', strtotime($request->date_debut)) : (int) date('Y');

        // CONTRÔLE STRICT 1 : Présence d'un planning annuel
        $planningExistant = PlanningAnnuel::where('im', $im)
            ->where(function ($query) use ($anneeDemande) {
                $query->where('annee', $anneeDemande)
                    ->orWhere('annee', '<=', $anneeDemande);
            })
            ->orderBy('annee', 'desc')
            ->first();

        if (!$planningExistant) {
            return response()->json([
                'success' => false,
                'message' => "Demande rejetée : L'agent doit obligatoirement disposer d'un planning annuel validé."
            ], 422);
        }

        // CONTRÔLE STRICT 2 : Présence d'une décision active
        $decisionExistante = Decision::where('im', $im)
            ->where('annee_debut', '<=', $anneeDemande)
            ->where('annee_fin', '>=', $anneeDemande)
            ->first() ?? Decision::where('im', $im)->orderBy('annee_fin', 'desc')->first();

        if (!$decisionExistante) {
            return response()->json([
                'success' => false,
                'message' => "Demande rejetée : Aucune décision officielle active n’a été trouvée pour cet agent."
            ], 422);
        }

        // CONTRÔLE STRICT 3 : Vérification mathématique exacte du solde global
        $totalDroits = $decisionExistante->nombre_jours ?? 90;
        $joursDejapris = DemandeConge::where('im', $im)
            ->where('statut_global', '!=', 'rejete')
            ->sum('nombre_jours_demandes');

        $soldeRestant = max(0, $totalDroits - $joursDejapris);

        if ($nombreJoursDemandes > $soldeRestant) {
            return response()->json([
                'success' => false,
                'message' => "Solde insuffisant : Il reste {$soldeRestant} jour(s) disponible(s) sur les {$totalDroits} jours attribués par la décision."
            ], 422);
        }

        $fichiersDemandePaths = [];
        $fichiersPlanningPaths = [];

        if ($request->hasFile('fichier_demande')) {
            $files = $request->file('fichier_demande');
            foreach (is_array($files) ? $files : [$files] as $file) {
                $fichiersDemandePaths[] = $file->store('conges/demandes', 'public');
            }
        }

        if ($request->hasFile('fichier_planning')) {
            $files = $request->file('fichier_planning');
            foreach (is_array($files) ? $files : [$files] as $file) {
                $fichiersPlanningPaths[] = $file->store('conges/plannings', 'public');
            }
        }

        $fichiersDecisionPaths = $decisionExistante->fichier_decision ?? [];

        $demande = DemandeConge::create([
            'date_demande' => now(),
            'type_periode' => $request->type_periode,
            'date_debut' => $request->date_debut,
            'date_fin' => $request->date_fin,
            'nombre_jours_demandes' => $nombreJoursDemandes,
            'motif' => $request->motif,
            'statut_global' => 'en_attente',
            'im' => $im,
            'fichier_demande' => $fichiersDemandePaths,
            'fichier_planning' => $fichiersPlanningPaths,
            'fichier_decision' => $fichiersDecisionPaths,
        ]);

        VisaEtape::create([
            'etape' => 'Chef_Hierarchique',
            'decision_visa' => 'En_Attente',
            'id_demande' => $demande->id_demande
        ]);

        DossierTraitementCourrier::create([
            'statut_dossier' => 'en_attente',
            'id_demande' => $demande->id_demande
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Demande validée par la décision n° ' . $decisionExistante->reference_decision . ' et transmise avec succès.',
            'data' => $demande->load(['personnel', 'visas', 'dossierCourrier'])
        ], 201);
    }

    public function telechargerFichierParChemin(Request $request)
    {
        $chemin = $request->route('path') ?? $request->input('path');

        if ($chemin && Storage::disk('public')->exists($chemin)) {
            $cheminAbsolu = Storage::disk('public')->path($chemin);
            return response()->download($cheminAbsolu);
        }

        return response()->json([
            'success' => false,
            'message' => 'Fichier introuvable sur le serveur.'
        ], 404);
    }

    public function exportCsv()
    {
        $demandes = DemandeConge::with(['personnel', 'visas', 'dossierCourrier'])
            ->orderBy('created_at', 'desc')
            ->get();

        $filename = 'registre_demandes_conges_haute_matsiatra_' . date('Y-m-d_H-i-s') . '.csv';

        $headers = [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function () use ($demandes) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($file, [
                'ID Demande',
                'Agent (IM)',
                'Nom et Prénom',
                'Type & Période',
                'Date Début',
                'Date Fin',
                'Durée (Jours)',
                'Motif',
                'Statut Global',
                'Statut Hiérarchique',
                'Date de Création'
            ], ';');

            foreach ($demandes as $demande) {
                fputcsv($file, [
                    $demande->id_demande,
                    $demande->personnel->im ?? $demande->im,
                    ($demande->personnel->nom ?? '') . ' ' . ($demande->personnel->prenom ?? ''),
                    $demande->type_periode,
                    $demande->date_debut,
                    $demande->date_fin,
                    $demande->nombre_jours_demandes,
                    $demande->motif,
                    $demande->statut_global,
                    $demande->visas->first()->decision_visa ?? 'En_Attente',
                    $demande->created_at,
                ], ';');
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
