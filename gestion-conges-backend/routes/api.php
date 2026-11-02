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
use App\Http\Controllers\Api\AuthController;

/*
|--------------------------------------------------------------------------
| API Routes - Région Haute Matsiatra
|--------------------------------------------------------------------------
*/

// Routes publiques
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']); // <--- Route d'inscription ajoutée

// Routes protégées par Sanctum
Route::middleware('auth:sanctum')->group(function () {

    // Session & Profil utilisateur
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // ==========================================
    // 1. GESTION DU PERSONNEL
    // ==========================================
    Route::prefix('personnels')->group(function () {
        Route::get('/', [PersonnelController::class, 'index']);
        Route::get('/export/csv', [PersonnelController::class, 'exportCsv']);
        Route::get('/{im}', [PersonnelController::class, 'show']);
        Route::post('/', [PersonnelController::class, 'store']);
        Route::put('/{im}', [PersonnelController::class, 'update']);
        Route::delete('/{im}', [PersonnelController::class, 'destroy']);
    });

    // ==========================================
    // 2. GESTION DES DEMANDES DE CONGÉ
    // ==========================================
    Route::prefix('demandes-conges')->group(function () {
        Route::get('/', [DemandeCongeController::class, 'index']);
        Route::get('/export/csv', [DemandeCongeController::class, 'exportCsv']);
        Route::get('/{id}', [DemandeCongeController::class, 'show']);
        Route::get('/verifier-decision/{im}', [DemandeCongeController::class, 'verifierDecisionAgent']);
        Route::post('/', [DemandeCongeController::class, 'store']);
        Route::put('/{id}/statut', [DemandeCongeController::class, 'updateStatut']);
    });

    // ==========================================
    // 3. TÉLÉCHARGEMENT DES FICHIERS
    // ==========================================
    Route::get('/storage/{path}', [DemandeCongeController::class, 'telechargerFichierParChemin'])->where('path', '.*');

    // ==========================================
    // 4. WORKFLOW & VALIDATION HIÉRARCHIQUE
    // ==========================================
    Route::prefix('visas')->group(function () {
        Route::get('/', [WorkflowController::class, 'index']);
        Route::get('/export/csv', [WorkflowController::class, 'exportCsv']);
        Route::get('/{id_visa}', [WorkflowController::class, 'show']);
        Route::post('/{id_visa}/traiter', [WorkflowController::class, 'traiterVisa']);
    });

    // ==========================================
    // 5. SERVICE COURRIER
    // ==========================================
    Route::prefix('courrier')->group(function () {
        Route::get('/dossiers', [CourrierController::class, 'index']);
        Route::get('/export/csv', [CourrierController::class, 'exportCsv']);
        Route::get('/dossiers/{id_traitement}', [CourrierController::class, 'show']);
        Route::post('/dossiers/{id_traitement}/traiter', [CourrierController::class, 'traiterCourrier']);
        Route::get('/dossiers/{id_traitement}/decision', [CourrierController::class, 'telechargerDecision']);
    });

    // ==========================================
    // 6. DOSSIERS ARCHIVÉS & AMPLICATIONS
    // ==========================================
    Route::prefix('archives')->group(function () {
        Route::get('/amplications/liste', [ArchivageController::class, 'listerAmplications']);
        Route::get('/', [ArchivageController::class, 'index']);
        Route::get('/export/csv', [ArchivageController::class, 'exportCsv']);
        Route::get('/{id}', [ArchivageController::class, 'show']);
        Route::post('/{id}/ampliation', [ArchivageController::class, 'genererAmpliation']);
    });

    // ==========================================
    // 7. DÉCISIONS DE CONGÉS
    // ==========================================
    Route::prefix('decisions')->group(function () {
        Route::get('/', [DecisionController::class, 'index']);
        Route::post('/', [DecisionController::class, 'store']);
        Route::get('/verifier/{im}', [DecisionController::class, 'verifierDecisionAgent']);
    });

    // ==========================================
    // 8. PLANNINGS ANNUELS
    // ==========================================
    Route::prefix('plannings')->group(function () {
        Route::get('/', [PlanningController::class, 'index']);
        Route::get('/export/csv', [PlanningController::class, 'exportCsv']);
        Route::post('/', [PlanningController::class, 'store']);
        Route::get('/verifier/{im}', [PlanningController::class, 'verifierDecisionAgent']);
        Route::get('/verifier-decision/{im}', [PlanningController::class, 'verifierDecisionAgent']);
    });

    // ==========================================
    // TABLEAU DE BORD
    // ==========================================
    Route::get('/dashboard/stats', [DashboardController::class, 'index']);
});
