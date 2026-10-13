<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Personnel;
use App\Models\Decision;
use App\Models\PlanningAnnuel;
use App\Models\DemandeConge;
use App\Models\DossierTraitementCourrier;

class DashboardController extends Controller
{
    public function index()
    {
        try {
            // 1. Nombre total de personnel enregistré
            $nombrePersonnels = Personnel::count();

            // 2. Nombre de décisions officielles générées
            $nombreDecisions = Decision::count();

            // 3. Nombre de personnels ayant établi un planning de congé
            $nombrePersonnelsAvecPlanning = PlanningAnnuel::distinct('im')->count('im');

            // 4. Nombre de demandes de congés soumises (tous statuts confondus)
            $nombreDemandesSoumises = DemandeConge::count();

            // 5. Nombre de demandes validées (statut validé / autorités)
            $nombreDemandesValidees = DemandeConge::where('statut_global', 'valide')
                ->orWhere('statut_global', 'valide_par_autorite')
                ->orWhere('statut_global', 'cloture_et_archive')
                ->count();

            // 6. Nombre de demandes refusées (statut explicitement refusé ou rejeté)
            $nombreDemandesRefusees = DemandeConge::where('statut_global', 'refuse')
                ->orWhere('statut_global', 'rejete')
                ->count();

            // 7. Nombre de demandes en attente (en cours de workflow ou en attente)
            $nombreDemandesEnAttente = DemandeConge::where('statut_global', 'en_attente')
                ->orWhere('statut_global', 'soumis')
                ->orWhere('statut_global', 'like', 'en_cours_%')
                ->count();

            // 8. Nombre de dossiers clôturés par le service courrier / archivés
            $nombreDossiersClotures = DossierTraitementCourrier::where('statut_dossier', 'cloture')->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'nombre_personnels' => $nombrePersonnels,
                    'nombre_decisions' => $nombreDecisions,
                    'nombre_personnels_planning' => $nombrePersonnelsAvecPlanning,
                    'nombre_demandes_soumises' => $nombreDemandesSoumises,
                    'nombre_demandes_validees' => $nombreDemandesValidees,
                    'nombre_demandes_refusees' => $nombreDemandesRefusees,
                    'nombre_demandes_en_attente' => $nombreDemandesEnAttente,
                    'nombre_dossiers_clotures' => $nombreDossiersClotures,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des statistiques du tableau de bord : ' . $e->getMessage()
            ], 500);
        }
    }
}
