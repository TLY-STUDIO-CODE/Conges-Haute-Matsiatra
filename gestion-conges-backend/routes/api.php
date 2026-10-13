<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PersonnelController;
use App\Http\Controllers\Api\DemandeCongeController;
use App\Http\Controllers\Api\WorkflowController;
use App\Http\Controllers\Api\CourrierController;
use App\Http\Controllers\Api\PlanningController;
use App\Http\Controllers\Api\ArchivageController;
use App\Http\Controllers\Api\DecisionController;
use App\Http\Controllers\Api\DashboardController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| Routes API de l'application de Gestion des Congés - Région Haute Matsiatra.
| Préfixées automatiquement par "/api".
|--------------------------------------------------------------------------
*/

// Route utilisateur authentifié (via Sanctum)
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// ==========================================
// 1. GESTION DU PERSONNEL
// ==========================================
Route::prefix('personnels')->group(function () {
    Route::get('/', [PersonnelController::class, 'index']);
    Route::get('/export/csv', [PersonnelController::class, 'exportCsv']);
    Route::post('/', [PersonnelController::class, 'store']);
    Route::get('/{im}', [PersonnelController::class, 'show']);
    Route::put('/{im}', [PersonnelController::class, 'update']);
    Route::delete('/{im}', [PersonnelController::class, 'destroy']);
});

// ==========================================
// 2. GESTION DES DEMANDES DE CONGÉ
// ==========================================
Route::prefix('demandes-conges')->group(function () {
    Route::get('/', [DemandeCongeController::class, 'index']);
    Route::get('/export/csv', [DemandeCongeController::class, 'exportCsv']);
    Route::post('/', [DemandeCongeController::class, 'store']);
    Route::get('/{id}', [DemandeCongeController::class, 'show']);
    Route::put('/{id}/statut', [DemandeCongeController::class, 'updateStatut']);
    Route::get('/verifier-decision/{im}', [DemandeCongeController::class, 'verifierDecisionAgent']); // Route unifiée de vérification croisée (Décision + Planning + Solde)
});

// ==========================================
// 3. TÉLÉCHARGEMENT DES FICHIERS & PIÈCES JOINTES
// ==========================================
Route::get('/storage/{path}', [DemandeCongeController::class, 'telechargerFichierParChemin'])->where('path', '.*');

// ==========================================
// 4. GESTION DU WORKFLOW EN CASCADE & COURRIER
// ==========================================
Route::prefix('visas')->group(function () {
    Route::get('/', [WorkflowController::class, 'index']);
    Route::get('/export/csv', [WorkflowController::class, 'exportCsv']);
    Route::get('/{id_visa}', [WorkflowController::class, 'show']);
    Route::post('/{id_visa}/traiter', [WorkflowController::class, 'traiterVisa']);
});

// Routes du Service Courrier (Clôture, Ampliation & Téléchargement Décision)
Route::prefix('courrier')->group(function () {
    Route::get('/dossiers', [CourrierController::class, 'index']);
    Route::get('/export/csv', [CourrierController::class, 'exportCsv']);
    Route::get('/dossiers/{id_traitement}', [CourrierController::class, 'show']);
    Route::post('/dossiers/{id_traitement}/traiter', [CourrierController::class, 'traiterCourrier']);
    Route::get('/dossiers/{id_traitement}/decision', [CourrierController::class, 'telechargerDecision']);
});

// Routes pour les dossiers archivés & historiques
Route::prefix('archives')->group(function () {
    Route::get('/', [ArchivageController::class, 'index']);
    Route::get('/amplications/liste', [ArchivageController::class, 'listerAmplications']); // <--- NOUVELLE ROUTE
    Route::get('/export/csv', [ArchivageController::class, 'exportCsv']);
    Route::get('/{id}', [ArchivageController::class, 'show']);
    Route::post('/{id}/ampliation', [ArchivageController::class, 'genererAmpliation']);
});

// ==========================================
// 5. GESTION DES DÉCISIONS DE CONGÉS
// ==========================================
Route::prefix('decisions')->group(function () {
    Route::get('/', [DecisionController::class, 'index']);
    Route::post('/', [DecisionController::class, 'store']);
    Route::get('/verifier/{im}', [DecisionController::class, 'verifierDecisionAgent']);
});

// ==========================================
// 6. GESTION DES PLANNINGS ANNUELS
// ==========================================
Route::prefix('plannings')->group(function () {
    Route::get('/', [PlanningController::class, 'index']);
    Route::get('/export/csv', [PlanningController::class, 'exportCsv']);
    Route::post('/', [PlanningController::class, 'store']);
    Route::get('/verifier/{im}', [PlanningController::class, 'verifierDecisionAgent']);         // Correspondance directe Front-End
    Route::get('/verifier-decision/{im}', [PlanningController::class, 'verifierDecisionAgent']); // Rétrocompatibilité
});

// ==========================================
// TABLEAU DE BORD (Statistiques Globales)
// ==========================================
Route::get('/dashboard/stats', [DashboardController::class, 'index']);
