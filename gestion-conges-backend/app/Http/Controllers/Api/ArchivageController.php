<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DossierTraitementCourrier;
use App\Models\DemandeConge;
use App\Models\Decision;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class ArchivageController extends Controller
{
    public function index()
    {
        try {
            $dossiersArchives = DossierTraitementCourrier::with(['demande.personnel', 'demande.visas'])
                ->where('statut_dossier', 'cloture')
                ->orderBy('updated_at', 'desc')
                ->get();

            $dossiersArchives->transform(function ($dossier) {
                if (is_string($dossier->fichier_ampliation)) {
                    $decoded = json_decode($dossier->fichier_ampliation, true);
                    $dossier->fichier_ampliation = is_array($decoded) ? $decoded : [$dossier->fichier_ampliation];
                }

                if ($dossier->demande && $dossier->demande->im) {
                    $im = $dossier->demande->im;
                    $decision = Decision::where('im', $im)->orderBy('annee_fin', 'desc')->first();
                    $totalDroits = $decision ? $decision->nombre_jours : 90;
                    $joursPris = DemandeConge::where('im', $im)->where('statut_global', '!=', 'rejete')->sum('nombre_jours_demandes');
                    $dossier->solde_restant_calcule = max(0, $totalDroits - $joursPris); // <--- Correction du signe -
                }
                return $dossier;
            });

            return response()->json([
                'success' => true,
                'data' => $dossiersArchives
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
        $dossier = DossierTraitementCourrier::with(['demande.personnel', 'demande.visas'])->findOrFail($id_traitement);

        if (is_string($dossier->fichier_ampliation)) {
            $decoded = json_decode($dossier->fichier_ampliation, true);
            $dossier->fichier_ampliation = is_array($decoded) ? $decoded : [$dossier->fichier_ampliation];
        }

        if ($dossier->demande && $dossier->demande->im) {
            $im = $dossier->demande->im;
            $decision = Decision::where('im', $im)->orderBy('annee_fin', 'desc')->first();
            $totalDroits = $decision ? $decision->nombre_jours : 90;
            $joursPris = DemandeConge::where('im', $im)->where('statut_global', '!=', 'rejete')->sum('nombre_jours_demandes');
            $dossier->solde_restant_calcule = max(0, $totalDroits - $joursPris); // <--- Correction du signe -
        }

        return response()->json([
            'success' => true,
            'data' => $dossier
        ]);
    }

    public function exportCsv(): StreamedResponse
    {
        $fileName = 'export_dossiers_archives_' . date('Y-m-d_H-i-s') . '.csv';
        $dossiers = DossierTraitementCourrier::with(['demande.personnel'])->where('statut_dossier', 'cloture')->orderBy('updated_at', 'desc')->get();

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

            fputcsv($file, ['ID Traitement', 'IM Agent', 'Nom & Prénom', 'Réf. Ampliation', 'Date Saisie Décision', 'Solde Restant Agent', 'ID Demande'], ';');

            foreach ($dossiers as $d) {
                $im = $d->demande->im ?? null;
                $soldeTexte = 'N/A';

                if ($im) {
                    $decision = Decision::where('im', $im)->orderBy('annee_fin', 'desc')->first();
                    $totalDroits = $decision ? $decision->nombre_jours : 90;
                    $joursPris = DemandeConge::where('im', $im)->where('statut_global', '!=', 'rejete')->sum('nombre_jours_demandes');
                    $soldeRestant = max(0, $totalDroits - $joursPris); // <--- Correction du signe -
                    $soldeTexte = $soldeRestant . ' jours';
                }

                fputcsv($file, [
                    $d->id_traitement,
                    $im ?? 'N/A',
                    trim(($d->demande->personnel->nom ?? '') . ' ' . ($d->demande->personnel->prenom ?? '')) ?: 'N/A',
                    $d->reference_ampliation ?? 'N/A',
                    $d->date_saisie_decision ?? 'N/A',
                    $soldeTexte,
                    $d->id_demande
                ], ';');
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function genererAmpliation(Request $request, $id_traitement)
    {
        try {
            $dossier = DossierTraitementCourrier::with(['demande.personnel'])->findOrFail($id_traitement);
            $demande = $dossier->demande;

            if (!$demande || !$demande->personnel) {
                return response()->json([
                    'success' => false,
                    'message' => 'Demande ou personnel introuvable pour ce dossier.'
                ], 404);
            }

            $personnel = $demande->personnel;
            $im = $personnel->im;

            $decision = Decision::where('im', $im)->orderBy('annee_fin', 'desc')->first();

            $referenceDecision = $decision ? $decision->reference_decision : 'S/N';
            $anneeDebut = $decision ? $decision->annee_debut : date('Y');
            $anneeFin = $decision ? $decision->annee_fin : date('Y');
            $imputation = $decision ? $decision->imputation_budgetaire : '60 601 6011';
            $grade = $decision ? $decision->grade_emploi : ($personnel->grade ?? 'Encadreur');

            $referenceAmpliation = $dossier->reference_ampliation ?? ('AMP-' . $id_traitement . '-' . date('Y'));

            $pdfData = [
                'reference_decision' => $referenceDecision,
                'reference_ampliation' => $referenceAmpliation,
                'im' => $im,
                'nom_prenom' => strtoupper($personnel->nom . ' ' . ($personnel->prenom ?? '')),
                'annee_debut' => $anneeDebut,
                'annee_fin' => $anneeFin,
                'imputation_budgetaire' => $imputation,
                'grade_emploi' => $grade,
                'date_generation' => now()->format('d/m/Y')
            ];

            $pdf = Pdf::loadView('pdf.ampliation', $pdfData);
            $fileName = 'ampliation_' . time() . '_' . $im . '.pdf';
            $filePath = 'conges/amplications/' . $fileName;

            // Enregistrement effectif dans le disque public storage
            Storage::disk('public')->put($filePath, $pdf->output());

            $fichiersExistants = is_string($dossier->fichier_ampliation) ? json_decode($dossier->fichier_ampliation, true) : $dossier->fichier_ampliation;
            $fichiersExistants = is_array($fichiersExistants) ? $fichiersExistants : [];

            if (!in_array($filePath, $fichiersExistants)) {
                $fichiersExistants[] = $filePath;
            }

            // Mise à jour de la base de données (maintenant autorisé grâce au fillable)
            $dossier->update([
                'fichier_ampliation' => json_encode($fichiersExistants)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Ampliation générée et enregistrée avec succès.',
                'file_path' => $filePath,
                'data' => $dossier
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur génération ampliation : ' . $e->getMessage()
            ], 500);
        }
    }

    public function listerAmplications()
    {
        try {
            $amplications = DossierTraitementCourrier::with(['demande.personnel'])
                ->whereNotNull('fichier_ampliation')
                ->where('fichier_ampliation', '!=', '[]')
                ->orderBy('updated_at', 'desc')
                ->get();

            $amplications->transform(function ($dossier) {
                if (is_string($dossier->fichier_ampliation)) {
                    $decoded = json_decode($dossier->fichier_ampliation, true);
                    $dossier->fichier_ampliation = is_array($decoded) ? $decoded : [$dossier->fichier_ampliation];
                }
                return $dossier;
            });

            return response()->json([
                'success' => true,
                'data' => $amplications
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des amplications : ' . $e->getMessage()
            ], 500);
        }
    }
}
