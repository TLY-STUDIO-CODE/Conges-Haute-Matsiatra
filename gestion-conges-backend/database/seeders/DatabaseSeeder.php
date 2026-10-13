<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Personnel;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Insertion de personnels de test basés sur vos listes administratives
        Personnel::updateOrCreate(
            ['im' => '367.375'],
            [
                'nom' => 'ANDRIANARIVELO',
                'prenom' => 'Lalatseheno',
                'date_naissance' => '1991-03-07',
                'categorie' => 'III',
                'grade' => 'Encadreur',
                'date_contrat' => '2020-02-28',
                'solde_conge_actuel' => 30
            ]
        );

        Personnel::updateOrCreate(
            ['im' => '323019'],
            [
                'nom' => 'RAZAFIMAMONJY',
                'prenom' => 'Davy',
                'date_naissance' => '1980-06-13',
                'categorie' => 'IV',
                'grade' => '1ère Classe 1ère échelon',
                'date_contrat' => '2021-11-08',
                'solde_conge_actuel' => 30
            ]
        );
    }
}
