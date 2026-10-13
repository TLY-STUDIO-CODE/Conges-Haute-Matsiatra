<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Personnel;
use App\Models\DemandeConge;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PersonnelController extends Controller
{
    public function index()
    {
        $personnels = Personnel::with(['decisions', 'plannings', 'demandes'])->get();

        // Calcul automatique et dynamique basé sur l'ancienneté et les congés pris
        $personnels->transform(function ($p) {
            $anneeCourante = (int) date('Y');
            $anneeContrat = $p->date_contrat ? (int) date('Y', strtotime($p->date_contrat)) : $anneeCourante;

            // 1. Calcul des années écoulées (30 jours par an depuis le contrat)
            $anneesEcoulees = max(1, ($anneeCourante - $anneeContrat) + 1);
            $totalDroitsAcquis = $anneesEcoulees * 30;

            // 2. Somme de tous les jours de congés pris et validés/clôturés
            $joursPrisTotal = DemandeConge::where('im', $p->im)
                ->where('statut_global', '!=', 'rejete')
                ->sum('nombre_jours_demandes');

            // 3. Solde restant réel et synchronisé
            $soldeRestantReel = max(0, $totalDroitsAcquis - $joursPrisTotal);

            // Injection des propriétés pour l'API et le front-end
            $p->total_droits_acquis = $totalDroitsAcquis;
            $p->solde_conge_actuel = $soldeRestantReel;

            // Mise à jour synchrone en base si nécessaire
            if ($p->solde_conge_actuel !== $soldeRestantReel) {
                $p->update(['solde_conge_actuel' => $soldeRestantReel]);
            }

            return $p;
        });

        return response()->json([
            'success' => true,
            'data' => $personnels
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'im' => 'required|string|unique:personnels,im',
            'nom' => 'required|string|max:255',
            'prenom' => 'nullable|string|max:255',
            'date_naissance' => 'nullable|date',
            'categorie' => 'nullable|string|max:50',
            'grade' => 'nullable|string|max:100',
            'date_contrat' => 'required|date',
        ]);

        $anneeCourante = (int) date('Y');
        $anneeContrat = (int) date('Y', strtotime($validated['date_contrat']));

        // Calcul automatique du solde initial selon l'année de contrat
        $anneesEcoulees = max(1, ($anneeCourante - $anneeContrat) + 1);
        $soldeInitialTotal = $anneesEcoulees * 30;

        $validated['solde_conge_actuel'] = $soldeInitialTotal;

        $personnel = Personnel::create($validated);

        return response()->json([
            'success' => true,
            'message' => "Agent enregistré avec succès ! Droits initiaux calculés : {$soldeInitialTotal} jours.",
            'data' => $personnel
        ], 201);
    }

    public function show($im)
    {
        $personnel = Personnel::with(['decisions', 'plannings', 'demandes'])->where('im', $im)->first();
        if (!$personnel) {
            return response()->json(['success' => false, 'message' => 'Personnel non trouvé'], 404);
        }

        $anneeCourante = (int) date('Y');
        $anneeContrat = $personnel->date_contrat ? (int) date('Y', strtotime($personnel->date_contrat)) : $anneeCourante;
        $anneesEcoulees = max(1, ($anneeCourante - $anneeContrat) + 1);
        $totalDroitsAcquis = $anneesEcoulees * 30;

        $joursPrisTotal = DemandeConge::where('im', $im)
            ->where('statut_global', '!=', 'rejete')
            ->sum('nombre_jours_demandes');

        $personnel->total_droits_acquis = $totalDroitsAcquis;
        $personnel->solde_conge_actuel = max(0, $totalDroitsAcquis - $joursPrisTotal);

        return response()->json(['success' => true, 'data' => $personnel]);
    }

    public function exportCsv(): StreamedResponse
    {
        $fileName = 'liste_personnel_' . date('Y-m-d') . '.csv';
        $personnels = Personnel::all();

        $callback = function () use ($personnels) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($file, ['IM', 'Nom', 'Prenom', 'Date de naissance', 'Categorie', 'Grade', 'Date de contrat', 'Total Droits Acquis', 'Solde Congé Actuel'], ';');

            foreach ($personnels as $p) {
                $anneeCourante = (int) date('Y');
                $anneeContrat = $p->date_contrat ? (int) date('Y', strtotime($p->date_contrat)) : $anneeCourante;
                $anneesEcoulees = max(1, ($anneeCourante - $anneeContrat) + 1);
                $totalDroits = $anneesEcoulees * 30;
                $pris = DemandeConge::where('im', $p->im)->where('statut_global', '!=', 'rejete')->sum('nombre_jours_demandes');
                $soldeReel = max(0, $totalDroits - $pris);

                fputcsv($file, [
                    $p->im,
                    $p->nom,
                    $p->prenom,
                    $p->date_naissance,
                    $p->categorie,
                    $p->grade,
                    $p->date_contrat,
                    $totalDroits . ' jours',
                    $soldeReel . ' jours'
                ], ';');
            }

            fclose($file);
        };

        return response()->stream($callback, 200, [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ]);
    }
}
